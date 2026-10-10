<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Assistant - admin chat that understands Marathi / English / Hinglish.
 *
 * Flow:  admin message ──► AI (local Gemma / ChatGPT / Gemini) returns JSON {reply, actions[]}
 *        ──► server resolves every action against real data (member, loan, account)
 *        ──► browser shows editable preview cards ──► admin clicks Confirm
 *        ──► execute() re-validates and saves through the normal models
 *            (same ledger posting + audit log as the manual screens).
 *
 * The AI never writes to the database itself.
 */
class Ai_assistant extends Admin_Controller {

    const ACTION_TYPES = ['loan_payment', 'savings_deposit', 'fine', 'member_status', 'reverse_entry', 'update_member', 'navigate'];

    // Member fields the assistant may change (label => column). Identity / KYC / money fields are deliberately excluded.
    const MEMBER_EDITABLE = [
        'phone' => 'phone', 'alternate_phone' => 'alternate_phone', 'email' => 'email',
        'address' => 'address_line1', 'address_line1' => 'address_line1', 'address_line2' => 'address_line2',
        'city' => 'city', 'state' => 'state', 'pincode' => 'pincode', 'occupation' => 'occupation',
        'nominee_name' => 'nominee_name', 'nominee_relationship' => 'nominee_relationship', 'nominee_phone' => 'nominee_phone',
        'bank_name' => 'bank_name', 'bank_account_number' => 'bank_account_number', 'bank_ifsc' => 'bank_ifsc',
        'father_name' => 'father_name', 'notes' => 'notes', 'remarks' => 'notes',
    ];
    const CONV_KEY = 'ai_conversation';   // session: messages, members in focus, entries made
    const MEMBER_STATUSES = ['active', 'inactive', 'blocked', 'suspended'];

    /**
     * Models offered in Settings, with list prices in USD per 1M tokens (input / output).
     * Prices are estimates used only for the spending cap — confirm on the provider's pricing page.
     */
    const MODEL_CATALOG = [
        'openai' => [
            ['id' => 'gpt-4o-mini',  'label' => 'GPT-4o mini',  'tag' => 'recommended: fast, cheap, good Marathi', 'in' => 0.15, 'out' => 0.60],
            ['id' => 'gpt-4.1-mini', 'label' => 'GPT-4.1 mini', 'tag' => 'better accuracy',                       'in' => 0.40, 'out' => 1.60],
            ['id' => 'gpt-4.1-nano', 'label' => 'GPT-4.1 nano', 'tag' => 'cheapest',                              'in' => 0.10, 'out' => 0.40],
            ['id' => 'gpt-4o',       'label' => 'GPT-4o',       'tag' => 'best for passbook photos',              'in' => 2.50, 'out' => 10.00],
            ['id' => 'gpt-4.1',      'label' => 'GPT-4.1',      'tag' => 'highest accuracy',                      'in' => 2.00, 'out' => 8.00],
        ],
        'deepseek' => [
            ['id' => 'deepseek-chat',     'label' => 'DeepSeek Chat (V3)',     'tag' => 'recommended: very cheap, text only', 'in' => 0.014, 'out' => 0.028],
            ['id' => 'deepseek-reasoner', 'label' => 'DeepSeek Reasoner (R)',  'tag' => 'thinks longer, slower',             'in' => 0.55, 'out' => 2.19],
        ],
        'gemini' => [
            ['id' => 'gemini-2.5-flash',      'label' => 'Gemini 2.5 Flash',      'tag' => 'recommended',            'in' => 0.30, 'out' => 2.50],
            ['id' => 'gemini-2.0-flash',      'label' => 'Gemini 2.0 Flash',      'tag' => 'cheap and fast',         'in' => 0.10, 'out' => 0.40],
            ['id' => 'gemini-2.5-flash-lite', 'label' => 'Gemini 2.5 Flash-Lite', 'tag' => 'cheapest',               'in' => 0.10, 'out' => 0.40],
            ['id' => 'gemini-2.5-pro',        'label' => 'Gemini 2.5 Pro',        'tag' => 'highest accuracy',       'in' => 1.25, 'out' => 10.00],
        ],
        'openrouter' => [
            ['id' => 'deepseek/deepseek-r1-distill-llama-70b', 'label' => 'DeepSeek R1 Distill (70B)',     'tag' => 'cheapest with reasoning', 'in' => 0.07, 'out' => 0.14],
            ['id' => 'deepseek/deepseek-chat',                'label' => 'DeepSeek Chat (fast)',           'tag' => 'ultra cheap, very fast', 'in' => 0.02, 'out' => 0.04],
            ['id' => 'qwen/qwen-2.5-7b-instruct',             'label' => 'Qwen 2.5 (7B)',                  'tag' => 'good for Indic, very cheap', 'in' => 0.07, 'out' => 0.14],
            ['id' => 'llama/llama-3.3-70b-instruct',          'label' => 'Llama 3.3 (70B)',                'tag' => 'strong multi-lingual', 'in' => 0.37, 'out' => 0.55],
            ['id' => 'openai/gpt-4.5-turbo-2025-01-13',       'label' => 'GPT-4.5 Turbo via OpenRouter', 'tag' => 'latest, via aggregator',  'in' => 0.42, 'out' => 1.68],
            ['id' => 'google/gemini-2.0-flash-001',           'label' => 'Gemini 2.0 Flash via OpenRouter', 'tag' => 'cheap, multimodal',      'in' => 0.05, 'out' => 0.20],
        ],
    ];

    // Pages the assistant may open for the admin (key => [label, uri])
    private $pages = [
        'dashboard'           => ['Dashboard', 'admin/dashboard'],
        'members'             => ['Members', 'admin/members'],
        'add_member'          => ['Add Member', 'admin/members/create'],
        'loans'               => ['All Loans', 'admin/loans'],
        'loan_applications'   => ['Loan Applications', 'admin/loans/applications'],
        'overdue_loans'       => ['Overdue Loans', 'admin/loans/overdue'],
        'disbursement'        => ['Disbursement', 'admin/loans/disbursement'],
        'foreclosure'         => ['Foreclosure Requests', 'admin/loans/foreclosure_requests'],
        'repayment_history'   => ['Repayment History', 'admin/loans/repayment_history'],
        'savings'             => ['Savings Accounts', 'admin/savings'],
        'fines'               => ['Fines', 'admin/fines'],
        'add_fine'            => ['Add Fine', 'admin/fines/create'],
        'bank_import'         => ['Bank Import', 'admin/bank/import'],
        'receive_payment'     => ['Receive Payment', 'admin/payments/receive'],
        'payment_history'     => ['Payment History', 'admin/payments/history'],
        'reports'             => ['Reports', 'admin/reports'],
        'member_statement'    => ['Member Statement', 'admin/reports/member_statement'],
        'ledger'              => ['Member Ledger', 'admin/ledger/member'],
        'settings'            => ['Settings', 'admin/settings'],
    ];

    public function __construct() {
        parent::__construct();
        $this->load->model(['Member_model', 'Loan_model', 'Savings_model', 'Fine_model', 'Setting_model']);
        $this->load->helper(['format']);
    }

    // ───────────────────────────── Pages ─────────────────────────────

    public function index() {
        $data['page_title']  = 'AI Assistant';
        $data['breadcrumbs'] = [['title' => 'AI Assistant']];
        $data['ai_config']   = $this->_config(true);
        $data['is_super']    = $this->_is_billing_owner();   // Settings (provider, keys, budget) — owner only
        $data['is_owner']    = $this->_is_billing_owner();                                                    // provider, keys, rate
        $data['model_catalog'] = self::MODEL_CATALOG;
        $this->load_view('admin/ai_assistant/index', $data);
    }

    /** Save provider settings (super admin only). Blank key fields keep the stored key. */
    public function save_settings() {
        if (!$this->_is_billing_owner()) {
            return $this->error_response('Only the AI account owner can change AI settings.', [], 403);
        }
        $provider = $this->input->post('ai_provider');
        if (!in_array($provider, ['local', 'openai', 'gemini', 'deepseek', 'openrouter'], true)) {
            return $this->error_response('Unknown provider.');
        }
        $save = [
            'ai_provider'        => $provider,
            'ai_local_url'       => trim((string) $this->input->post('ai_local_url')),
            'ai_local_model'     => trim((string) $this->input->post('ai_local_model')),
            'ai_openai_model'    => trim((string) $this->input->post('ai_openai_model')),
            'ai_gemini_model'    => trim((string) $this->input->post('ai_gemini_model')),
            'ai_deepseek_model'  => trim((string) $this->input->post('ai_deepseek_model')),
            'ai_openrouter_model'=> trim((string) $this->input->post('ai_openrouter_model')),
        ];
        foreach (['ai_openai_key', 'ai_gemini_key', 'ai_deepseek_key', 'ai_openrouter_key'] as $k) {
            $v = trim((string) $this->input->post($k));
            if ($v !== '') $save[$k] = $v;
        }
        foreach ($save as $k => $v) {
            $this->Setting_model->update_setting($k, $v);
        }
        $this->log_activity('AI settings updated', 'provider: ' . $provider, 'ai_assistant');
        $this->success_response('AI settings saved.');
    }

    /** Quick connectivity check for the current provider. */
    public function test_connection() {
        if (!$this->_is_billing_owner()) {
            return $this->error_response('Only the AI account owner can test the AI connection.', [], 403);
        }
        try {
            // Test the values typed in the dialog (falls back to saved ones for blank fields / keys)
            $client = $this->_client($this->input->post() ?: []);
            $t = microtime(true);
            $reply = $client->chat([['role' => 'user', 'content' => 'Reply with the single word OK.']], ['temperature' => 0]);
            $this->success_response('Connected (' . round((microtime(true) - $t) * 1000) . ' ms): ' . mb_substr(trim($reply), 0, 60));
        } catch (Exception $e) {
            $this->error_response($e->getMessage());
        }
    }

    /** Restore the current conversation (page reload). */
    public function history() {
        $c = $this->_conv();
        $this->json_response(['success' => true, 'messages' => $c['messages']]);
    }

    /** Start a new conversation. */
    public function reset() {
        $this->session->unset_userdata(self::CONV_KEY);
        $this->success_response('New chat started.');
    }

    /** Spend and request counts for the header and the Usage tab. */
    public function usage() {
        $this->json_response($this->_usage_for_viewer());
    }

    /**
     * The developer who pays the AI provider sees real cost and caps (AI_BILLING_OWNER_EMAIL in .env).
     * Everyone else sees the plan they are subscribed to and how much of it is used.
     */
    private function _is_billing_owner() {
        // Super admins manage the AI integration. Budget/markup live in .env and are never shown.
        return $this->admin_data->role === 'super_admin';
    }

    /**
     * Usage as shown on screen. Amounts are BILLED usage (real API cost × billing rate) and the cap is the
     * monthly budget in billed rupees — e.g. budget ₹6,000 at rate 2 stops when real cost reaches ₹3,000.
     * Only the billing owner also receives the real cost and the rate.
     */
    private function _recharge_message($u) {
        $c = $this->_config();
        return 'AI balance for this month is used up (₹' . number_format($u['cap_inr']) . '). Please recharge to continue using the AI Assistant'
             . ($c['recharge_contact'] !== '' ? ' — contact ' . $c['recharge_contact'] : ' — contact your software provider') . '. '
             . 'It also renews automatically on the 1st.';
    }

    private function _usage_for_viewer() {
        $u = $this->_usage();
        $u['exhausted'] = $u['provider'] !== 'local' && $u['cap_inr'] > 0 && $u['month_cost_inr'] >= $u['cap_inr'];
        $u['recharge_message'] = $u['exhausted'] ? $this->_recharge_message($u) : '';
        // Real API cost and the billing rate are never sent to the browser — only billed usage
        unset($u['real_cost_inr'], $u['real_cap_inr'], $u['markup'], $u['by_model_real']);
        return $u;
    }

    private function _usage() {
        $c = $this->_config();
        $u = ['provider' => $c['provider'], 'cap_inr' => (float) $c['monthly_cap_inr'], 'warn_pct' => (int) $c['warn_pct'],
              'daily_limit' => (int) $c['daily_limit'], 'month_cost_inr' => 0.0, 'month_requests' => 0, 'today_requests' => 0,
              'month_tokens_in' => 0, 'month_tokens_out' => 0, 'by_model' => [], 'tracking' => false,
              'markup' => max(1, (float) $c['markup']), 'real_cost_inr' => 0.0,
              'real_cap_inr' => (float) $c['monthly_cap_inr'] > 0 ? round((float) $c['monthly_cap_inr'] / max(1, (float) $c['markup']), 2) : 0];
        if (!$this->db->table_exists('ai_chat_logs') || !$this->db->field_exists('cost_usd', 'ai_chat_logs')) return $u;

        $u['tracking'] = true;
        $rate = (float) $c['usd_inr'];
        $from = date('Y-m-01 00:00:00');
        $m = $this->db->select('COUNT(*) n, COALESCE(SUM(cost_usd),0) cost, COALESCE(SUM(tokens_in),0) tin, COALESCE(SUM(tokens_out),0) tout', false)
            ->where('created_at >=', $from)->get('ai_chat_logs')->row();
        $u['month_requests']   = (int) $m->n;
        $markup = max(1, (float) $c['markup']);
        $u['markup']           = $markup;
        $u['real_cost_inr']    = round((float) $m->cost * $rate, 2);
        $u['real_cap_inr']     = $u['cap_inr'] > 0 ? round($u['cap_inr'] / $markup, 2) : 0;
        $u['month_cost_inr']   = round($u['real_cost_inr'] * $markup, 2);   // billed
        $u['month_tokens_in']  = (int) $m->tin;
        $u['month_tokens_out'] = (int) $m->tout;
        $u['today_requests']   = (int) $this->db->where('admin_id', (int) $this->session->userdata('admin_id'))
            ->where('created_at >=', date('Y-m-d 00:00:00'))->count_all_results('ai_chat_logs');
        foreach ($this->db->select('model, COUNT(*) n, COALESCE(SUM(cost_usd),0) cost', false)->where('created_at >=', $from)
                     ->group_by('model')->order_by('cost', 'DESC')->get('ai_chat_logs')->result() as $r) {
            $u['by_model'][] = ['model' => $r->model ?: '—', 'n' => (int) $r->n, 'cost_inr' => round((float) $r->cost * $rate * $markup, 2)];
        }
        return $u;
    }

    /** Estimated USD cost of one call for the configured model (0 for self-hosted / unknown models). */
    private function _cost_usd($provider, $model, $in, $out) {
        foreach (self::MODEL_CATALOG[$provider] ?? [] as $m) {
            if ($m['id'] === $model) return ($in * $m['in'] + $out * $m['out']) / 1000000;
        }
        return 0.0;
    }

    private function _conv() {
        $c = $this->session->userdata(self::CONV_KEY);
        return is_array($c) ? $c + ['messages' => [], 'members' => [], 'entries' => []]
                            : ['messages' => [], 'members' => [], 'entries' => []];
    }

    private function _save_conv(array $c) {
        $c['messages'] = array_slice($c['messages'], -40);
        $c['members']  = array_slice(array_values(array_unique($c['members'])), 0, 6);
        $c['entries']  = array_slice($c['entries'], -30);
        $this->session->set_userdata(self::CONV_KEY, $c);
    }

    // ───────────────────────────── Autocomplete ─────────────────────────────

    /**
     * Live suggestions while typing: members (code / name / phone) and loan numbers.
     * GET q
     */
    public function suggest() {
        $q = trim((string) $this->input->get('q'));
        $q = $this->_latin_digits($q);
        if (mb_strlen($q) < 1) {
            return $this->json_response(['items' => []]);
        }

        $items = [];
        foreach ($this->_find_members($q, 8) as $m) {
            $items[] = [
                'kind'  => 'member',
                'id'    => (int) $m->id,
                'label' => $this->_member_name($m),
                'sub'   => $m->member_code . ' · ' . $m->phone,
                'token' => '@' . $m->member_code,
            ];
        }

        if (preg_match('/^(ln|loan)?\s*\d+$/i', $q) || stripos($q, 'LN') === 0) {
            $loans = $this->db->select('l.id, l.loan_number, l.emi_amount, l.status, m.first_name, m.last_name, m.member_code')
                ->from('loans l')->join('members m', 'm.id = l.member_id')
                ->like('l.loan_number', preg_replace('/^(loan)\s*/i', '', $q))
                ->limit(5)->get()->result();
            foreach ($loans as $l) {
                $items[] = [
                    'kind'  => 'loan',
                    'id'    => (int) $l->id,
                    'label' => $l->loan_number . ' (' . $l->status . ')',
                    'sub'   => trim($l->first_name . ' ' . $l->last_name) . ' · EMI ₹' . number_format($l->emi_amount, 2),
                    'token' => $l->loan_number,
                ];
            }
        }

        $this->json_response(['items' => $items]);
    }

    // ───────────────────────────── Chat ─────────────────────────────

    /**
     * POST message, mentions (JSON array of member ids), history (JSON [{role,content}]), image (data URL, optional)
     */
    public function chat() {
        $message = trim((string) $this->input->post('message'));
        $image   = (string) $this->input->post('image');
        if ($message === '' && $image === '') {
            return $this->error_response('Please type a message.');
        }

        $mentions = json_decode((string) $this->input->post('mentions'), true) ?: [];
        $conv     = $this->_conv();

        // Members referenced by @code in the text count as mentions too
        if (preg_match_all('/@?(MEMB\d+)/i', $message, $mm)) {
            foreach ($mm[1] as $code) {
                $row = $this->db->select('id')->where('member_code', strtoupper($code))->get('members')->row();
                if ($row) $mentions[] = (int) $row->id;
            }
        }
        $mentions = array_values(array_unique(array_map('intval', $mentions)));
        if (!$mentions) {
            $spotted = $this->_spot_member($message);
            if ($spotted) $mentions = [$spotted];
        }
        // Members talked about earlier stay in focus ("his loan", "same member")
        $focus = array_values(array_unique(array_merge($mentions, $conv['members'])));

        $messages = [['role' => 'system', 'content' => $this->_system_prompt($focus, $conv['entries'])]];
        foreach (array_slice($conv['messages'], -12) as $h) {
            $messages[] = ['role' => $h['role'], 'content' => mb_substr((string) ($h['memory'] ?? $h['text']), 0, 3000)];
        }
        $messages[] = ['role' => 'user', 'content' => $message !== '' ? $message : 'Read this passbook / statement image and create the entries.'];

        $opts = ['json' => true, 'temperature' => 0.1];
        if ($image !== '' && $this->_config()['provider'] === 'deepseek') {
            return $this->error_response('DeepSeek can\'t read photos. Paste the passbook text instead, or switch to ChatGPT / Gemini in AI Settings for photos.');
        }
        if ($image !== '' && preg_match('#^data:image/(png|jpe?g|webp);base64,#', $image)) {
            $opts['images'] = [$image];
        }

        // Spending cap / daily limit
        $cfg   = $this->_config();
        $usage = $this->_usage();
        if ($cfg['provider'] !== 'local' && $usage['cap_inr'] > 0 && $usage['month_cost_inr'] >= $usage['cap_inr']) {
            return $this->json_response(['success' => false, 'recharge' => true, 'message' => $this->_recharge_message($usage)], 402);
        }
        if ($usage['daily_limit'] > 0 && $usage['today_requests'] >= $usage['daily_limit']) {
            return $this->error_response('Daily limit of ' . $usage['daily_limit'] . ' AI requests reached. Try again tomorrow.');
        }
        $opts['max_tokens'] = (int) $cfg['max_tokens'];

        try {
            $client = $this->_client();
            $raw = $client->chat($messages, $opts);
        } catch (Exception $e) {
            return $this->error_response($e->getMessage());
        }
        $model   = ['local' => $cfg['local_model'], 'openai' => $cfg['openai_model'], 'gemini' => $cfg['gemini_model'],
                    'deepseek' => $cfg['deepseek_model']][$cfg['provider']] ?? '';
        $tok     = $client->last_usage;
        $cost    = $this->_cost_usd($cfg['provider'], $model, $tok['in'], $tok['out']);

        $parsed = Ai_client::extract_json($raw);
        if (!$parsed) {
            // Model answered in plain text — still useful as a reply
            $parsed = ['reply' => trim($raw), 'actions' => []];
        }

        $cards = [];
        foreach ((array) ($parsed['actions'] ?? []) as $a) {
            if (!is_array($a)) continue;
            $card = $this->_resolve_action($a, $mentions ?: array_slice($conv['members'], 0, 1), $conv['entries']);
            if (!$card) continue;
            // Small models sometimes flip the status — trust the admin's own words over the model
            if ($card['type'] === 'member_status') {
                $said = $this->_status_from_text($message);
                if ($said && $said !== $card['status']) {
                    $card['status'] = $said;
                    $card['warnings'] = array_values(array_filter($card['warnings'], function ($w) {
                        return strpos($w, 'already') === false && strpos($w, 'choose the new status') === false;
                    }));
                    if (($card['current_status'] ?? null) === $said) $card['warnings'][] = 'Member is already ' . $said . '.';
                }
            }
            $cards[] = $card;
        }

        // Safety net: admin clearly asked to undo, the model didn't produce it → undo the latest entry of this chat
        $has_reverse = (bool) array_filter($cards, function ($c) { return $c['type'] === 'reverse_entry'; });
        $wants_entry = (bool) array_filter($cards, function ($c) { return !in_array($c['type'], ['navigate', 'reverse_entry'], true); });
        if (!$has_reverse && !$wants_entry && $this->_wants_undo($message)) {
            $card = $this->_resolve_reverse(['remarks' => mb_substr($message, 0, 255)], [], $conv['entries']);
            if (!empty($card['target_id'])) {
                $cards = [$card];
                $parsed['reply'] = '';
            }
        }

        // ── Auto-save: run every action that is complete and unambiguous; ask about the rest ──
        $auto = $this->input->post('auto') !== '0';
        $questions = [];
        foreach ($cards as &$card) {
            if ($card['type'] === 'navigate') continue;
            if ($card['type'] === 'member_status' && ($card['remarks'] ?? '') === '') {
                $card['remarks'] = mb_substr('AI assistant: ' . $message, 0, 255);
            }
            $ready = $auto && empty($card['warnings']) && !empty($card['member_id'])
                && ($card['type'] !== 'loan_payment'    || !empty($card['loan_id']))
                && ($card['type'] !== 'savings_deposit' || !empty($card['savings_account_id']))
                && ($card['type'] !== 'reverse_entry'   || !empty($card['from_chat']) || !empty($card['unique']))
                && ($card['type'] !== 'update_member'   || !empty($card['changes']));
            if ($ready) {
                $r = $this->_perform($card);
                $card['executed'] = $r['ok'];
                $card['result']   = $r;
                if (!$r['ok']) $questions[] = $r['message'];
            } else {
                foreach ($card['warnings'] as $w) $questions[] = $w;
            }
        }
        unset($card);

        // An entry request never needs a page link
        $has_entry = (bool) array_filter($cards, function ($c) { return $c['type'] !== 'navigate'; });
        if ($has_entry) {
            $cards = array_values(array_filter($cards, function ($c) { return $c['type'] !== 'navigate'; }));
        }

        $reply   = (string) ($parsed['reply'] ?? '');
        $saved   = array_filter($cards, function ($c) { return !empty($c['executed']); });
        $pending = array_filter($cards, function ($c) { return $c['type'] !== 'navigate' && empty($c['executed']); });
        if ($saved || $pending) {
            // The model's own text may say "done" — only the server's results are reported for entries
            $reply = $saved ? '✅ ' . implode("\n✅ ", array_map(function ($c) { return $c['result']['message']; }, $saved)) : '';
            if ($pending) {
                $reply .= ($reply !== '' ? "\n\n" : '') . '👇 Not saved yet — check the details below and press Confirm.';
            }
        }
        if ($questions) {
            $reply .= ($reply !== '' ? "\n\n" : '') . '❓ ' . implode("\n❓ ", array_unique($questions));
        }

        // ── Remember this turn ──
        $memory = [];
        foreach ($cards as &$card) {
            if (!empty($card['member_id'])) array_unshift($conv['members'], (int) $card['member_id']);
            if (!empty($card['executed'])) {
                $memory[] = $this->_remember_entry($conv, $card);
            }
        }
        unset($card);
        foreach ($mentions as $id) array_unshift($conv['members'], $id);

        $conv['messages'][] = ['role' => 'user', 'text' => $message, 'image' => $image !== ''];
        $conv['messages'][] = ['role' => 'assistant', 'text' => $reply, 'cards' => $this->_compact_cards($cards),
                               'memory' => $reply . ($memory ? "\n[Done: " . implode('; ', $memory) . ']' : '')];
        $this->_save_conv($conv);

        $this->_log_chat($message, $raw, $cards, ['provider' => $cfg['provider'], 'model' => $model,
                                                  'tokens_in' => $tok['in'], 'tokens_out' => $tok['out'], 'cost_usd' => $cost]);

        $usage = $this->_usage();
        $warn  = $usage['cap_inr'] > 0 && $usage['month_cost_inr'] >= $usage['cap_inr'] * $usage['warn_pct'] / 100;
        if ($warn) {
            $reply .= "\n\n⚠️ AI usage this month: ₹" . number_format($usage['month_cost_inr'], 2) . ' of ₹' . number_format($usage['cap_inr']) . ' budget.';
        }

        $this->json_response([
            'success' => true,
            'reply'   => $reply,
            'actions' => $cards,
            'usage'   => $this->_usage_for_viewer(),
        ]);
    }

    /**
     * Admin re-picks the member on a card → return that member's loans/accounts for the card.
     * GET member_id
     */
    public function member_options() {
        $id = (int) $this->input->get('member_id');
        $m  = $this->Member_model->get_by_id($id);
        if (!$m) return $this->error_response('Member not found.');
        $this->json_response(['success' => true, 'loans' => $this->_loan_options($id), 'accounts' => $this->_account_options($id)]);
    }

    // ───────────────────────────── Execute (after Confirm) ─────────────────────────────

    /**
     * POST type + fields. Everything is validated again here — nothing from the AI is trusted.
     */
    public function execute() {
        $f = $this->input->post() ?: [];
        $r = $this->_perform($f);
        if ($r['ok']) {
            $conv = $this->_conv();
            $card = $f + ['executed' => true, 'result' => $r];
            $note = $this->_remember_entry($conv, $card);
            $conv['messages'][] = ['role' => 'assistant', 'text' => '✅ ' . $r['message'], 'memory' => '[Done: ' . $note . ']',
                                   'cards' => $this->_compact_cards([$card])];
            $this->_save_conv($conv);
            $r['data']['entry'] = end($conv['entries']) ?: null;
        }
        return $r['ok'] ? $this->success_response($r['message'], $r['data'] ?? [])
                        : $this->error_response($r['message']);
    }

    /**
     * Validate and save one action. Used by Confirm (execute) and by auto-save in chat().
     * @return array ['ok' => bool, 'message' => string, 'data' => array]
     */
    private function _perform(array $f) {
        $in = function ($k) use ($f) { return $f[$k] ?? null; };
        $type = $in('type');
        if ($type === 'update_member') {
            return $this->_perform_update_member((int) $in('member_id'), $in('changes'));
        }
        if ($type === 'reverse_entry') {
            return $this->_perform_reverse($in('kind'), (int) $in('target_id'),
                mb_substr(trim((string) $in('remarks')), 0, 255), (int) $this->session->userdata('admin_id'));
        }
        if (!in_array($type, ['loan_payment', 'savings_deposit', 'fine', 'member_status'], true)) {
            return $this->_fail('Unknown action.');
        }

        $member_id = (int) $in('member_id');
        $amount    = round((float) $this->_latin_digits((string) $in('amount')), 2);
        $date      = $this->_valid_date($in('date')) ?: date('Y-m-d');
        $mode      = $this->_payment_mode($in('mode'));
        $reference = mb_substr(trim((string) $in('reference')), 0, 100);
        $remarks   = mb_substr(trim((string) $in('remarks')), 0, 255);
        $admin_id  = (int) $this->session->userdata('admin_id');

        $member = $this->Member_model->get_by_id($member_id);
        if (!$member) return $this->_fail('Please choose the member.');
        if ($type === 'member_status') {
            $status = $in('status');
            if (!in_array($status, self::MEMBER_STATUSES, true)) return $this->_fail('Please choose the new status.');
            if ($member->status === $status) return $this->_fail($this->_member_name($member) . ' is already ' . $status . '.');
            if ($remarks === '' && $status !== 'active') return $this->_fail('Please give a reason.');
            $ok = $this->Member_model->update_status($member_id, $status, $remarks, $admin_id);
            if (!$ok) return $this->_fail('Status could not be changed.');
            $this->log_audit('status_change', 'members', 'members', $member_id, ['status' => $member->status],
                             ['status' => $status, 'reason' => $remarks], 'AI assistant');
            return $this->_done($this->_member_name($member) . ' is now ' . $status . '.', [
                'link' => site_url('admin/members/view/' . $member_id), 'link_text' => 'Open member',
            ]);
        }
        if ($amount <= 0) return $this->_fail('Amount must be greater than zero.');
        if ($date > date('Y-m-d')) return $this->_fail('Date cannot be in the future.');

        $note = 'Entered via AI assistant' . ($remarks !== '' ? ' — ' . $remarks : '');

        try {
            switch ($type) {
                case 'loan_payment':
                    $loan_id = (int) $in('loan_id');
                    $loan = $this->db->where('id', $loan_id)->where('member_id', $member_id)->get('loans')->row();
                    if (!$loan) return $this->_fail('Please choose the member\'s loan.');
                    if (!in_array($loan->status, ['active', 'npa'], true)) {
                        return $this->_fail('Loan ' . $loan->loan_number . ' is ' . $loan->status . ' and cannot take payments.');
                    }
                    $ptype = $in('payment_type') === 'interest_only' ? 'interest_only' : 'emi';

                    $next = $this->db->where('loan_id', $loan_id)
                        ->where_in('status', ['overdue', 'pending', 'partial'])
                        ->order_by('installment_number', 'ASC')->limit(1)
                        ->get('loan_installments')->row();

                    $payment_data = [
                        'loan_id'          => $loan_id,
                        'installment_id'   => $next ? $next->id : null,
                        'total_amount'     => $amount,
                        'payment_mode'     => $mode,
                        'payment_type'     => $ptype,
                        'payment_date'     => $date,
                        'reference_number' => $reference,
                        'narration'        => $note,
                        'created_by'       => $admin_id,
                    ];
                    $payment_id = $this->Loan_model->record_payment($payment_data);
                    if (!$payment_id) throw new Exception('The payment could not be saved.');

                    $this->load->model('Ledger_model');
                    $this->Ledger_model->post_loan_payment($payment_id, $member_id, 'Loan payment for ' . $loan->loan_number, $admin_id);
                    $this->log_audit('create', 'loan_payments', 'loan_payments', $payment_id, null, $payment_data, 'AI assistant');

                    return $this->_done('Payment of ₹' . number_format($amount, 2) . ' recorded on ' . $loan->loan_number . '.', [
                        'link' => site_url('admin/loans/view/' . $loan_id), 'link_text' => 'Open loan',
                        'kind' => 'loan_payment', 'target_id' => (int) $payment_id, 'member_id' => $member_id, 'amount' => $amount,
                    ]);

                case 'savings_deposit':
                    if (!$this->_can('savings_collect')) return $this->_fail('You do not have permission to collect savings.');
                    $acc_id  = (int) $in('savings_account_id');
                    $account = $this->db->where('id', $acc_id)->where('member_id', $member_id)->get('savings_accounts')->row();
                    if (!$account) return $this->_fail('Please choose the member\'s savings account.');
                    $onetime = $in('deposit_type') === 'onetime';

                    $payment_data = [
                        'savings_account_id' => $acc_id,
                        'transaction_type'   => 'deposit',
                        'deposit_type'       => $onetime ? 'onetime' : 'regular',
                        'amount'             => $amount,
                        'payment_mode'       => $mode === 'auto_debit' ? 'auto' : $mode,
                        'reference_number'   => $reference,
                        'schedule_id'        => null,   // regular deposits auto-apply to oldest dues (FIFO)
                        'transaction_date'   => $date,
                        'remarks'            => $note,
                        'received_by'        => $admin_id,
                    ];
                    $txn_id = $this->Savings_model->record_payment($payment_data);
                    if (!$txn_id) throw new Exception('The deposit could not be saved.');

                    $this->load->model('Ledger_model');
                    $this->Ledger_model->post_transaction(
                        'savings_deposit', $txn_id, $amount, $member_id,
                        ($onetime ? 'One-time savings deposit for account ' : 'Savings deposit for account ') . $account->account_number,
                        $admin_id, $date, $payment_data['payment_mode']
                    );
                    $this->log_audit('create', 'savings_transactions', 'savings_transactions', $txn_id, null, $payment_data, 'AI assistant');

                    return $this->_done('Deposit of ₹' . number_format($amount, 2) . ' recorded in ' . $account->account_number . '.', [
                        'link' => site_url('admin/payments/receipt/' . $txn_id . '?type=savings'), 'link_text' => 'Receipt',
                        'kind' => 'savings_deposit', 'target_id' => (int) $txn_id, 'member_id' => $member_id, 'amount' => $amount,
                    ]);

                case 'fine':
                    if ($remarks === '') return $this->_fail('Please give a reason for the fine.');
                    $fine_data = [
                        'member_id'   => $member_id,
                        'fine_type'   => 'other',
                        'fine_date'   => $date,
                        'fine_amount' => $amount,
                        'remarks'     => $remarks,
                        'created_by'  => $admin_id,
                    ];
                    $fine_id = $this->Fine_model->create_fine($fine_data);
                    if (!$fine_id) throw new Exception('The fine could not be saved.');
                    $this->log_audit('create', 'fines', 'fines', $fine_id, null, $fine_data, 'AI assistant');

                    return $this->_done('Fine of ₹' . number_format($amount, 2) . ' added for ' . $this->_member_name($member) . '.', [
                        'link' => site_url('admin/fines/view/' . $fine_id), 'link_text' => 'Open fine',
                        'kind' => 'fine', 'target_id' => (int) $fine_id, 'member_id' => $member_id, 'amount' => $amount,
                    ]);
            }
        } catch (Exception $e) {
            return $this->_fail($e->getMessage());
        }
    }

    /** Add an executed card to the conversation's entry list; returns a one-line note for the model. */
    private function _remember_entry(array &$conv, array $card) {
        $d = $card['result']['data'] ?? [];
        $msg = $card['result']['message'] ?? '';
        if (empty($d['kind']) || empty($d['target_id'])) return $msg;   // status change / reversal
        $ref = 'E' . (count($conv['entries']) + 1);
        $conv['entries'][] = ['ref' => $ref, 'kind' => $d['kind'], 'id' => (int) $d['target_id'],
                              'member_id' => (int) ($d['member_id'] ?? 0), 'amount' => (float) ($d['amount'] ?? 0),
                              'label' => $msg, 'reversed' => false];
        return $ref . ' ' . $msg;
    }

    /** Small, display-only copy of cards for the stored conversation. */
    private function _compact_cards(array $cards) {
        $out = [];
        foreach ($cards as $c) {
            if (!empty($c['executed'])) {
                $out[] = ['type' => $c['type'], 'executed' => true, 'result' => $c['result']];
            } elseif (($c['type'] ?? '') === 'navigate') {
                $out[] = $c;
            }
        }
        return $out;
    }

    /**
     * Undo an entry: savings deposit → reversed (balance recalculated + contra ledger voucher),
     * fine → cancelled (only if nothing was paid). Loan payments are not reversed from chat.
     */
    private function _perform_reverse($kind, $id, $reason, $admin_id) {
        if ($reason === '') $reason = 'Reversed via AI assistant';
        switch ($kind) {
            case 'savings_deposit':
                if (!$this->_can('savings_edit')) return $this->_fail('You do not have permission to reverse savings entries.');
                $txn = $this->db->select('st.*, sa.member_id, sa.account_number')->from('savings_transactions st')
                    ->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('st.id', $id)->get()->row();
                if (!$txn) return $this->_fail('Savings entry not found.');
                if (!empty($txn->is_reversed)) return $this->_fail('This savings entry is already reversed.');
                if ($txn->transaction_type !== 'deposit') return $this->_fail('Only deposits can be reversed from chat.');

                $res = $this->Savings_model->reverse_transaction($id, $reason, $admin_id);
                if (empty($res['success'])) return $this->_fail($res['error'] ?? 'Could not reverse the deposit.');

                $this->load->model('Ledger_model');
                $this->Ledger_model->post_transaction('savings_withdrawal', $id, $txn->amount, $txn->member_id,
                    'Reversal of savings deposit ' . ($txn->transaction_code ?? '#' . $id) . ' — ' . $reason,
                    $admin_id, date('Y-m-d'), $txn->payment_mode);
                $this->log_audit('reverse', 'savings_transactions', 'savings_transactions', $id, null, ['reason' => $reason], 'AI assistant');
                $this->_mark_reversed('savings_deposit', $id);
                return $this->_done('Reversed deposit of ₹' . number_format((float) $txn->amount, 2) . ' in ' . $txn->account_number . '.');

            case 'fine':
                $fine = $this->Fine_model->get_by_id($id);
                if (!$fine) return $this->_fail('Fine not found.');
                if ($fine->status === 'cancelled') return $this->_fail('This fine is already cancelled.');
                if ((float) $fine->paid_amount > 0) return $this->_fail('This fine has payments — reverse the payment from the Fines page first.');
                $this->Fine_model->cancel_fine($id, $reason, $admin_id);
                $this->log_audit('cancelled', 'fines', 'fines', $id, null, ['reason' => $reason], 'AI assistant');
                $this->_mark_reversed('fine', $id);
                return $this->_done('Cancelled fine of ₹' . number_format((float) $fine->fine_amount, 2) . '.');

            case 'loan_payment':
                $lp = $this->db->select('loan_id')->where('id', $id)->get('loan_payments')->row();
                return $this->_fail('Loan payments can\'t be reversed from chat yet (it changes EMIs and interest). Open the loan to correct it.'
                    . ($lp ? ' ' . site_url('admin/loans/view/' . $lp->loan_id) : ''));
        }
        return $this->_fail('Tell me which entry to delete.');
    }

    private function _perform_update_member($member_id, $changes) {
        if (!$this->_can('members_edit') && $this->admin_data->role !== 'super_admin' && $this->admin_data->role !== 'admin') {
            return $this->_fail('You do not have permission to edit members.');
        }
        $member = $this->Member_model->get_by_id($member_id);
        if (!$member) return $this->_fail('Please choose the member.');
        if (is_string($changes)) $changes = json_decode($changes, true);
        $data = [];
        foreach ((array) $changes as $c) {
            $col = $c['field'] ?? '';
            if (!in_array($col, self::MEMBER_EDITABLE, true)) continue;
            $val = trim((string) ($c['new'] ?? ''));
            if ($err = $this->_validate_member_field($col, $val)) return $this->_fail($err);
            if (in_array($col, ['phone', 'alternate_phone', 'nominee_phone'], true)) $val = preg_replace('/\D/', '', $val);
            if ($col === 'bank_ifsc') $val = strtoupper($val);
            $data[$col] = $val;
        }
        if (!$data) return $this->_fail('Nothing to change.');
        // Keep the twin columns in step (see Members::update)
        if (isset($data['bank_account_number'])) $data['account_number'] = $data['bank_account_number'];
        if (isset($data['bank_ifsc']))           $data['ifsc_code']      = $data['bank_ifsc'];
        if (isset($data['nominee_relationship'])) $data['nominee_relation'] = $data['nominee_relationship'];

        $old = array_intersect_key((array) $member, $data);
        if (!$this->Member_model->update($member_id, $data)) {
            $e = $this->db->error();
            return $this->_fail('Could not update the member' . (!empty($e['message']) ? ' (' . $e['message'] . ')' : '') . '.');
        }
        $this->log_audit('update', 'members', 'members', $member_id, $old, $data, 'AI assistant');
        $list = [];
        foreach ($data as $k => $v) if (!in_array($k, ['account_number', 'ifsc_code', 'nominee_relation'], true)) $list[] = str_replace('_', ' ', $k) . ' → ' . ($v === '' ? '(blank)' : $v);
        return $this->_done('Updated ' . $this->_member_name($member) . ': ' . implode(', ', $list) . '.', [
            'link' => site_url('admin/members/view/' . $member_id), 'link_text' => 'Open member',
        ]);
    }

    private function _mark_reversed($kind, $id) {
        $conv = $this->_conv();
        foreach ($conv['entries'] as &$e) {
            if ($e['kind'] === $kind && $e['id'] === (int) $id) $e['reversed'] = true;
        }
        unset($e);
        $this->_save_conv($conv);
    }

    private function _fail($msg) { return ['ok' => false, 'message' => $msg, 'data' => []]; }
    private function _done($msg, $data = []) { return ['ok' => true, 'message' => $msg, 'data' => $data]; }

    /** Permission check that returns instead of redirecting/exiting. */
    private function _can($permission) {
        if ($this->admin_data->role === 'super_admin') return true;
        $perms = json_decode((string) $this->admin_data->permissions, true) ?: [];
        return in_array($permission, $perms, true);
    }

    // ───────────────────────────── Prompt & context ─────────────────────────────

    private function _system_prompt(array $mention_ids, array $entries = []) {
        $today = date('Y-m-d');
        $pages = [];
        foreach ($this->pages as $k => $p) $pages[] = $k . ' (' . $p[0] . ')';

        $ctx = '';
        foreach (array_slice($mention_ids, 0, 4) as $i => $id) {
            $ctx .= ($i === 0 ? '(member currently being discussed) ' : '') . $this->_member_context($id) . "\n";
        }
        $made = '';
        foreach ($entries as $e) {
            $made .= "  {$e['ref']}: {$e['label']}" . ($e['reversed'] ? ' [REVERSED]' : '') . "\n";
        }
        $made = $made !== '' ? "ENTRIES MADE IN THIS CHAT (use these refs to undo):\n" . $made : "ENTRIES MADE IN THIS CHAT: none yet\n";

        $s = $this->db->query("SELECT
                (SELECT COUNT(*) FROM members WHERE status = 'active') active_members,
                (SELECT COUNT(*) FROM loans WHERE status IN ('active','npa')) active_loans,
                (SELECT COALESCE(SUM(outstanding_principal),0) FROM loans WHERE status IN ('active','npa')) loan_outstanding,
                (SELECT COUNT(*) FROM loan_installments li JOIN loans l ON l.id = li.loan_id
                    WHERE l.status IN ('active','npa') AND li.status IN ('pending','overdue','partial') AND li.due_date < CURDATE()) overdue_emis,
                (SELECT COUNT(*) FROM loan_applications WHERE status IN ('pending','under_review')) pending_applications,
                (SELECT COALESCE(SUM(balance_amount),0) FROM fines WHERE status IN ('pending','partial')) pending_fines")->row();

        return <<<PROMPT
You are the smart office assistant inside "Windeep Finance", a member savings & loan society app. You talk to the ADMIN (staff), not to members.
Be friendly and brief, like a helpful colleague. Remember the conversation: "he/his/same member/तोच/त्यांचे" means the member being discussed;
"that/last entry/ती नोंद" means the most recent entry in ENTRIES MADE IN THIS CHAT.
Today is {$today}. Currency is Indian Rupees (₹).

LANGUAGE: The admin may write in Marathi (Devanagari), Hindi, English or a mix. Always write "reply" in the SAME language the admin used.
Member names in the database are in English letters — when you put a name into an action, transliterate it to English letters
(e.g. "रमेश पाटील" -> "Ramesh Patil"). Convert Devanagari digits to normal digits (५००० -> 5000).
Marathi hints: भरले/जमा = paid/deposited, हप्ता/EMI = loan installment, बचत/सेव्हिंग = savings, दंड = fine, व्याज = interest,
फक्त व्याज = interest only, रोख = cash, काल = yesterday, आज = today,
थकबाकी = overdue (page overdue_loans), अर्ज = application (page loan_applications), सभासद = member, दाखवा/उघडा = show/open (use navigate).
For "page" write ONLY the key, e.g. "overdue_loans".

WHAT YOU CAN DO
1. Answer questions using the DATA below (never invent numbers; if data is missing say so and suggest @mentioning the member).
2. Make entries: when the admin asks to add / pay / deposit / fine / activate / deactivate, return the action — the system SAVES IT
   IMMEDIATELY, so never answer an entry request with a page link. If something is unclear (which member, how much), ask in "reply"
   and return no action for it. Only create an entry when the admin clearly asks for it (not for questions).
3. Open a page of the app for the admin.
4. Read passbook / bank statement text or images: create ONE action per credit line that belongs to a member.

ALWAYS answer with ONE JSON object only, no other text:
{"reply": "short message for the admin", "actions": [ ... ]}

Action formats (omit fields you don't know; dates as YYYY-MM-DD):
{"type":"loan_payment","member":"name or MEMB code","amount":5000,"date":"{$today}","mode":"cash|upi|bank_transfer|cheque","payment_type":"emi|interest_only","reference":"","remarks":""}
{"type":"savings_deposit","member":"name or MEMB code","amount":1000,"date":"{$today}","mode":"cash","deposit_type":"regular|onetime","reference":"","remarks":""}
{"type":"fine","member":"name or MEMB code","amount":100,"date":"{$today}","remarks":"reason (required)"}
{"type":"member_status","member":"name or MEMB code","status":"active|inactive|blocked|suspended","remarks":"reason"}
{"type":"reverse_entry","entry":"E1","remarks":"reason"}   ← undo an entry made in this chat
{"type":"reverse_entry","kind":"savings_deposit|fine|loan_payment","member":"name or MEMB code","amount":2000,"date":"YYYY-MM-DD","remarks":"reason"}   ← undo an older entry
{"type":"update_member","member":"name or MEMB code","changes":{"phone":"9876543210","city":"Pune"}}   ← edit member details.
   Allowed fields: phone, alternate_phone, email, address, address_line2, city, state, pincode, occupation, nominee_name,
   nominee_relationship, nominee_phone, bank_name, bank_account_number, bank_ifsc, father_name, notes
{"type":"navigate","page":"one of: {$this->_join($pages)}"}

To activate / deactivate / block a member use member_status (निष्क्रिय/बंद = inactive, सक्रिय/चालू = active, ब्लॉक = blocked) — do NOT just navigate.
NEVER write in "reply" that something is done, saved, cancelled or deleted — the system adds the real result itself.
ACT, DON'T INTERVIEW: when the member and amount are known, return the action right away. Never ask about optional fields —
use defaults: mode "cash", deposit_type "regular", reference "", date today. Only ask when the member or the amount is really unknown.
When the admin asks about a member's data (balance, loan, dues), answer from the DATA section.
Rules: Use "actions": [] when nothing needs to be entered. If the admin didn't say loan vs savings, use "loan_payment" when the amount matches
the member's EMI, otherwise ask in "reply". If the date isn't given, use today.
Delete / undo / cancel / remove / चुकीची / रद्द करा / काढून टाका = reverse_entry. Withdrawals and editing other records are not possible
from chat — say so briefly and add a navigate action.

DATA — society summary: active members {$s->active_members}, active loans {$s->active_loans}, loan outstanding ₹{$s->loan_outstanding},
overdue EMIs {$s->overdue_emis}, pending loan applications {$s->pending_applications}, pending fines ₹{$s->pending_fines}.
{$ctx}
{$made}
PROMPT;
    }

    private function _join(array $a) { return implode(', ', $a); }

    /** Compact text overview of one member for the prompt. */
    private function _member_context($member_id) {
        $m = $this->Member_model->get_by_id($member_id);
        if (!$m) return '';
        $out = "DATA — member {$m->member_code} " . $this->_member_name($m) . " (phone {$m->phone}, status {$m->status}):\n";

        foreach ($this->_loan_options($member_id, true) as $l) {
            $out .= "  Loan {$l['loan_number']} [{$l['status']}] EMI ₹{$l['emi']} outstanding principal ₹{$l['outstanding']}"
                  . ($l['next_due'] ? ", next due {$l['next_due']} ₹{$l['next_amount']}" : '')
                  . ($l['overdue'] ? ", {$l['overdue']} EMI(s) overdue" : '') . "\n";
        }
        foreach ($this->_account_options($member_id) as $a) {
            $out .= "  Savings {$a['account_number']} monthly ₹{$a['monthly']} balance ₹{$a['balance']}, pending dues ₹{$a['dues']}\n";
        }
        $fines = $this->db->select_sum('balance_amount')->where('member_id', $member_id)
            ->where_in('status', ['pending', 'partial'])->get('fines')->row();
        $out .= '  Pending fines ₹' . number_format((float) ($fines->balance_amount ?? 0), 2, '.', '') . "\n";

        $pay = $this->db->select('lp.total_amount, lp.payment_date, l.loan_number')->from('loan_payments lp')
            ->join('loans l', 'l.id = lp.loan_id')->where('l.member_id', $member_id)->where('lp.is_reversed', 0)
            ->order_by('lp.payment_date', 'DESC')->limit(3)->get()->result();
        foreach ($pay as $p) {
            $out .= "  Recent loan payment {$p->payment_date} ₹{$p->total_amount} ({$p->loan_number})\n";
        }
        return $out;
    }

    // ───────────────────────────── Resolving AI actions ─────────────────────────────

    /** Turn one raw AI action into a preview card the admin can edit and confirm. */
    private function _resolve_action(array $a, array $mentions, array $entries = []) {
        $type = $a['type'] ?? '';
        if (!in_array($type, self::ACTION_TYPES, true)) return null;
        if ($type === 'reverse_entry') return $this->_resolve_reverse($a, $mentions, $entries);
        if ($type === 'update_member') return $this->_resolve_update_member($a, $mentions);

        if ($type === 'navigate') {
            // Small models echo the list format: "loans (All Loans)" — accept key or label
            $key = strtolower(trim(preg_replace('/\s*\(.*$/', '', (string) ($a['page'] ?? ''))));
            if (!isset($this->pages[$key])) {
                foreach ($this->pages as $k => $p) {
                    if (strcasecmp($p[0], trim((string) ($a['page'] ?? ''))) === 0) { $key = $k; break; }
                }
            }
            if (!isset($this->pages[$key])) return null;
            return ['type' => 'navigate', 'label' => $this->pages[$key][0], 'url' => site_url($this->pages[$key][1])];
        }

        $card = [
            'type'      => $type,
            'amount'    => round((float) $this->_latin_digits((string) ($a['amount'] ?? 0)), 2),
            'date'      => $this->_valid_date($a['date'] ?? '') ?: date('Y-m-d'),
            'mode'      => $this->_payment_mode($a['mode'] ?? 'cash'),
            'reference' => (string) ($a['reference'] ?? ''),
            'remarks'   => (string) ($a['remarks'] ?? ''),
            'member_text' => (string) ($a['member'] ?? ''),
            'warnings'  => [],
        ];
        if ($type === 'member_status') {
            $st = strtolower(trim((string) ($a['status'] ?? '')));
            $st = ['deactivate' => 'inactive', 'deactive' => 'inactive', 'activate' => 'active', 'block' => 'blocked', 'suspend' => 'suspended'][$st] ?? $st;
            $card['status'] = in_array($st, self::MEMBER_STATUSES, true) ? $st : '';
            if ($card['status'] === '') $card['warnings'][] = 'Please choose the new status.';
        }
        if ($type === 'loan_payment')    $card['payment_type'] = ($a['payment_type'] ?? '') === 'interest_only' ? 'interest_only' : 'emi';
        if ($type === 'savings_deposit') $card['deposit_type'] = ($a['deposit_type'] ?? '') === 'onetime' ? 'onetime' : 'regular';

        // Member: explicit id from AI → name/code search → single @mention
        $candidates = [];
        if (!empty($a['member_id']) && ($m = $this->Member_model->get_by_id((int) $a['member_id']))) {
            $candidates = [$m];
        } elseif ($card['member_text'] !== '') {
            $candidates = $this->_find_members($card['member_text'], 5);
        }
        if (!$candidates && count($mentions) === 1) {
            $candidates = [$this->Member_model->get_by_id($mentions[0])];
        }
        $candidates = array_values(array_filter($candidates));

        $card['member_candidates'] = array_map(function ($m) {
            return ['id' => (int) $m->id, 'label' => $m->member_code . ' - ' . $this->_member_name($m) . ' (' . $m->phone . ')'];
        }, $candidates);
        $card['member_id'] = count($candidates) === 1 ? (int) $candidates[0]->id : null;

        if (!$candidates) {
            $card['warnings'][] = 'Member "' . $card['member_text'] . '" not found — please pick the member.';
        } elseif (count($candidates) > 1) {
            $card['warnings'][] = count($candidates) . ' members match "' . $card['member_text'] . '" — please pick the right one.';
        }

        if ($card['member_id']) {
            if ($type === 'loan_payment') {
                $card['loans'] = $this->_loan_options($card['member_id']);
                $card['loan_id'] = count($card['loans']) === 1 ? $card['loans'][0]['id'] : null;
                if (!$card['loans']) $card['warnings'][] = 'This member has no active loan.';
                if (count($card['loans']) > 1) $card['warnings'][] = 'Member has ' . count($card['loans']) . ' active loans — which one?';
                if ($card['loan_id'] && $card['amount'] > (float) $card['loans'][0]['outstanding'] * 1.2 + 1) {
                    $card['warnings'][] = 'Amount is more than the loan outstanding (Rs ' . number_format((float) $card['loans'][0]['outstanding'], 2) . ') — please check.';
                }
                // Amount sanity vs EMI
                if ($card['loan_id'] && $card['payment_type'] === 'emi' && $card['amount'] > 0) {
                    $emi = (float) $card['loans'][0]['emi'];
                    if ($emi > 0 && abs($card['amount'] - $emi) > 1 && $card['amount'] < $emi) {
                        $card['warnings'][] = 'Amount is less than the EMI (₹' . number_format($emi, 2) . ').';
                    }
                }
            } elseif ($type === 'savings_deposit') {
                $card['accounts'] = $this->_account_options($card['member_id']);
                $card['savings_account_id'] = count($card['accounts']) === 1 ? $card['accounts'][0]['id'] : null;
                if (!$card['accounts']) $card['warnings'][] = 'This member has no active savings account.';
                if (count($card['accounts']) > 1) $card['warnings'][] = 'Member has ' . count($card['accounts']) . ' savings accounts — which one?';
                if ($card['savings_account_id'] && $card['amount'] > 0) {
                    $monthly = (float) $card['accounts'][0]['monthly'];
                    if ($monthly > 0 && $card['amount'] > $monthly * 12) {
                        $card['warnings'][] = 'Rs ' . number_format($card['amount'], 2) . ' is more than 12x the monthly deposit (Rs ' . number_format($monthly, 2) . ') — please check the amount.';
                    }
                }
            }

            // Possible duplicate: same member + amount + date already recorded
            if ($card['amount'] > 0 && $type !== 'fine') {
                $dup = $type === 'loan_payment'
                    ? $this->db->from('loan_payments lp')->join('loans l', 'l.id = lp.loan_id')
                        ->where('l.member_id', $card['member_id'])->where('lp.total_amount', $card['amount'])
                        ->where('lp.payment_date', $card['date'])->where('lp.is_reversed', 0)->count_all_results()
                    : $this->db->from('savings_transactions st')->join('savings_accounts sa', 'sa.id = st.savings_account_id')
                        ->where('sa.member_id', $card['member_id'])->where('st.amount', $card['amount'])
                        ->where('st.transaction_date', $card['date'])->where('st.transaction_type', 'deposit')->count_all_results();
                if ($dup) $card['warnings'][] = 'A payment of the same amount on this date already exists — possible duplicate.';
            }
        }

        if ($type === 'member_status') {
            if (count($candidates) === 1) {
                $card['current_status'] = $candidates[0]->status;
                if ($candidates[0]->status === $card['status']) $card['warnings'][] = 'Member is already ' . $card['status'] . '.';
            }
            return $card;
        }
        if ($card['amount'] <= 0) $card['warnings'][] = 'Amount missing.';
        if ($type === 'fine' && $card['remarks'] === '') $card['warnings'][] = 'Reason for the fine is required.';

        return $card;
    }

    /** Find the record to undo: a ref from this chat, or the latest matching record for a member. */
    private function _resolve_reverse(array $a, array $mentions, array $entries) {
        $card = ['type' => 'reverse_entry', 'remarks' => (string) ($a['remarks'] ?? ''), 'warnings' => [], 'from_chat' => false];
        $live = array_values(array_filter($entries, function ($e) { return !$e['reversed']; }));

        $ref = strtoupper(trim((string) ($a['entry'] ?? '')));
        $entry = null;
        foreach ($live as $e) if ($e['ref'] === $ref) $entry = $e;
        if (!$entry && $ref === '' && empty($a['member']) && $live) $entry = end($live);   // "undo that"

        if ($entry) {
            $card = array_merge($card, ['kind' => $entry['kind'], 'target_id' => $entry['id'], 'member_id' => $entry['member_id'],
                                        'label' => $entry['label'], 'from_chat' => true]);
        } else {
            $kind = in_array($a['kind'] ?? '', ['savings_deposit', 'fine', 'loan_payment'], true) ? $a['kind'] : 'savings_deposit';
            $members = !empty($a['member']) ? $this->_find_members((string) $a['member'], 3)
                     : ($mentions ? [$this->Member_model->get_by_id($mentions[0])] : []);
            $members = array_values(array_filter($members));
            if (count($members) !== 1) {
                $card['warnings'][] = $members ? 'Several members match — please name the member exactly.' : 'Which member\'s entry should I delete?';
                return $card + ['kind' => $kind, 'target_id' => null, 'member_id' => null, 'label' => ''];
            }
            $m = $members[0];
            $amount = round((float) $this->_latin_digits((string) ($a['amount'] ?? 0)), 2);
            $date = $this->_valid_date($a['date'] ?? '');
            $row = null;
            if ($kind === 'savings_deposit') {
                $this->db->select('st.id, st.amount, st.transaction_date d, sa.account_number ref')->from('savings_transactions st')
                    ->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('sa.member_id', $m->id)
                    ->where('st.transaction_type', 'deposit')->where('st.is_reversed', 0);
                if ($amount > 0) $this->db->where('st.amount', $amount);
                if ($date) $this->db->where('st.transaction_date', $date);
                $rows = $this->db->order_by('st.id', 'DESC')->limit(2)->get()->result();
            } elseif ($kind === 'fine') {
                $this->db->select('id, fine_amount amount, fine_date d, fine_code ref')->from('fines')
                    ->where('member_id', $m->id)->where_in('status', ['pending', 'partial']);
                if ($amount > 0) $this->db->where('fine_amount', $amount);
                $rows = $this->db->order_by('id', 'DESC')->limit(2)->get()->result();
            } else {
                $this->db->select('lp.id, lp.total_amount amount, lp.payment_date d, l.loan_number ref')->from('loan_payments lp')
                    ->join('loans l', 'l.id = lp.loan_id')->where('l.member_id', $m->id)->where('lp.is_reversed', 0);
                if ($amount > 0) $this->db->where('lp.total_amount', $amount);
                $rows = $this->db->order_by('lp.id', 'DESC')->limit(2)->get()->result();
            }
            $row = $rows[0] ?? null;
            $card['unique'] = count($rows) === 1;   // only one record fits → safe to run without asking
            if (count($rows) > 1) $card['warnings_soft'] = 'More than one record matches — the latest is selected.';
            $names = ['savings_deposit' => 'savings deposit', 'fine' => 'fine', 'loan_payment' => 'loan payment'];
            if (!$row) {
                $card['warnings'][] = 'No matching ' . $names[$kind] . ' found for ' . $this->_member_name($m) . '.';
                return $card + ['kind' => $kind, 'target_id' => null, 'member_id' => (int) $m->id, 'label' => ''];
            }
            $card += ['kind' => $kind, 'target_id' => (int) $row->id, 'member_id' => (int) $m->id,
                      'label' => ucfirst($names[$kind]) . ' ₹' . number_format((float) $row->amount, 2) . ' on ' . $row->d
                                 . ' (' . $row->ref . ') — ' . $this->_member_name($m)];
        }
        if ($card['kind'] === 'loan_payment') {
            $card['warnings'][] = 'Loan payments can\'t be reversed from chat yet — open the loan to correct it.';
        }
        return $card;
    }

    private function _resolve_update_member(array $a, array $mentions) {
        $card = ['type' => 'update_member', 'member_text' => (string) ($a['member'] ?? ''), 'warnings' => [], 'changes' => [], 'member_id' => null];
        $members = $card['member_text'] !== '' ? $this->_find_members($card['member_text'], 3)
                 : ($mentions ? [$this->Member_model->get_by_id($mentions[0])] : []);
        $members = array_values(array_filter($members));
        $card['member_candidates'] = array_map(function ($m) {
            return ['id' => (int) $m->id, 'label' => $m->member_code . ' - ' . $this->_member_name($m) . ' (' . $m->phone . ')'];
        }, $members);
        if (count($members) !== 1) {
            $card['warnings'][] = $members ? 'Several members match — please pick the member.' : 'Which member should I update?';
        } else {
            $card['member_id'] = (int) $members[0]->id;
        }
        $current = count($members) === 1 ? $members[0] : null;
        if ($current) $current = $this->Member_model->get_by_id($current->id);
        foreach ((array) ($a['changes'] ?? []) as $field => $value) {
            $col = self::MEMBER_EDITABLE[strtolower(trim((string) $field))] ?? null;
            if (!$col) { $card['warnings'][] = '"' . $field . '" can\'t be changed from chat.'; continue; }
            $value = trim($this->_latin_digits((string) $value));
            $err = $this->_validate_member_field($col, $value);
            if ($err) { $card['warnings'][] = $err; continue; }
            $card['changes'][] = ['field' => $col, 'old' => $current ? (string) ($current->$col ?? '') : '', 'new' => $value];
        }
        if (!$card['changes'] && !$card['warnings']) $card['warnings'][] = 'What should I change?';
        return $card;
    }

    private function _validate_member_field($col, $v) {
        if (in_array($col, ['phone', 'alternate_phone', 'nominee_phone'], true) && $v !== '' && !preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $v))) {
            return 'Phone "' . $v . '" is not a valid 10-digit mobile number.';
        }
        if ($col === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) return 'Email "' . $v . '" is not valid.';
        if ($col === 'pincode' && $v !== '' && !preg_match('/^\d{6}$/', $v)) return 'Pincode must be 6 digits.';
        if ($col === 'bank_ifsc' && $v !== '' && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/i', $v)) return 'IFSC "' . $v . '" is not valid.';
        return null;
    }

    private function _loan_options($member_id, $with_details = false) {
        $loans = $this->db->select('id, loan_number, emi_amount, outstanding_principal, status')
            ->where('member_id', $member_id)->where_in('status', ['active', 'npa'])
            ->order_by('id', 'DESC')->get('loans')->result();
        $out = [];
        foreach ($loans as $l) {
            $row = ['id' => (int) $l->id, 'loan_number' => $l->loan_number, 'emi' => number_format((float) $l->emi_amount, 2, '.', ''),
                    'outstanding' => number_format((float) $l->outstanding_principal, 2, '.', ''), 'status' => $l->status];
            if ($with_details) {
                $next = $this->db->select('due_date, emi_amount, total_paid')->where('loan_id', $l->id)
                    ->where_in('status', ['pending', 'overdue', 'partial'])->order_by('installment_number', 'ASC')
                    ->limit(1)->get('loan_installments')->row();
                $row['next_due']    = $next ? $next->due_date : null;
                $row['next_amount'] = $next ? number_format((float) $next->emi_amount - (float) $next->total_paid, 2, '.', '') : null;
                $row['overdue']     = $this->db->where('loan_id', $l->id)->where_in('status', ['pending', 'overdue', 'partial'])
                    ->where('due_date <', date('Y-m-d'))->count_all_results('loan_installments');
            }
            $out[] = $row;
        }
        return $out;
    }

    private function _account_options($member_id) {
        $accs = $this->db->select('id, account_number, monthly_amount, current_balance')
            ->where('member_id', $member_id)->where('status', 'active')->get('savings_accounts')->result();
        $out = [];
        foreach ($accs as $a) {
            $dues = $this->db->select('COALESCE(SUM(due_amount - COALESCE(paid_amount,0)),0) d', false)
                ->where('savings_account_id', $a->id)->where_in('status', ['pending', 'partial', 'overdue'])
                ->where('due_date <=', date('Y-m-d'))->get('savings_schedule')->row();
            $out[] = ['id' => (int) $a->id, 'account_number' => $a->account_number,
                      'monthly' => number_format((float) $a->monthly_amount, 2, '.', ''),
                      'balance' => number_format((float) $a->current_balance, 2, '.', ''),
                      'dues'    => number_format((float) ($dues->d ?? 0), 2, '.', '')];
        }
        return $out;
    }

    // ───────────────────────────── Helpers ─────────────────────────────

    /** Member search that matches every word (name parts / code / phone). */
    private function _find_members($text, $limit = 5) {
        $text = trim(preg_replace('/^@/', '', $this->_latin_digits($text)));
        if ($text === '') return [];

        if (preg_match('/^MEMB\d+$/i', $text)) {
            return $this->db->where('member_code', strtoupper($text))->get('members')->result();
        }

        $words = array_filter(preg_split('/\s+/', $text), function ($w) { return mb_strlen($w) >= 1; });
        $this->db->select('id, member_code, first_name, middle_name, last_name, phone, status')->from('members');
        $this->db->where('deleted_at', null);
        foreach (array_slice($words, 0, 4) as $w) {
            $this->db->group_start()
                ->like('first_name', $w)->or_like('middle_name', $w)->or_like('last_name', $w)
                ->or_like('member_code', $w)->or_like('phone', $w)
                ->group_end();
        }
        return $this->db->order_by("status = 'active'", 'DESC', false)->order_by('first_name', 'ASC')
            ->limit($limit)->get()->result();
    }

    private function _member_name($m) {
        return function_exists('member_full_name')
            ? member_full_name($m)
            : trim(($m->first_name ?? '') . ' ' . ($m->last_name ?? ''));
    }

    /**
     * Find one member named in free text: tries each pair of consecutive Latin words ("yogesh auchare"),
     * then single words of 4+ letters. Returns a member id only when exactly one member matches.
     */
    private function _spot_member($text) {
        preg_match_all('/[A-Za-z]{3,}/', (string) $text, $m);
        $words = $m[0];
        $stop = ['the','and','for','add','pay','paid','cash','upi','bank','loan','emi','saving','savings','account','deposit','fine',
                 'jama','kara','karo','rokh','bachat','khatyat','yanchya','member','inactive','active','delete','undo','show','today'];
        $words = array_values(array_filter($words, function ($w) use ($stop) { return !in_array(strtolower($w), $stop, true); }));
        for ($i = 0; $i < count($words) - 1; $i++) {
            $r = $this->_find_members($words[$i] . ' ' . $words[$i + 1], 2);
            if (count($r) === 1) return (int) $r[0]->id;
        }
        foreach ($words as $w) {
            if (strlen($w) < 4) continue;
            $r = $this->_find_members($w, 2);
            if (count($r) === 1) return (int) $r[0]->id;
        }
        return null;
    }

    private function _wants_undo($text) {
        $t = mb_strtolower((string) $text);
        foreach (['undo', 'delete', 'remove', 'reverse', 'cancel that', 'cancel this', 'cancel last', 'wrong entry',
                  'रद्द', 'काढून', 'काढा', 'हटवा', 'चुकीची', 'चुकीचे', 'चुकून', 'डिलीट', 'मिटवा'] as $w) {
            if (mb_strpos($t, $w) !== false) return true;
        }
        return false;
    }

    /** Status the admin explicitly asked for, from keywords (checked negative-first: "inactive" contains "active"). */
    private function _status_from_text($text) {
        $t = mb_strtolower((string) $text);
        $rules = [
            'inactive'  => ['inactive', 'in-active', 'deactivate', 'deactive', 'disable', 'निष्क्रिय', 'बंद कर', 'बंद करा'],
            'blocked'   => ['block', 'ब्लॉक'],
            'suspended' => ['suspend', 'निलंबित'],
            'active'    => ['activate', 'active', 'enable', 'सक्रिय', 'चालू कर', 'चालू करा'],
        ];
        foreach ($rules as $status => $words) {
            foreach ($words as $w) {
                if (mb_strpos($t, $w) !== false) return $status;
            }
        }
        return null;
    }

    private function _latin_digits($s) {
        return strtr((string) $s, ['०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4',
                                   '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9', ',' => '']);
    }

    private function _valid_date($d) {
        $d = $this->_latin_digits(trim((string) $d));
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
    }

    private function _payment_mode($mode) {
        $mode = strtolower(trim((string) $mode));
        $map = ['bank' => 'bank_transfer', 'neft' => 'bank_transfer', 'imps' => 'bank_transfer', 'rtgs' => 'bank_transfer',
                'online' => 'upi', 'gpay' => 'upi', 'phonepe' => 'upi', 'रोख' => 'cash', 'चेक' => 'cheque'];
        $mode = $map[$mode] ?? $mode;
        return in_array($mode, ['cash', 'upi', 'bank_transfer', 'cheque'], true) ? $mode : 'cash';
    }

    /** Provider config: settings table first, then .env. $public hides keys. */
    private function _config($public = false) {
        $get = function ($k, $env, $def = '') {
            $v = $this->Setting_model->get_setting($k, null);
            return ($v !== null && $v !== '') ? $v : env($env, $def);
        };
        $cfg = [
            'provider'        => $get('ai_provider', 'AI_PROVIDER', 'local'),
            'local_url'       => $get('ai_local_url', 'AI_LOCAL_URL', 'http://192.168.1.5:8080/v1'),
            'local_model'     => $get('ai_local_model', 'AI_LOCAL_MODEL', 'Gemma-4-E2B-it'),
            'openai_model'    => $get('ai_openai_model', 'AI_OPENAI_MODEL', 'gpt-4o-mini'),
            'gemini_model'    => $get('ai_gemini_model', 'AI_GEMINI_MODEL', 'gemini-2.0-flash'),
            'deepseek_model'  => $get('ai_deepseek_model', 'AI_DEEPSEEK_MODEL', 'deepseek-chat'),
            'openrouter_model'=> $get('ai_openrouter_model', 'AI_OPENROUTER_MODEL', 'deepseek/deepseek-chat'),
            // Budget & limits: .env only (not editable from the app)
            'monthly_cap_inr' => (string) max(0, (float) env('AI_MONTHLY_BUDGET_INR', '4000')) ,   // billed budget per month
            'markup'          => (string) max(1, (float) env('AI_MARKUP', '2')),
            'warn_pct'        => (string) min(100, max(1, (int) env('AI_WARN_PCT', '80'))),
            'daily_limit'     => (string) max(0, (int) env('AI_DAILY_LIMIT', '0')),
            'max_tokens'      => (string) min(8000, max(200, (int) env('AI_MAX_TOKENS', '1500'))),
            'usd_inr'         => (string) max(1, (float) env('AI_USD_INR', '85')),
            'recharge_contact'=> (string) env('AI_RECHARGE_CONTACT', ''),
            'openai_key'      => $get('ai_openai_key', 'AI_OPENAI_KEY'),
            'gemini_key'      => $get('ai_gemini_key', 'AI_GEMINI_KEY'),
            'deepseek_key'    => $get('ai_deepseek_key', 'AI_DEEPSEEK_KEY'),
            'openrouter_key'  => $get('ai_openrouter_key', 'AI_OPENROUTER_KEY'),
        ];
        if ($public) {
            $cfg['openai_key_set'] = $cfg['openai_key'] !== '';
            $cfg['gemini_key_set'] = $cfg['gemini_key'] !== '';
            $cfg['deepseek_key_set'] = $cfg['deepseek_key'] !== '';
            $cfg['openrouter_key_set'] = $cfg['openrouter_key'] !== '';
            unset($cfg['openai_key'], $cfg['gemini_key'], $cfg['deepseek_key'], $cfg['openrouter_key']);
        }
        return $cfg;
    }

    private function _client(array $override = []) {
        $c = $this->_config();
        $map = ['ai_provider' => 'provider', 'ai_local_url' => 'local_url', 'ai_local_model' => 'local_model',
                'ai_openai_model' => 'openai_model', 'ai_gemini_model' => 'gemini_model', 'ai_deepseek_model' => 'deepseek_model',
                'ai_openrouter_model' => 'openrouter_model', 'ai_openai_key' => 'openai_key', 'ai_gemini_key' => 'gemini_key',
                'ai_deepseek_key' => 'deepseek_key', 'ai_openrouter_key' => 'openrouter_key'];
        foreach ($map as $field => $key) {
            if (isset($override[$field]) && trim((string) $override[$field]) !== '') $c[$key] = trim((string) $override[$field]);
        }
        $this->load->library('Ai_client');
        switch ($c['provider']) {
            case 'openai':
                return $this->ai_client->configure(['provider' => 'openai', 'model' => $c['openai_model'], 'api_key' => $c['openai_key']]);
            case 'gemini':
                return $this->ai_client->configure(['provider' => 'gemini', 'model' => $c['gemini_model'], 'api_key' => $c['gemini_key']]);
            case 'deepseek':
                return $this->ai_client->configure(['provider' => 'deepseek', 'model' => $c['deepseek_model'], 'api_key' => $c['deepseek_key']]);
            case 'openrouter':
                return $this->ai_client->configure(['provider' => 'openrouter', 'model' => $c['openrouter_model'], 'api_key' => $c['openrouter_key']]);
            default:
                return $this->ai_client->configure(['provider' => 'local', 'base_url' => $c['local_url'], 'model' => $c['local_model'], 'timeout' => 180]);
        }
    }

    private function _log_chat($message, $raw, $cards, array $meta = []) {
        if (!$this->db->table_exists('ai_chat_logs')) return;
        $row = [
            'admin_id'   => (int) $this->session->userdata('admin_id'),
            'message'    => mb_substr($message, 0, 5000),
            'response'   => mb_substr((string) $raw, 0, 20000),
            'actions'    => json_encode($cards, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ];
        if ($meta && $this->db->field_exists('cost_usd', 'ai_chat_logs')) {
            $row += $meta;
        }
        $this->db->insert('ai_chat_logs', $row);
    }
}
