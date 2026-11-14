<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Passbook_import - bulk passbook / collection entries from an Excel sheet, for the AI Assistant.
 *
 *   template()  → the sample .xlsx admins fill in
 *   read()      → rows from an uploaded .xlsx / .xls / .csv
 *   resolve()   → finds member, loan / savings account and the allocation of every row with the same
 *                 identifiers the Bank Import uses (member code, phone, SAV…, LN…), checks duplicates.
 *                 Nothing is written.
 *   execute()   → each entry becomes a credit in bank_transactions (Cash Account for cash, the bank
 *                 account otherwise) and is mapped with Bank_model::apply_mappings() — the exact code of
 *                 the Bank Mapping screen — so it shows up in Mapping / Reconciliation like any statement line.
 *
 * The template is one row per payment: Member + Amount + For Month. With Type blank ("month") the amount is split
 * automatically over that month's EMI of every active loan and that month's savings due; extra goes to savings, a small
 * shortfall (≤ ADJUST_LIMIT) is taken from savings by the loan rule. Amount blank = exactly that month's dues.
 * "Paid By" = another member paid. Rows with the same Date + Mode + Reference (or Receipt No) are ONE receipt.
 * Optional columns still read when present: Receipt No, Loan / Savings A/c No, Receipt Total, Adjust Difference To.
 * Older 8-column files still work.
 */
class Passbook_import {

    const HEADERS = ['Date', 'Member (pick or type)', 'Monthly Due (auto)', 'Amount Paid', 'For Month', 'Mode', 'Reference / UTR',
                     'Paid By (if someone else paid)', 'Type (blank = month EMI + savings)', 'Loan / Savings A/c No (optional)', 'Remarks'];
    const TYPES   = ['month', 'auto', 'all_dues', 'emi', 'savings', 'fine', 'membership_fee', 'joining_fee', 'admission_fee', 'share_capital', 'processing_fee', 'penalty', 'other_fee'];
    const PICK_TYPES = ['month', 'all_dues', 'emi', 'savings', 'fine', 'membership_fee', 'joining_fee', 'admission_fee', 'share_capital', 'processing_fee', 'penalty', 'other_fee'];
    const MODES   = ['cash', 'upi', 'bank_transfer', 'cheque'];
    const MAX_ROWS = 500;
    const ADJUST_LIMIT = 500;   // a receipt total may differ from its rows by at most this; more needs review

    private $CI;
    private $planned = [];   // installment id => amount already allocated by earlier rows of this file

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->model(['Member_model', 'Bank_model']);
        require_once FCPATH . 'vendor/autoload.php';
    }

    // ───────────────────────────── Template ─────────────────────────────

    public function template() {
        $book = new Spreadsheet();
        $book->getProperties()->setCreator('Windeep Finance')->setTitle('Passbook entries for AI Assistant');
        $s = $book->getActiveSheet()->setTitle('Passbook');
        $this->_head_row($s);

        // Members list (searchable dropdown) with the usual monthly due: EMIs of active loans + monthly savings
        $db = $this->CI->db;
        $members = $db->query("
            SELECT m.member_code, TRIM(CONCAT(m.first_name, ' ', m.last_name)) AS name, m.phone,
                   COALESCE((SELECT SUM(l.emi_amount) FROM loans l WHERE l.member_id = m.id AND l.status IN ('active','npa')), 0)
                 + COALESCE((SELECT SUM(sa.monthly_amount) FROM savings_accounts sa WHERE sa.member_id = m.id AND sa.status = 'active'), 0) AS monthly_due
              FROM members m WHERE m.deleted_at IS NULL AND m.status = 'active' ORDER BY m.first_name, m.last_name")->result();
        $ms = $book->createSheet()->setTitle('Members');
        $ms->fromArray(['Member (pick this text)', 'Code', 'Monthly due'], null, 'A1');
        $list = [];
        foreach ($members as $m) {
            $list[] = [$m->member_code . ' - ' . mb_strtoupper($m->name) . ($m->phone ? ' - ' . $m->phone : ''), $m->member_code, round((float) $m->monthly_due, 2)];
        }
        if ($list) $ms->fromArray($list, null, 'A2');
        $ms->getColumnDimension('A')->setWidth(45);
        $last_m = max(2, count($list) + 1);

        // Sample rows (real members so the file can be tried as it is) — the dropdown text, a code, a mobile number
        $label = function ($code) use ($list) { foreach ($list as $x) if ($x[1] === $code) return $x[0]; return $code; };
        $pick = function ($skip = []) use ($db) {
            $db->select('m.id, m.member_code')->from('members m')
               ->join('loans l', "l.member_id = m.id AND l.status = 'active'")->join('savings_accounts sa', "sa.member_id = m.id AND sa.status = 'active'")
               ->where('m.deleted_at', null)->where('m.status', 'active')
               ->where("(SELECT COUNT(*) FROM savings_accounts y WHERE y.member_id = m.id AND y.status = 'active') = 1", null, false);
            if ($skip) $db->where_not_in('m.id', $skip);
            return $db->order_by('m.id', 'ASC')->limit(1)->get()->row();
        };
        $a = $pick() ?: (object) ['id' => 0, 'member_code' => 'MEMB000001'];
        $b = $pick([$a->id]) ?: $a;
        $saver = $db->select('m.member_code, m.phone')->from('members m')->join('savings_accounts sa', "sa.member_id = m.id AND sa.status = 'active'")
            ->where('m.deleted_at', null)->where("m.id NOT IN (SELECT member_id FROM loans WHERE status IN ('active','npa'))", null, false)
            ->where("(SELECT COUNT(*) FROM members p WHERE p.phone = m.phone) = 1", null, false)->limit(1)->get()->row();
        $fined = $db->select('m.member_code, f.balance_amount')->from('fines f')->join('members m', 'm.id = f.member_id')
            ->where_in('f.status', ['pending', 'partial'])->where('m.deleted_at', null)->order_by('f.id', 'ASC')->limit(1)->get()->row();
        $d = function ($days) { return date('d/m/Y', strtotime("-{$days} days")); };
        $month = date('M Y');

        // Date | Member | Monthly Due (formula) | Amount Paid | For Month | Mode | Reference | Paid By | Type | Loan / Savings A/c | Remarks
        $rows = [
            [$d(3), $label($a->member_code), null, 15000, $month, 'cash', '', '', '', '', 'Split automatically: this month\'s EMIs + savings, extra to savings'],
            [$d(3), $label($b->member_code), null, '', $month, 'upi', 'UPI' . date('ymd') . '4521', $label($a->member_code), '', '', 'Amount blank = exactly this month\'s dues; paid by another member'],
            [$d(2), $saver ? $saver->phone : '9876543210', null, 2000, '', 'bank_transfer', 'NEFT' . date('ymd') . '77', '', '', '', 'Member by mobile number; For Month blank = month of the date'],
            [$d(1), $fined ? $fined->member_code : $a->member_code, null, $fined ? round((float) $fined->balance_amount) : 100, '', 'cash', '', '', 'fine', '', 'Pending fine'],
        ];
        $s->fromArray($rows, null, 'A2', true);
        for ($i = 2; $i <= 500; $i++) {   // Monthly Due = looked up from the Members sheet once a member is picked
            $s->setCellValue('C' . $i, '=IFERROR(VLOOKUP(B' . $i . ',Members!$A$2:$C$' . $last_m . ',3,FALSE),"")');
        }
        $this->_body_format($s, 500);
        $s->freezePane('C2');
        $s->getStyle('A2:K' . (count($rows) + 1))->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3F6FB');

        // Dropdowns: member (Members sheet — type to search in Excel 365; any other text is still allowed), month, mode, type
        $lists = $book->createSheet()->setTitle('Lists');
        foreach (self::PICK_TYPES as $i => $t) $lists->setCellValue('A' . ($i + 1), $t);
        foreach (self::MODES as $i => $t) $lists->setCellValue('B' . ($i + 1), $t);
        for ($i = -3; $i <= 14; $i++) $lists->setCellValue('C' . ($i + 4), date('M Y', strtotime(date('Y-m-01') . " {$i} months")));
        $valid = [
            'B' => ['=Members!$A$2:$A$' . $last_m, false], 'H' => ['=Members!$A$2:$A$' . $last_m, false],
            'E' => ['=Lists!$C$1:$C$18', false], 'F' => ['=Lists!$B$1:$B$' . count(self::MODES), true],
            'I' => ['=Lists!$A$1:$A$' . count(self::PICK_TYPES), true],
        ];
        foreach ($valid as $col => list($formula, $strict)) {
            $v = $s->getCell($col . '2')->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST)->setAllowBlank(true)->setShowDropDown(true)->setShowErrorMessage($strict)
              ->setErrorTitle('Not in the list')->setError('Choose a value from the list.')->setFormula1($formula);
            $s->setDataValidation($col . '2:' . $col . '500', clone $v);
        }
        $ms->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
        $lists->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        // Help in English, Marathi and Hindi + worked examples of every situation
        foreach (['en' => 'Instructions', 'mr' => 'सूचना (मराठी)', 'hi' => 'निर्देश (हिंदी)'] as $lang => $title) {
            $this->_help_sheet($book, $title, $this->_help_text($lang));
        }
        $this->_examples_sheet($book);

        $book->setActiveSheetIndex(0);
        return $book;
    }

    /** Header row of the Passbook / Examples sheets. */
    private function _head_row($s) {
        $s->fromArray(self::HEADERS, null, 'A1');
        $s->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3C6E']],
        ]);
        $s->getStyle('C1')->getFill()->getStartColor()->setRGB('6C757D');   // information only
        $s->getStyle('H1:J1')->getFill()->getStartColor()->setRGB('4A6A8C'); // optional
        $s->getRowDimension(1)->setRowHeight(30);
        $s->getStyle('A1:K1')->getAlignment()->setWrapText(true)->setVertical('center');
        foreach (['A' => 12, 'B' => 42, 'C' => 13, 'D' => 13, 'E' => 11, 'F' => 14, 'G' => 20, 'H' => 34, 'I' => 16, 'J' => 18, 'K' => 55] as $col => $w) {
            $s->getColumnDimension($col)->setWidth($w);
        }
    }

    private function _body_format($s, $to) {
        $s->getStyle('C2:C' . $to)->getFont()->getColor()->setRGB('6C757D');
        foreach (['C', 'D'] as $c) $s->getStyle($c . '2:' . $c . $to)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (['A', 'E', 'G'] as $c) $s->getStyle($c . '2:' . $c . $to)->getNumberFormat()->setFormatCode('@');
    }

    /** One help sheet from [kind, text, text] rows: t = title, h = section, p = line, k = column name + meaning. */
    private function _help_sheet($book, $title, array $items) {
        $h = $book->createSheet()->setTitle($title);
        $n = 0;
        foreach ($items as $it) {
            $n++;
            list($kind, $a) = $it;
            $b = $it[2] ?? '';
            $h->setCellValue('A' . $n, $a);
            if ($b !== '') $h->setCellValue('B' . $n, $b);
            if ($kind === 't') $h->getStyle('A' . $n)->getFont()->setBold(true)->setSize(15)->getColor()->setRGB('1A3C6E');
            if ($kind === 'h') {
                $h->getStyle('A' . $n . ':B' . $n)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $h->getStyle('A' . $n . ':B' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A3C6E');
            }
            if ($kind === 'k') $h->getStyle('A' . $n)->getFont()->setBold(true);
            if ($kind === 'p' || $kind === 'k') $h->getRowDimension($n)->setRowHeight(mb_strlen($a . $b) > 150 ? 32 : 17);
        }
        $h->getStyle('A1:B' . $n)->getFont()->setName('Nirmala UI');
        $h->getStyle('B1:B' . $n)->getAlignment()->setWrapText(true)->setVertical('top');
        $h->getStyle('A1:A' . $n)->getAlignment()->setVertical('top');
        $h->getColumnDimension('A')->setWidth(30);
        $h->getColumnDimension('B')->setWidth(125);
        return $h;
    }

    private function _help_text($lang) {
        $L = self::ADJUST_LIMIT;
        if ($lang === 'mr') return [
            ['t', 'पासबुक नोंदी → AI असिस्टंट'],
            ['p', 'एक ओळ = एका सभासदाचा एक भरणा. रक्कम हप्ते व बचतीत कशी विभागायची ते सिस्टम स्वतः ठरवते. उदाहरणांसाठी "Examples - उदाहरणे" शीट पहा.'],
            ['h', 'कॉलम', 'काय लिहायचे'],
            ['k', 'Date (तारीख)', 'DD/MM/YYYY (उदा. 05/11/2026). भविष्यातील तारीख चालत नाही.'],
            ['k', 'Member (सभासद)', 'यादीतून निवडा (Excel 365 मध्ये नाव, कोड किंवा मोबाईल नंबरचा भाग टाइप करून शोधा) — किंवा सभासद कोड (MEMB000006), मोबाईल नंबर किंवा पूर्ण नाव लिहा. मराठी अक्षरातील नावही चालते.'],
            ['k', 'Monthly Due (मासिक देय)', 'करडा कॉलम, आपोआप भरतो: सभासदाचे दरमहाचे सर्व हप्ते + बचत. फक्त तपासण्यासाठी — अपलोड करताना वाचला जात नाही.'],
            ['k', 'Amount Paid (भरलेली रक्कम)', 'मिळालेली रक्कम. रिकामी ठेवल्यास त्या महिन्याचे पूर्ण देय (हप्ते + बचत) आपोआप धरले जाते.'],
            ['k', 'For Month (कोणत्या महिन्याचा)', 'भरणा कोणत्या महिन्याचा आहे (उदा. Nov 2026). रिकामे ठेवल्यास तारखेचा महिना धरला जातो.'],
            ['k', 'Mode (पद्धत)', 'cash (रोख), upi, bank_transfer (बँक ट्रान्सफर / NEFT), cheque (चेक).'],
            ['k', 'Reference / UTR', 'UTR किंवा चेक नंबर (रोख भरण्यास गरज नाही). एकाच तारखेच्या, एकाच पद्धतीच्या आणि एकाच UTR च्या ओळी = एकच पावती (उदा. एका UPI ने अनेक सभासदांचे पैसे).'],
            ['k', 'Paid By (कोणी भरले)', 'ऐच्छिक. दुसऱ्या सभासदाने पैसे भरले असतील तरच (उदा. पतीने पत्नीचा हप्ता भरला). रिकामे = सभासदाने स्वतः भरले.'],
            ['k', 'Type (प्रकार)', 'नेहमीच्या मासिक भरण्यासाठी रिकामे ठेवा. नाहीतर: all_dues (सर्व थकबाकी), emi (फक्त हप्ता), savings (फक्त बचत), fine (दंड), membership_fee, joining_fee, admission_fee, share_capital, processing_fee, penalty, other_fee.'],
            ['k', 'Loan / Savings A/c No', 'ऐच्छिक. एकापेक्षा जास्त कर्जे असताना फक्त एकाच कर्जाचा हप्ता (Type emi) भरायचा असेल तर कर्ज क्रमांक (LN…) लिहा; एकापेक्षा जास्त बचत खाती असल्यास खाते क्रमांक (SAV…).'],
            ['k', 'Remarks (शेरा)', 'काहीही मजकूर, नोंदीसोबत जतन होतो.'],
            ['p', ''],
            ['h', 'रक्कम कशी विभागली जाते (Type रिकामे)'],
            ['p', '१. आधी त्या महिन्याचा प्रत्येक चालू कर्जाचा हप्ता, नंतर त्या महिन्याची बचत, उरलेली रक्कम बचतीत.'],
            ['p', '२. जास्त भरले: जास्तीची रक्कम सभासदाच्या बचतीत जमा होते.'],
            ['p', '३. कमी भरले: हप्ते पूर्ण, बचत अंशतः भरली जाते (उरलेली बचत बाकी राहते).'],
            ['p', '४. हप्त्याचीच रक्कम ₹' . $L . ' पर्यंत कमी असेल तर कर्जाच्या नियमानुसार फरक बचतीतून घेतला जातो; ₹' . $L . ' पेक्षा जास्त कमी असल्यास ओळ तपासणीसाठी (Review) दाखवली जाते.'],
            ['p', '५. आधीचे न भरलेले महिने प्रीव्ह्यूमध्ये दाखवले जातात. ते भरायचे असल्यास त्या महिन्यासाठी वेगळी ओळ जोडा किंवा Type all_dues वापरा.'],
            ['p', ''],
            ['h', 'अपलोड व जतन'],
            ['p', '१. AI असिस्टंटमध्ये 📎 वर क्लिक करून ही फाईल निवडा.'],
            ['p', '२. प्रत्येक ओळीचा प्रीव्ह्यू दिसेल: सभासद, रक्कम कशी विभागली आणि काही अडचण असल्यास. अजून काहीही जतन होत नाही.'],
            ['p', '३. "Save entries" दाबल्यावर प्रत्येक पावती Bank Transactions मध्ये एक जमा नोंद होते आणि प्रत्येक भाग (हप्ता / बचतीचा महिना / दंड) Bank → Mapping प्रमाणे नोंदवला जातो.'],
            ['p', '४. तीच फाईल पुन्हा अपलोड केली तरी चालते: आधी नोंदलेल्या नोंदी परत होत नाहीत. सर्व नोंदी Bank → Mapping मधून रद्द (reverse) करता येतात.'],
        ];
        if ($lang === 'hi') return [
            ['t', 'पासबुक प्रविष्टियाँ → AI असिस्टेंट'],
            ['p', 'एक पंक्ति = एक सदस्य का एक भुगतान। राशि किस्तों और बचत में कैसे बँटेगी, यह सिस्टम खुद तय करता है। उदाहरणों के लिए "Examples - उदाहरणे" शीट देखें।'],
            ['h', 'कॉलम', 'क्या लिखें'],
            ['k', 'Date (तारीख)', 'DD/MM/YYYY (जैसे 05/11/2026)। भविष्य की तारीख नहीं चलती।'],
            ['k', 'Member (सदस्य)', 'सूची से चुनें (Excel 365 में नाम, कोड या मोबाइल नंबर का हिस्सा टाइप करके खोजें) — या सदस्य कोड (MEMB000006), मोबाइल नंबर या पूरा नाम लिखें। देवनागरी में नाम भी चलता है।'],
            ['k', 'Monthly Due (मासिक देय)', 'धूसर कॉलम, अपने आप भरता है: सदस्य की हर महीने की सभी किस्तें + बचत। सिर्फ जाँच के लिए — अपलोड में पढ़ा नहीं जाता।'],
            ['k', 'Amount Paid (भरी गई राशि)', 'प्राप्त राशि। खाली छोड़ें तो उस महीने का पूरा देय (किस्तें + बचत) अपने आप लिया जाता है।'],
            ['k', 'For Month (किस महीने का)', 'भुगतान किस महीने का है (जैसे Nov 2026)। खाली छोड़ें तो तारीख वाला महीना लिया जाता है।'],
            ['k', 'Mode (तरीका)', 'cash (नकद), upi, bank_transfer (बैंक ट्रांसफर / NEFT), cheque (चेक)।'],
            ['k', 'Reference / UTR', 'UTR या चेक नंबर (नकद में ज़रूरी नहीं)। एक ही तारीख, एक ही तरीके और एक ही UTR वाली पंक्तियाँ = एक ही रसीद (जैसे एक UPI से कई सदस्यों का भुगतान)।'],
            ['k', 'Paid By (किसने भरा)', 'वैकल्पिक। सिर्फ तब जब किसी दूसरे सदस्य ने भुगतान किया हो (जैसे पति ने पत्नी की किस्त भरी)। खाली = सदस्य ने खुद भरा।'],
            ['k', 'Type (प्रकार)', 'सामान्य मासिक भुगतान के लिए खाली छोड़ें। अन्यथा: all_dues (सारा बकाया), emi (सिर्फ किस्त), savings (सिर्फ बचत), fine (जुर्माना), membership_fee, joining_fee, admission_fee, share_capital, processing_fee, penalty, other_fee।'],
            ['k', 'Loan / Savings A/c No', 'वैकल्पिक। एक से ज़्यादा लोन हों और सिर्फ एक लोन की किस्त (Type emi) भरनी हो तो लोन नंबर (LN…) लिखें; एक से ज़्यादा बचत खाते हों तो खाता नंबर (SAV…)।'],
            ['k', 'Remarks (टिप्पणी)', 'कोई भी टेक्स्ट, प्रविष्टि के साथ सेव होता है।'],
            ['p', ''],
            ['h', 'राशि कैसे बँटती है (Type खाली)'],
            ['p', '1. पहले उस महीने की हर चालू लोन की किस्त, फिर उस महीने की बचत, बची हुई राशि बचत में।'],
            ['p', '2. ज़्यादा भरा: अतिरिक्त राशि सदस्य की बचत में जमा होती है।'],
            ['p', '3. कम भरा: किस्तें पूरी, बचत आंशिक भरी जाती है (बाकी बचत देय रहती है)।'],
            ['p', '4. अगर किस्त ही ₹' . $L . ' तक कम हो तो लोन नियम के अनुसार अंतर बचत से लिया जाता है; ₹' . $L . ' से ज़्यादा कम हो तो पंक्ति जाँच (Review) के लिए दिखाई जाती है।'],
            ['p', '5. पिछले बकाया महीने प्रीव्यू में दिखाए जाते हैं। उन्हें भरने के लिए उस महीने की अलग पंक्ति जोड़ें या Type all_dues इस्तेमाल करें।'],
            ['p', ''],
            ['h', 'अपलोड और सेव'],
            ['p', '1. AI असिस्टेंट में 📎 पर क्लिक करके यह फ़ाइल चुनें।'],
            ['p', '2. हर पंक्ति का प्रीव्यू दिखेगा: सदस्य, राशि का बँटवारा और कोई समस्या हो तो। अभी कुछ भी सेव नहीं होता।'],
            ['p', '3. "Save entries" दबाने पर हर रसीद Bank Transactions में एक जमा प्रविष्टि बनती है और हर हिस्सा (किस्त / बचत का महीना / जुर्माना) Bank → Mapping की तरह दर्ज होता है।'],
            ['p', '4. वही फ़ाइल दोबारा अपलोड करना सुरक्षित है: पहले दर्ज प्रविष्टियाँ दोबारा नहीं बनतीं। सब कुछ Bank → Mapping से रिवर्स किया जा सकता है।'],
        ];
        return [
            ['t', 'Passbook entries → AI Assistant'],
            ['p', 'One row = one payment by a member. The system splits it over the loan EMIs and savings for you. See the "Examples - उदाहरणे" sheet for every situation.'],
            ['h', 'Column', 'What to write'],
            ['k', 'Date', 'DD/MM/YYYY (e.g. 05/11/2026). Not in the future.'],
            ['k', 'Member', 'Pick from the list (in Excel 365 type part of the name, code or mobile to search) — or type a member code (MEMB000006), mobile number or full name. Names in Marathi / Hindi letters also work.'],
            ['k', 'Monthly Due (auto)', 'Grey, filled by itself: the member\'s usual monthly EMIs + savings, to check the amount. Not read on upload.'],
            ['k', 'Amount Paid', 'Money received. Leave blank to take exactly the dues of For Month.'],
            ['k', 'For Month', 'Month the payment is for (Nov 2026). Blank = the month of the Date.'],
            ['k', 'Mode', 'cash, upi, bank_transfer (NEFT / IMPS), cheque.'],
            ['k', 'Reference / UTR', 'UTR / cheque no. (optional for cash). Rows with the same Date + Mode + Reference are one receipt — e.g. one UPI paying for several members.'],
            ['k', 'Paid By', 'Optional. Only when another member paid (e.g. husband pays for wife). Blank = the member paid.'],
            ['k', 'Type', 'Leave blank for the normal monthly payment. Otherwise: all_dues, emi, savings, fine, membership_fee, joining_fee, admission_fee, share_capital, processing_fee, penalty, other_fee.'],
            ['k', 'Loan / Savings A/c No', 'Optional. Loan number (LN…) to pay one particular loan\'s EMI (Type emi) when the member has several loans; savings account (SAV…) when they have several accounts.'],
            ['k', 'Remarks', 'Free text, saved with the entry.'],
            ['p', ''],
            ['h', 'How the amount is split (Type blank)'],
            ['p', '1. First that month\'s EMI of every active loan, then that month\'s savings, anything left goes to savings.'],
            ['p', '2. More paid: the extra is added to the member\'s savings.'],
            ['p', '3. Less paid: EMIs in full, savings part-paid (the rest of the savings stays due).'],
            ['p', '4. If even the EMIs are short by up to ₹' . $L . ', the loan rule takes the gap from savings; more than that is shown for review.'],
            ['p', '5. Earlier unpaid months are listed in the preview. To pay them, add a row with that For Month, or use Type all_dues (all dues oldest first, then fines).'],
            ['p', ''],
            ['h', 'Upload and save'],
            ['p', '1. In AI Assistant click 📎 and choose this file.'],
            ['p', '2. The assistant shows a preview of every row: member, how the amount is split, and any problem. Nothing is saved yet.'],
            ['p', '3. "Save entries" records each receipt as one credit in Bank Transactions (payer = Paid By) and maps every part to the loan EMI / savings month / fine, exactly like Bank → Mapping.'],
            ['p', '4. Re-uploading the same file is safe: entries already recorded are skipped. Everything can be reversed from Bank → Mapping.'],
        ];
    }

    /** Worked examples of every situation, explained in English / मराठी / हिंदी, with the rows to write. */
    private function _examples_sheet($book) {
        $x = $book->createSheet()->setTitle('Examples - उदाहरणे');
        $this->_head_row($x);
        $C = date('M Y');
        $P = date('M Y', strtotime(date('Y-m-01') . ' -1 month'));
        $A = date('M Y', strtotime(date('Y-m-01') . ' +2 months'));
        $dt = date('d/m/Y');
        $R = 'MEMB000101 - RAMESH PATIL'; $S = 'MEMB000102 - SUNITA PATIL'; $RA = 'MEMB000103 - RAHUL PATIL'; $M = 'MEMB000104 - MAHESH JADHAV';
        $row = function ($member, $due, $amount, $month, $mode = 'cash', $ref = '', $by = '', $type = '', $acc = '', $rem = '') use ($dt) {
            return [$dt, $member, $due, $amount, $month, $mode, $ref, $by, $type, $acc, $rem];
        };

        $ex = [
            [['Example members — not real, only to explain', 'उदाहरणातील सभासद — खरे नाहीत, फक्त समजावण्यासाठी', 'उदाहरण के सदस्य — असली नहीं, सिर्फ समझाने के लिए'],
             ['Ramesh Patil MEMB000101 — 1 loan LN2026000501, EMI ₹10,000/month, savings ₹5,000/month. Sunita Patil (wife) — EMI ₹4,000, savings ₹1,000. Rahul Patil (son) — savings ₹1,000 only. Mahesh Jadhav — 2 loans: EMI ₹7,000 (LN2026000601) + ₹3,000 (LN2026000602), savings ₹2,000. Write real entries only on the "Passbook" sheet.',
              'रमेश पाटील MEMB000101 — १ कर्ज LN2026000501, हप्ता ₹10,000 दरमहा, बचत ₹5,000 दरमहा. सुनीता पाटील (पत्नी) — हप्ता ₹4,000, बचत ₹1,000. राहुल पाटील (मुलगा) — फक्त बचत ₹1,000. महेश जाधव — २ कर्जे: हप्ता ₹7,000 (LN2026000601) + ₹3,000 (LN2026000602), बचत ₹2,000. खऱ्या नोंदी फक्त "Passbook" शीटवर लिहा.',
              'रमेश पाटिल MEMB000101 — 1 लोन LN2026000501, किस्त ₹10,000 हर महीने, बचत ₹5,000 हर महीने। सुनीता पाटिल (पत्नी) — किस्त ₹4,000, बचत ₹1,000। राहुल पाटिल (बेटा) — सिर्फ बचत ₹1,000। महेश जाधव — 2 लोन: किस्त ₹7,000 (LN2026000601) + ₹3,000 (LN2026000602), बचत ₹2,000। असली प्रविष्टियाँ सिर्फ "Passbook" शीट पर लिखें।'],
             []],
            [['1. Normal monthly payment', '१. नेहमीचा मासिक भरणा', '1. सामान्य मासिक भुगतान'],
             ["Ramesh pays ₹15,000 in cash for {$C}. Write member, amount and month, leave Type blank. → EMI {$C} ₹10,000 + savings {$C} ₹5,000.",
              "रमेशने {$C} साठी ₹15,000 रोख भरले. सभासद, रक्कम आणि महिना लिहा, Type रिकामे ठेवा. → {$C} चा हप्ता ₹10,000 + {$C} ची बचत ₹5,000.",
              "रमेश ने {$C} के लिए ₹15,000 नकद दिए। सदस्य, राशि और महीना लिखें, Type खाली छोड़ें। → {$C} की किस्त ₹10,000 + {$C} की बचत ₹5,000।"],
             [$row($R, 15000, 15000, $C)]],
            [['2. Amount not known — leave it blank', '२. रक्कम माहीत नाही — रिकामी ठेवा', '2. राशि पता नहीं — खाली छोड़ें'],
             ['Ramesh paid exactly his dues. Leave Amount Paid blank. → the system takes that month\'s dues (₹15,000) and splits them.',
              'रमेशने बरोबर देय रक्कम भरली. Amount Paid रिकामे ठेवा. → सिस्टम त्या महिन्याचे देय (₹15,000) घेऊन विभागते.',
              'रमेश ने ठीक देय राशि भरी। Amount Paid खाली छोड़ें। → सिस्टम उस महीने का देय (₹15,000) लेकर बाँट देता है।'],
             [$row($R, 15000, '', $C)]],
            [['3. Paid more than the dues', '३. देयापेक्षा जास्त भरले', '3. देय से ज़्यादा भरा'],
             ['Ramesh pays ₹15,500. → EMI ₹10,000 + savings ₹5,000 + the extra ₹500 to his savings.',
              'रमेशने ₹15,500 भरले. → हप्ता ₹10,000 + बचत ₹5,000 + जास्तीचे ₹500 त्याच्या बचतीत.',
              'रमेश ने ₹15,500 भरे। → किस्त ₹10,000 + बचत ₹5,000 + अतिरिक्त ₹500 उसकी बचत में।'],
             [$row($R, 15000, 15500, $C)]],
            [['4. Paid less than the dues', '४. देयापेक्षा कमी भरले', '4. देय से कम भरा'],
             ["Ramesh pays ₹12,000. → EMI ₹10,000 in full, savings ₹2,000 (₹3,000 of the {$C} savings stays due).",
              "रमेशने ₹12,000 भरले. → हप्ता ₹10,000 पूर्ण, बचत ₹2,000 ({$C} ची ₹3,000 बचत बाकी राहते).",
              "रमेश ने ₹12,000 भरे। → किस्त ₹10,000 पूरी, बचत ₹2,000 ({$C} की ₹3,000 बचत बाकी रहती है)।"],
             [$row($R, 15000, 12000, $C)]],
            [['5. Paid less than even the EMI', '५. हप्त्यापेक्षाही कमी भरले', '5. किस्त से भी कम भरा'],
             ['Ramesh pays ₹9,800. → EMI recorded ₹9,800; the loan rule takes the ₹200 gap from his savings. Short by more than ₹' . self::ADJUST_LIMIT . ' → the row is shown for review.',
              'रमेशने ₹9,800 भरले. → हप्ता ₹9,800 नोंदला जातो; कर्जाच्या नियमानुसार ₹200 फरक त्याच्या बचतीतून घेतला जातो. ₹' . self::ADJUST_LIMIT . ' पेक्षा जास्त कमी असल्यास ओळ तपासणीसाठी दाखवली जाते.',
              'रमेश ने ₹9,800 भरे। → किस्त ₹9,800 दर्ज होती है; लोन नियम से ₹200 का अंतर उसकी बचत से लिया जाता है। ₹' . self::ADJUST_LIMIT . ' से ज़्यादा कम हो तो पंक्ति जाँच के लिए दिखाई जाती है।'],
             [$row($R, 15000, 9800, $C)]],
            [['6. Member with two loans', '६. दोन कर्जे असलेला सभासद', '6. दो लोन वाला सदस्य'],
             ["Mahesh pays ₹12,000 for {$C}. → EMI ₹7,000 (LN2026000601) + EMI ₹3,000 (LN2026000602) + savings ₹2,000. Every loan's EMI of that month is paid.",
              "महेशने {$C} साठी ₹12,000 भरले. → हप्ता ₹7,000 (LN2026000601) + हप्ता ₹3,000 (LN2026000602) + बचत ₹2,000. त्या महिन्याचा प्रत्येक कर्जाचा हप्ता भरला जातो.",
              "महेश ने {$C} के लिए ₹12,000 भरे। → किस्त ₹7,000 (LN2026000601) + किस्त ₹3,000 (LN2026000602) + बचत ₹2,000। उस महीने की हर लोन की किस्त भरी जाती है।"],
             [$row($M, 12000, 12000, $C)]],
            [['7. Paying an old month', '७. मागील महिन्याचा भरणा', '7. पिछले महीने का भुगतान'],
             ["Ramesh pays the {$P} dues today. For Month = {$P}. → the {$P} EMI and savings are paid, even though the date is in {$C}.",
              "रमेश आज {$P} चे देय भरतो. For Month = {$P}. → तारीख {$C} ची असली तरी {$P} चा हप्ता व बचत भरली जाते.",
              "रमेश आज {$P} का देय भरता है। For Month = {$P}। → तारीख {$C} की होने पर भी {$P} की किस्त और बचत भरी जाती है।"],
             [$row($R, 15000, 15000, $P)]],
            [['8. Two months at once', '८. दोन महिने एकदम', '8. दो महीने एक साथ'],
             ["Ramesh pays ₹30,000 for {$P} and {$C}. Option A: two rows, one per month. Option B: one row with Type all_dues (all dues oldest first, then fines, rest to savings). Use only ONE of the options.",
              "रमेशने {$P} आणि {$C} साठी ₹30,000 भरले. पर्याय A: दोन ओळी, प्रत्येक महिन्यासाठी एक. पर्याय B: Type all_dues ची एकच ओळ (सर्व थकबाकी जुन्यापासून, नंतर दंड, उरलेले बचतीत). दोनपैकी एकच पर्याय वापरा.",
              "रमेश ने {$P} और {$C} के लिए ₹30,000 भरे। विकल्प A: दो पंक्तियाँ, हर महीने की एक। विकल्प B: Type all_dues वाली एक पंक्ति (सारा बकाया पुराने से, फिर जुर्माना, बाकी बचत में)। दोनों में से एक ही विकल्प इस्तेमाल करें।"],
             [$row($R, 15000, 15000, $P, 'cash', '', '', '', '', 'Option A'), $row($R, 15000, 15000, $C, 'cash', '', '', '', '', 'Option A'),
              $row($R, 15000, 30000, '', 'cash', '', '', 'all_dues', '', 'Option B')]],
            [['9. Someone else paid', '९. दुसऱ्याने पैसे भरले', '9. किसी और ने भुगतान किया'],
             ['Ramesh pays his wife Sunita\'s ₹5,000 in cash. Member = Sunita, Paid By = Ramesh. → Sunita\'s EMI ₹4,000 + savings ₹1,000; the entry shows "paid by Ramesh".',
              'रमेश पत्नी सुनीताचे ₹5,000 रोख भरतो. Member = सुनीता, Paid By = रमेश. → सुनीताचा हप्ता ₹4,000 + बचत ₹1,000; नोंदीवर "रमेशने भरले" दिसते.',
              'रमेश अपनी पत्नी सुनीता के ₹5,000 नकद भरता है। Member = सुनीता, Paid By = रमेश। → सुनीता की किस्त ₹4,000 + बचत ₹1,000; प्रविष्टि पर "रमेश ने भरा" दिखता है।'],
             [$row($S, 5000, 5000, $C, 'cash', '', $R)]],
            [['10. One UPI for the whole family', '१०. पूर्ण कुटुंबासाठी एकच UPI', '10. पूरे परिवार के लिए एक ही UPI'],
             ['Ramesh sends ONE UPI of ₹21,000 for himself, Sunita and Rahul. Write 3 rows with the same Date, Mode and UTR, Paid By = Ramesh. → one bank credit of ₹21,000, split to each member\'s EMI / savings.',
              'रमेश स्वतः, सुनीता आणि राहुलसाठी ₹21,000 चा एकच UPI पाठवतो. एकच तारीख, पद्धत आणि UTR असलेल्या ३ ओळी लिहा, Paid By = रमेश. → ₹21,000 ची एक बँक जमा नोंद, प्रत्येक सभासदाच्या हप्ता / बचतीत विभागली जाते.',
              'रमेश अपने, सुनीता और राहुल के लिए ₹21,000 का एक ही UPI भेजता है। एक ही तारीख, तरीके और UTR वाली 3 पंक्तियाँ लिखें, Paid By = रमेश। → ₹21,000 की एक बैंक जमा प्रविष्टि, हर सदस्य की किस्त / बचत में बँटती है।'],
             [$row($R, 15000, 15000, $C, 'upi', 'UPI123456789', $R), $row($S, 5000, 5000, $C, 'upi', 'UPI123456789', $R), $row($RA, 1000, 1000, $C, 'upi', 'UPI123456789', $R)]],
            [['11. Only savings', '११. फक्त बचत', '11. सिर्फ बचत'],
             ['Rahul pays only his savings ₹1,000. Type = savings (Type blank also works when he has no loan).',
              'राहुल फक्त बचत ₹1,000 भरतो. Type = savings (कर्ज नसेल तर Type रिकामे ठेवले तरी चालते).',
              'राहुल सिर्फ बचत ₹1,000 भरता है। Type = savings (लोन न हो तो Type खाली भी चलता है)।'],
             [$row($RA, 1000, 1000, $C, 'cash', '', '', 'savings')]],
            [['12. Only one loan\'s EMI', '१२. फक्त एका कर्जाचा हप्ता', '12. सिर्फ एक लोन की किस्त'],
             ['Mahesh pays only the EMI of LN2026000602 (₹3,000). Type = emi and Loan / Savings A/c No = LN2026000602.',
              'महेश फक्त LN2026000602 चा हप्ता (₹3,000) भरतो. Type = emi आणि Loan / Savings A/c No = LN2026000602.',
              'महेश सिर्फ LN2026000602 की किस्त (₹3,000) भरता है। Type = emi और Loan / Savings A/c No = LN2026000602।'],
             [$row($M, 12000, 3000, $C, 'cash', '', '', 'emi', 'LN2026000602')]],
            [['13. Fine', '१३. दंड', '13. जुर्माना'],
             ['Ramesh pays a pending fine of ₹200. Type = fine. → the oldest open fine is paid.',
              'रमेश ₹200 चा थकीत दंड भरतो. Type = fine. → सर्वात जुना दंड भरला जातो.',
              'रमेश ₹200 का बकाया जुर्माना भरता है। Type = fine। → सबसे पुराना जुर्माना भरा जाता है।'],
             [$row($R, 15000, 200, '', 'cash', '', '', 'fine')]],
            [['14. Fees and share capital', '१४. शुल्क व शेअर भांडवल', '14. शुल्क और शेयर पूंजी'],
             ['Membership / joining / admission fee, share capital, processing fee, penalty or other fee: write that Type.',
              'सभासद शुल्क / प्रवेश शुल्क / शेअर भांडवल / प्रोसेसिंग फी / दंड / इतर शुल्क: तो Type लिहा.',
              'सदस्यता / प्रवेश शुल्क, शेयर पूंजी, प्रोसेसिंग फीस, पेनल्टी या अन्य शुल्क: वही Type लिखें।'],
             [$row($RA, 1000, 500, '', 'cash', '', '', 'membership_fee'), $row($RA, 1000, 1000, '', 'cash', '', '', 'share_capital')]],
            [['15. Savings in advance', '१५. आगाऊ बचत', '15. अग्रिम बचत'],
             ["Rahul pays his {$A} savings in advance. For Month = {$A}. → deposited as savings for {$A}.",
              "राहुल {$A} ची बचत आगाऊ भरतो. For Month = {$A}. → {$A} साठी बचत म्हणून जमा.",
              "राहुल {$A} की बचत पहले से भरता है। For Month = {$A}। → {$A} के लिए बचत के रूप में जमा।"],
             [$row($RA, 1000, 1000, $A, 'cash', '', '', 'savings')]],
            [['16. No member code? Use mobile or name', '१६. सभासद कोड नाही? मोबाईल किंवा नाव', '16. सदस्य कोड नहीं? मोबाइल या नाम'],
             ['Write the mobile number or the full name (English or Marathi / Hindi letters). If two members match, the row asks for the member code.',
              'मोबाईल नंबर किंवा पूर्ण नाव लिहा (इंग्रजी किंवा मराठी अक्षरांत). दोन सभासद जुळल्यास ओळ सभासद कोड विचारते.',
              'मोबाइल नंबर या पूरा नाम लिखें (अंग्रेज़ी या देवनागरी में)। दो सदस्य मिलें तो पंक्ति सदस्य कोड माँगती है।'],
             [$row('9876500101', 15000, 15000, $C), $row('रमेश पाटील', 15000, 15000, $C)]],
            [['17. Cheque or NEFT', '१७. चेक किंवा NEFT', '17. चेक या NEFT'],
             ['Mode = cheque or bank_transfer, and the cheque number / UTR in Reference.',
              'Mode = cheque किंवा bank_transfer, आणि Reference मध्ये चेक नंबर / UTR.',
              'Mode = cheque या bank_transfer, और Reference में चेक नंबर / UTR।'],
             [$row($R, 15000, 15000, $C, 'cheque', '004512'), $row($S, 5000, 5000, $C, 'bank_transfer', 'NEFT26110455')]],
            [['18. Same file uploaded twice', '१८. तीच फाईल दोनदा अपलोड', '18. वही फ़ाइल दो बार अपलोड'],
             ['Entries already saved are shown as "Duplicate" and skipped — nothing is recorded twice.',
              'आधी जतन झालेल्या नोंदी "Duplicate" म्हणून दिसतात आणि वगळल्या जातात — काहीही दोनदा नोंदले जात नाही.',
              'पहले सेव हो चुकी प्रविष्टियाँ "Duplicate" दिखती हैं और छोड़ दी जाती हैं — कुछ भी दो बार दर्ज नहीं होता।'],
             []],
        ];

        $n = 2;
        foreach ($ex as list($title, $text, $rows)) {
            $n++;
            $x->setCellValue('A' . $n, implode('   /   ', $title));
            $x->mergeCells('A' . $n . ':K' . $n);
            $x->getStyle('A' . $n)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FFFFFF');
            $x->getStyle('A' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2E5C8A');
            foreach ($text as $k => $t) {
                $n++;
                $x->setCellValue('A' . $n, ['EN', 'मराठी', 'हिंदी'][$k] . ':  ' . $t);
                $x->mergeCells('A' . $n . ':K' . $n);
                $x->getStyle('A' . $n)->getAlignment()->setWrapText(true)->setVertical('top');
                $x->getRowDimension($n)->setRowHeight(mb_strlen($t) > 190 ? 34 : 18);
            }
            foreach ($rows as $r) {
                $n++;
                $x->fromArray($r, null, 'A' . $n, true);
                $x->getStyle('A' . $n . ':K' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8E1');
            }
            $n++;
        }
        $x->setCellValue('A2', 'Examples only — do not upload this sheet. Write real entries on "Passbook".   /   फक्त उदाहरणे — ही शीट अपलोड करू नका, खऱ्या नोंदी "Passbook" शीटवर.   /   सिर्फ उदाहरण — यह शीट अपलोड न करें, असली प्रविष्टियाँ "Passbook" शीट पर।');
        $x->mergeCells('A2:K2');
        $x->getStyle('A2')->getFont()->setBold(true)->getColor()->setRGB('B00020');
        $this->_body_format($x, $n);
        $x->getStyle('A1:K' . $n)->getFont()->setName('Nirmala UI');
        $x->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $x->freezePane('A2');
        return $x;
    }

    // ───────────────────────────── Read ─────────────────────────────

    /** @return array rows: [row, date, member, amount, type, account, mode, reference, remarks] */
    public function read($path) {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('Passbook') ?: $book->getActiveSheet();
        $data = $sheet->toArray(null, true, false, false);
        if (!$data) return [];

        $head = array_map(function ($h) { return strtolower(trim(preg_replace('/[^a-z]+/i', ' ', (string) $h))); }, array_shift($data));
        // Each header is claimed once, most specific first ("Paid By (… Member …)", "For Month", "Receipt Total" must not be
        // taken for Member / Type / Amount). Without a matching header the old 8-column positions apply.
        $used = [];
        $find = function (array $words, $default) use ($head, &$used) {
            foreach ($head as $i => $h) {
                if ($h === '' || isset($used[$i])) continue;
                foreach ($words as $w) if (strpos($h, $w) !== false) { $used[$i] = true; return $i; }
            }
            if ($default !== null && !isset($used[$default])) { $used[$default] = true; return $default; }
            return null;
        };
        $col = [];
        $find(['monthly due', 'due auto'], null);   // information column of the template (formula), not read
        $col['receipt_total'] = $find(['receipt total', 'total received', 'total'], null);
        $col['adjust']        = $find(['adjust'], null);
        $col['receipt']       = $find(['receipt no', 'receipt number', 'receipt', 'rcpt', 'voucher'], null);
        $col['paid_by']       = $find(['paid by', 'payer', 'given by', 'depositor'], null);
        $col['for_month']     = $find(['for month', 'month', 'period'], null);
        $col['date']          = $find(['date'], 0);
        $col['member']        = $find(['member', 'name', 'code'], 1);
        $col['amount']        = $find(['amount', 'credit', 'deposit'], 2);
        $col['type']          = $find(['type', 'head'], null) ?? $find(['for'], 3);
        $col['account']       = $find(['loan', 'savings a', 'a c', 'account'], 4);
        $col['mode']          = $find(['mode'], 5);
        $col['reference']     = $find(['reference', 'utr', 'ref', 'cheque'], 6);
        $col['remarks']       = $find(['remark', 'narration', 'description', 'note'], 7);

        $rows = [];
        foreach ($data as $i => $r) {
            $get = function ($k) use ($r, $col) { return ($col[$k] !== null && isset($r[$col[$k]])) ? $r[$col[$k]] : null; };
            if (trim(implode('', array_map('strval', array_filter($r, function ($v) { return $v !== null; })))) === '') continue;
            $rows[] = [
                'row'       => $i + 2,
                'date_raw'  => $get('date'),
                'member'    => trim((string) $get('member')),
                'amount'    => $get('amount'),
                'type'      => strtolower(trim((string) $get('type'))),
                'account'   => strtoupper(trim((string) $get('account'))),
                'mode'      => strtolower(trim((string) $get('mode'))),
                'reference' => trim((string) $get('reference')),
                'remarks'   => trim((string) $get('remarks')),
                'receipt_no'        => trim((string) $get('receipt')),
                'paid_by'           => trim((string) $get('paid_by')),
                'for_month_raw'     => $get('for_month'),
                'receipt_total_raw' => $get('receipt_total'),
                'adjust_to'         => strtoupper(trim((string) $get('adjust'))),
                'month_layout'      => $col['for_month'] !== null,   // new template: blank Type = that month's EMI + savings
            ];
            if (count($rows) >= self::MAX_ROWS) break;
        }
        return $rows;
    }

    // ───────────────────────────── Resolve (no writes) ─────────────────────────────

    /**
     * @param callable|null $transliterate fn(array $texts): array text => Latin text (AI help for Devanagari names)
     */
    public function resolve(array $rows, $transliterate = null) {
        $db = $this->CI->db;
        $cash_acc = $db->where('is_active', 1)->like('account_name', 'cash')->order_by('is_primary', 'DESC')->limit(1)->get('bank_accounts')->row();
        $bank_acc = $db->where('is_active', 1)->not_like('account_name', 'cash')->order_by('is_primary', 'DESC')->order_by('id', 'ASC')->limit(1)->get('bank_accounts')->row();

        // Devanagari / other-script member names → ask the AI for the English spelling once
        $latin = [];
        if ($transliterate) {
            $need = array_values(array_unique(array_filter(array_column($rows, 'member'), function ($t) { return $t !== '' && preg_match('/[^\x00-\x7F]/u', $t); })));
            if ($need) { try { $latin = (array) call_user_func($transliterate, $need); } catch (Exception $e) { $latin = []; } }
        }

        $out = [];
        $seen = [];
        $this->planned = [];
        foreach ($rows as $r) {
            $r['issues'] = [];
            $r['allocations'] = [];
            $r['notes'] = [];
            $r['status'] = 'ready';

            // Date / amount / mode / type
            $r['date'] = $this->_date($r['date_raw']);
            if (!$r['date']) $r['issues'][] = 'Date "' . $r['date_raw'] . '" not understood';
            elseif ($r['date'] > date('Y-m-d')) $r['issues'][] = 'Date is in the future';
            $amount_raw = trim((string) $r['amount']);
            $r['amount'] = round((float) preg_replace('/[^0-9.\-]/', '', strtr($amount_raw, ['०' => 0, '१' => 1, '२' => 2, '३' => 3, '४' => 4, '५' => 5, '६' => 6, '७' => 7, '८' => 8, '९' => 9])), 2);
            $r['mode'] = $this->_mode($r['mode'], $r['reference']);
            $r['type'] = $this->_type($r['type'], $r['remarks'], !empty($r['month_layout']));
            if ($r['type'] === null) $r['issues'][] = 'Unknown Type';
            $r['amount_auto'] = false;
            if ($r['amount'] <= 0) {
                if ($r['type'] === 'month' && $amount_raw === '') $r['amount_auto'] = true;   // = that month's dues
                else $r['issues'][] = 'Amount missing';
            }
            foreach (['receipt_no', 'paid_by', 'for_month_raw', 'receipt_total_raw', 'adjust_to'] as $k) if (!isset($r[$k])) $r[$k] = '';
            $r['for_month'] = null;
            if ((string) $r['for_month_raw'] !== '') {
                $r['for_month'] = $this->_month($r['for_month_raw']);
                if (!$r['for_month']) $r['issues'][] = 'For Month "' . $r['for_month_raw'] . '" not understood (use Oct 2026, 10/2026 or 2026-10)';
            } elseif ($r['type'] === 'month' && $r['date']) {
                $r['for_month'] = substr($r['date'], 0, 7) . '-01';   // blank = the month of the payment
            }
            // One receipt = same Receipt No; without one, same Date + Mode + Reference (older files); else the row alone
            $r['group_key'] = !empty($r['bank_txn_id']) ? 'bt' . $r['bank_txn_id']
                : ($r['receipt_no'] !== '' ? 'R:' . mb_strtoupper($r['receipt_no'])
                : ($r['reference'] !== '' ? $r['date'] . '|' . $r['mode'] . '|' . $r['reference'] : 'row' . $r['row']));
            $r['bank_account'] = !empty($r['bank_txn_id'])
                ? $db->where('id', $r['bank_account_id_fixed'])->get('bank_accounts')->row()
                : ($r['mode'] === 'cash' ? $cash_acc : $bank_acc);
            if (!$r['bank_account']) $r['issues'][] = 'No active ' . ($r['mode'] === 'cash' ? '"Cash Account"' : 'bank account') . ' in Bank → Accounts';

            // Loan / savings account named on the row
            $loan = $acc = null;
            if ($r['account'] !== '') {
                $loan = $db->where('loan_number', $r['account'])->get('loans')->row();
                if (!$loan) $acc = $db->where('account_number', $r['account'])->get('savings_accounts')->row();
                if (!$loan && !$acc) $r['issues'][] = 'Loan / account "' . $r['account'] . '" not found';
            }

            // Member: column → loan / account → identifiers in remarks / reference (Bank Import rules)
            $member = null;
            $text = $r['member'];
            if (!empty($r['bank_txn_id'])) {
                $mt = $this->_match_bank($r);
                $member = $mt['member'];
                $r['confidence'] = $mt['confidence'];
                $r['match'] = $mt['why'];
                $r['paying_member_id'] = $mt['payer'] ? (int) $mt['payer']->id : null;
                if (!$member) $r['issues'][] = 'Member not identified from "' . mb_substr($r['payer'], 0, 40) . '"' . ($mt['candidates'] ? ' — maybe: ' . implode(', ', $mt['candidates']) : '');
                $text = 'bank';   // skip the column-based search below
            }
            if ($text !== '' && isset($latin[$text]) && is_string($latin[$text])) $text = $latin[$text];
            if ($text !== '' && empty($r['bank_txn_id'])) {
                $found = $this->_members($text);
                if (count($found) === 1) $member = $found[0];
                elseif ($found) $r['issues'][] = count($found) . ' members match "' . $r['member'] . '" — use the member code';
                elseif (!$loan && !$acc) $r['issues'][] = 'Member "' . $r['member'] . '" not found';
            }
            $owner = $loan ? (int) $loan->member_id : ($acc ? (int) $acc->member_id : null);
            if (!$member && $owner && empty($r['bank_txn_id']) && ($text === '' || count($this->_members($text)) === 0)) {
                $member = $db->where('id', $owner)->get('members')->row();
            }
            if (!$member && $text === '' && !$owner) {
                $member = $this->_detect($r['remarks'] . ' ' . $r['reference']);
                if (!$member) $r['issues'][] = 'Member missing';
            }
            if ($member && $owner && (int) $member->id !== $owner) $r['issues'][] = $r['account'] . ' belongs to another member';
            $r['member_id'] = $member ? (int) $member->id : null;
            $r['member_label'] = $member ? $member->member_code . ' — ' . trim($member->first_name . ' ' . $member->last_name) : ($r['member'] ?: '—');

            if ($member && !$r['issues'] && !empty($r['bank_txn_id'])) {
                if (preg_match('/\b(NEFT|IMPS|RTGS)[\s\-]*DR\b|^UPI\/DR\//i', $r['bank_text'])) {
                    $r['issues'][] = 'Bank text says DR (money out) although the line is a credit — check it in Bank Mapping';
                } elseif ($this->_link_existing($r)) {
                    $r['notes'][] = 'Already entered manually — the bank line is only linked, no new payment';
                } else {
                    $monthly = $this->_monthly_dues($member->id);
                    if ($r['amount'] > max(50000, 3 * $monthly)) $r['issues'][] = 'Large amount (monthly dues about ₹' . number_format($monthly, 2) . ') — check what it is for';
                }
            }
            if ($member && !$r['issues'] && !$r['allocations']) $this->_allocate($r, $member, $loan, $acc);

            // Duplicates: same reference already imported / same entry already recorded / repeated in this file
            if (!$r['issues'] && $r['allocations']) {
                if (!empty($r['bank_txn_id'])) {
                    if ($this->_already_recorded($r)) $r['issues'][] = 'A payment of this amount on this date is already recorded for this member (entered manually?) — map this line by hand';
                } elseif ($r['reference'] !== '' && $r['mode'] !== 'cash'
                    && $db->group_start()->where('utr_number', $r['reference'])->or_where('reference_number', $r['reference'])->group_end()
                          ->where('transaction_type', 'credit')->where('amount >=', $r['amount'])->count_all_results('bank_transactions') > 0
                    && !isset($seen['ref:' . $r['date'] . $r['mode'] . $r['reference']])) {
                    $r['status'] = 'duplicate';
                    $r['issues'][] = 'Reference ' . $r['reference'] . ' is already in Bank Transactions';
                } elseif ($this->_already_recorded($r)) {
                    $r['status'] = 'duplicate';
                    $r['issues'][] = 'Same amount for this member on this date is already recorded';
                }
                $key = $r['date'] . '|' . $r['member_id'] . '|' . $r['amount'] . '|' . $r['type'] . '|' . $r['reference'];
                if (isset($seen[$key])) { $r['status'] = 'duplicate'; $r['issues'][] = 'Same as row ' . $seen[$key]; }
                $seen[$key] = $r['row'];
                if ($r['reference'] !== '') $seen['ref:' . $r['date'] . $r['mode'] . $r['reference']] = true;
            }
            if ($r['status'] === 'ready' && ($r['issues'] || !$r['allocations'])) $r['status'] = 'review';
            unset($r['date_raw']);
            $r['bank_account_id'] = $r['bank_account'] ? (int) $r['bank_account']->id : null;
            $r['bank_account_name'] = $r['bank_account'] ? $r['bank_account']->account_name : '';
            unset($r['bank_account']);
            $out[] = $r;
        }

        $this->_receipts($out);

        $sum = ['rows' => count($out), 'ready' => 0, 'review' => 0, 'duplicate' => 0, 'amount_ready' => 0.0, 'receipts' => 0];
        $receipts = [];
        foreach ($out as $r) {
            $sum[$r['status']]++;
            if ($r['status'] === 'ready') {
                $sum['amount_ready'] += array_sum(array_column($r['allocations'], 'amount'));
                $receipts[$r['group_key']] = true;
            }
        }
        $sum['amount_ready'] = round($sum['amount_ready'], 2);
        $sum['receipts'] = count($receipts);
        return ['rows' => $out, 'summary' => $sum];
    }

    /** Allocation of one row (same targets the Bank Mapping screen sends). */
    private function _allocate(array &$r, $member, $loan, $acc) {
        $db = $this->CI->db;
        $mid = (int) $member->id;
        $loans = $loan ? [$loan] : $db->where('member_id', $mid)->where_in('status', ['active', 'npa'])->get('loans')->result();
        $accs  = $acc ? [$acc] : $db->where('member_id', $mid)->where('status', 'active')->get('savings_accounts')->result();
        $ym = $r['for_month'] ? substr($r['for_month'], 0, 7) : null;

        switch ($r['type']) {
            case 'emi':
                if ($acc && !$loan) { $r['issues'][] = $r['account'] . ' is a savings account, not a loan'; return; }
                if (!$loans) { $r['issues'][] = 'Member has no active loan'; return; }
                if ($ym) {
                    if (count($loans) > 1) { $r['issues'][] = 'Member has ' . count($loans) . ' loans — fill Loan No with For Month'; return; }
                    $inst = $this->_inst_for_month($loans[0], $ym);
                    if (!$inst) { $r['issues'][] = 'No unpaid EMI of ' . $loans[0]->loan_number . ' falls in ' . $this->_mlabel($r['for_month']); return; }
                } else {
                    if (count($loans) > 1) { $this->_loans_alloc($r, $loans, $accs, false); return; }
                    $inst = $this->_next_inst($loans[0]);
                    if (!$inst) { $r['issues'][] = 'Loan ' . $loans[0]->loan_number . ' has no unpaid EMI'; return; }
                }
                $due = $this->_emi($r, $loans[0], $inst, $r['amount']);
                if ($due !== false && $r['amount'] + 1 < $due) $r['notes'][] = 'Less than EMI due ₹' . number_format($due, 2) . ' — shortfall is taken from savings (loan rule)';
                if ($due !== false && $r['amount'] > $due + 1) $r['notes'][] = 'More than EMI due ₹' . number_format($due, 2) . ' — excess goes to savings (loan rule)';
                return;
            case 'savings':
                if ($loan && !$acc) { $r['issues'][] = $r['account'] . ' is a loan, not a savings account'; return; }
                if (count($accs) !== 1) { $r['issues'][] = $accs ? 'Member has ' . count($accs) . ' savings accounts — fill A/c No' : 'Member has no active savings account'; return; }
                $this->_sav($r, $accs[0], $r['amount'], $r['for_month']);
                return;
            case 'fine':
                $fine = $db->where('member_id', $mid)->where_in('status', ['pending', 'partial'])->order_by('fine_date', 'ASC')->get('fines')->row();
                if (!$fine) { $r['issues'][] = 'Member has no pending fine'; return; }
                $r['allocations'][] = ['type' => 'fine', 'related' => 'fine_' . $fine->id, 'amount' => $r['amount'], 'label' => 'Fine ' . ($fine->fine_code ?? '#' . $fine->id)];
                $open = (float) $db->select_sum('balance_amount', 's')->where('member_id', $mid)->where_in('status', ['pending', 'partial'])->get('fines')->row()->s;
                if ($r['amount'] > $open + 0.01) {
                    $r['issues'][] = 'More than the member\'s open fines (₹' . number_format($open, 2) . ') — book the extra as savings or another fee';
                } elseif ($r['amount'] > (float) $fine->balance_amount + 0.01) {
                    $r['notes'][] = 'Covers the oldest fine (₹' . number_format((float) $fine->balance_amount, 2) . ') and then the next open fines';
                }
                return;
            case 'month':
                $this->_month_split($r, $loans, $accs);
                return;
            case 'all_dues':
                $this->_all_dues($r, $loans, $accs, $mid, true);
                return;
            case 'auto':
                if ($ym) { $this->_all_dues($r, $loans, $accs, $mid, false); return; }   // dues up to that month, no fines
                if ($loans && $this->_loans_alloc($r, $loans, $accs, true)) return;
                if ($r['issues']) return;
                if (count($accs) !== 1) { $r['issues'][] = $accs ? 'Member has ' . count($accs) . ' savings accounts — fill A/c No' : 'No active loan or savings account'; return; }
                $this->_sav($r, $accs[0], $r['amount'], null);
                return;
            default:   // fees
                $fee = $r['type'] === 'other_fee' ? 'other' : $r['type'];
                $r['allocations'][] = ['type' => 'other_fee_' . $fee, 'related' => '', 'amount' => $r['amount'], 'label' => ucwords(str_replace('_', ' ', $fee))];
        }
    }

    /**
     * Unpaid EMIs of a loan, oldest first, less what earlier rows of this file already put on them (->due_left).
     * @param string|null $cutoff only EMIs due on or before this date
     */
    private function _open_insts($l, $cutoff = null) {
        $db = $this->CI->db;
        $db->where('loan_id', $l->id)->where_in('status', ['overdue', 'pending', 'partial', 'upcoming']);
        if ($cutoff) $db->where('due_date <=', $cutoff);
        $out = [];
        foreach ($db->order_by('installment_number', 'ASC')->limit(120)->get('loan_installments')->result() as $inst) {
            $left = round((float) $inst->emi_amount - (float) $inst->total_paid - ($this->planned[$inst->id] ?? 0), 2);
            if ($left > 0.01) { $inst->due_left = $left; $out[] = $inst; }
        }
        return $out;
    }

    private function _next_inst($l) {
        $o = $this->_open_insts($l);
        return $o ? $o[0] : null;
    }

    private function _inst_for_month($l, $ym) {
        foreach ($this->_open_insts($l) as $i) if (substr($i->due_date, 0, 7) === $ym) return $i;
        return null;
    }

    /** One EMI allocation. @return float|false what was due on it */
    private function _emi(array &$r, $l, $inst, $amount) {
        if (!in_array($l->status, ['active', 'npa'], true)) { $r['issues'][] = 'Loan ' . $l->loan_number . ' is ' . $l->status; return false; }
        $this->planned[$inst->id] = ($this->planned[$inst->id] ?? 0) + round($amount, 2);
        $r['allocations'][] = ['type' => 'emi', 'related' => 'loan_' . $inst->id, 'amount' => round($amount, 2),
                               'for_month' => substr($inst->due_date, 0, 7) . '-01',
                               'label' => 'EMI #' . $inst->installment_number . ' ' . $l->loan_number . ' (' . $this->_mlabel($inst->due_date) . ')'];
        return $inst->due_left;
    }

    /** One savings deposit; with a month it goes on that month's due (schedule row) when there is one. */
    private function _sav(array &$r, $a, $amount, $for_month = null, $label = null) {
        $alloc = ['type' => 'savings', 'related' => 'savings_' . $a->id, 'amount' => round($amount, 2), 'label' => $label ?: 'Savings ' . $a->account_number];
        if ($for_month) {
            $alloc['for_month'] = $for_month;
            $alloc['label'] .= ' (' . $this->_mlabel($for_month) . ')';
            $sch = $this->CI->db->where('savings_account_id', $a->id)->where('due_month', $for_month)->get('savings_schedule')->row();
            if ($sch && in_array($sch->status, ['pending', 'partial', 'overdue'], true)) {
                $alloc['schedule_id'] = (int) $sch->id;
            } elseif ($sch) {
                $r['issues'][] = 'Savings for ' . $this->_mlabel($for_month) . ' on ' . $a->account_number . ' is already ' . $sch->status . ' — leave For Month blank for an extra deposit';
                return;
            } else {
                $r['notes'][] = 'No savings due for ' . $this->_mlabel($for_month) . ' on ' . $a->account_number . ' — saved as a deposit for that month';
            }
        }
        $r['allocations'][] = $alloc;
    }

    /**
     * auto / emi on several loans: EMIs due on the member's loans — every overdue month (oldest first), else each loan's next EMI.
     * Amount ≈ one EMI → that EMI; ≈ all of them (or more) → all, rest to savings; = the oldest few → those.
     * ₹1 tolerance: bank payments are whole rupees (₹10,033 for an EMI of ₹10,033.37 counts as the EMI).
     * @return bool true when allocated (or an issue was raised); false = no EMI due, caller decides (savings).
     */
    private function _loans_alloc(array &$r, array $loans, array $accs, $auto) {
        $today = $r['date'] ?: date('Y-m-d');
        $items = [];
        foreach ($loans as $l) {
            if (!in_array($l->status, ['active', 'npa'], true)) continue;
            $open = $this->_open_insts($l);
            if (!$open) continue;
            $over = array_values(array_filter($open, function ($i) use ($today) { return $i->due_date < $today; }));
            foreach ($over ?: [$open[0]] as $i) $items[] = ['l' => $l, 'i' => $i, 'due' => $i->due_left];
        }
        if (!$items) return false;
        usort($items, function ($a, $b) { return strcmp($a['i']->due_date, $b['i']->due_date) ?: $a['l']->id - $b['l']->id; });
        $amt = $r['amount'];
        $total = round(array_sum(array_column($items, 'due')), 2);
        $list = implode(', ', array_map(function ($d) { return $d['l']->loan_number . ' #' . $d['i']->installment_number . ' ₹' . number_format($d['due'], 2); }, $items));

        $one = array_values(array_filter($items, function ($d) use ($amt) { return abs($amt - $d['due']) <= 1; }));
        if (count($items) > 1 && $one && abs($amt - $total) > 1) {
            $this->_emi($r, $one[0]['l'], $one[0]['i'], $amt);
            $r['notes'][] = 'Matched the EMI of ' . $one[0]['l']->loan_number . ' (' . $this->_mlabel($one[0]['i']->due_date) . ')';
            return true;
        }
        if ($amt + count($items) >= $total) {
            $rest = round($amt - $total, 2);
            foreach ($items as $k => $d) {
                $x = $d['due'] + ($k === count($items) - 1 && $rest < 0 ? $rest : 0);   // whole-rupee shortfall on the last EMI
                $this->_emi($r, $d['l'], $d['i'], round($x, 2));
            }
            if ($rest > 0.5) {
                if (count($accs) === 1) $this->_sav($r, $accs[0], $rest, null, 'Extra → Savings ' . $accs[0]->account_number);
                else $r['notes'][] = 'Excess ₹' . number_format($rest, 2) . ' goes to savings by the loan rule';
            }
            if (count($items) > 1) $r['notes'][] = 'Paid ' . count($items) . ' EMIs: ' . $list;
            return true;
        }
        // The oldest EMIs the amount covers exactly (e.g. 2 of 3 overdue months)
        $paid = 0; $n = 0;
        foreach ($items as $d) { if ($paid + $d['due'] <= $amt + 1) { $paid += $d['due']; $n++; } else break; }
        if ($n > 0 && abs($amt - $paid) <= 1) {
            $rest = round($amt - $paid, 2);
            for ($k = 0; $k < $n; $k++) $this->_emi($r, $items[$k]['l'], $items[$k]['i'], round($items[$k]['due'] + ($k === $n - 1 ? $rest : 0), 2));
            $r['notes'][] = 'Paid the ' . $n . ' oldest EMI(s); still due: ' . implode(', ', array_map(function ($d) { return $d['l']->loan_number . ' #' . $d['i']->installment_number; }, array_slice($items, $n)));
            return true;
        }
        $r['issues'][] = count($items) === 1
            ? '₹' . number_format($amt, 2) . ' is less than the EMI due ₹' . number_format($items[0]['due'], 2) . ($auto ? ' — set Type emi (shortfall from savings) or savings' : '')
            : '₹' . number_format($amt, 2) . ' does not match the EMIs due (' . $list . ') — fill Loan No, or use Type all_dues';
        return true;
    }

    /**
     * month (blank Type in the template): the amount is split over the chosen month's EMI of every active loan and that
     * month's savings due — EMIs first, then savings, extra to savings. Short by up to ADJUST_LIMIT: the last EMI is
     * recorded short and the loan rule takes the gap from savings; more → review. Amount left blank = exactly the dues.
     */
    private function _month_split(array &$r, array $loans, array $accs) {
        $db = $this->CI->db;
        $m = $r['for_month'] ?: date('Y-m-01');
        $ym = substr($m, 0, 7);
        $ml = $this->_mlabel($m);

        $emis = [];
        foreach ($loans as $l) {
            if (!in_array($l->status, ['active', 'npa'], true)) continue;
            $i = $this->_inst_for_month($l, $ym);
            if ($i) $emis[] = ['l' => $l, 'i' => $i];
        }
        $savs = [];
        foreach ($accs as $a) {
            $s = $db->where('savings_account_id', $a->id)->where('due_month', $m)->where_in('status', ['pending', 'partial', 'overdue'])->get('savings_schedule')->row();
            $rem = $s ? round((float) $s->due_amount - (float) $s->paid_amount - ($this->planned['s' . $s->id] ?? 0), 2) : 0;
            if ($rem > 0.009) $savs[] = ['a' => $a, 's' => $s, 'due' => $rem];
        }
        $emi_total = round(array_sum(array_map(function ($e) { return $e['i']->due_left; }, $emis)), 2);
        $sav_total = round(array_sum(array_column($savs, 'due')), 2);
        $dues = round($emi_total + $sav_total, 2);

        if ($r['amount_auto']) {
            if ($dues < 0.01) { $r['issues'][] = 'Nothing is due for ' . $ml . ' — enter the Amount Paid'; return; }
            $r['amount'] = $dues;
            $r['notes'][] = 'Amount = dues of ' . $ml;
        }
        $left = round($r['amount'], 2);

        // 1. EMIs of the month (a small shortfall: the last EMI is recorded short, the loan rule takes the gap from savings)
        if ($emis && $left + 1 < $emi_total) {
            $short = round($emi_total - $left, 2);
            if ($short > self::ADJUST_LIMIT) {
                $r['issues'][] = '₹' . number_format($left, 2) . ' is less than the ' . $ml . ' EMIs ₹' . number_format($emi_total, 2) . ' ('
                               . implode(', ', array_map(function ($e) { return $e['l']->loan_number . ' ₹' . number_format($e['i']->due_left, 2); }, $emis))
                               . ') — set Type emi with a Loan No, or check the amount';
                return;
            }
        }
        $short = max(0, round($emi_total - $r['amount'], 2));   // > 0 only when the EMIs are short by ≤ limit (checked above)
        foreach ($emis as $k => $e) {
            $x = $e['i']->due_left;
            if ($k === count($emis) - 1 && $short > 0) $x = round($x - $short, 2);   // the last EMI carries the shortfall
            if ($x < 0.01) continue;
            $this->_emi($r, $e['l'], $e['i'], $x);
            $left = round($left - $x, 2);
        }
        if ($emis && $r['amount'] + 1 < $emi_total) {
            $r['notes'][] = '₹' . number_format($emi_total - $r['amount'], 2) . ' short of the ' . $ml . ' EMIs — the loan rule takes it from savings';
        }

        // 2. Savings due of the month (part-paid when the money runs out)
        foreach ($savs as $s) {
            if ($left < 0.01) { $r['notes'][] = 'Savings ' . $s['a']->account_number . ' for ' . $ml . ' (₹' . number_format($s['due'], 2) . ') stays due'; continue; }
            $x = min($s['due'], $left);
            $this->_sav($r, $s['a'], $x, $m);
            $this->planned['s' . $s['s']->id] = ($this->planned['s' . $s['s']->id] ?? 0) + $x;
            if ($x + 0.01 < $s['due']) $r['notes'][] = 'Savings for ' . $ml . ' part-paid: ₹' . number_format($x, 2) . ' of ₹' . number_format($s['due'], 2);
            $left = round($left - $x, 2);
        }

        // 3. Extra → savings
        if ($left >= 0.01) {
            if (count($accs) === 1) $this->_sav($r, $accs[0], $left, null, 'Extra → Savings ' . $accs[0]->account_number);
            else $r['issues'][] = ($accs ? 'Member has ' . count($accs) . ' savings accounts' : 'Member has no active savings account') . ' for the extra ₹' . number_format($left, 2);
        }
        if (!$emis && !$savs) $r['notes'][] = 'Nothing was due for ' . $ml . ' — the whole amount goes to savings';
        else $r['notes'][] = $ml . ': ' . count($emis) . ' EMI(s) ₹' . number_format($emi_total, 2) . ($savs ? ' + savings ₹' . number_format($sav_total, 2) : '');

        // Earlier months still open — shown so nothing is missed
        $older = [];
        foreach ($loans as $l) {
            if (!in_array($l->status, ['active', 'npa'], true)) continue;
            foreach ($this->_open_insts($l, date('Y-m-d', strtotime($m . ' -1 day'))) as $i) $older[] = $l->loan_number . ' ' . $this->_mlabel($i->due_date);
        }
        foreach ($accs as $a) {
            $n = $db->where('savings_account_id', $a->id)->where('due_month <', $m)->where_in('status', ['pending', 'partial', 'overdue'])->count_all_results('savings_schedule');
            if ($n) $older[] = 'savings ' . $n . ' month(s)';
        }
        if ($older) $r['notes'][] = 'Still unpaid before ' . $ml . ': ' . implode(', ', array_slice($older, 0, 6)) . (count($older) > 6 ? ' …' : '');
    }

    /**
     * all_dues: overdue / current EMIs (oldest first, all loans) → savings dues → open fines → the rest to savings.
     * Dues up to the end of For Month (else of the payment month). An EMI is never part-paid here: when the money runs out
     * in the middle of one, the remainder is saved as savings.
     */
    private function _all_dues(array &$r, array $loans, array $accs, $mid, $with_fines) {
        $db = $this->CI->db;
        $cut = date('Y-m-t', strtotime($r['for_month'] ?: ($r['date'] ?: date('Y-m-d'))));
        $left = round($r['amount'], 2);
        $got = ['emi' => 0, 'sav' => 0, 'fine' => 0];

        $items = [];
        foreach ($loans as $l) {
            if (!in_array($l->status, ['active', 'npa'], true)) continue;
            foreach ($this->_open_insts($l, $cut) as $i) $items[] = ['l' => $l, 'i' => $i];
        }
        usort($items, function ($a, $b) { return strcmp($a['i']->due_date, $b['i']->due_date) ?: $a['l']->id - $b['l']->id; });
        $stopped = null;
        foreach ($items as $it) {
            $due = $it['i']->due_left;
            if ($due > $left + 1) { $stopped = $it; break; }
            $this->_emi($r, $it['l'], $it['i'], min($due, $left));
            $left = round($left - min($due, $left), 2);
            $got['emi']++;
        }

        if (!$stopped) {
            foreach ($accs as $a) {
                if ($left < 0.01) break;
                $rows = $db->where('savings_account_id', $a->id)->where_in('status', ['pending', 'partial', 'overdue'])
                           ->where('due_date <=', $cut)->order_by('due_date', 'ASC')->get('savings_schedule')->result();
                $due = round(array_sum(array_map(function ($s) { return max(0, (float) $s->due_amount - (float) $s->paid_amount); }, $rows)), 2);
                if ($due < 0.01) continue;
                $x = min($due, $left);
                $months = array_unique(array_map(function ($s) { return $this->_mlabel($s->due_month); }, $rows));
                $this->_sav($r, $a, $x, null, 'Savings dues ' . (count($months) > 2 ? reset($months) . ' – ' . end($months) : implode(', ', $months)) . ' ' . $a->account_number);
                $left = round($left - $x, 2);
                $got['sav']++;
            }
            if ($with_fines && $left >= 0.01) {
                $fine = $db->where('member_id', $mid)->where_in('status', ['pending', 'partial'])->where('balance_amount >', 0)->order_by('fine_date', 'ASC')->get('fines')->row();
                $open = (float) $db->select_sum('balance_amount', 's')->where('member_id', $mid)->where_in('status', ['pending', 'partial'])->get('fines')->row()->s;
                if ($fine && $open > 0.009) {
                    $x = round(min($open, $left), 2);
                    $r['allocations'][] = ['type' => 'fine', 'related' => 'fine_' . $fine->id, 'amount' => $x, 'label' => 'Fines (open ₹' . number_format($open, 2) . ')'];
                    $left = round($left - $x, 2);
                    $got['fine']++;
                }
            }
        }

        if ($left >= 0.01) {
            if (count($accs) === 1) {
                $this->_sav($r, $accs[0], $left, null, ($stopped ? 'Remaining → Savings ' : 'Extra → Savings ') . $accs[0]->account_number);
            } else {
                $r['issues'][] = ($accs ? 'Member has ' . count($accs) . ' savings accounts — fill A/c No' : 'Member has no active savings account')
                               . ' for the remaining ₹' . number_format($left, 2);
            }
        }
        if ($stopped) {
            $r['notes'][] = 'Not enough for EMI #' . $stopped['i']->installment_number . ' of ' . $stopped['l']->loan_number . ' (₹' . number_format($stopped['i']->due_left, 2)
                          . ', ' . $this->_mlabel($stopped['i']->due_date) . ') — it stays due' . ($left >= 0.01 ? '; the remainder is saved as savings' : '');
        }
        $r['notes'][] = 'All dues up to ' . $this->_mlabel($cut) . ': ' . $got['emi'] . ' EMI(s)' . ($got['sav'] ? ', savings dues' : '') . ($got['fine'] ? ', fines' : '');
    }

    /**
     * Receipts (rows with the same group key): one payer ("Paid By"), consistent date / mode, and the money actually
     * received ("Receipt Total") — a difference up to ADJUST_LIMIT goes to / comes from the savings of "Adjust Difference To".
     * A receipt is saved as a whole, so one row needing review holds back its other rows.
     */
    private function _receipts(array &$rows) {
        $db = $this->CI->db;
        $groups = [];
        foreach ($rows as $i => $r) if (empty($r['bank_txn_id'])) $groups[$r['group_key']][] = $i;
        $name = function ($m) { return $m->member_code . ' — ' . trim($m->first_name . ' ' . $m->last_name); };

        foreach ($groups as $idx) {
            $first = $idx[0];
            $multi = count($idx) > 1;
            $label = $rows[$first]['receipt_no'] !== '' ? 'Receipt ' . $rows[$first]['receipt_no'] : 'Receipt of row ' . $rows[$first]['row'];
            $flag = function ($msg) use (&$rows, $idx) {
                foreach ($idx as $i) { $rows[$i]['issues'][] = $msg; if ($rows[$i]['status'] === 'ready') $rows[$i]['status'] = 'review'; }
            };
            $vals = function ($k) use (&$rows, $idx) {
                return array_values(array_unique(array_filter(array_map(function ($i) use (&$rows, $k) { return trim((string) $rows[$i][$k]); }, $idx), 'strlen')));
            };
            if ($multi && (count($vals('date')) > 1 || count($vals('mode')) > 1)) $flag($label . ': its rows have a different Date or Mode');

            // Who gave the money
            $payer = null;
            $pb = array_values(array_unique(array_map('mb_strtoupper', $vals('paid_by'))));
            if (count($pb) > 1) {
                $flag($label . ': different "Paid By" on its rows');
            } elseif ($pb) {
                $found = $this->_members($pb[0]);
                if (count($found) === 1) $payer = $found[0];
                else $flag('Paid By "' . $rows[$first]['paid_by'] . '" ' . ($found ? 'matches ' . count($found) . ' members — use the member code' : 'not found'));
            } elseif ($rows[$first]['member_id']) {
                $payer = $db->where('id', $rows[$first]['member_id'])->get('members')->row();
            }
            foreach ($idx as $i) {
                $rows[$i]['paying_member_id'] = $payer ? (int) $payer->id : null;
                $rows[$i]['payer_label'] = $payer ? $name($payer) : '';
                $rows[$i]['receipt_label'] = $multi || $rows[$i]['receipt_no'] !== '' ? $label : '';
                if ($payer && $rows[$i]['member_id'] && (int) $payer->id !== (int) $rows[$i]['member_id']) $rows[$i]['notes'][] = 'Paid by ' . $name($payer);
            }

            $tot = array_values(array_unique(array_map(function ($v) { return number_format((float) preg_replace('/[^0-9.\-]/', '', $v), 2, '.', ''); }, $vals('receipt_total_raw'))));
            $adj = $vals('adjust_to');
            if (count($tot) > 1) $flag($label . ': different Receipt Total values on its rows');
            if (count($adj) > 1) $flag($label . ': different "Adjust Difference To" values on its rows');

            // Only a receipt whose rows are all ready can be balanced and saved
            $held = array_values(array_filter($idx, function ($i) use (&$rows) { return $rows[$i]['status'] !== 'ready'; }));
            if ($held) {
                if ($multi) {
                    $nums = implode(', ', array_map(function ($i) use (&$rows) { return $rows[$i]['row']; }, $held));
                    foreach ($idx as $i) if ($rows[$i]['status'] === 'ready') {
                        $rows[$i]['status'] = 'review';
                        $rows[$i]['issues'][] = $label . ': row ' . $nums . ' needs attention first — a receipt is saved as a whole';
                    }
                }
                continue;
            }
            if (count($tot) !== 1) continue;

            $total = round((float) $tot[0], 2);
            $alloc = round(array_sum(array_map(function ($i) use (&$rows) { return array_sum(array_column($rows[$i]['allocations'], 'amount')); }, $idx)), 2);
            $diff = round($total - $alloc, 2);
            $head = $label . ': received ₹' . number_format($total, 2) . ', rows ₹' . number_format($alloc, 2);
            if (abs($diff) < 0.01) { $rows[$first]['notes'][] = $head . ' — matches'; continue; }
            if ($total <= 0 || abs($diff) > self::ADJUST_LIMIT) {
                $flag($head . ' — difference ₹' . number_format(abs($diff), 2) . ' is more than ₹' . self::ADJUST_LIMIT . ', check the sheet');
                continue;
            }
            $target = $this->_adjust_account($adj ? $adj[0] : '', $payer);
            if (is_string($target)) { $flag($head . ' — ' . $target); continue; }
            list($tacc, $tmem) = $target;
            $who = $name($tmem);

            if ($diff > 0) {
                $rows[$first]['allocations'][] = ['type' => 'savings', 'related' => 'savings_' . $tacc->id, 'amount' => $diff, 'member_id' => (int) $tmem->id,
                                                  'label' => 'Extra → Savings ' . $tacc->account_number . ' (' . $tmem->member_code . ')'];
                $rows[$first]['notes'][] = $head . ' — extra ₹' . number_format($diff, 2) . ' deposited to the savings of ' . $who;
                continue;
            }
            $short = -$diff;
            $done = false;
            foreach (['savings', 'emi'] as $kind) {        // 1. their savings entry, 2. their EMI (recorded short)
                foreach ($idx as $i) {
                    foreach ($rows[$i]['allocations'] as $k => $a) {
                        $owner = (int) ($a['member_id'] ?? $rows[$i]['member_id']);
                        $mine = $kind === 'savings' ? $a['related'] === 'savings_' . $tacc->id : $owner === (int) $tmem->id;
                        if ($a['type'] !== $kind || !$mine || $a['amount'] < $short - 0.001 || ($kind === 'emi' && $a['amount'] - $short < 0.01)) continue;
                        $rest = round($a['amount'] - $short, 2);
                        if ($rest < 0.01) { unset($rows[$i]['allocations'][$k]); $rows[$i]['allocations'] = array_values($rows[$i]['allocations']); }
                        else { $rows[$i]['allocations'][$k]['amount'] = $rest; $rows[$i]['allocations'][$k]['label'] .= ' (−₹' . number_format($short, 2) . ')'; }
                        $rows[$first]['notes'][] = $head . ' — ₹' . number_format($short, 2) . ' less '
                            . ($kind === 'savings' ? 'on the savings of ' . $who : 'on the EMI of ' . $who . '; the loan rule takes the gap from their savings');
                        $done = true;
                        break 3;
                    }
                }
            }
            if (!$done) $flag($head . ' — ₹' . number_format($short, 2) . ' short and there is no savings / EMI entry of ' . $who . ' in this receipt to reduce');
        }
    }

    /** Savings account that takes a receipt difference: "Adjust Difference To" (A/c No or member), else the payer's. */
    private function _adjust_account($text, $payer) {
        $db = $this->CI->db;
        $acc = null;
        $member = $payer;
        if ($text !== '') {
            $acc = $db->where('account_number', $text)->where('status', 'active')->get('savings_accounts')->row();
            if ($acc) {
                $member = $db->where('id', $acc->member_id)->get('members')->row();
            } else {
                $found = $this->_members($text);
                if (count($found) !== 1) return '"Adjust Difference To" ' . $text . ($found ? ' matches ' . count($found) . ' members — use the member code' : ' not found');
                $member = $found[0];
            }
        }
        if (!$member) return 'no member to give the difference to — fill "Adjust Difference To"';
        if (!$acc) {
            $accs = $db->where('member_id', $member->id)->where('status', 'active')->get('savings_accounts')->result();
            if (count($accs) !== 1) return $member->member_code . ($accs ? ' has ' . count($accs) . ' savings accounts — put the A/c No in "Adjust Difference To"' : ' has no active savings account for the difference');
            $acc = $accs[0];
        }
        return [$acc, $member];
    }

    private function _already_recorded(array $r) {
        $db = $this->CI->db;
        foreach ($r['allocations'] as $a) {
            if ($a['type'] === 'emi') {
                $n = $db->from('loan_payments lp')->join('loans l', 'l.id = lp.loan_id')->where('l.member_id', $r['member_id'])
                        ->where('lp.payment_date', $r['date'])->where('lp.total_amount', $a['amount'])->where('lp.is_reversed', 0)->count_all_results();
            } elseif ($a['type'] === 'savings') {
                $n = $db->from('savings_transactions st')->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('sa.member_id', $r['member_id'])
                        ->where('st.transaction_date', $r['date'])->where('st.amount', $a['amount'])->where('st.transaction_type', 'deposit')
                        ->where('st.is_reversed', 0)->count_all_results();
            } elseif (strpos($a['type'], 'other_fee_') === 0) {
                $n = $db->where('member_id', $r['member_id'])->where('transaction_type', substr($a['type'], 10))
                        ->where('transaction_date', $r['date'])->where('amount', $a['amount'])->where('reversed_at', null)->count_all_results('member_other_transactions');
            } else {
                continue;
            }
            if ($n > 0) return true;
        }
        return false;
    }

    // ───────────────────────────── Execute ─────────────────────────────

    /**
     * Save the ready rows. Each receipt (rows grouped by Date + Mode + Reference) is saved in its own DB
     * transaction, so one bad row never blocks the others.
     * @return array ['saved' => n, 'failed' => n, 'amount' => float, 'results' => [row => [status, message]]]
     */
    public function execute(array $rows, $admin_id, $file_name = '') {
        $db = $this->CI->db;
        $groups = [];
        foreach ($rows as $r) {
            if ($r['status'] !== 'ready') continue;
            $key = !empty($r['group_key']) ? $r['group_key'] . '|' . $r['bank_account_id']
                 : (!empty($r['bank_txn_id']) ? 'bt' . $r['bank_txn_id']
                 : ($r['reference'] !== '' ? $r['date'] . '|' . $r['mode'] . '|' . $r['reference'] . '|' . $r['bank_account_id'] : 'row' . $r['row']));
            $groups[$key][] = $r;
        }

        $imports = [];
        $res = ['saved' => 0, 'failed' => 0, 'amount' => 0.0, 'results' => [], 'import_codes' => []];
        $debug = $db->db_debug;
        $db->db_debug = false;
        foreach ($groups as $g) {
            $first = $g[0];
            $total = round(array_sum(array_map(function ($r) { return array_sum(array_column($r['allocations'], 'amount')); }, $g)), 2);
            $db->trans_begin();
            try {
                if (!empty($first['bank_txn_id'])) {
                    $this->_map_existing($first, $g, $total, $admin_id);
                    $db->trans_commit();
                    foreach ($g as $r) {
                        $res['results'][$r['row']] = ['status' => 'saved', 'message' => implode(' + ', array_map(function ($a) { return $a['label'] . ' ₹' . number_format($a['amount'], 2); }, $r['allocations']))];
                        $res['saved']++;
                    }
                    $res['amount'] += $total;
                    continue;
                }
                $acc_id = $first['bank_account_id'];
                if (!isset($imports[$acc_id])) {
                    $code = 'AIPB-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
                    $db->insert('bank_statement_imports', [
                        'import_code' => $code, 'bank_account_id' => $acc_id, 'file_name' => mb_substr($file_name ?: 'AI passbook import', 0, 255),
                        'file_path' => '', 'file_type' => 'excel', 'statement_date' => date('Y-m-d'), 'status' => 'completed',
                        'imported_by' => $admin_id, 'imported_at' => date('Y-m-d H:i:s'),
                    ]);
                    $imports[$acc_id] = (int) $db->insert_id();
                    $db->trans_commit();   // the import header is kept even if a receipt fails
                    $db->trans_begin();
                    $res['import_codes'][] = $code;
                }
                // Who gave the money (Paid By) and, when it is one member, for whom
                $payer = !empty($first['paying_member_id']) ? (int) $first['paying_member_id'] : (int) $first['member_id'];
                $for = [];
                foreach ($g as $r) foreach ($r['allocations'] as $a) $for[(int) ($a['member_id'] ?? $r['member_id'])] = true;
                $desc = 'AI passbook' . (!empty($first['receipt_label']) ? ' ' . $first['receipt_label'] : '')
                       . (!empty($first['payer_label']) && count($for) > 1 ? ' paid by ' . $first['payer_label'] : '') . ': ' . implode(' + ', array_map(function ($r) {
                    return $r['member_label'] . ' ' . implode('/', array_column($r['allocations'], 'label')) . ($r['remarks'] !== '' ? ' (' . $r['remarks'] . ')' : '');
                }, $g));
                $txn = [
                    'import_id' => $imports[$acc_id], 'bank_account_id' => $acc_id, 'transaction_date' => $first['date'], 'value_date' => $first['date'],
                    'transaction_type' => 'credit', 'amount' => $total, 'credit_amount' => $total, 'debit_amount' => 0,
                    'description' => mb_substr($desc, 0, 500), 'reference_number' => $first['reference'] ?: null,
                    'utr_number' => ($first['reference'] !== '' && $first['mode'] !== 'cash') ? mb_substr($first['reference'], 0, 50) : null,
                    'cheque_number' => $first['mode'] === 'cheque' ? mb_substr($first['reference'], 0, 30) : null,
                    'mapping_status' => 'unmapped', 'detected_member_id' => $payer, 'paid_by_member_id' => $payer,
                    'paid_for_member_id' => count($for) === 1 ? (int) key($for) : null,
                    'remarks' => 'Imported from passbook via AI Assistant', 'created_at' => date('Y-m-d H:i:s'),
                ];
                if (!$db->insert('bank_transactions', $txn)) throw new Exception($db->error()['message'] ?? 'could not create the bank transaction');
                $txn_id = (int) $db->insert_id();
                $txn_row = $db->where('id', $txn_id)->get('bank_transactions')->row();

                $mappings = [];
                foreach ($g as $r) {
                    foreach ($r['allocations'] as $a) {
                        $for_id = (int) ($a['member_id'] ?? $r['member_id']);
                        $mappings[] = ['paying_member_id' => $payer, 'paid_for_member_id' => $for_id, 'transaction_type' => $a['type'],
                                       'related_account' => $a['related'], 'amount' => $a['amount'],
                                       'for_month' => $a['for_month'] ?? null, 'schedule_id' => $a['schedule_id'] ?? null,
                                       'remarks' => mb_substr(($r['remarks'] !== '' ? $r['remarks'] : 'Passbook entry')
                                           . (!empty($a['for_month']) ? ' · for ' . date('M Y', strtotime($a['for_month'])) : '')
                                           . ($payer !== $for_id && !empty($r['payer_label']) ? ' · paid by ' . $r['payer_label'] : '')
                                           . ' (AI import' . (!empty($r['receipt_label']) ? ', ' . $r['receipt_label'] : '') . ', row ' . $r['row'] . ')', 0, 255)];
                    }
                }
                $this->CI->Bank_model->apply_mappings($txn_row, $mappings, $admin_id, 0, $total, $total, $first['mode']);

                // The mapping code does not check record_payment() — make sure every allocation really became a record
                $need = ['emi' => 0, 'savings' => 0];
                foreach ($mappings as $mp) if (isset($need[$mp['transaction_type']])) $need[$mp['transaction_type']]++;
                $got_lp = $db->where('bank_transaction_id', $txn_id)->where('is_reversed', 0)->count_all_results('loan_payments');
                $got_sv = $db->where('bank_transaction_id', $txn_id)->where('transaction_type', 'deposit')->count_all_results('savings_transactions');
                if ($got_lp < $need['emi']) throw new Exception('The loan payment could not be recorded (check the loan / EMI).');
                if ($got_sv < $need['savings']) throw new Exception('The savings deposit could not be recorded (check the savings account).');

                // The mapping code records "bank_transfer" — keep the passbook's real mode and reference
                $this->_fix_modes($txn_id, $first['mode'], $first['reference']);

                if ($db->trans_status() === false) {
                    $e = $db->error();
                    throw new Exception($e['message'] ?: 'database error');
                }
                $db->trans_commit();
                foreach ($g as $r) {
                    $res['results'][$r['row']] = ['status' => 'saved', 'message' => implode(' + ', array_map(function ($a) {
                        return $a['label'] . ' ₹' . number_format($a['amount'], 2); }, $r['allocations']))];
                    $res['saved']++;
                }
                $res['amount'] += $total;
            } catch (Exception $e) {
                $db->trans_rollback();
                foreach ($g as $r) {
                    $res['results'][$r['row']] = ['status' => 'failed', 'message' => $e->getMessage()];
                    $res['failed']++;
                }
            }
        }
        $db->db_debug = $debug;

        foreach ($imports as $acc_id => $import_id) {
            $n = $db->where('import_id', $import_id)->count_all_results('bank_transactions');
            $db->where('id', $import_id)->update('bank_statement_imports', [
                'total_transactions' => $n, 'mapped_count' => $n, 'unmapped_count' => 0, 'completed_at' => date('Y-m-d H:i:s'),
                'total_credits' => $db->select_sum('amount')->where('import_id', $import_id)->get('bank_transactions')->row()->amount ?? 0,
            ]);
            if ($n === 0) $db->where('id', $import_id)->delete('bank_statement_imports');
        }
        return $res;
    }

    /** Map an EXISTING bank credit (bank mapping suggestions). Same checks as the Bank Mapping screen. */
    private function _map_existing(array $first, array $g, $total, $admin_id) {
        $db = $this->CI->db;
        $txn = $db->query('SELECT * FROM bank_transactions WHERE id = ? FOR UPDATE', [$first['bank_txn_id']])->row();
        if (!$txn) throw new Exception('Bank transaction no longer exists');
        if ($txn->mapping_status === 'mapped') throw new Exception('Already mapped by someone else');
        $txn_amount = (float) $txn->amount;
        $already = (float) $txn->mapped_amount;
        if ($total > ceil($txn_amount - $already) + 0.01) throw new Exception('Amount is more than the unmapped balance of this bank line');

        $mappings = [];
        foreach ($g as $r) {
            foreach ($r['allocations'] as $a) {
                $mappings[] = ['paying_member_id' => $r['paying_member_id'] ?: $r['member_id'], 'paid_for_member_id' => $r['member_id'],
                               'transaction_type' => $a['type'], 'related_account' => $a['related'], 'amount' => $a['amount'],
                               'remarks' => mb_substr('AI mapping: ' . ($r['match'] ?? ''), 0, 255)];
            }
        }
        $links = array_values(array_filter($mappings, function ($m) { return strpos($m['transaction_type'], 'link_') === 0; }));
        if ($links) {
            if (count($links) !== count($mappings)) throw new Exception('Mixed link / new entries are not supported — map this line by hand');
            foreach ($links as $l) {
                $table = $l['transaction_type'] === 'link_savings' ? 'savings_transactions' : 'loan_payments';
                $rec = $db->where('id', (int) $l['related_account'])->where('bank_transaction_id IS NULL', null, false)->get($table)->row();
                if (!$rec) throw new Exception('The manual entry is no longer unlinked — refresh the suggestions');
                $db->where('id', $rec->id)->update($table, ['bank_transaction_id' => $txn->id]);
                $db->insert('transaction_mappings', ['bank_transaction_id' => $txn->id, 'member_id' => $l['paid_for_member_id'],
                    'mapping_type' => $l['transaction_type'] === 'link_savings' ? 'savings' : 'loan_payment', 'related_type' => $table, 'related_id' => $rec->id,
                    'amount' => $l['amount'], 'narration' => mb_substr('Linked to existing entry — ' . $l['remarks'], 0, 255),
                    'mapped_by' => $admin_id, 'mapped_at' => date('Y-m-d H:i:s')]);
            }
            $done = $already + $total;
            $db->where('id', $txn->id)->update('bank_transactions', ['mapping_status' => $done >= $txn_amount - 1 ? 'mapped' : 'partial',
                'mapped_amount' => $done, 'unmapped_amount' => max(0, $txn_amount - $done), 'mapped_by' => $admin_id, 'mapped_at' => date('Y-m-d H:i:s'),
                'updated_by' => $admin_id, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
        $before_lp = $db->where('bank_transaction_id', $txn->id)->where('is_reversed', 0)->count_all_results('loan_payments');
        $before_sv = $db->where('bank_transaction_id', $txn->id)->where('transaction_type', 'deposit')->count_all_results('savings_transactions');
        $this->CI->Bank_model->apply_mappings($txn, $mappings, $admin_id, $already, $total, $txn_amount, $first['mode']);

        $need = ['emi' => 0, 'savings' => 0];
        foreach ($mappings as $mp) if (isset($need[$mp['transaction_type']])) $need[$mp['transaction_type']]++;
        if ($db->where('bank_transaction_id', $txn->id)->where('is_reversed', 0)->count_all_results('loan_payments') - $before_lp < $need['emi']) {
            throw new Exception('The loan payment could not be recorded (check the loan / EMI).');
        }
        if ($db->where('bank_transaction_id', $txn->id)->where('transaction_type', 'deposit')->count_all_results('savings_transactions') - $before_sv < $need['savings']) {
            throw new Exception('The savings deposit could not be recorded (check the savings account).');
        }
        $this->_fix_modes($txn->id, $first['mode'], $first['reference']);
        }
        $db->where('id', $txn->id)->update('bank_transactions', [
            'detected_member_id'   => $first['member_id'],
            'detection_confidence' => ($first['confidence'] ?? '') === 'high' ? 95 : 75,
            'paid_by_member_id'    => $first['paying_member_id'] ?: $first['member_id'],
            'paid_for_member_id'   => $first['member_id'],
            'mapping_remarks'      => mb_substr('AI Assistant: ' . ($first['match'] ?? ''), 0, 255),
        ]);
        if ($db->trans_status() === false) throw new Exception($db->error()['message'] ?: 'database error');
    }

    private function _fix_modes($txn_id, $mode, $reference) {
        if ($mode === 'bank_transfer') return;
        $db = $this->CI->db;
        foreach ($db->where('bank_transaction_id', $txn_id)->get('transaction_mappings')->result() as $m) {
            if (!$m->related_id) continue;
            if ($m->mapping_type === 'loan_payment') {
                $db->where('id', $m->related_id)->update('loan_payments', ['payment_mode' => $mode, 'reference_number' => $reference !== '' ? mb_substr($reference, 0, 50) : null]);
            } elseif ($m->mapping_type === 'savings') {
                $db->where('id', $m->related_id)->update('savings_transactions', ['payment_mode' => $mode, 'reference_number' => $reference !== '' ? mb_substr($reference, 0, 50) : null]);
            } elseif ($m->mapping_type === 'fine') {
                $db->where('id', $m->related_id)->where('payment_mode', 'bank_transfer')->update('fines', ['payment_mode' => $mode === 'upi' ? 'online' : $mode]);
            }
        }
        $db->where('bank_transaction_id', $txn_id)->where('payment_mode', 'bank_transfer')
           ->update('member_other_transactions', ['payment_mode' => $mode]);
        $db->where('bank_transaction_id', $txn_id)->where('payment_mode', 'bank_transfer')
           ->update('savings_transactions', ['payment_mode' => $mode]);
    }

    // ───────────────────────────── Bank mapping suggestions ─────────────────────────────

    const NAME_STOP = ['MR', 'MRS', 'MS', 'MISS', 'SMT', 'SHRI', 'DR', 'KUM', 'UPI', 'PAYMENT', 'FROM', 'PHONEPE', 'PAYTM', 'SENT', 'USING',
                       'NO', 'REMARKS', 'REMARK', 'EMI', 'LOAN', 'AMOUNT', 'MONTH', 'OF', 'THE', 'AU', 'JAGATPURA', 'PAY', 'BY', 'WHATSAPP',
                       'GPAY', 'GOOGLE', 'TO', 'FOR', 'SAVING', 'SAVINGS', 'IMPS', 'NEFT', 'RTGS', 'CR', 'DR', 'SOLUTION', 'SOLUTIONS',
                       'INSTALLMENT', 'BANK', 'TRANSFER', 'FUND', 'FUNDS', 'MOBILE', 'BANKING', 'NET', 'ONLINE', 'DEPOSIT', 'AND', 'JUNE',
                       'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER', 'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY',
                       'FEES', 'FEE', 'MEMBER', 'MEMBERSHIP', 'INTEREST', 'BHIM', 'YONO', 'SBI', 'HDFC', 'ICICI', 'AXIS', 'KOTAK'];

    private $member_index = null;
    private $history = null;

    /**
     * Unmapped / partly mapped CREDIT lines of Bank Transactions as rows for resolve(). The member is found from
     * the bank text (identifiers, payer / "paid for" names, UPI id, history of earlier mappings, amount).
     */
    public function bank_rows(array $f = []) {
        $db = $this->CI->db;
        $db->select('bt.*')->from('bank_transactions bt')->where('bt.transaction_type', 'credit')->where_in('bt.mapping_status', ['unmapped', 'partial']);
        if (!empty($f['from'])) $db->where('bt.transaction_date >=', $f['from']);
        if (!empty($f['to']))   $db->where('bt.transaction_date <=', $f['to']);
        if (!empty($f['ids']))  $db->where_in('bt.id', array_map('intval', (array) $f['ids']));
        $txns = $db->order_by('bt.transaction_date', 'ASC')->order_by('bt.id', 'ASC')->limit(max(1, min(150, (int) ($f['limit'] ?? 100))))->get()->result();

        $rows = [];
        foreach ($txns as $t) {
            $p = $this->_parse_bank_text((string) $t->description, (string) $t->description2, (string) $t->reference_number);
            $rows[] = [
                'row' => (int) $t->id, 'date_raw' => $t->transaction_date, 'member' => '',
                'amount' => round((float) $t->amount - (float) $t->mapped_amount, 2),
                'type' => '', 'account' => '', 'mode' => $p['mode'],
                'reference' => mb_substr(trim(explode('|', (string) ($t->utr_number ?: $t->reference_number))[0]), 0, 50),
                'remarks' => $p['remark'], 'bank_txn_id' => (int) $t->id, 'bank_account_id_fixed' => (int) $t->bank_account_id,
                'payer' => $p['payer'], 'vpa' => $p['vpa'], 'payer_acct' => $p['acct'], 'bank_text' => trim($t->description . ' ' . $t->description2),
            ];
        }
        return $rows;
    }

    /** Payer name, remark, UPI id and payer account from the common Indian bank statement formats. */
    private function _parse_bank_text($desc, $desc2, $ref) {
        $all = trim($desc . ' ' . $desc2);
        $out = ['payer' => '', 'remark' => '', 'vpa' => '', 'acct' => '', 'mode' => 'bank_transfer'];
        if (preg_match('/[\w.\-]{2,}@[a-z]{2,}/i', $all . ' ' . $ref, $m)) $out['vpa'] = strtolower($m[0]);
        if (preg_match('/^UPI\/(CR|DR)\/\d+\/([^\/]+)\/([A-Z]{4})?\/?(\d{6,})?\/?(.*)$/i', $all, $m)) {          // UPI/CR/rrn/NAME/BANK/ACCT/REMARK
            $out['payer'] = $m[2]; $out['acct'] = $m[4] ?? ''; $out['remark'] = $m[5] ?? ''; $out['mode'] = 'upi';
        } elseif (preg_match('/^\d{9,}\s*\|\s*([^|]+)\|?\s*(.*)$/', $all, $m)) {                                     // rrn | NAME | REMARK
            $out['payer'] = $m[1]; $out['remark'] = $m[2]; $out['mode'] = 'upi';
        } elseif (preg_match('/^(NEFT|RTGS|IMPS)[\s\-]*(CR)?-?\S*\s+-([^-]+)-(\S+)\s*(?:-(\**\d+))?\s*-?(.*)$/i', $all, $m)) {   // NEFT CR-ref -NAME -IFSC -…
            $out['payer'] = $m[3]; $out['acct'] = isset($m[5]) ? ltrim($m[5], '*') : ''; $out['remark'] = $m[6] ?? '';
        } else {
            $out['payer'] = $all;
            if (stripos($all, 'UPI') !== false) $out['mode'] = 'upi';
        }
        $out['remark'] = trim(preg_replace('/\bAU JAGATPURA\b/i', '', $out['remark']));
        $out['payer'] = trim($out['payer']);
        return $out;
    }

    private function _tokens($s) {
        $s = strtoupper(preg_replace('/[^A-Za-z]+/', ' ', (string) $s));
        return array_values(array_filter(preg_split('/\s+/', $s), function ($t) { return strlen($t) >= 2 && !in_array($t, self::NAME_STOP, true); }));
    }

    private function _members_index() {
        if ($this->member_index !== null) return $this->member_index;
        $this->member_index = [];
        foreach ($this->CI->db->select('id, member_code, first_name, middle_name, last_name, phone, alternate_phone, status')
                     ->where('deleted_at', null)->get('members')->result() as $m) {
            $split = function ($v) { return array_values(array_filter(preg_split('/[\s\-]+/', strtoupper(trim((string) $v))))); };
            $m->F = $split($m->first_name);
            $m->M = $split($m->middle_name);
            $m->L = $split($m->last_name);
            $this->member_index[(int) $m->id] = $m;
        }
        return $this->member_index;
    }

    /** Spelling-insensitive form of an Indian name part: DEEPAK/DIPAK, GAWANDGAVE/GAVANDGAVE, SANTHOSH/SANTOSH, MUSALLAGI/MUSALAGI. */
    private function _canon($t) {
        $t = strtolower($t);
        $t = str_replace(['ee', 'oo', 'w', 'ph', 'aa', 'ii', 'uu', 'y'], ['i', 'u', 'v', 'f', 'a', 'i', 'u', 'i'], $t);
        $t = preg_replace('/([bcdfgjklmnpqrstvxz])h/', '$1', $t);
        return preg_replace('/(.)\1+/', '$1', $t);
    }

    /** Member's monthly dues: next EMI of every active loan + monthly savings. */
    private function _monthly_dues($member_id) {
        $db = $this->CI->db;
        $emi = 0;
        foreach ($db->select('id')->where('member_id', $member_id)->where_in('status', ['active', 'npa'])->get('loans')->result() as $l) {
            $i = $db->select('emi_amount')->where('loan_id', $l->id)->where_in('status', ['overdue', 'pending', 'partial', 'upcoming'])
                    ->order_by('installment_number', 'ASC')->limit(1)->get('loan_installments')->row();
            if ($i) $emi += (float) $i->emi_amount;
        }
        return $emi + (float) ($db->select_sum('monthly_amount')->where('member_id', $member_id)->where('status', 'active')->get('savings_accounts')->row()->monthly_amount ?? 0);
    }

    /**
     * The payment was already keyed in by hand (same member, same date, not linked to any bank line, same total):
     * link the bank line to those entries instead of recording the money twice.
     */
    private function _link_existing(array &$r) {
        $db = $this->CI->db;
        $lp = $db->select('lp.id, lp.total_amount amount, l.loan_number ref')->from('loan_payments lp')->join('loans l', 'l.id = lp.loan_id')
            ->where('l.member_id', $r['member_id'])->where('lp.payment_date', $r['date'])->where('lp.is_reversed', 0)
            ->where('lp.bank_transaction_id IS NULL', null, false)->get()->result();
        $st = $db->select('st.id, st.amount, sa.account_number ref')->from('savings_transactions st')->join('savings_accounts sa', 'sa.id = st.savings_account_id')
            ->where('sa.member_id', $r['member_id'])->where('st.transaction_date', $r['date'])->where('st.transaction_type', 'deposit')
            ->where('st.is_reversed', 0)->where('st.bank_transaction_id IS NULL', null, false)->get()->result();
        $items = array_merge(array_map(function ($x) { return ['link_loan_payment', $x]; }, $lp), array_map(function ($x) { return ['link_savings', $x]; }, $st));
        if (!$items) return false;
        $pick = null;
        foreach ($items as $it) if (abs((float) $it[1]->amount - $r['amount']) <= 1) { $pick = [$it]; break; }
        if (!$pick && abs(array_sum(array_map(function ($it) { return (float) $it[1]->amount; }, $items)) - $r['amount']) <= 1) $pick = $items;
        if (!$pick) return false;
        $left = $r['amount'];   // the bank line decides the amount (whole rupees vs paise in the manual entry)
        foreach ($pick as $k => $it) {
            $amt = $k === count($pick) - 1 ? $left : min($left, round((float) $it[1]->amount, 2));
            $left = round($left - $amt, 2);
            $r['allocations'][] = ['type' => $it[0], 'related' => (string) $it[1]->id, 'amount' => round($amt, 2),
                                   'label' => 'Link existing ' . ($it[0] === 'link_savings' ? 'deposit ' : 'payment ') . $it[1]->ref];
        }
        return true;
    }

    /** 0-6: surname 2, first name 2, middle name 1, +1 when every part matched. Joined names (YOGESHKISANDATKHILE) too. */
    private function _name_score(array $toks, $m) {
        if (!$toks || !$m->F) return 0;
        $ct = array_map([$this, '_canon'], $toks);
        $hit = function ($parts) use ($ct) {
            foreach ($parts as $p) {
                $p = $this->_canon($p);
                foreach ($ct as $t) {
                    if ($t === $p) return true;
                    if (strlen($p) >= 5 && strlen($t) >= 5 && levenshtein($t, $p) <= 1) return true;   // NETKAE ~ NETKAR
                }
            }
            return false;
        };
        if (count($toks) === 1 && strlen($toks[0]) >= 9) {   // name written without spaces
            $j = $ct[0];
            $f = strpos($j, $this->_canon($m->F[0])) !== false;
            $l = $m->L && strpos($j, $this->_canon(end($m->L))) !== false;
            if ($f && $l) return 4 + ($m->M && strpos($j, $this->_canon($m->M[0])) !== false ? 2 : 0);
            return 0;
        }
        $F = $hit($m->F); $L = $m->L ? $hit($m->L) : false; $M = $m->M ? $hit($m->M) : false;
        $score = ($L ? 2 : 0) + ($F ? 2 : 0) + ($M ? 1 : 0);
        if ($F && $L && (!$m->M || $M)) $score++;
        return $score;
    }

    /** payer key (vpa / account / name) → member ids it was mapped to before. */
    private function _history() {
        if ($this->history !== null) return $this->history;
        $this->history = [];
        $q = $this->CI->db->select('bt.description, bt.description2, bt.reference_number, tm.member_id')->from('transaction_mappings tm')
            ->join('bank_transactions bt', 'bt.id = tm.bank_transaction_id')->where('tm.is_reversed', 0)->where('tm.member_id IS NOT NULL', null, false)
            ->where('bt.transaction_type', 'credit')->get()->result();
        foreach ($q as $h) {
            $p = $this->_parse_bank_text((string) $h->description, (string) $h->description2, (string) $h->reference_number);
            foreach (array_filter(['vpa:' . $p['vpa'], 'acct:' . $p['acct'], 'name:' . implode(' ', $this->_tokens($p['payer']))], function ($k) { return !preg_match('/:$/', $k); }) as $k) {
                $this->history[$k][(int) $h->member_id] = true;
            }
        }
        return $this->history;
    }

    /** Does the amount equal this member's EMI due, monthly savings, or both? */
    private function _amount_fits($member_id, $amount) {
        $db = $this->CI->db;
        $emi = 0;
        foreach ($db->select('id')->where('member_id', $member_id)->where_in('status', ['active', 'npa'])->get('loans')->result() as $l) {
            $i = $db->select('emi_amount, total_paid')->where('loan_id', $l->id)->where_in('status', ['overdue', 'pending', 'partial', 'upcoming'])
                    ->order_by('installment_number', 'ASC')->limit(1)->get('loan_installments')->row();
            if ($i) $emi += (float) $i->emi_amount - (float) $i->total_paid;
        }
        $sav = (float) ($db->select_sum('monthly_amount')->where('member_id', $member_id)->where('status', 'active')->get('savings_accounts')->row()->monthly_amount ?? 0);
        foreach (array_filter([$emi, $sav, $emi + $sav]) as $x) if (abs($amount - $x) <= 1) return true;
        return false;
    }

    /**
     * Who is this bank credit for?
     * @return array [member|null, payer|null, confidence high|medium|low, why, candidates[]]
     */
    private function _match_bank(array $r) {
        $idx = $this->_members_index();
        $text = $r['bank_text'] . ' ' . $r['reference'];
        $label = function ($m) { return $m->member_code . ' ' . trim($m->first_name . ' ' . $m->last_name); };
        $res = function ($m, $conf, $why, $payer = null, $cands = []) { return ['member' => $m, 'payer' => $payer, 'confidence' => $conf, 'why' => $why, 'candidates' => $cands]; };

        // 1. Explicit identifiers (Bank Import rules) and a mobile number inside the UPI id
        $m = $this->_detect($text);
        if ($m) return $res($idx[(int) $m->id] ?? $m, 'high', 'code / phone / account number in the bank text');
        if ($r['vpa'] && preg_match('/^(?:91)?([6-9]\d{9})@/', $r['vpa'], $mm)) {
            foreach ($idx as $x) if ($x->phone === $mm[1] || $x->alternate_phone === $mm[1]) return $res($x, 'high', 'mobile ' . $mm[1] . ' in UPI id');
        }

        $rank = function (array $toks) use ($idx, $r) {
            $c = [];
            foreach ($idx as $x) { $s = $this->_name_score($toks, $x); if ($s >= 2) $c[] = ['m' => $x, 's' => $s]; }
            usort($c, function ($a, $b) { return $b['s'] - $a['s']; });
            return $c;
        };
        $payer_c = $rank($this->_tokens($r['payer']));
        $payer = ($payer_c && $payer_c[0]['s'] >= 4 && (count($payer_c) === 1 || $payer_c[1]['s'] < $payer_c[0]['s'])) ? $payer_c[0]['m'] : null;

        // 2. "Paid for" name in the remark (e.g. parent paying for a child)
        $rem_c = $rank($this->_tokens($r['remarks']));
        if ($rem_c && $rem_c[0]['s'] >= 4 && (count($rem_c) === 1 || $rem_c[1]['s'] < $rem_c[0]['s'])) {
            $m = $rem_c[0]['m'];
            if (!$payer || $payer->id !== $m->id) {
                $conf = ($rem_c[0]['s'] >= 5 || $this->_amount_fits($m->id, $r['amount'])) ? 'high' : 'medium';
                return $res($m, $conf, 'name in remark "' . $r['remarks'] . '"' . ($payer ? ', paid by ' . $label($payer) : ''), $payer);
            }
        }

        // 3. History of this payer (UPI id / account / name)
        $hist = $this->_history();
        $seen = [];
        foreach (['vpa:' . $r['vpa'], 'acct:' . $r['payer_acct'], 'name:' . implode(' ', $this->_tokens($r['payer']))] as $k) {
            if (!preg_match('/:$/', $k) && isset($hist[$k])) $seen += $hist[$k];
        }

        // 4. Payer name, ties broken by amount and history
        if ($payer_c && $payer_c[0]['s'] >= 4) {
            $top = array_values(array_filter($payer_c, function ($c) use ($payer_c) { return $c['s'] === $payer_c[0]['s']; }));
            if (count($top) > 1) {
                $fit = array_values(array_filter($top, function ($c) use ($r, $seen) { return isset($seen[$c['m']->id]) || $this->_amount_fits($c['m']->id, $r['amount']); }));
                if (count($fit) === 1) return $res($fit[0]['m'], 'medium', 'payer name "' . $r['payer'] . '" (several members; picked by amount / history)');
                return $res(null, 'low', 'payer name matches several members', null, array_map(function ($c) use ($label) { return $label($c['m']); }, array_slice($top, 0, 4)));
            }
            $m = $top[0]['m'];
            $fits = $this->_amount_fits($m->id, $r['amount']);
            if ($seen && !isset($seen[$m->id]) && count($seen) === 1) {   // this payer always paid for someone else
                $other = $idx[(int) key($seen)] ?? null;
                if ($other && $this->_amount_fits($other->id, $r['amount'])) return $res($other, 'medium', 'earlier payments of ' . $r['payer'] . ' were for this member; amount fits', $m);
            }
            $conf = ($top[0]['s'] >= 5 || $fits || isset($seen[$m->id])) ? 'high' : 'medium';
            return $res($m, $conf, 'payer name "' . $r['payer'] . '"' . ($fits ? ' + amount matches dues' : '') . (isset($seen[$m->id]) ? ' + mapped before' : ''));
        }

        // 5. History only
        if (count($seen) === 1 && isset($idx[(int) key($seen)])) return $res($idx[(int) key($seen)], 'medium', 'same payer was mapped to this member before');
        if (count($seen) > 1) {
            $fit = array_values(array_filter(array_keys($seen), function ($id) use ($r) { return $this->_amount_fits($id, $r['amount']); }));
            if (count($fit) === 1 && isset($idx[$fit[0]])) return $res($idx[$fit[0]], 'medium', 'payer paid for several members before; amount fits this one');
        }

        $cands = array_map(function ($c) use ($label) { return $label($c['m']); }, array_slice(array_merge($rem_c, $payer_c), 0, 3));
        return $res(null, 'low', 'no clear match', null, array_values(array_unique($cands)));
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    private function _date($v) {
        if ($v === null || $v === '') return null;
        if (is_numeric($v) && $v > 20000 && $v < 80000) {   // Excel serial date
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
        }
        $v = trim(strtr((string) $v, ['०' => 0, '१' => 1, '२' => 2, '३' => 3, '४' => 4, '५' => 5, '६' => 6, '७' => 7, '८' => 8, '९' => 9]));
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d', 'd M Y', 'd-M-Y'] as $f) {
            $d = DateTime::createFromFormat('!' . $f, $v);
            if ($d && $d->format($f) === $v) return $d->format('Y-m-d');
        }
        return null;
    }

    /** "Oct 2026", "October 2026", "Oct-26", "10/2026", "2026-10", a date, or an Excel date → Y-m-01. */
    private function _month($v) {
        if ($v === null || $v === '') return null;
        if (is_numeric($v) && $v > 20000 && $v < 80000) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-01');
        }
        $v = trim(strtr((string) $v, ['०' => 0, '१' => 1, '२' => 2, '३' => 3, '४' => 4, '५' => 5, '६' => 6, '७' => 7, '८' => 8, '९' => 9]));
        $ok = function ($y, $m) { return ($m >= 1 && $m <= 12 && $y >= 2000 && $y <= 2100) ? sprintf('%04d-%02d-01', $y, $m) : null; };
        if (preg_match('/^(\d{4})[\/\-.](\d{1,2})$/', $v, $x)) return $ok((int) $x[1], (int) $x[2]);
        if (preg_match('/^(\d{1,2})[\/\-.](\d{4})$/', $v, $x)) return $ok((int) $x[2], (int) $x[1]);
        if (preg_match('/^(\d{1,2})[\/\-.](\d{2})$/', $v, $x)) return $ok(2000 + (int) $x[2], (int) $x[1]);
        $t = ucwords(strtolower(preg_replace('/[\s,\-\/\'’]+/', ' ', $v)));
        foreach (['M Y', 'F Y', 'M y', 'F y'] as $f) {
            $d = DateTime::createFromFormat('!' . $f, $t);
            if ($d && $d->format($f) === $t) return $d->format('Y-m-01');
        }
        $d = $this->_date($v);
        return $d ? substr($d, 0, 7) . '-01' : null;
    }

    private function _mlabel($date) {
        return date('M Y', strtotime(substr((string) $date, 0, 10)));
    }

    private function _mode($m, $ref) {
        $map = ['online' => 'upi', 'gpay' => 'upi', 'phonepe' => 'upi', 'paytm' => 'upi', 'bank' => 'bank_transfer', 'neft' => 'bank_transfer',
                'rtgs' => 'bank_transfer', 'imps' => 'bank_transfer', 'transfer' => 'bank_transfer', 'bank transfer' => 'bank_transfer',
                'chq' => 'cheque', 'check' => 'cheque', 'रोख' => 'cash', 'चेक' => 'cheque'];
        $m = $map[$m] ?? $m;
        if (in_array($m, self::MODES, true)) return $m;
        if (preg_match('/^upi/i', $ref)) return 'upi';
        if (preg_match('/^(neft|rtgs|imps)/i', $ref)) return 'bank_transfer';
        return 'cash';
    }

    private function _type($t, $remarks, $month_layout = false) {
        $map = ['loan' => 'emi', 'installment' => 'emi', 'हप्ता' => 'emi', 'कर्ज' => 'emi', 'saving' => 'savings', 'deposit' => 'savings',
                'बचत' => 'savings', 'दंड' => 'fine', 'penalty fine' => 'fine', 'membership' => 'membership_fee', 'joining' => 'joining_fee',
                'admission' => 'admission_fee', 'share' => 'share_capital', 'shares' => 'share_capital', 'fee' => 'other_fee', 'other' => 'other_fee'];
        $t = str_replace([' ', '-'], '_', trim($t));
        $t = $map[str_replace('_', ' ', $t)] ?? ($map[$t] ?? $t);
        if ($t === '' && $month_layout) return 'month';   // new template: blank Type is always the monthly split (remarks are free text)
        if ($t === '') {
            $x = mb_strtolower($remarks);
            if (preg_match('/\b(emi|installment)\b|हप्ता/u', $x)) return 'emi';
            if (preg_match('/\bsaving|बचत/u', $x)) return 'savings';
            if (preg_match('/\bfine\b|दंड/u', $x)) return 'fine';
            return $month_layout ? 'month' : 'auto';
        }
        return in_array($t, self::TYPES, true) ? $t : null;
    }

    /** Member by code, numeric id, mobile or every word of the name. */
    private function _members($text) {
        $db = $this->CI->db;
        $text = trim(preg_replace('/^@/', '', $text));
        if (preg_match('/^(MEMB\d+)\s*[-–—|]/i', $text, $x)) $text = $x[1];   // value picked from the template's member list
        if (preg_match('/^MEMB\d+$/i', $text)) return $db->where('member_code', strtoupper($text))->where('deleted_at', null)->get('members')->result();
        $digits = preg_replace('/\D/', '', $text);
        if (strlen($digits) >= 10 && strlen($digits) === strlen(preg_replace('/[\s+\-]/', '', $text))) {
            return $db->where('deleted_at', null)->group_start()->where('phone', substr($digits, -10))->or_where('alternate_phone', substr($digits, -10))->group_end()
                      ->limit(3)->get('members')->result();
        }
        if (preg_match('/^\d{1,6}$/', $text)) return $db->where('id', (int) $text)->where('deleted_at', null)->get('members')->result();
        $words = array_slice(array_filter(preg_split('/\s+/', $text)), 0, 4);
        if (!$words) return [];
        $db->where('deleted_at', null);
        foreach ($words as $w) {
            $db->group_start()->like('first_name', $w)->or_like('middle_name', $w)->or_like('last_name', $w)->group_end();
        }
        return $db->order_by("status = 'active'", 'DESC', false)->limit(3)->get('members')->result();
    }

    /** Same identifiers as Bank_model::auto_match (member code, phone, savings account, loan number). */
    private function _detect($text) {
        $db = $this->CI->db;
        if (preg_match('/MEMB\d{6}/i', $text, $m)) { $r = $db->where('member_code', strtoupper($m[0]))->get('members')->row(); if ($r) return $r; }
        if (preg_match('/\b(\d{10})\b/', $text, $m)) { $r = $db->where('phone', $m[1])->get('members')->row(); if ($r) return $r; }
        if (preg_match('/SAV\d{10}/i', $text, $m)) {
            $a = $db->where('account_number', strtoupper($m[0]))->get('savings_accounts')->row();
            if ($a) return $db->where('id', $a->member_id)->get('members')->row();
        }
        if (preg_match('/LN[A-Z0-9]{5,}/i', $text, $m)) {
            $l = $db->where('loan_number', strtoupper($m[0]))->get('loans')->row();
            if ($l) return $db->where('id', $l->member_id)->get('members')->row();
        }
        return null;
    }
}
