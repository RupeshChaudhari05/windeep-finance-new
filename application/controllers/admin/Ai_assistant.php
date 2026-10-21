<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * AI Assistant - admin chat agent that understands Marathi / English / Hinglish.
 *
 * Flow (tool-calling agent, like ChatGPT with functions):
 *   admin message ──► model ──► calls tools: describe_tables / run_sql (read-only) / get_member / get_loan …
 *                ◄── tool results ──┘  (repeats up to MAX_STEPS)
 *   write tools (payment, deposit, fine, member edit, status, reverse, foreclose, email, correct_record)
 *   only PROPOSE: the server resolves the real records, stores the proposal in the session and the browser
 *   shows a card. Simple, unambiguous entries may auto-save; everything risky waits for Confirm.
 *   execute() runs the stored proposal through the normal models (ledger posting + audit log).
 *
 * The AI never writes to the database itself.
 */
class Ai_assistant extends Admin_Controller {

    const ACTION_TYPES = ['loan_payment', 'savings_deposit', 'fine', 'member_status', 'reverse_entry', 'update_member', 'navigate'];
    const MAX_STEPS    = 8;               // model ↔ tool round trips per message
    const PENDING_KEY  = 'ai_pending';    // session: proposals waiting for Confirm (token => card)

    // Tables the agent may never read (credentials / sessions)
    const SQL_BLOCKED_TABLES = ['admin_details', 'admin_sessions', 'api_tokens', 'ci_sessions', 'password_history', 'two_factor_auth',
                                'verification_tokens', 'active_sessions', 'failed_login_attempts', 'member_details', 'security_logs'];
    // Tables correct_record may edit (one row by id, audit-logged)
    const CORRECTABLE_TABLES = ['members', 'loans', 'loan_installments', 'loan_payments', 'loan_applications', 'loan_foreclosure_requests',
                                'loan_guarantors', 'savings_accounts', 'savings_schedule', 'savings_transactions', 'fines', 'non_members',
                                'non_member_funds', 'bank_transactions', 'member_other_transactions', 'expenditure'];
    const CORE_TABLES = ['members', 'loans', 'loan_installments', 'loan_payments', 'loan_applications', 'loan_foreclosure_requests',
                         'loan_products', 'loan_part_payments', 'savings_accounts', 'savings_transactions', 'savings_schedule',
                         'savings_schemes', 'fines', 'bank_transactions', 'general_ledger'];

    /** State of the message being processed (cards shown, steps taken, conversation). */
    private $run = null;

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
            ['id' => 'meta-llama/llama-3.3-70b-instruct',          'label' => 'Llama 3.3 (70B)',                'tag' => 'strong multi-lingual', 'in' => 0.37, 'out' => 0.55],
            ['id' => 'openai/gpt-4o-mini',                   'label' => 'GPT-4o mini via OpenRouter',   'tag' => 'reliable tool use',       'in' => 0.15, 'out' => 0.60],
            ['id' => 'google/gemini-2.0-flash-001',           'label' => 'Gemini 2.0 Flash via OpenRouter', 'tag' => 'cheap, multimodal',      'in' => 0.05, 'out' => 0.20],
        ],
        // AICredits prices converted from their INR list (≈ ₹97/USD). Aion models do not call tools — the agent needs GPT models.
        'aicredits' => [
            ['id' => 'gpt-4o-mini',             'label' => 'GPT-4o Mini via AICredits',    'tag' => 'recommended: full agent (DB, foreclose, email)', 'in' => 0.0158, 'out' => 0.0629],
            ['id' => 'gpt-4o',                  'label' => 'GPT-4o via AICredits',         'tag' => 'best accuracy + passbook photos',  'in' => 0.262,  'out' => 1.049],
            ['id' => 'o3-mini',                 'label' => 'O3-mini via AICredits',        'tag' => 'reasoning, slower',               'in' => 0.115,  'out' => 0.461],
            ['id' => 'o1',                      'label' => 'O1 via AICredits',             'tag' => 'reasoning, expensive',            'in' => 1.573,  'out' => 6.291],
            ['id' => 'aion-labs/aion-3.5-mini', 'label' => 'Aion 3.5 Mini (AICredits)',    'tag' => 'cheapest — chat only, cannot use tools', 'in' => 0.0073, 'out' => 0.0146, 'tools' => false],
            ['id' => 'aion-labs/aion-3.0-mini', 'label' => 'Aion 3.0 Mini (AICredits)',    'tag' => 'chat only, cannot use tools',     'in' => 0.0073, 'out' => 0.0146, 'tools' => false],
            ['id' => 'aion-labs/aion-rp-llama-3.1-8b', 'label' => 'Aion-RP Llama 8B (AICredits)', 'tag' => 'chat only, cannot use tools', 'in' => 0.0084, 'out' => 0.0168, 'tools' => false],
            ['id' => 'aion-labs/aion-3.5',      'label' => 'Aion 3.5 (AICredits)',         'tag' => 'chat only, cannot use tools',     'in' => 0.0314, 'out' => 0.0629, 'tools' => false],
            ['id' => 'aion-labs/aion-3.0',      'label' => 'Aion 3.0 (AICredits)',         'tag' => 'chat only, cannot use tools',     'in' => 0.0314, 'out' => 0.0629, 'tools' => false],
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
        'day_book'            => ['Day Book', 'admin/reports/day_book'],
        'member_dues'         => ['Member Dues', 'admin/reports/member_dues'],
        'loan_portfolio'      => ['Loan Portfolio', 'admin/reports/portfolio'],
        'fine_register'       => ['Fine Register', 'admin/reports/fines'],
        'income_report'       => ['Income Analysis', 'admin/reports/income'],
        'savings_register'    => ['Savings Register', 'admin/reports/savings_register'],
        'collection_efficiency' => ['Collection Efficiency', 'admin/reports/collection_efficiency'],
        'collection_report'   => ['Collection Report', 'admin/reports/collection'],
        'outstanding_report'  => ['Outstanding Loans', 'admin/reports/outstanding'],
        'npa_report'          => ['NPA Report', 'admin/reports/npa'],
        'ageing_report'       => ['Ageing Analysis', 'admin/reports/ageing'],
        'cash_book'           => ['Cash Book', 'admin/reports/cash-book'],
        'bank_book'           => ['Bank Book', 'admin/reports/cash-book?book=bank'],
        'bank_reconciliation' => ['Bank Reconciliation', 'admin/reports/bank-reconciliation'],
        'kyc_pending'         => ['KYC Pending', 'admin/reports/kyc-pending'],
        'trial_balance'       => ['Trial Balance', 'admin/reports/trial-balance'],
        'profit_loss'         => ['Profit & Loss', 'admin/reports/profit-loss'],
        'balance_sheet'       => ['Balance Sheet', 'admin/reports/balance-sheet'],
        'fine_rules'          => ['Fine Rules', 'admin/fines/rules'],
        'fine_preview'        => ['Auto Fine Preview', 'admin/fines/preview_auto_fines'],
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
        if (!in_array($provider, ['local', 'openai', 'gemini', 'deepseek', 'openrouter', 'aicredits'], true)) {
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
            'ai_aicredits_model' => trim((string) $this->input->post('ai_aicredits_model')),
        ];
        foreach (['ai_openai_key', 'ai_gemini_key', 'ai_deepseek_key', 'ai_openrouter_key', 'ai_aicredits_key'] as $k) {
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
        $pending = $this->_pending();
        foreach ($c['messages'] as &$m) {
            unset($m['memory']);
            foreach ($m['cards'] ?? [] as &$card) {
                if (!empty($card['token']) && !isset($pending[$card['token']])) $card['stale'] = true;   // confirmed / cancelled since
            }
            unset($card);
        }
        unset($m);
        $this->json_response(['success' => true, 'messages' => $c['messages']]);
    }

    /** Start a new conversation. */
    public function reset() {
        $this->session->unset_userdata([self::CONV_KEY, self::PENDING_KEY]);
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
    private function _cost_usd($provider, $model, $in, $out, $cached = 0) {
        foreach (self::MODEL_CATALOG[$provider] ?? [] as $m) {
            if ($m['id'] === $model) return (($in - 0.5 * $cached) * $m['in'] + $out * $m['out']) / 1000000;
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
        foreach ($c['messages'] as &$m) {   // keep the session file small
            foreach ($m['steps'] ?? [] as $i => $s) $m['steps'][$i]['detail'] = mb_substr((string) ($s['detail'] ?? ''), 0, 600);
        }
        unset($m);
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

    // ───────────────────────────── Chat (agent loop) ─────────────────────────────

    /**
     * POST message, mentions (JSON array of member ids), image (data URL, optional), auto (1|0)
     */
    public function chat() {
        // stream=1: the browser reads NDJSON events (status / step / done / error) as they happen, like ChatGPT
        $this->stream = $this->input->post('stream') === '1';
        if ($this->stream) $this->_stream_open();

        $message = trim((string) $this->input->post('message'));
        $image   = (string) $this->input->post('image');
        if ($message === '' && $image === '') {
            return $this->_chat_out(['success' => false, 'message' => 'Please type a message.'], 400);
        }

        $cfg = $this->_config();
        $model = $cfg[$cfg['provider'] . '_model'] ?? '';
        if ($image !== '' && $cfg['provider'] === 'deepseek') {
            return $this->_chat_out(['success' => false, 'message' => 'DeepSeek can\'t read photos. Paste the passbook text instead, or switch to ChatGPT / Gemini in AI Settings for photos.'], 400);
        }
        if ($this->_model_info($cfg['provider'], $model)['tools'] === false) {
            return $this->_chat_out(['success' => false, 'message' => 'The selected model "' . $model . '" can only chat — it cannot look up the database or make entries. '
                . 'Open ⚙️ AI Settings and choose a GPT model (recommended: GPT-4o Mini).'], 400);
        }
        @set_time_limit(300);   // several model calls per message

        // Spending cap / daily limit
        $usage = $this->_usage();
        if ($cfg['provider'] !== 'local' && $usage['cap_inr'] > 0 && $usage['month_cost_inr'] >= $usage['cap_inr']) {
            return $this->_chat_out(['success' => false, 'recharge' => true, 'message' => $this->_recharge_message($usage)], 402);
        }
        if ($usage['daily_limit'] > 0 && $usage['today_requests'] >= $usage['daily_limit']) {
            return $this->_chat_out(['success' => false, 'message' => 'Daily limit of ' . $usage['daily_limit'] . ' AI requests reached. Try again tomorrow.'], 400);
        }

        // Members tagged with @ (chips or @MEMB codes in the text) are handed to the model as a hint
        $mentions = json_decode((string) $this->input->post('mentions'), true) ?: [];
        if (preg_match_all('/@?(MEMB\d+)/i', $message, $mm)) {
            foreach ($mm[1] as $code) {
                $row = $this->db->select('id')->where('member_code', strtoupper($code))->get('members')->row();
                if ($row) $mentions[] = (int) $row->id;
            }
        }
        $hint = '';
        foreach (array_slice(array_values(array_unique(array_map('intval', $mentions))), 0, 5) as $id) {
            $m = $this->Member_model->get_by_id($id);
            if ($m) $hint .= "\n[Admin tagged member {$m->member_code} " . $this->_member_name($m) . " (member id {$m->id})]";
        }

        $conv = $this->_conv();
        $this->run = ['auto' => $this->input->post('auto') !== '0', 'message' => $message, 'cards' => [], 'steps' => [], 'log' => [], 'conv' => $conv];

        $messages = [['role' => 'system', 'content' => $this->_system_prompt()]];
        // History: last 12 messages; only the 2 latest assistant turns keep their tool log (follow-ups like "his loan")
        $hist = array_slice($conv['messages'], -12);
        $recent = 0;
        for ($i = count($hist) - 1; $i >= 0; $i--) {
            $h = $hist[$i];
            if ($h['role'] === 'assistant') {
                $full = $recent++ < 2;
                $hist[$i] = ['role' => 'assistant', 'content' => mb_substr((string) ($full ? ($h['memory'] ?? $h['text']) : $h['text']), 0, $full ? 2500 : 700)];
            } else {
                $hist[$i] = ['role' => 'user', 'content' => mb_substr((string) $h['text'], 0, 1500)];
            }
        }
        foreach ($hist as $h) if (trim($h['content']) !== '') $messages[] = $h;
        $messages[] = ['role' => 'system', 'content' => $this->_context_prompt()];
        $messages[] = ['role' => 'user', 'content' => ($message !== '' ? $message : 'Read this passbook / statement image and create the entries.') . $hint];

        $opts = ['temperature' => 0.1, 'max_tokens' => (int) $cfg['max_tokens']];
        if ($image !== '' && preg_match('#^data:image/(png|jpe?g|webp);base64,#', $image)) {
            $opts['images'] = [$image];
        }

        $tin = $tout = $tcached = 0;
        $reply = null;
        $error = null;
        $nudged = false;
        try {
            $client = $this->_client();
            $tools  = $this->_tool_defs();
            for ($step = 0; $step < self::MAX_STEPS; $step++) {
                if ($step === self::MAX_STEPS - 1) {
                    $messages[] = ['role' => 'system', 'content' => 'Step limit reached. Do not call more tools — answer the admin now with what you have.'];
                }
                $this->_emit(['type' => 'status', 'text' => $step === 0 ? 'Thinking…' : 'Reading the results…']);
                $res = $client->chat_tools($messages, $tools, $opts);
                $tin  += $client->last_usage['in'];
                $tout += $client->last_usage['out'];
                $tcached += $client->last_usage['cached'] ?? 0;
                $messages[] = $res['message'];

                if (!$res['tool_calls']) {
                    // Weaker models announce "I'll check the loan…" and stop, or return nothing — push once to actually do it
                    $said = trim($res['content']);
                    if (!$nudged && $step < self::MAX_STEPS - 2 && !$this->run['cards']
                        && ($said === '' || preg_match('/\b(I\'ll|I will|let me|first|checking|going to)\b|तपासतो|पाहतो|करतो|चेक/iu', $said))) {
                        $nudged = true;
                        $messages[] = ['role' => 'user', 'content' => $said === ''
                            ? 'You returned an empty answer. Answer my request now, using the tools if needed.'
                            : 'Go ahead — call the tool now instead of describing it.'];
                        continue;
                    }
                    $reply = $said;
                    break;
                }
                if ($step === self::MAX_STEPS - 1) { $reply = trim($res['content']); break; }
                foreach ($res['tool_calls'] as $tc) {
                    $this->_emit(['type' => 'status', 'text' => $this->_tool_status($tc['name'], $tc['args'])]);
                    $out = $this->_run_tool($tc['name'], $tc['args']);
                    $this->run['log'][] = $tc['name'] . '(' . mb_substr(json_encode($tc['args'], JSON_UNESCAPED_UNICODE), 0, 300) . ') → '
                                        . mb_substr(json_encode($out, JSON_UNESCAPED_UNICODE), 0, 600);
                    $messages[] = ['role' => 'tool', 'tool_call_id' => $tc['id'], 'name' => $tc['name'], 'content' => $this->_json_for_model($out)];
                }
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        $cards = $this->run['cards'];
        $steps = $this->run['steps'];
        $conv  = $this->run['conv'];
        $tool_log = $this->run['log'];
        $this->run = null;

        if ($error !== null && !$cards) {
            $this->_log_chat($message, 'ERROR: ' . $error, [], ['provider' => $cfg['provider'], 'model' => $model,
                             'tokens_in' => $tin, 'tokens_out' => $tout, 'cost_usd' => $this->_cost_usd($cfg['provider'], $model, $tin, $tout)]);
            return $this->_chat_out(['success' => false, 'message' => $error, 'steps' => $steps], 400);
        }
        if ($error !== null) {
            $reply = '⚠️ ' . $error;
        }
        if ($reply === null || $reply === '') {
            $saved = array_filter($cards, function ($c) { return !empty($c['executed']); });
            $reply = $saved ? '✅ ' . implode("\n✅ ", array_map(function ($c) { return $c['result']['message']; }, $saved))
                   : ($cards ? '👇 Please check the details below and press Confirm.'
                             : '⚠️ The AI model (' . $model . ') returned an empty answer. Please try again, or choose GPT-4o Mini in ⚙️ AI Settings.');
        }

        // Remember this turn (the model sees the tool log next time, so "his loan" / "send him the same" works)
        $log = mb_substr($reply . ($tool_log ? "\n[Tool log of this turn:\n" . implode("\n", $tool_log) . ']' : ''), 0, 6000);
        $conv['messages'][] = ['role' => 'user', 'text' => $message, 'image' => $image !== ''];
        $conv['messages'][] = ['role' => 'assistant', 'text' => $reply, 'cards' => $this->_compact_cards($cards), 'steps' => $steps,
                               'memory' => $log];
        $this->_save_conv($conv);

        $this->_log_chat($message, $log, $cards, ['provider' => $cfg['provider'], 'model' => $model,
                                                    'tokens_in' => $tin, 'tokens_out' => $tout, 'cost_usd' => $this->_cost_usd($cfg['provider'], $model, $tin, $tout, $tcached)]);

        $usage = $this->_usage();
        if ($usage['cap_inr'] > 0 && $usage['month_cost_inr'] >= $usage['cap_inr'] * $usage['warn_pct'] / 100) {
            $reply .= "\n\n⚠️ AI usage this month: ₹" . number_format($usage['month_cost_inr'], 2) . ' of ₹' . number_format($usage['cap_inr']) . ' budget.';
        }

        $this->_chat_out([
            'success' => true,
            'reply'   => $reply,
            'actions' => $cards,
            'steps'   => $steps,
            'usage'   => $this->_usage_for_viewer(),
        ]);
    }

    // ── streaming (NDJSON over a chunked response) ──

    private $stream = false;

    private function _stream_open() {
        @ini_set('zlib.output_compression', '0');
        @ini_set('output_buffering', '0');
        @ini_set('implicit_flush', '1');
        if (function_exists('apache_setenv')) @apache_setenv('no-gzip', '1');
        while (ob_get_level() > 0) @ob_end_flush();
        header('Content-Type: application/x-ndjson; charset=utf-8');
        header('Cache-Control: no-cache, no-transform');
        header('X-Accel-Buffering: no');        // nginx / LiteSpeed: don't hold the chunks
        echo str_repeat(' ', 2048) . "\n";       // push through proxies that buffer the first KBs
        flush();
    }

    /** Send one progress event to the browser (no-op when not streaming). */
    private function _emit(array $event) {
        if (!$this->stream) return;
        echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
        @ob_flush();
        flush();
    }

    /** Final answer of chat(): one "done"/"error" event when streaming, a normal JSON response otherwise. */
    private function _chat_out(array $payload, $status = 200) {
        if (!$this->stream) return $this->json_response($payload, $status);
        $this->_emit(['type' => empty($payload['success']) ? 'error' : 'done'] + $payload);
    }

    /** What the admin sees while a tool runs. */
    private function _tool_status($name, array $a) {
        $labels = [
            'describe_tables' => 'Reading the database structure…',
            'map_bank_entries' => 'Matching unmapped bank entries to members…',
            'get_report'      => 'Preparing the ' . str_replace('_', ' ', (string) ($a['report'] ?? '')) . ' report…',
            'run_sql'         => 'Querying the database' . (!empty($a['purpose']) ? ': ' . $a['purpose'] : '') . '…',
            'get_member'      => 'Looking up member ' . ($a['member'] ?? '') . '…',
            'get_loan'        => 'Looking up loan ' . ($a['loan'] ?? ($a['member'] ?? '')) . '…',
            'open_page'       => 'Preparing the page link…',
            'foreclose_loan'  => 'Calculating the ' . (($a['closure_type'] ?? '') === 'force_close' ? 'force-close' : 'foreclosure') . ' settlement…',
            'send_email'      => 'Drafting the email…',
            'correct_record'  => 'Preparing the correction…',
        ];
        return $labels[$name] ?? 'Preparing ' . str_replace('_', ' ', $name) . '…';
    }

    /** Catalog entry for a model (tools => false for chat-only models). */
    private function _model_info($provider, $model) {
        foreach (self::MODEL_CATALOG[$provider] ?? [] as $m) {
            if ($m['id'] === $model) return $m + ['tools' => true];
        }
        return ['tools' => null];   // custom / local model: unknown, let it try
    }

    /** Pass each step to the browser as it happens. */
    private function _step($icon, $label, $detail = '') {
        $s = ['icon' => $icon, 'label' => $label, 'detail' => mb_substr((string) $detail, 0, 2000)];
        $this->run['steps'][] = $s;
        $this->_emit(['type' => 'step', 'step' => $s]);
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
        $token = (string) ($f['token'] ?? '');
        $pending = $this->_pending();
        if ($token !== '') {
            if (!isset($pending[$token])) return $this->error_response('This request has expired or was already handled — please ask again.');
            $card = $pending[$token];
            if (in_array($card['type'], ['loan_payment', 'savings_deposit', 'fine', 'member_status', 'update_member'], true)) {
                $f['type'] = $card['type'];          // the admin may have edited the card's fields
            } elseif ($card['type'] === 'reverse_entry') {
                $f = ['type' => 'reverse_entry', 'kind' => $card['kind'], 'target_id' => $card['target_id'],
                      'remarks' => trim((string) ($f['remarks'] ?? '')) !== '' ? $f['remarks'] : ($card['remarks'] ?? '')];
            } else {
                $f = $card;                          // foreclose / email / correction: exactly what was shown
            }
        } elseif (!in_array($f['type'] ?? '', ['loan_payment', 'savings_deposit', 'fine', 'member_status', 'update_member', 'reverse_entry'], true)) {
            return $this->error_response('Unknown action.');
        }
        $r = $this->_perform($f);
        if ($r['ok'] && $token !== '') {
            unset($pending[$token]);
            $this->session->set_userdata(self::PENDING_KEY, $pending);
        }
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

    /** Admin pressed Cancel on a proposal. POST token */
    public function cancel() {
        $token = (string) $this->input->post('token');
        $pending = $this->_pending();
        if (isset($pending[$token])) {
            $card = $pending[$token];
            unset($pending[$token]);
            $this->session->set_userdata(self::PENDING_KEY, $pending);
            $conv = $this->_conv();
            $conv['messages'][] = ['role' => 'assistant', 'text' => '✖ Cancelled.', 'memory' => '[Admin cancelled the proposed ' . $card['type'] . ']'];
            $this->_save_conv($conv);
        }
        $this->success_response('Cancelled.');
    }

    /**
     * Validate and save one action. Used by Confirm (execute) and by auto-save in chat().
     * @return array ['ok' => bool, 'message' => string, 'data' => array]
     */
    private function _perform(array $f) {
        $in = function ($k) use ($f) { return $f[$k] ?? null; };
        $type = $in('type');
        if ($type === 'foreclose_loan') return $this->_perform_foreclose($f);
        if ($type === 'send_email')     return $this->_perform_email($f);
        if ($type === 'correct_record') return $this->_perform_correct($f);
        if ($type === 'passbook_import') return $this->_perform_passbook($f);
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
                    $this->Ledger_model->post_savings_txn($txn_id, $admin_id);
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
            } elseif (($c['type'] ?? '') === 'data_table') {
                $out[] = ['rows' => array_slice($c['rows'], 0, 15), 'more' => $c['more'] || count($c['rows']) > 15] + $c;
            } elseif (($c['type'] ?? '') === 'navigate' || !empty($c['token'])) {
                $out[] = $c;   // pending cards stay confirmable after a reload
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
                foreach (['savings_transaction', 'savings_deposit'] as $rt) $this->Ledger_model->reverse_source($rt, $id, $reason, date('Y-m-d'), $admin_id);
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
        if ($this->run !== null) {   // inside chat(): the conversation is saved at the end of the turn
            foreach ($this->run['conv']['entries'] as &$e) {
                if ($e['kind'] === $kind && $e['id'] === (int) $id) $e['reversed'] = true;
            }
            unset($e);
            return;
        }
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

    // ───────────────────────────── Passbook Excel import ─────────────────────────────

    /** Download the sample Excel the admin fills with passbook entries. */
    public function passbook_template() {
        $this->load->library('Passbook_import');
        $book = $this->passbook_import->template();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Passbook_Entries_Template.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
        exit;
    }

    /**
     * POST file (xlsx / xls / csv). Reads and checks every row, then shows a preview card — nothing is saved
     * until the admin confirms (execute() with the card's token).
     */
    public function passbook_upload() {
        if (!$this->_can_passbook()) return $this->error_response('You do not have permission to record collections.', [], 403);
        $f = $_FILES['file'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return $this->error_response('Please choose the Excel file again.');
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) return $this->error_response('Only .xlsx, .xls or .csv files can be imported.');
        if ($f['size'] > 5 * 1024 * 1024) return $this->error_response('File is too large (max 5 MB).');

        // Keep the uploaded file for audit
        $dir = FCPATH . 'uploads/ai_passbook/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $path = $dir . date('Ymd_His') . '_' . (int) $this->session->userdata('admin_id') . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $path)) return $this->error_response('Could not store the uploaded file.');
        @set_time_limit(180);

        $this->load->library('Passbook_import');
        try {
            $rows = $this->passbook_import->read($path);
        } catch (Exception $e) {
            return $this->error_response('Could not read the file: ' . $e->getMessage());
        }
        if (!$rows) return $this->error_response('No entries found. Fill the "Passbook" sheet of the template (one row per entry).');

        $plan = $this->passbook_import->resolve($rows, function (array $names) { return $this->_transliterate($names); });
        $sum = $plan['summary'];
        $name = mb_substr(basename($f['name']), 0, 120);

        $entries = array_map(function ($r) {
            return ['row' => $r['row'], 'date' => $r['date'], 'member' => $r['member_label'], 'amount' => $r['amount'], 'mode' => $r['mode'],
                    'alloc' => implode(' + ', array_map(function ($a) { return $a['label'] . ' ₹' . number_format($a['amount'], 2); }, $r['allocations'])),
                    'status' => $r['status'], 'issues' => array_merge($r['issues'], $r['notes'])];
        }, $plan['rows']);
        $card = ['type' => 'passbook_import', 'title' => 'Passbook import — ' . $name, 'file' => $name, 'summary' => $sum,
                 'entries' => $entries, 'warnings' => []];
        if ($sum['ready'] > 0) {
            $this->_add_pending($card, ['plan' => $plan['rows'], 'stored_file' => basename($path)]);
        }

        $reply = "📄 **{$name}** — {$sum['rows']} row(s) checked:\n"
               . "- ✅ **{$sum['ready']}** ready to save (₹" . number_format($sum['amount_ready'], 2) . ")\n"
               . ($sum['review'] ? "- ⚠️ **{$sum['review']}** need attention (see the reason on each row — fix the Excel and upload again, or tell me)\n" : '')
               . ($sum['duplicate'] ? "- ♻️ **{$sum['duplicate']}** already recorded — will be skipped\n" : '')
               . ($sum['ready'] ? "\nPress **Save entries** to record them (same rules as Bank Mapping)." : "\nNothing can be saved yet.");

        // The model sees the problem rows on the next turn, so the admin can sort them out in chat
        $issues = [];
        foreach ($plan['rows'] as $r) {
            if ($r['status'] !== 'ready') $issues[] = 'row ' . $r['row'] . ': ' . $r['date'] . ' ' . $r['member'] . ' ₹' . $r['amount'] . ' ' . $r['type']
                                                    . ' → ' . $r['status'] . ' (' . implode('; ', $r['issues']) . ')';
        }
        $conv = $this->_conv();
        $conv['messages'][] = ['role' => 'user', 'text' => '📄 ' . $name, 'image' => false];
        $conv['messages'][] = ['role' => 'assistant', 'text' => $reply, 'cards' => [$card],
                               'memory' => $reply . ($issues ? "\n[Rows not ready:\n" . implode("\n", array_slice($issues, 0, 40)) . ']' : '')];
        $this->_save_conv($conv);
        $this->log_activity('AI passbook file checked', $name . ': ' . json_encode($sum), 'ai_assistant');

        $this->json_response(['success' => true, 'reply' => $reply, 'actions' => [$card]]);
    }

    private function _can_passbook() {
        return in_array($this->admin_data->role, ['super_admin', 'admin'], true) || $this->_can('savings_collect');
    }

    /**
     * map_bank_entries: suggestions for the unmapped bank CREDITS — member found from the bank text (identifiers, UPI id,
     * payer / "paid for" names, earlier mappings, amount), allocation by the passbook rules. Shown as a "Save" card;
     * on confirm each line is mapped with Bank_model::apply_mappings (or linked to the manual entry that already exists).
     */
    private function _tool_bank_map(array $a) {
        if (!$this->_can_passbook()) return ['error' => 'You do not have permission to map bank entries.'];
        $this->load->library('Passbook_import');
        $from = $this->_valid_date($a['from'] ?? '');
        $to   = $this->_valid_date($a['to'] ?? '');
        $rows = $this->passbook_import->bank_rows(['from' => $from, 'to' => $to, 'limit' => (int) ($a['limit'] ?? 100),
                                                   'ids' => !empty($a['ids']) ? (array) $a['ids'] : []]);
        $debits = $this->db->where('transaction_type', 'debit')->where_in('mapping_status', ['unmapped', 'partial'])->count_all_results('bank_transactions');
        if (!$rows) {
            $this->_step('university', 'No unmapped bank credits');
            return ['status' => 'nothing_to_map', 'unmapped_debits' => $debits];
        }
        $plan = $this->passbook_import->resolve($rows);
        $sum = $plan['summary'];
        $link = 0;
        foreach ($plan['rows'] as $r) if ($r['allocations'] && strpos($r['allocations'][0]['type'], 'link_') === 0) $link++;

        $entries = array_map(function ($r) {
            $why = !empty($r['match']) ? ['🔎 ' . $r['match'] . (isset($r['confidence']) ? ' (' . $r['confidence'] . ')' : '')] : [];
            return ['row' => $r['row'], 'date' => $r['date'], 'member' => $r['member_label'], 'amount' => $r['amount'], 'mode' => $r['mode'],
                    'alloc' => implode(' + ', array_map(function ($x) { return $x['label'] . ' ₹' . number_format($x['amount'], 2); }, $r['allocations'])),
                    'status' => $r['status'], 'issues' => array_merge($r['issues'], $r['notes'], $why), 'payer' => mb_substr($r['payer'], 0, 40)];
        }, $plan['rows']);
        $card = ['type' => 'passbook_import', 'title' => 'Bank mapping suggestions — ' . $sum['rows'] . ' unmapped credit(s)', 'file' => 'Bank mapping (AI)',
                 'summary' => $sum, 'entries' => $entries, 'warnings' => [], 'bank' => true];
        if ($sum['ready'] > 0) $this->_add_pending($card, ['plan' => $plan['rows']]);
        $this->run['cards'][] = $card;
        $this->_step('university', 'Bank mapping: ' . $sum['ready'] . ' of ' . $sum['rows'] . ' credits ready');

        $review = [];
        foreach ($plan['rows'] as $r) {
            if ($r['status'] !== 'ready') $review[] = '#' . $r['row'] . ' ' . $r['date'] . ' ₹' . $r['amount'] . ' payer "' . mb_substr($r['payer'], 0, 30) . '": ' . implode('; ', $r['issues']);
        }
        return ['status' => $sum['ready'] ? 'awaiting_admin_confirmation' : 'nothing_ready', 'credits_checked' => $sum['rows'], 'ready' => $sum['ready'],
                'ready_amount' => round($sum['amount_ready'], 2), 'of_which_link_to_existing_manual_entries' => $link, 'need_review' => $sum['review'],
                'unmapped_debits_not_handled' => $debits,
                'note' => 'A card lists every line with the matched member and the reason; the admin presses Save. Debits (expenses / disbursements) are mapped on the Bank Mapping screen.',
                'review_rows' => array_slice($review, 0, 25)];
    }

    private function _perform_passbook(array $c) {
        if (!$this->_can_passbook()) return $this->_fail('You do not have permission to record collections.');
        if (empty($c['plan'])) return $this->_fail('This import has expired — upload the file again.');
        $this->load->library('Passbook_import');
        $res = $this->passbook_import->execute($c['plan'], (int) $this->session->userdata('admin_id'), $c['file'] ?? '');
        $this->log_audit('import', 'bank_transactions', 'bank_transactions', 0, null,
                         ['file' => $c['file'] ?? '', 'saved' => $res['saved'], 'failed' => $res['failed'], 'amount' => $res['amount'], 'imports' => $res['import_codes']],
                         !empty($c['bank']) ? 'AI bank mapping' : 'AI passbook import');
        $rows = [];
        foreach ($c['entries'] ?? [] as $e) {
            $r = $res['results'][$e['row']] ?? null;
            $rows[] = ['row' => $e['row'], 'member' => $e['member'], 'amount' => $e['amount'],
                       'status' => $r ? $r['status'] : ($e['status'] === 'ready' ? 'failed' : 'skipped'),
                       'message' => $r ? $r['message'] : implode('; ', $e['issues'])];
        }
        $msg = (!empty($c['bank']) ? 'Bank mapping: ' : 'Passbook import: ') . $res['saved'] . ' entr' . ($res['saved'] === 1 ? 'y' : 'ies') . ' saved (₹' . number_format($res['amount'], 2) . ')'
             . ($res['failed'] ? ', ' . $res['failed'] . ' failed' : '') . '.';
        if (!$res['saved']) return ['ok' => false, 'message' => $msg . ' ' . implode(' | ', array_unique(array_column(array_filter($rows, function ($r) { return $r['status'] === 'failed'; }), 'message'))), 'data' => []];
        return $this->_done($msg, ['results' => $rows, 'link' => site_url('admin/bank/mapping'), 'link_text' => 'Open Bank Mapping']);
    }

    /** English spelling of Devanagari member names (one small model call per file). */
    private function _transliterate(array $names) {
        $cfg = $this->_config();
        $client = $this->_client();
        $raw = $client->chat([
            ['role' => 'system', 'content' => 'Transliterate Indian person names (Marathi / Hindi in Devanagari) to the usual English spelling used in Indian records. '
                                             . 'Reply with JSON only: {"names": {"<original>": "<English>"}}'],
            ['role' => 'user', 'content' => json_encode(array_slice($names, 0, 100), JSON_UNESCAPED_UNICODE)],
        ], ['json' => true, 'temperature' => 0, 'max_tokens' => 1500]);
        $model = $cfg[$cfg['provider'] . '_model'] ?? '';
        $tok = $client->last_usage;
        $this->_log_chat('[passbook transliteration] ' . implode(', ', array_slice($names, 0, 20)), $raw, [],
                         ['provider' => $cfg['provider'], 'model' => $model, 'tokens_in' => $tok['in'], 'tokens_out' => $tok['out'],
                          'cost_usd' => $this->_cost_usd($cfg['provider'], $model, $tok['in'], $tok['out'])]);
        $j = Ai_client::extract_json($raw);
        return is_array($j['names'] ?? null) ? $j['names'] : [];
    }

    // ───────────────────────────── Prompt & schema ─────────────────────────────

    /**
     * Static instructions + schema. Kept byte-identical between calls (no date, user or chat state) so the
     * provider's automatic prompt cache can reuse it — per-turn facts go in _context_prompt().
     */
    private function _system_prompt() {
        $schema = $this->_schema_brief();
        $pages  = implode(', ', array_keys($this->pages));
        return <<<PROMPT
You are the AI operations assistant of "Windeep Finance", a member savings & loan society. You work for the ADMIN staff,
with live read access to the MySQL database and tools to make changes. Think like a careful senior accountant. Currency ₹.

HOW TO WORK
- Every figure must come from a tool; never invent numbers.
- Pick the cheapest tool: get_report for standard reports (summary, overdue, collections, dues, applications, unmapped bank
  entries, pending fines); get_member / get_loan for one member or loan; run_sql for anything else; describe_tables only for
  tables or enum values not listed below. Fix and retry a failing query yourself.
- Results of run_sql / get_report are shown to the admin as a table with an Excel download. Do NOT repeat the rows —
  reply with a short summary (count, totals, the top 3-5 names) and any insight. For counts and totals use the tool's
  row_count / totals_of_all_rows (exact) — never add up rows yourself. State the period of period-based figures.
  A one-row result (e.g. the summary) is already shown as a list — give only 3-4 highlights or concerns.
- To CHANGE anything use the write tools. They return "executed" (saved) or "awaiting_admin_confirmation" (a Confirm card is
  shown). Never say something is saved / closed / sent unless the tool said "executed"; when awaiting, summarise in one line.
- Prefer the dedicated tools (they post the ledger). correct_record only fixes a wrong field no other tool covers — say that
  ledger entries are not re-posted.
- Ambiguous or missing member / loan → ask one short question with the options.
- Defaults (never ask): mode cash, date today, deposit_type regular, payment_type emi.
- Foreclosure: regular = principal + configured % of remaining interest + pending fines; force_close = only next month's interest,
  remaining interest and fines waived. Show the quote from get_loan when the admin asks for an amount.
- Loans can be "disbursed in parts": EMI is on the sanctioned amount, money is released in parts (get_loan → disbursement).
  Later parts are added on the loan page (Add disbursement) or by mapping the bank debit in Bank Mapping.
- Bank mapping: map_bank_entries matches unmapped bank credits to members and shows a Save card. Summarise ready / link /
  review counts and the main review reasons; for review lines help the admin decide (who paid, which loan).
- Bulk passbook entries: the admin fills the "Passbook Excel template" (link under the chat box) and uploads it with 📎; a
  "Save entries" card follows. Help fix rows that were not ready, or record them one by one.
- Emails: polite and professional, in the admin's language, signed "Windeep Finance".

LANGUAGE: Reply in the admin's language (Marathi / Hindi / English / mix). DB names are in English letters — transliterate before
searching ("रमेश पाटील" → "Ramesh Patil"); Devanagari digits → normal (५००० → 5000).
Marathi: भरले/जमा paid, हप्ता EMI, बचत savings, दंड fine, व्याज interest, रोख cash, काल yesterday, थकबाकी overdue, अर्ज application,
सभासद member, बंद करा close, रद्द/काढून टाका cancel (reverse_entry), दुरुस्ती correction.

OUTPUT: short Markdown, **bold** key amounts. Pages for open_page: {$pages}.

DATA RULES
- Member name = first_name middle_name last_name; member_code like MEMB000123; ignore members.deleted_at NOT NULL.
- Active loans: loans.status IN ('active','npa'); outstanding = outstanding_principal (+ outstanding_interest).
- Overdue EMI: loan_installments.status IN ('pending','overdue','partial') AND due_date < CURDATE(); due = emi_amount - total_paid.
- Ignore loan_payments / savings_transactions with is_reversed = 1.
- Savings balance savings_accounts.current_balance; dues savings_schedule (due_amount - paid_amount), status pending/partial/overdue.
- Unpaid fines: fines.balance_amount, status IN ('pending','partial').

{$schema}
PROMPT;
    }

    /** Per-turn facts, sent right before the admin's message (after the cached prefix). */
    private function _context_prompt() {
        $admin = $this->admin_data;
        $out = 'Today is ' . date('Y-m-d (l)') . '. Admin: ' . $admin->full_name . ' (' . $admin->role . ').';
        $made = '';
        foreach ($this->run['conv']['entries'] ?? [] as $e) {
            $made .= "\n  {$e['ref']}: {$e['label']}" . ($e['reversed'] ? ' [REVERSED]' : '');
        }
        if ($made !== '') $out .= "\nEntries made in this chat (refs for reverse_entry):" . $made;
        return $out;
    }

    /** Columns of the core tables + enum values + names of the other tables. */
    private function _schema_brief() {
        $db = $this->db->database;
        $rows = $this->db->query("SELECT TABLE_NAME t, COLUMN_NAME c, COLUMN_TYPE ty FROM information_schema.COLUMNS
                                  WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME, ORDINAL_POSITION", [$db])->result();
        $cols = $enums = [];
        foreach ($rows as $r) {
            if (preg_match('/photo|image|_doc$|password|must_change|^(created_by|updated_by)$|consent_token|consent_ip/', $r->c)) continue;
            $cols[$r->t][] = $r->c;
            if (in_array($r->t, self::CORE_TABLES, true) && stripos($r->ty, 'enum(') === 0) {
                $enums[] = $r->t . '.' . $r->c . ': ' . str_replace("'", '', substr($r->ty, 5, -1));
            }
        }
        $out = "DATABASE SCHEMA (MySQL/MariaDB) — core tables:\n";
        foreach (self::CORE_TABLES as $t) {
            if (isset($cols[$t])) $out .= "  {$t}(" . implode(',', $cols[$t]) . ")\n";
        }
        if ($enums) $out .= "Enum values:\n  " . implode("\n  ", $enums) . "\n";
        $other = array_diff(array_keys($cols), self::CORE_TABLES, self::SQL_BLOCKED_TABLES);
        $out .= "Other tables (use describe_tables): " . implode(', ', $other) . "\n";
        return $out;
    }

    // ───────────────────────────── Tools ─────────────────────────────

    private function _tool_defs() {
        $s = function ($desc, $enum = null) { $p = ['type' => 'string', 'description' => $desc]; if ($enum) $p['enum'] = $enum; return $p; };
        $n = function ($desc) { return ['type' => 'number', 'description' => $desc]; };
        $member = $s('Member code (MEMB…), name, phone or numeric member id');
        $changes = ['type' => 'array', 'description' => 'Fields to change',
                    'items' => ['type' => 'object', 'properties' => ['field' => $s('Column name'), 'value' => $s('New value ("NULL" to clear)')],
                                'required' => ['field', 'value']]];
        $obj = function ($props, $req = []) { return ['type' => 'object', 'properties' => $props, 'required' => $req]; };
        $modes = ['cash', 'upi', 'bank_transfer', 'cheque'];

        return [
            ['name' => 'describe_tables', 'description' => 'Columns, types, keys and enum values of DB tables. Pass no tables to list every table with its row count.',
             'parameters' => $obj(['tables' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Table names (max 10)']])],
            ['name' => 'run_sql', 'description' => 'Run ONE read-only SQL query (SELECT / WITH / SHOW / DESCRIBE) on the live database. Returns up to 100 rows. Use for any list, total, report or search.',
             'parameters' => $obj(['query' => $s('The SQL query'), 'purpose' => $s('Short description shown to the admin, e.g. "Overdue loans this month"')], ['query'])],
            ['name' => 'get_report', 'description' => 'Ready-made report, shown to the admin as a table (prefer over run_sql). summary = society totals + collections in the period; overdue = overdue EMIs by loan; collections = daily collections; dues = EMIs due in the next N days; savings_dues; applications = open loan applications; unmapped_bank; pending_fines; member_dues = per-member EMI + savings + fine dues; portfolio = product-wise outstanding / overdue / PAR 30-90; fine_register = all fines in the period; income = month-wise income & expenses from the ledger; day_book = money in/out per day (cash+bank); savings_register = every active savings account with dues; collection_efficiency = EMI demand vs collected per month; bank_reconciliation = bank lines not mapped + books bank balance; kyc_pending; fine_preview = DRY RUN of the auto-fine job (what fines would be created today, nothing saved). Each table has an Excel download of the matching report page.',
             'parameters' => $obj(['report' => $s('Report', ['summary', 'overdue', 'collections', 'dues', 'savings_dues', 'applications', 'unmapped_bank', 'pending_fines', 'member_dues', 'portfolio', 'fine_register', 'income', 'day_book', 'savings_register', 'collection_efficiency', 'bank_reconciliation', 'kyc_pending', 'fine_preview']),
                                   'from' => $s('YYYY-MM-DD; default 1st of this month. For "today" pass today as from and to'), 'to' => $s('YYYY-MM-DD, default today'), 'days' => $n('dues: days ahead, default 7')], ['report'])],
            ['name' => 'map_bank_entries', 'description' => 'Suggest mappings for UNMAPPED bank CREDIT lines (Bank Transactions): finds the member from the bank text / UPI id / payer name / history and allocates to EMI / savings, or links to an existing manual entry. Shows a Save card. Use when the admin asks to map / reconcile bank entries.',
             'parameters' => $obj(['from' => $s('YYYY-MM-DD, optional'), 'to' => $s('YYYY-MM-DD, optional'), 'limit' => $n('Max lines, default 100'),
                                   'ids' => ['type' => 'array', 'items' => ['type' => 'number'], 'description' => 'Only these bank transaction ids']])],
            ['name' => 'get_member', 'description' => 'Full overview of one member: profile, loans, savings accounts, unpaid fines, recent payments. Also use it to search members.',
             'parameters' => $obj(['member' => $member], ['member'])],
            ['name' => 'get_loan', 'description' => 'Details of one loan: amounts, schedule status, next dues, recent payments, pending foreclosure request and foreclosure / force-close quotes.',
             'parameters' => $obj(['loan' => $s('Loan number (e.g. LN0001) or numeric loan id'), 'member' => $s('Member, if the loan number is not known')])],
            ['name' => 'open_page', 'description' => 'Show the admin a link to a page of the app.',
             'parameters' => $obj(['page' => $s('Page key', array_keys($this->pages))], ['page'])],

            ['name' => 'record_loan_payment', 'description' => 'Record an EMI / interest-only payment received on a member\'s loan (posts ledger).',
             'parameters' => $obj(['member' => $member, 'loan_number' => $s('Loan number if the member has several loans'), 'amount' => $n('Amount in ₹'),
                                   'date' => $s('YYYY-MM-DD, default today'), 'mode' => $s('Payment mode', $modes),
                                   'payment_type' => $s('emi or interest_only', ['emi', 'interest_only']), 'reference' => $s('UTR / cheque no.'), 'remarks' => $s('Remarks')],
                                  ['member', 'amount'])],
            ['name' => 'record_savings_deposit', 'description' => 'Record a savings deposit for a member (posts ledger).',
             'parameters' => $obj(['member' => $member, 'account_number' => $s('Savings account number if several'), 'amount' => $n('Amount in ₹'),
                                   'date' => $s('YYYY-MM-DD'), 'mode' => $s('Payment mode', $modes),
                                   'deposit_type' => $s('regular = monthly dues, onetime = extra', ['regular', 'onetime']), 'reference' => $s('Reference'), 'remarks' => $s('Remarks')],
                                  ['member', 'amount'])],
            ['name' => 'add_fine', 'description' => 'Add a fine (penalty) on a member.',
             'parameters' => $obj(['member' => $member, 'amount' => $n('Amount in ₹'), 'reason' => $s('Reason (required)'), 'date' => $s('YYYY-MM-DD')],
                                  ['member', 'amount', 'reason'])],
            ['name' => 'update_member', 'description' => 'Change member contact / address / nominee / bank details. Allowed fields: ' . implode(', ', array_keys(self::MEMBER_EDITABLE)) . '. For other member columns use correct_record.',
             'parameters' => $obj(['member' => $member, 'changes' => $changes], ['member', 'changes'])],
            ['name' => 'change_member_status', 'description' => 'Activate / deactivate / block / suspend a member.',
             'parameters' => $obj(['member' => $member, 'status' => $s('New status', self::MEMBER_STATUSES), 'reason' => $s('Reason')], ['member', 'status'])],
            ['name' => 'reverse_entry', 'description' => 'Undo a wrong savings deposit or cancel a fine (with contra ledger entry). Identify it by entry ref from this chat (E1…), by record id, or by member + amount + date.',
             'parameters' => $obj(['entry' => $s('Entry ref from this chat, e.g. E1'), 'kind' => $s('Record kind', ['savings_deposit', 'fine', 'loan_payment']),
                                   'record_id' => $n('savings_transactions.id / fines.id'), 'member' => $member, 'amount' => $n('Amount'), 'date' => $s('YYYY-MM-DD'),
                                   'reason' => $s('Reason')], ['reason'])],
            ['name' => 'foreclose_loan', 'description' => 'Close a loan early: regular foreclosure or force close. Collects the settlement, closes the loan, cancels future EMIs, releases guarantors, posts ledger.',
             'parameters' => $obj(['loan' => $s('Loan number or id'), 'member' => $s('Member, if loan not given'),
                                   'closure_type' => $s('regular or force_close', ['regular', 'force_close']),
                                   'interest_charge_pct' => $n('Override % of remaining interest charged (regular only)'),
                                   'final_amount' => $n('Exact amount collected, if the admin states one'),
                                   'payment_mode' => $s('Payment mode', $modes), 'payment_date' => $s('YYYY-MM-DD'),
                                   'reference' => $s('UTR / cheque no.'), 'remarks' => $s('Reason / remarks')], ['closure_type'])],
            ['name' => 'send_email', 'description' => 'Send an email to a member (by member code/name) or to an email address.',
             'parameters' => $obj(['to' => $s('Member code / name, or an email address'), 'subject' => $s('Subject'), 'body' => $s('Plain-text email body')],
                                  ['to', 'subject', 'body'])],
            ['name' => 'correct_record', 'description' => 'Correct wrong values in ONE row of a table by its id (audit-logged, needs Confirm). Tables: ' . implode(', ', self::CORRECTABLE_TABLES) . '. Ledger is NOT re-posted.',
             'parameters' => $obj(['table' => $s('Table', self::CORRECTABLE_TABLES), 'record_id' => $n('Row id'), 'changes' => $changes, 'reason' => $s('Why the correction is needed')],
                                  ['table', 'record_id', 'changes', 'reason'])],
        ];
    }

    /** Dispatch one tool call. Always returns an array (sent back to the model as JSON). */
    private function _run_tool($name, array $args) {
        try {
            switch ($name) {
                case 'describe_tables': return $this->_tool_describe((array) ($args['tables'] ?? []));
                case 'run_sql':         return $this->_tool_sql((string) ($args['query'] ?? ''), (string) ($args['purpose'] ?? ''));
                case 'get_report':      return $this->_tool_report($args);
                case 'map_bank_entries': return $this->_tool_bank_map($args);
                case 'get_member':      return $this->_tool_member((string) ($args['member'] ?? ''));
                case 'get_loan':        return $this->_tool_loan($args);
                case 'open_page':
                    $card = $this->_resolve_action(['type' => 'navigate', 'page' => $args['page'] ?? ''], []);
                    if (!$card) return ['error' => 'Unknown page.'];
                    $this->run['cards'][] = $card;
                    return ['status' => 'link_shown', 'page' => $card['label']];
                case 'record_loan_payment':
                case 'record_savings_deposit':
                case 'add_fine':
                case 'update_member':
                case 'change_member_status':
                case 'reverse_entry':   return $this->_tool_entry($name, $args);
                case 'foreclose_loan':  return $this->_tool_foreclose($args);
                case 'send_email':      return $this->_tool_email($args);
                case 'correct_record':  return $this->_tool_correct($args);
            }
            return ['error' => 'Unknown tool ' . $name];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /** JSON for the model, kept under ~14k characters. */
    private function _json_for_model($out) {
        $j = json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (mb_strlen($j) > 14000) {
            $j = json_encode(['truncated' => true, 'note' => 'Result too large — narrow the query (fewer columns / rows or aggregate).',
                              'partial' => mb_substr($j, 0, 12000)], JSON_UNESCAPED_UNICODE);
        }
        return $j;
    }

    // ── read tools ──

    private function _tool_describe(array $tables) {
        $db = $this->db->database;
        if (!$tables) {
            $rows = $this->db->query('SELECT TABLE_NAME t, TABLE_ROWS n FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME', [$db])->result();
            $this->_step('table', 'Listed database tables');
            $out = [];
            foreach ($rows as $r) if (!in_array($r->t, self::SQL_BLOCKED_TABLES, true)) $out[$r->t] = (int) $r->n;
            return ['tables' => $out];
        }
        $out = [];
        foreach (array_slice($tables, 0, 10) as $t) {
            $t = preg_replace('/[^a-z0-9_]/i', '', (string) $t);
            if (in_array($t, self::SQL_BLOCKED_TABLES, true)) { $out[$t] = 'not accessible'; continue; }
            $cols = $this->db->query('SELECT COLUMN_NAME c, COLUMN_TYPE ty, IS_NULLABLE nl, COLUMN_KEY k, COLUMN_DEFAULT d FROM information_schema.COLUMNS
                                      WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION', [$db, $t])->result();
            if (!$cols) { $out[$t] = 'table not found'; continue; }
            $out[$t] = array_map(function ($c) {
                return $c->c . ' ' . $c->ty . ($c->k === 'PRI' ? ' PK' : ($c->k ? ' ' . $c->k : '')) . ($c->nl === 'YES' ? ' NULL' : '')
                     . ($c->d !== null ? ' default ' . $c->d : '');
            }, $cols);
        }
        $this->_step('table', 'Read table structure', implode(', ', array_keys($out)));
        return $out;
    }

    /** Read-only guard. Returns [normalised query, error|null]. */
    private function _sql_guard($q) {
        $q = trim(preg_replace('/;\s*$/', '', trim((string) $q)));
        if ($q === '') return [$q, 'Empty query.'];
        if (strpos($q, ';') !== false) return [$q, 'Only one statement is allowed.'];
        if (preg_match('#(--|/\*|\#)#', $q)) return [$q, 'Comments are not allowed in queries.'];
        if (!preg_match('/^\s*(select|with|show|describe|desc|explain)\b/i', $q)) return [$q, 'Only read-only SELECT / SHOW / DESCRIBE queries are allowed. Use the write tools to change data.'];
        if (preg_match('/\b(insert|update|delete|drop|alter|create|truncate|grant|revoke|rename|lock|unlock|call|handler|load_file|outfile|dumpfile|sleep|benchmark|get_lock|set)\b/i', $q)) {
            return [$q, 'This query contains a keyword that is not allowed in read-only mode.'];
        }
        foreach (self::SQL_BLOCKED_TABLES as $t) {
            if (preg_match('/\b' . $t . '\b/i', $q)) return [$q, 'Table ' . $t . ' is not accessible.'];
        }
        return [$q, null];
    }

    /** Run a guarded query in a read-only transaction. Returns [rows (secrets hidden), error|null, ms, more_rows]. */
    private function _sql_run($q, $limit) {
        $is_select = (bool) preg_match('/^\s*(select|with)\b/i', $q);
        if ($is_select && !preg_match('/\blimit\s+\d+(\s*(,|offset)\s*\d+)?\s*$/i', $q)) {
            $q .= ' LIMIT ' . ($limit + 1);
        }
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $maria = stripos($this->db->version(), 'mariadb') !== false;
        $t0 = microtime(true);
        try {
            if (!$maria) { try { $this->db->simple_query('SET SESSION max_execution_time = 10000'); } catch (Exception $e) {} }
            $this->db->simple_query('START TRANSACTION READ ONLY');
            // 3rd arg TRUE: CI treats a query starting with SET as a write and would return TRUE instead of rows
            $res = $this->db->query(($maria && $is_select ? 'SET STATEMENT max_statement_time = 10 FOR ' : '') . $q, false, $is_select ? true : null);
            $err = $this->db->error();
        } catch (Exception $e) {   // PHP 8.1+ mysqli throws instead of returning false
            $res = false;
            $err = ['code' => $e->getCode() ?: 1, 'message' => $e->getMessage()];
        } finally {
            try { $this->db->simple_query('ROLLBACK'); } catch (Exception $e) {}
            $this->db->db_debug = $debug;
        }
        $ms = round((microtime(true) - $t0) * 1000);
        if (!$res || !empty($err['code'])) return [[], 'SQL error: ' . ($err['message'] ?? 'query failed'), $ms, false];

        $rows = is_object($res) ? $res->result_array() : [];
        $more = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        foreach ($rows as &$r) {   // never hand secrets to the model or the export
            foreach ($r as $k => &$v) {
                if (preg_match('/pass|token|secret|otp|backup_code|api_key/i', $k)) $v = '[hidden]';
            }
            unset($v);
            if (isset($r['setting_key'], $r['setting_value']) && preg_match('/key|pass|secret|token/i', $r['setting_key'])) $r['setting_value'] = '[hidden]';
        }
        unset($r);
        return [$rows, null, $ms, $more];
    }

    /**
     * run_sql / get_report: the admin gets the rows as a table card (with Excel export); the model only gets
     * the first rows — it is told not to retype them, which saves most of the output tokens.
     */
    private function _tool_sql($q, $purpose) {
        list($q, $err) = $this->_sql_guard($q);
        if ($err) {
            $this->_step('database', ($purpose ?: 'Database query') . ' — rejected', $q . "\n→ " . $err);
            return ['error' => $err];
        }
        list($rows, $err, $ms, $more) = $this->_sql_run($q, 1000);
        if ($err) {
            $this->_step('database', ($purpose ?: 'Database query') . ' — failed', $q . "\n→ " . $err);
            return ['error' => $err];
        }
        $cols = $rows ? array_keys($rows[0]) : [];
        $this->_step('database', ($purpose ?: 'Database query') . ' — ' . count($rows) . ($more ? '+' : '') . ' row(s), ' . $ms . ' ms', $q);

        // Exact totals of money / count columns over ALL rows, so the model never adds up a partial list
        $totals = [];
        if (count($rows) > 1) {
            foreach ($cols as $col) {
                if (!preg_match('/amount|due|balance|outstanding|collected|total|principal|interest|paid|fine|payments|deposits|unmapped|emis|months|entries/i', $col)) continue;
                $sum = 0; $ok = true;
                foreach ($rows as $r) { if ($r[$col] === null || $r[$col] === '') continue; if (!is_numeric($r[$col])) { $ok = false; break; } $sum += $r[$col]; }
                if ($ok) $totals[$col] = round($sum, 2);
            }
        }

        // Table card for the admin (single-value answers don't need one)
        if (count($rows) > 1 || count($cols) > 2) {
            $token = bin2hex(random_bytes(6));
            $ex = $this->session->userdata('ai_exports');
            $ex = is_array($ex) ? $ex : [];
            $ex[$token] = ['sql' => $q, 'title' => $purpose ?: 'Query result'];
            $this->session->set_userdata('ai_exports', array_slice($ex, -20, null, true));
            $this->run['cards'][] = ['type' => 'data_table', 'title' => $purpose ?: 'Query result', 'columns' => $cols,
                                     'rows' => array_map(function ($r) { return array_map(function ($v) {
                                         return is_string($v) && mb_strlen($v) > 120 ? mb_substr($v, 0, 120) . '…' : $v; }, array_values($r)); }, array_slice($rows, 0, 100)),
                                     'total_rows' => count($rows) . ($more ? '+' : ''), 'totals' => $totals,
                                     'more' => $more || count($rows) > 100, 'export' => $token];
        }

        $model_rows = array_slice($rows, 0, 25);
        foreach ($model_rows as &$r) foreach ($r as &$v) if (is_string($v) && mb_strlen($v) > 80) $v = mb_substr($v, 0, 80) . '…';
        unset($r, $v);
        return ['title' => $purpose, 'row_count' => count($rows) . ($more ? '+ (more than 1000)' : ''), 'totals_of_all_rows' => $totals ?: null,
                'shown_to_admin_as_table' => count($rows) > 1 || count($cols) > 2,
                'columns' => $cols, 'rows' => array_map('array_values', $model_rows),
                'note' => count($rows) > 25 ? 'Only the first ' . count($model_rows) . ' of ' . count($rows) . ' rows are included here. Use row_count and totals_of_all_rows for totals — never add up these rows.' : null];
    }

    /** Ready-made reports: one tool call instead of describe → SQL → retry. Same read-only runner as run_sql. */
    private function _tool_report(array $a) {
        $name = (string) ($a['report'] ?? '');
        $from = $this->_valid_date($a['from'] ?? '') ?: date('Y-m-01');
        $to   = $this->_valid_date($a['to'] ?? '') ?: date('Y-m-d');
        if ($from > $to) list($from, $to) = [$to, $from];
        $days = max(1, min(90, (int) ($a['days'] ?? 7)));
        $f = $this->db->escape($from);
        $t = $this->db->escape($to);
        $mname = "TRIM(CONCAT_WS(' ', m.first_name, NULLIF(m.middle_name, ''), m.last_name))";
        $overdue = "l.status IN ('active','npa') AND li.status IN ('pending','overdue','partial') AND li.due_date < CURDATE()";

        if (!in_array($name, ['summary', 'overdue', 'collections', 'dues', 'savings_dues', 'applications', 'unmapped_bank', 'pending_fines'], true)) {
            $res = $this->_tool_report_center($name, $from, $to);
            if ($res !== null) return $res;
        }

        switch ($name) {
            case 'summary':
                $title = "Society summary (collections {$from} to {$to})";
                $sql = "SELECT
                    (SELECT COUNT(*) FROM members WHERE status = 'active' AND deleted_at IS NULL) active_members,
                    (SELECT COUNT(*) FROM loans WHERE status IN ('active','npa')) active_loans,
                    (SELECT ROUND(COALESCE(SUM(outstanding_principal), 0), 2) FROM loans WHERE status IN ('active','npa')) loan_outstanding,
                    (SELECT COUNT(*) FROM loan_installments li JOIN loans l ON l.id = li.loan_id WHERE {$overdue}) overdue_emis,
                    (SELECT ROUND(COALESCE(SUM(li.emi_amount - li.total_paid), 0), 2) FROM loan_installments li JOIN loans l ON l.id = li.loan_id WHERE {$overdue}) overdue_amount,
                    (SELECT ROUND(COALESCE(SUM(total_amount), 0), 2) FROM loan_payments WHERE is_reversed = 0 AND payment_date BETWEEN {$f} AND {$t}) loan_collected,
                    (SELECT ROUND(COALESCE(SUM(amount), 0), 2) FROM savings_transactions WHERE is_reversed = 0 AND transaction_type = 'deposit' AND transaction_date BETWEEN {$f} AND {$t}) savings_collected,
                    (SELECT ROUND(COALESCE(SUM(current_balance), 0), 2) FROM savings_accounts WHERE status = 'active') savings_balance,
                    (SELECT ROUND(COALESCE(SUM(balance_amount), 0), 2) FROM fines WHERE status IN ('pending','partial')) pending_fines,
                    (SELECT COUNT(*) FROM loan_applications WHERE status IN ('pending','under_review','guarantor_pending','member_review','member_approved','needs_revision')) open_applications,
                    (SELECT COUNT(*) FROM bank_transactions WHERE mapping_status IN ('unmapped','partial')) unmapped_bank_entries";
                break;
            case 'overdue':
                $title = 'Overdue EMIs by loan';
                $sql = "SELECT m.member_code, {$mname} member, m.phone, l.loan_number, COUNT(*) overdue_emis,
                               ROUND(SUM(li.emi_amount - li.total_paid), 2) overdue_amount, MIN(li.due_date) oldest_due,
                               DATEDIFF(CURDATE(), MIN(li.due_date)) days_overdue
                        FROM loan_installments li JOIN loans l ON l.id = li.loan_id JOIN members m ON m.id = l.member_id
                        WHERE {$overdue} GROUP BY l.id ORDER BY overdue_amount DESC";
                break;
            case 'collections':
                $title = "Collections {$from} to {$to}";
                $sql = "SELECT d date, ROUND(SUM(loan), 2) loan_payments, ROUND(SUM(sav), 2) savings_deposits, ROUND(SUM(fine), 2) fines,
                               ROUND(SUM(loan + sav + fine), 2) total, SUM(n) entries
                        FROM (SELECT payment_date d, total_amount loan, 0 sav, 0 fine, 1 n FROM loan_payments WHERE is_reversed = 0 AND payment_date BETWEEN {$f} AND {$t}
                              UNION ALL SELECT transaction_date, 0, amount, 0, 1 FROM savings_transactions WHERE is_reversed = 0 AND transaction_type = 'deposit' AND transaction_date BETWEEN {$f} AND {$t}
                              UNION ALL SELECT payment_date, 0, 0, paid_amount, 1 FROM fines WHERE paid_amount > 0 AND payment_date BETWEEN {$f} AND {$t}) x
                        GROUP BY d ORDER BY d";
                break;
            case 'dues':
                $title = "EMIs due in the next {$days} day(s)";
                $sql = "SELECT li.due_date, m.member_code, {$mname} member, m.phone, l.loan_number, li.installment_number emi_no,
                               ROUND(li.emi_amount - li.total_paid, 2) due
                        FROM loan_installments li JOIN loans l ON l.id = li.loan_id JOIN members m ON m.id = l.member_id
                        WHERE l.status IN ('active','npa') AND li.status IN ('pending','upcoming','partial','overdue')
                          AND li.due_date BETWEEN CURDATE() AND CURDATE() + INTERVAL {$days} DAY
                        ORDER BY li.due_date, member";
                break;
            case 'savings_dues':
                $title = 'Savings dues (unpaid months up to today)';
                $sql = "SELECT m.member_code, {$mname} member, m.phone, sa.account_number, COUNT(*) months,
                               ROUND(SUM(ss.due_amount - COALESCE(ss.paid_amount, 0)), 2) due, MIN(ss.due_date) oldest_due
                        FROM savings_schedule ss JOIN savings_accounts sa ON sa.id = ss.savings_account_id JOIN members m ON m.id = sa.member_id
                        WHERE sa.status = 'active' AND ss.status IN ('pending','partial','overdue') AND ss.due_date <= CURDATE()
                        GROUP BY sa.id ORDER BY due DESC";
                break;
            case 'applications':
                $title = 'Open loan applications';
                $sql = "SELECT la.application_number, m.member_code, {$mname} member, la.requested_amount, la.requested_tenure_months tenure,
                               la.purpose, la.status, la.application_date
                        FROM loan_applications la JOIN members m ON m.id = la.member_id
                        WHERE la.status IN ('pending','under_review','guarantor_pending','member_review','member_approved','needs_revision')
                        ORDER BY la.application_date";
                break;
            case 'unmapped_bank':
                $title = 'Unmapped bank transactions';
                $sql = "SELECT bt.id, bt.transaction_date, ba.account_name bank, bt.transaction_type, bt.amount,
                               COALESCE(bt.unmapped_amount, bt.amount) unmapped, bt.description, bt.reference_number, bt.mapping_status
                        FROM bank_transactions bt LEFT JOIN bank_accounts ba ON ba.id = bt.bank_account_id
                        WHERE bt.mapping_status IN ('unmapped','partial') ORDER BY bt.transaction_date DESC";
                break;
            case 'pending_fines':
                $title = 'Pending fines';
                $sql = "SELECT f.id, f.fine_code, m.member_code, {$mname} member, f.fine_type, f.fine_date, f.fine_amount, f.balance_amount, f.status, f.remarks
                        FROM fines f JOIN members m ON m.id = f.member_id WHERE f.status IN ('pending','partial') ORDER BY f.fine_date";
                break;
            default:
                return ['error' => 'Unknown report. Use one of: ' . str_replace("'", '', "'summary', 'overdue', 'collections', 'dues', 'savings_dues', 'applications', 'unmapped_bank', 'pending_fines', 'member_dues', 'portfolio', 'fine_register', 'income', 'day_book', 'savings_register', 'collection_efficiency', 'bank_reconciliation', 'kyc_pending', 'fine_preview'") . '.'];
        }
        return $this->_tool_sql(preg_replace('/\s+/', ' ', $sql), $title);
    }

    /**
     * Report Center reports (same queries as admin/reports/*). Shown as a table card with
     * "Open report" and the styled Excel download of that report page.
     */
    private function _tool_report_center($name, $from, $to) {
        $this->load->model('Report_model');
        $R = $this->Report_model;
        $q = http_build_query(['from_date' => $from, 'to_date' => $to]);
        switch ($name) {
            case 'member_dues':
                $rows = array_map(function ($r) { return ['member_code' => $r['member_code'], 'member' => $r['member_name'], 'phone' => $r['phone'],
                    'emis_due' => $r['emi_count'], 'emi_due' => $r['emi_due'], 'savings_due' => $r['savings_due'], 'fine_due' => $r['fine_due'],
                    'total_due' => $r['total_due'], 'oldest_due' => $r['oldest_due'], 'check' => $r['flag']]; }, $R->member_dues(date('Y-m-d')));
                return $this->_tool_rows('Member dues (EMI + savings + fines) as on today', $rows, 'admin/reports/member_dues', '');
            case 'portfolio':
                $rows = array_map(function ($r) { return ['product' => $r['product_name'], 'loans' => $r['loans'], 'disbursed' => $r['disbursed'],
                    'outstanding_principal' => $r['outstanding_principal'], 'overdue_amount' => $r['overdue_amount'],
                    'par_30_amount' => $r['par_30'], 'par_30_pct' => $r['par_30_pct'], 'par_90_amount' => $r['par_90'], 'par_90_pct' => $r['par_90_pct']]; }, $R->loan_portfolio());
                return $this->_tool_rows('Loan portfolio by product (PAR 30 / 90)', $rows, 'admin/reports/portfolio', '');
            case 'fine_register':
                $rows = array_map(function ($r) { return ['fine_code' => $r['fine_code'], 'fine_date' => $r['fine_date'], 'member_code' => $r['member_code'],
                    'member' => $r['member_name'], 'type' => $r['fine_type'], 'reference' => $r['reference_no'], 'days_late' => $r['days_late'],
                    'fine_amount' => $r['fine_amount'], 'paid_amount' => $r['paid_amount'], 'waived_amount' => $r['waived_amount'],
                    'balance_amount' => $r['balance_amount'], 'status' => $r['status']]; }, $R->fine_register($from, $to));
                return $this->_tool_rows("Fine register {$from} to {$to}", $rows, 'admin/reports/fines', $q);
            case 'income':
                $rows = $R->income_by_month($from, $to);
                return $this->_tool_rows("Income by month {$from} to {$to} (from ledger)", $rows, 'admin/reports/income', $q);
            case 'day_book':
                $d = $R->day_book($from, $to);
                $rows = array_map(function ($r) { return ['date' => $r['date'], 'emi_in' => $r['emi'], 'savings_in' => $r['savings'], 'fines_in' => $r['fines'],
                    'fees_in' => $r['fees'], 'total_in' => $r['total_in'], 'disbursed_out' => $r['disbursed'], 'withdrawal_out' => $r['withdrawal'],
                    'expense_out' => $r['expense'], 'total_out' => $r['total_out'], 'closing_balance' => $r['closing']]; }, $d['rows']);
                return $this->_tool_rows("Day book {$from} to {$to} — opening ₹" . number_format($d['opening'], 2) . ', closing ₹' . number_format($d['closing'], 2),
                                         $rows, 'admin/reports/day_book', $q);
            case 'savings_register':
                $rows = array_map(function ($r) { return ['account' => $r['account_number'], 'member_code' => $r['member_code'], 'member' => $r['member_name'],
                    'monthly' => $r['monthly_amount'], 'deposited' => $r['total_deposited'], 'withdrawn' => $r['withdrawn'], 'balance' => $r['current_balance'],
                    'last_deposit' => $r['last_deposit'], 'months_due' => $r['months_due'], 'amount_due' => $r['amount_due']]; }, $R->savings_register('active'));
                return $this->_tool_rows('Savings register (active accounts)', $rows, 'admin/reports/savings_register', '');
            case 'collection_efficiency':
                $rows = array_map(function ($r) { return ['month' => $r['month'], 'emis' => $r['emis'], 'demand' => $r['demand'], 'collected' => $r['collected'],
                    'pending' => $r['pending'], 'efficiency_pct' => $r['efficiency'], 'on_time_pct' => $r['on_time_pct']]; }, $R->collection_efficiency(12));
                return $this->_tool_rows('Collection efficiency — last 12 months', $rows, 'admin/reports/collection_efficiency', '');
            case 'bank_reconciliation':
                $b = $R->bank_reconciliation_report($from, $to);
                $rows = array_map(function ($r) { return ['date' => $r['transaction_date'], 'direction' => $r['transaction_type'], 'amount' => $r['amount'],
                    'unmapped' => $r['pending_amount'], 'description' => $r['description'], 'detected_member' => $r['member_code']]; }, $b['unmapped']);
                return $this->_tool_rows("Bank reconciliation {$from} to {$to} — books bank balance ₹" . number_format($b['gl_bank'], 2) . '; lines not yet mapped',
                                         $rows, 'admin/reports/bank-reconciliation', $q);
            case 'kyc_pending':
                $rows = array_map(function ($m) { return ['member_code' => $m->member_code, 'member' => $m->member_name, 'phone' => $m->phone,
                    'items_missing' => $m->pending_documents, 'missing' => $m->missing]; }, $R->kyc_pending_list());
                return $this->_tool_rows('KYC pending members', $rows, 'admin/reports/kyc-pending', '');
            case 'fine_preview':
                // Full auto-fine job inside a rolled-back transaction: nothing is saved
                $this->Fine_model->run_late_fine_job((int) $this->session->userdata('admin_id'), true);
                $s = $this->Fine_model->last_run;
                $rows = array_map(function ($i) { return ['action' => $i['action'] === 'created' ? 'new fine' : 'recalculated', 'reference' => $i['reference'],
                    'member_id' => $i['member_id'], 'due_date' => $i['due_date'], 'days_late' => $i['days_late'], 'current_fine' => $i['old'],
                    'new_fine_amount' => $i['amount']]; }, $s['items'] ?? []);
                $res = $this->_tool_rows(sprintf('Auto-fine preview (nothing saved): %d new, %d recalculated, ₹%s added', $s['created'] ?? 0, $s['updated'] ?? 0,
                                         number_format($s['amount_change'] ?? 0, 2)), $rows, 'admin/fines/preview_auto_fines', '');
                $res['note'] = 'Dry run only. The admin applies it with Fines → Rules → Run Auto Fine Job. Rules start ' . ($s['rules_start'] ?? 'n/a')
                             . '; EMIs due before that are never fined. ' . ($res['note'] ?? '');
                return $res;
        }
        return null;
    }

    /** Rows from a model (not SQL) → table card with "Open report" + styled Excel of the report page. */
    private function _tool_rows($title, array $rows, $page, $query) {
        $rows = array_values(array_map(function ($r) { return (array) $r; }, $rows));
        $cols = $rows ? array_keys($rows[0]) : [];
        $this->_step('database', $title . ' — ' . count($rows) . ' row(s)', $page);
        $totals = [];
        if (count($rows) > 1) {
            foreach ($cols as $col) {
                if (preg_match('/pct|_date$|^date$|month|code|phone/i', $col)) continue;
                if (!preg_match('/amount|due|balance|outstanding|collected|total|principal|interest|paid|fine|in$|out$|deposited|withdrawn|demand|pending|unmapped|emis|loans|interest|processing|membership|expenses|net|disbursed|par_/i', $col)) continue;
                $sum = 0; $ok = true;
                foreach ($rows as $r) { if ($r[$col] === null || $r[$col] === '') continue; if (!is_numeric($r[$col])) { $ok = false; break; } $sum += $r[$col]; }
                if ($ok) $totals[$col] = round($sum, 2);
            }
        }
        $sep = strpos($page, '?') === false ? '?' : '&';
        $open = site_url($page) . ($query !== '' ? $sep . $query : '');
        $xls  = $open . (strpos($open, '?') === false ? '?' : '&') . 'export=excel';
        if ($rows) {
            $this->run['cards'][] = ['type' => 'data_table', 'title' => $title, 'columns' => $cols,
                                     'rows' => array_map('array_values', array_slice($rows, 0, 100)),
                                     'total_rows' => count($rows), 'totals' => $totals, 'more' => count($rows) > 100,
                                     'export_url' => $xls, 'open_url' => $open];
        }
        $model_rows = array_slice($rows, 0, 25);
        return ['title' => $title, 'row_count' => count($rows), 'totals_of_all_rows' => $totals ?: null,
                'shown_to_admin_as_table' => (bool) $rows, 'report_page' => $open,
                'columns' => $cols, 'rows' => array_map('array_values', $model_rows),
                'note' => count($rows) > 25 ? 'Only the first 25 of ' . count($rows) . ' rows are included here. Use row_count and totals_of_all_rows for totals.' : null];
    }

    /** GET t = token of a table card. Re-runs its read-only query (up to 5000 rows) and downloads it as Excel. */
    public function export() {
        $ex = $this->session->userdata('ai_exports');
        $e = is_array($ex) ? ($ex[(string) $this->input->get('t')] ?? null) : null;
        if (!$e) show_error('This table has expired — ask the assistant again.', 404);
        list($q, $err) = $this->_sql_guard(preg_replace('/\s+LIMIT\s+\d+\s*$/i', '', $e['sql']));
        $rows = [];
        if (!$err) list($rows, $err) = $this->_sql_run($q, 5000);
        if ($err) show_error(htmlspecialchars($err), 400);

        require_once FCPATH . 'vendor/autoload.php';
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $s = $book->getActiveSheet()->setTitle('Result');
        $s->setCellValue('A1', $e['title'] . ' — ' . date('d/m/Y H:i'));
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        if ($rows) {
            $s->fromArray(array_keys($rows[0]), null, 'A3');
            $s->fromArray(array_map('array_values', $rows), null, 'A4', true);
            $last = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($rows[0]));
            $s->getStyle('A3:' . $last . '3')->getFont()->setBold(true);
            $s->setAutoFilter('A3:' . $last . (3 + count($rows)));
            for ($i = 1; $i <= count($rows[0]); $i++) $s->getColumnDimensionByColumn($i)->setAutoSize(true);
            $s->freezePane('A4');
        }
        $this->log_activity('AI table exported', $e['title'] . ' (' . count($rows) . ' rows)', 'ai_assistant');
        $name = trim(substr(preg_replace('/[^A-Za-z0-9_-]+/', '_', $e['title']), 0, 60), '_');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . ($name ?: 'AI_result') . '_' . date('Ymd') . '.xlsx"');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
        exit;
    }

    private function _tool_member($text) {
        $found = $this->_find_members($text, 6);
        if (!$found) { $this->_step('user', 'Member "' . $text . '" not found'); return ['error' => 'No member matches "' . $text . '".']; }
        if (count($found) > 1) {
            $this->_step('user', 'Searched members: ' . $text, count($found) . ' matches');
            return ['status' => 'multiple_matches', 'members' => array_map(function ($m) {
                return ['id' => (int) $m->id, 'code' => $m->member_code, 'name' => $this->_member_name($m), 'phone' => $m->phone, 'status' => $m->status];
            }, $found)];
        }
        $m = $this->Member_model->get_by_id($found[0]->id);
        $id = (int) $m->id;
        $loans = $this->db->select('id, loan_number, status, principal_amount, interest_rate, tenure_months, emi_amount, outstanding_principal, outstanding_interest, disbursement_date, closure_date')
            ->where('member_id', $id)->order_by('id', 'DESC')->limit(10)->get('loans')->result_array();
        foreach ($loans as &$l) {
            if (!in_array($l['status'], ['active', 'npa'], true)) continue;
            $next = $this->db->select('installment_number, due_date, emi_amount, total_paid, status')->where('loan_id', $l['id'])
                ->where_in('status', ['pending', 'overdue', 'partial'])->order_by('installment_number', 'ASC')->limit(1)->get('loan_installments')->row_array();
            $l['next_installment'] = $next;
            $l['overdue_emis'] = $this->db->where('loan_id', $l['id'])->where_in('status', ['pending', 'overdue', 'partial'])
                ->where('due_date <', date('Y-m-d'))->count_all_results('loan_installments');
        }
        unset($l);
        $out = [
            'member' => ['id' => $id, 'code' => $m->member_code, 'name' => $this->_member_name($m), 'phone' => $m->phone, 'email' => $m->email,
                         'status' => $m->status, 'join_date' => $m->join_date ?? null, 'city' => $m->city ?? null,
                         'address' => trim(($m->address_line1 ?? '') . ' ' . ($m->address_line2 ?? '')), 'nominee' => $m->nominee_name ?? null,
                         'bank' => trim(($m->bank_name ?? '') . ' ' . ($m->bank_account_number ?? '') . ' ' . ($m->bank_ifsc ?? ''))],
            'loans' => $loans,
            'savings_accounts' => $this->_account_options($id),
            'unpaid_fines' => $this->db->select('id, fine_code, fine_date, fine_amount, balance_amount, status, remarks')->where('member_id', $id)
                ->where_in('status', ['pending', 'partial'])->order_by('id', 'DESC')->limit(10)->get('fines')->result_array(),
            'recent_loan_payments' => $this->db->select('lp.id, lp.payment_date, lp.total_amount, lp.payment_type, lp.payment_mode, l.loan_number')
                ->from('loan_payments lp')->join('loans l', 'l.id = lp.loan_id')->where('l.member_id', $id)->where('lp.is_reversed', 0)
                ->order_by('lp.payment_date', 'DESC')->limit(5)->get()->result_array(),
            'recent_savings_transactions' => $this->db->select('st.id, st.transaction_date, st.transaction_type, st.amount, st.payment_mode, sa.account_number')
                ->from('savings_transactions st')->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('sa.member_id', $id)
                ->where('st.is_reversed', 0)->order_by('st.id', 'DESC')->limit(5)->get()->result_array(),
        ];
        $this->_step('user', 'Looked up member ' . $m->member_code . ' ' . $this->_member_name($m));
        return $out;
    }

    /** Find a loan by number / id, or the single active loan of a member. Returns [loan|null, error|null]. */
    private function _find_loan($loan_text, $member_text = '') {
        $loan_text = trim($this->_latin_digits((string) $loan_text));
        if ($loan_text !== '') {
            $l = $this->db->where('loan_number', $loan_text)->get('loans')->row();
            if (!$l && preg_match('/^\d{1,7}$/', $loan_text)) $l = $this->db->where('id', (int) $loan_text)->get('loans')->row();
            if (!$l) {
                $like = $this->db->like('loan_number', $loan_text)->limit(3)->get('loans')->result();
                if (count($like) === 1) $l = $like[0];
                elseif ($like) return [null, 'Several loans match "' . $loan_text . '": ' . implode(', ', array_column($like, 'loan_number'))];
            }
            return $l ? [$l, null] : [null, 'Loan "' . $loan_text . '" not found.'];
        }
        if (trim((string) $member_text) === '') return [null, 'Which loan? Give the loan number or member.'];
        $ms = $this->_find_members($member_text, 3);
        if (count($ms) !== 1) return [null, $ms ? 'Several members match "' . $member_text . '".' : 'Member "' . $member_text . '" not found.'];
        $loans = $this->db->where('member_id', $ms[0]->id)->where_in('status', ['active', 'npa'])->get('loans')->result();
        if (count($loans) === 1) return [$loans[0], null];
        return [null, $loans ? 'Member has several active loans: ' . implode(', ', array_column($loans, 'loan_number')) . ' — which one?'
                             : $this->_member_name($ms[0]) . ' has no active loan.'];
    }

    private function _tool_loan(array $args) {
        list($l, $err) = $this->_find_loan($args['loan'] ?? '', $args['member'] ?? '');
        if (!$l) return ['error' => $err];
        $m = $this->Member_model->get_by_id($l->member_id);
        $keys = ['id', 'loan_number', 'status', 'principal_amount', 'interest_rate', 'interest_type', 'tenure_months', 'emi_amount', 'total_interest',
                 'total_payable', 'outstanding_principal', 'outstanding_interest', 'outstanding_fine', 'total_amount_paid', 'total_principal_paid',
                 'total_interest_paid', 'disbursement_date', 'first_emi_date', 'last_emi_date', 'closure_date', 'closure_type', 'closure_remarks'];
        $out = ['loan' => array_intersect_key((array) $l, array_flip($keys)),
                'member' => $m ? ['id' => (int) $m->id, 'code' => $m->member_code, 'name' => $this->_member_name($m), 'phone' => $m->phone, 'email' => $m->email] : null];
        $out['installments_by_status'] = $this->db->select('status, COUNT(*) n, SUM(emi_amount - total_paid) due', false)->where('loan_id', $l->id)
            ->group_by('status')->get('loan_installments')->result_array();
        $out['next_unpaid_installments'] = $this->db->select('id, installment_number, due_date, principal_amount, interest_amount, emi_amount, total_paid, status')
            ->where('loan_id', $l->id)->where_in('status', ['pending', 'overdue', 'partial', 'upcoming'])->order_by('installment_number', 'ASC')->limit(4)
            ->get('loan_installments')->result_array();
        $out['recent_payments'] = $this->db->select('id, payment_code, payment_date, payment_type, total_amount, principal_component, interest_component, fine_component, payment_mode, is_reversed')
            ->where('loan_id', $l->id)->order_by('id', 'DESC')->limit(5)->get('loan_payments')->result_array();
        $out['pending_foreclosure_request'] = $this->db->where('loan_id', $l->id)->where('status', 'pending')->get('loan_foreclosure_requests')->row_array();
        $out['disbursement'] = ['in_parts' => !empty($l->is_tranche), 'sanctioned' => (float) $l->principal_amount,
                                'disbursed' => $this->Loan_model->disbursed_amount($l), 'left_to_disburse' => $this->Loan_model->pending_disbursement($l),
                                'parts' => $this->db->select('tranche_no, disbursement_date, disbursement_amount, net_amount, status, bank_transaction_id IS NOT NULL AS bank_mapped', false)
                                                     ->where('loan_id', $l->id)->order_by('tranche_no')->get('disbursement_tracking')->result_array()];
        if (in_array($l->status, ['active', 'npa'], true)) {
            $reg = $this->Loan_model->calculate_foreclosure_amount($l->id);
            $fc  = $this->Loan_model->calculate_force_close_amount($l->id);
            unset($reg['pending_fines_list'], $fc['pending_fines_list']);
            $out['foreclosure_quote_regular'] = $reg;
            $out['force_close_quote'] = $fc;
        }
        $this->_step('file-invoice', 'Looked up loan ' . $l->loan_number);
        return $out;
    }

    // ── write tools ──

    /** Pending proposals (token => card) in the session. */
    private function _pending() { $p = $this->session->userdata(self::PENDING_KEY); return is_array($p) ? $p : []; }
    private function _add_pending(array &$card, array $server_only = []) {
        $card['token'] = bin2hex(random_bytes(8));
        $p = $this->_pending();
        $p[$card['token']] = $card + $server_only + ['created' => time()];
        $this->session->set_userdata(self::PENDING_KEY, array_slice($p, -25, null, true));
    }

    /** Tool result for a card that waits for the admin. */
    private function _awaiting(array $card, array $extra = []) {
        return ['status' => 'awaiting_admin_confirmation', 'note' => 'A confirmation card is shown to the admin. Nothing is saved until they press Confirm.',
                'warnings' => array_values($card['warnings'] ?? [])] + $extra;
    }

    /** Payment / deposit / fine / member edit / status / reverse — the editable cards. */
    private function _tool_entry($name, array $args) {
        $num = function ($v) { return $v === null ? null : $this->_latin_digits((string) $v); };
        $a = ['member' => $args['member'] ?? '', 'amount' => $num($args['amount'] ?? 0), 'date' => $args['date'] ?? '', 'mode' => $args['mode'] ?? 'cash',
              'reference' => $args['reference'] ?? '', 'remarks' => $args['remarks'] ?? ($args['reason'] ?? '')];
        switch ($name) {
            case 'record_loan_payment':    $a += ['type' => 'loan_payment', 'payment_type' => $args['payment_type'] ?? 'emi']; break;
            case 'record_savings_deposit': $a += ['type' => 'savings_deposit', 'deposit_type' => $args['deposit_type'] ?? 'regular']; break;
            case 'add_fine':               $a += ['type' => 'fine']; break;
            case 'change_member_status':   $a += ['type' => 'member_status', 'status' => $args['status'] ?? '']; break;
            case 'update_member':
                $ch = [];
                foreach ((array) ($args['changes'] ?? []) as $k => $c) {
                    if (is_array($c)) $ch[(string) ($c['field'] ?? '')] = (string) ($c['value'] ?? '');
                    else $ch[(string) $k] = (string) $c;   // tolerate {"phone": "…"}
                }
                $a = ['type' => 'update_member', 'member' => $args['member'] ?? '', 'changes' => $ch];
                break;
            case 'reverse_entry':
                $a = ['type' => 'reverse_entry', 'entry' => $args['entry'] ?? '', 'kind' => $args['kind'] ?? '', 'record_id' => (int) ($args['record_id'] ?? 0),
                      'member' => $args['member'] ?? '', 'amount' => $num($args['amount'] ?? 0), 'date' => $args['date'] ?? '', 'remarks' => $args['reason'] ?? ''];
                break;
        }

        $card = $this->_resolve_action($a, [], $this->run['conv']['entries']);
        if (!$card) return ['error' => 'Could not understand the request.'];

        // A specific loan / account named by the model
        if ($card['type'] === 'loan_payment' && !empty($args['loan_number']) && !empty($card['loans'])) {
            foreach ($card['loans'] as $l) if (strcasecmp($l['loan_number'], trim($args['loan_number'])) === 0) $card['loan_id'] = $l['id'];
            if ($card['loan_id']) $card['warnings'] = array_values(array_filter($card['warnings'], function ($w) { return strpos($w, 'which one') === false; }));
        }
        if ($card['type'] === 'savings_deposit' && !empty($args['account_number']) && !empty($card['accounts'])) {
            foreach ($card['accounts'] as $x) if (strcasecmp($x['account_number'], trim($args['account_number'])) === 0) $card['savings_account_id'] = $x['id'];
            if ($card['savings_account_id']) $card['warnings'] = array_values(array_filter($card['warnings'], function ($w) { return strpos($w, 'which one') === false; }));
        }

        $titles = ['loan_payment' => 'Loan payment', 'savings_deposit' => 'Savings deposit', 'fine' => 'Fine', 'member_status' => 'Member status',
                   'update_member' => 'Update member', 'reverse_entry' => 'Reverse entry'];
        $label = $titles[$card['type']] . (isset($card['member_text']) && $card['member_text'] !== '' ? ' — ' . $card['member_text'] : '');

        // Nothing to act on → let the model ask
        if ($card['type'] === 'reverse_entry' && empty($card['target_id'])) {
            $this->_step('undo', $label . ' — record not found');
            return ['error' => implode(' ', $card['warnings']) ?: 'Record not found.'];
        }
        if ($card['type'] === 'reverse_entry' && $card['kind'] === 'loan_payment') {
            $this->_step('undo', $label . ' — not supported');
            return ['error' => 'Loan payments cannot be reversed from chat (it changes EMIs and interest). Offer open_page or correct_record if a single field is wrong.'];
        }
        if (in_array($card['type'], ['loan_payment', 'savings_deposit', 'fine', 'member_status', 'update_member'], true)
            && empty($card['member_id']) && empty($card['member_candidates'])) {
            $this->_step('user', $label . ' — member not found');
            return ['error' => implode(' ', $card['warnings'])];
        }
        if ($card['type'] === 'member_status' && ($card['remarks'] ?? '') === '') {
            $card['remarks'] = mb_substr('AI assistant: ' . $this->run['message'], 0, 255);
        }

        $ready = $this->run['auto'] && empty($card['warnings']) && !empty($card['member_id'])
            && ($card['type'] !== 'loan_payment'    || !empty($card['loan_id']))
            && ($card['type'] !== 'savings_deposit' || !empty($card['savings_account_id']))
            && ($card['type'] !== 'reverse_entry'   || !empty($card['from_chat']) || !empty($card['unique']))
            && ($card['type'] !== 'update_member'   || !empty($card['changes']));
        if ($ready) {
            $r = $this->_perform($card);
            if (!$r['ok']) {
                $this->_step('times-circle', $label . ' — failed', $r['message']);
                return ['status' => 'failed', 'error' => $r['message']];
            }
            $card['executed'] = true;
            $card['result'] = $r;
            $ref = $this->_remember_entry($this->run['conv'], $card);
            $this->run['cards'][] = $card;
            $this->_step('check-circle', $label . ' — saved', $r['message']);
            return ['status' => 'executed', 'message' => $r['message'], 'entry' => $ref];
        }

        $this->_add_pending($card);
        $this->run['cards'][] = $card;
        $this->_step('hand-paper', $label . ' — waiting for Confirm');
        $extra = [];
        if (!empty($card['member_candidates']) && count($card['member_candidates']) > 1) $extra['member_options'] = array_column($card['member_candidates'], 'label');
        if (!empty($card['loans']) && empty($card['loan_id'])) $extra['loan_options'] = array_column($card['loans'], 'loan_number');
        if (!empty($card['accounts']) && empty($card['savings_account_id'])) $extra['account_options'] = array_column($card['accounts'], 'account_number');
        return $this->_awaiting($card, $extra);
    }

    private function _tool_foreclose(array $args) {
        if (!$this->_can('loans_approve')) return ['error' => 'You do not have permission to close loans (loans_approve).'];
        list($l, $err) = $this->_find_loan($args['loan'] ?? '', $args['member'] ?? '');
        if (!$l) return ['error' => $err];
        if (!in_array($l->status, ['active', 'npa'], true)) return ['error' => 'Loan ' . $l->loan_number . ' is ' . $l->status . ' — only active loans can be closed.'];

        $type = ($args['closure_type'] ?? '') === 'force_close' ? 'force_close' : 'regular';
        $pct  = ($type === 'regular' && isset($args['interest_charge_pct']) && $args['interest_charge_pct'] !== '') ? (float) $args['interest_charge_pct'] : null;
        if ($pct !== null && ($pct < 0 || $pct > 100)) return ['error' => 'Interest charge % must be between 0 and 100.'];
        $q = $type === 'force_close' ? $this->Loan_model->calculate_force_close_amount($l->id) : $this->Loan_model->calculate_foreclosure_amount($l->id, $pct);
        if (!$q) return ['error' => 'Could not calculate the settlement.'];
        $calc  = round((float) $q['total_amount'], 2);
        $final = (float) ($args['final_amount'] ?? 0) > 0 ? round((float) $args['final_amount'], 2) : $calc;
        $m = $this->Member_model->get_by_id($l->member_id);

        $rows = [['Loan', $l->loan_number . ' (' . $l->status . ')'], ['Member', $m ? $m->member_code . ' — ' . $this->_member_name($m) : '#' . $l->member_id],
                 ['Outstanding principal', '₹' . number_format((float) $q['outstanding_principal'], 2)]];
        if ($type === 'force_close') {
            $rows[] = ['Next month interest (charged)', '₹' . number_format((float) $q['next_month_interest'], 2)];
            $rows[] = ['Interest waived', '₹' . number_format((float) $q['waived_interest'], 2)];
            $rows[] = ['Fines waived', '₹' . number_format((float) $q['waived_fines'], 2)];
        } else {
            $rows[] = ['Remaining interest', '₹' . number_format((float) $q['total_interest'], 2)];
            $rows[] = ['Interest charge', number_format((float) $q['interest_charge_pct'], 2) . '% = ₹' . number_format((float) $q['interest_charge'], 2)];
            $rows[] = ['Pending fines', '₹' . number_format((float) $q['pending_fines'], 2)];
        }
        $rows[] = ['Calculated settlement', '₹' . number_format($calc, 2)];
        $rows[] = ['Amount to collect', '₹' . number_format($final, 2)];

        $card = ['type' => 'foreclose_loan', 'title' => $type === 'force_close' ? 'Force close loan' : 'Foreclose loan',
                 'loan_id' => (int) $l->id, 'member_id' => (int) $l->member_id, 'closure_type' => $type, 'interest_charge_pct' => $pct,
                 'final_amount' => $final, 'calculated' => $calc,
                 'payment_mode' => $this->_payment_mode($args['payment_mode'] ?? 'cash'),
                 'payment_date' => $this->_valid_date($args['payment_date'] ?? '') ?: date('Y-m-d'),
                 'reference' => mb_substr((string) ($args['reference'] ?? ''), 0, 100),
                 'remarks' => mb_substr(trim((string) ($args['remarks'] ?? '')), 0, 255) ?: 'Closed via AI assistant',
                 'rows' => $rows, 'warnings' => [], 'danger' => true];
        $card['rows'][] = ['Mode / date', $card['payment_mode'] . ' · ' . $card['payment_date']];
        if (abs($final - $calc) >= 0.01) $card['warnings'][] = 'Amount differs from the calculated settlement (₹' . number_format($calc, 2) . ').';
        if ($card['payment_date'] > date('Y-m-d')) return ['error' => 'Payment date cannot be in the future.'];

        $this->_add_pending($card);
        $this->run['cards'][] = $card;
        $this->_step('lock', $card['title'] . ' ' . $l->loan_number . ' — waiting for Confirm');
        return $this->_awaiting($card, ['quote' => array_diff_key($q, ['pending_fines_list' => 1]), 'amount_to_collect' => $final]);
    }

    private function _tool_email(array $args) {
        $to = trim((string) ($args['to'] ?? ''));
        $subject = mb_substr(trim((string) ($args['subject'] ?? '')), 0, 200);
        $body = trim((string) ($args['body'] ?? ''));
        if ($subject === '' || $body === '') return ['error' => 'Subject and body are required.'];
        $member = null;
        if (filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $email = $to;
            $member = $this->db->where('email', $to)->where('deleted_at', null)->get('members')->row();
        } else {
            $ms = $this->_find_members($to, 3);
            if (count($ms) !== 1) return ['error' => $ms ? 'Several members match "' . $to . '" — which one?' : 'No member or email "' . $to . '".'];
            $member = $this->Member_model->get_by_id($ms[0]->id);
            $email = trim((string) $member->email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['error' => $this->_member_name($member) . ' has no valid email address on file.'];
        }
        $card = ['type' => 'send_email', 'title' => 'Send email', 'to' => $email, 'member_id' => $member ? (int) $member->id : null,
                 'subject' => $subject, 'body' => mb_substr($body, 0, 10000), 'warnings' => [],
                 'rows' => [['To', $email . ($member ? ' (' . $member->member_code . ' — ' . $this->_member_name($member) . ')' : '')], ['Subject', $subject]]];
        $this->_add_pending($card);
        $this->run['cards'][] = $card;
        $this->_step('envelope', 'Email to ' . $email . ' — waiting for Confirm');
        return $this->_awaiting($card);
    }

    private function _tool_correct(array $args) {
        if (!in_array($this->admin_data->role, ['super_admin', 'admin'], true)) return ['error' => 'Only admins can correct records.'];
        $table = (string) ($args['table'] ?? '');
        $id = (int) ($args['record_id'] ?? 0);
        $reason = mb_substr(trim((string) ($args['reason'] ?? '')), 0, 255);
        if (!in_array($table, self::CORRECTABLE_TABLES, true)) return ['error' => 'Table ' . $table . ' cannot be corrected from chat.'];
        if ($reason === '') return ['error' => 'Give a reason for the correction.'];
        $row = $this->db->where('id', $id)->get($table)->row_array();
        if (!$row) return ['error' => 'No row with id ' . $id . ' in ' . $table . '.'];

        $changes = [];
        $warnings = [];
        foreach ((array) ($args['changes'] ?? []) as $k => $c) {
            $field = is_array($c) ? (string) ($c['field'] ?? '') : (string) $k;
            $value = is_array($c) ? ($c['value'] ?? null) : $c;
            if (!array_key_exists($field, $row)) return ['error' => 'Column ' . $field . ' does not exist in ' . $table . '.'];
            if (in_array($field, ['id', 'created_at', 'created_by'], true) || preg_match('/pass|token|secret/i', $field)) {
                return ['error' => 'Column ' . $field . ' cannot be changed.'];
            }
            $value = ($value === null || strtoupper(trim((string) $value)) === 'NULL') ? null : $this->_latin_digits_keep((string) $value);
            if ((string) $row[$field] === (string) $value && ($row[$field] === null) === ($value === null)) continue;
            $changes[] = ['field' => $field, 'old' => $row[$field], 'new' => $value];
            if (preg_match('/amount|balance|principal|interest|paid|outstanding|fine|emi|payable/i', $field)) $money = true;
        }
        if (!$changes) return ['error' => 'Nothing to change — the values are already the same.'];
        if (!empty($money)) $warnings[] = 'Money field edited directly — ledger / GL entries and totals elsewhere are not adjusted automatically.';

        $card = ['type' => 'correct_record', 'title' => 'Correct ' . $table . ' #' . $id, 'table' => $table, 'record_id' => $id,
                 'changes' => $changes, 'reason' => $reason, 'warnings' => $warnings, 'danger' => true,
                 'rows' => [['Reason', $reason]]];
        $this->_add_pending($card);
        $this->run['cards'][] = $card;
        $this->_step('pen', 'Correction of ' . $table . ' #' . $id . ' — waiting for Confirm');
        return $this->_awaiting($card, ['changes' => $changes]);
    }

    /** Devanagari digits → Latin, without stripping commas (free-text values). */
    private function _latin_digits_keep($s) {
        return strtr((string) $s, ['०' => '0', '१' => '1', '२' => '2', '३' => '3', '४' => '4', '५' => '5', '६' => '6', '७' => '7', '८' => '8', '९' => '9']);
    }

    // ── performing confirmed proposals ──

    private function _perform_foreclose(array $c) {
        if (!$this->_can('loans_approve')) return $this->_fail('You do not have permission to close loans.');
        $admin_id = (int) $this->session->userdata('admin_id');
        $loan = $this->db->where('id', (int) $c['loan_id'])->get('loans')->row();
        if (!$loan || !in_array($loan->status, ['active', 'npa'], true)) return $this->_fail('Loan is no longer active.');

        // DB errors must come back as a message (and a rollback), not as CI's HTML error page
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();
        try {
            return $this->_foreclose_in_transaction($loan, $c, $admin_id);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->_fail('Could not close the loan: ' . $e->getMessage());
        } finally {
            $this->db->db_debug = $debug;
        }
    }

    private function _foreclose_in_transaction($loan, array $c, $admin_id) {
        $req = $this->db->where('loan_id', $loan->id)->where('status', 'pending')->get('loan_foreclosure_requests')->row();
        if ($req) {
            if ($req->closure_type !== $c['closure_type']) {
                $this->db->where('id', $req->id)->update('loan_foreclosure_requests', ['closure_type' => $c['closure_type']]);
            }
            $req_id = (int) $req->id;
        } else {
            $this->db->insert('loan_foreclosure_requests', [
                'loan_id' => $loan->id, 'member_id' => $loan->member_id, 'foreclosure_amount' => $c['calculated'],
                'closure_type' => $c['closure_type'], 'reason' => $c['remarks'], 'settlement_date' => $c['payment_date'],
                'status' => 'pending', 'requested_at' => date('Y-m-d H:i:s'),
            ]);
            $req_id = (int) $this->db->insert_id();
        }
        $res = $this->Loan_model->process_foreclosure_request($req_id, $admin_id, 'approve', 'AI assistant: ' . $c['remarks'], [
            'payment_mode'        => $c['payment_mode'],
            'transaction_id'      => $c['reference'] !== '' ? $c['reference'] : null,
            'payment_date'        => $c['payment_date'],
            'interest_charge_pct' => $c['interest_charge_pct'] === null ? '' : $c['interest_charge_pct'],
            'final_amount'        => $c['final_amount'],
        ]);
        if (empty($res['success'])) {
            $this->db->trans_rollback();
            return $this->_fail($res['message'] ?? 'Could not close the loan.');
        }
        if (!empty($res['payment_id'])) {
            $this->load->model('Ledger_model');
            // Force close: cash → interest income, waived principal → write-off (Ledger_model rules)
            $posted = $this->Ledger_model->post_loan_payment($res['payment_id'], $loan->member_id,
                ($c['closure_type'] === 'force_close' ? 'Force close settlement for ' : 'Foreclosure settlement for ') . $loan->loan_number, $admin_id);
            if (!$posted) {
                $this->db->trans_rollback();
                return $this->_fail('Nothing was saved — the ledger entry for the settlement could not be posted (see application log).');
            }
        }
        if ($this->db->trans_status() === false) {
            $err = $this->db->error();
            $this->db->trans_rollback();
            log_message('error', 'AI foreclose of loan ' . $loan->id . ' rolled back: ' . ($err['message'] ?? ''));
            return $this->_fail('Nothing was saved — database error while closing the loan'
                                . (!empty($err['message']) ? ': ' . $err['message'] : '') . '.');
        }
        $this->db->trans_commit();
        $this->log_audit('foreclose', 'loans', 'loans', $loan->id, ['status' => $loan->status, 'outstanding_principal' => $loan->outstanding_principal],
                         ['status' => 'foreclosed', 'closure_type' => $c['closure_type'], 'collected' => $c['final_amount']], 'AI assistant: ' . $c['remarks']);
        return $this->_done(($c['closure_type'] === 'force_close' ? 'Force closed ' : 'Foreclosed ') . $loan->loan_number
                            . ' — settlement ₹' . number_format((float) $c['final_amount'], 2) . ' collected.', [
            'link' => site_url('admin/loans/view/' . $loan->id), 'link_text' => 'Open loan',
        ]);
    }

    private function _perform_email(array $c) {
        $this->load->helper('email');
        $html = '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.6">' . nl2br(htmlspecialchars($c['body'])) . '</div>';
        $r = send_email($c['to'], $c['subject'], $html);
        if (empty($r['success'])) return $this->_fail('Email not sent: ' . ($r['message'] ?? 'unknown error'));
        $this->log_activity('AI assistant email sent', 'To ' . $c['to'] . ': ' . $c['subject'], 'ai_assistant');
        return $this->_done('Email sent to ' . $c['to'] . ' — "' . $c['subject'] . '".');
    }

    private function _perform_correct(array $c) {
        if (!in_array($this->admin_data->role, ['super_admin', 'admin'], true)) return $this->_fail('Only admins can correct records.');
        if (!in_array($c['table'], self::CORRECTABLE_TABLES, true)) return $this->_fail('Table not allowed.');
        $row = $this->db->where('id', (int) $c['record_id'])->get($c['table'])->row_array();
        if (!$row) return $this->_fail('Record no longer exists.');
        $data = $old = [];
        foreach ($c['changes'] as $ch) {
            if ((string) $row[$ch['field']] !== (string) $ch['old']) {
                return $this->_fail($ch['field'] . ' was changed by someone else since this card was made — ask again.');
            }
            $data[$ch['field']] = $ch['new'];
            $old[$ch['field']] = $row[$ch['field']];
        }
        if (array_key_exists('updated_at', $row) && !isset($data['updated_at'])) $data['updated_at'] = date('Y-m-d H:i:s');
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $ok = $this->db->where('id', (int) $c['record_id'])->update($c['table'], $data);
            $err = $this->db->error();
        } catch (Exception $e) {
            $ok = false;
            $err = ['code' => 1, 'message' => $e->getMessage()];
        } finally {
            $this->db->db_debug = $debug;
        }
        if (!$ok || !empty($err['code'])) return $this->_fail('Could not save: ' . ($err['message'] ?? 'database error'));
        $this->log_audit('update', $c['table'], $c['table'], (int) $c['record_id'], $old, $data, 'AI correction: ' . $c['reason']);
        $list = array_map(function ($ch) { return $ch['field'] . ' ' . ($ch['old'] ?? 'NULL') . ' → ' . ($ch['new'] ?? 'NULL'); }, $c['changes']);
        return $this->_done('Corrected ' . $c['table'] . ' #' . $c['record_id'] . ': ' . implode(', ', $list) . '.');
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
        } elseif (!empty($a['record_id']) && in_array($a['kind'] ?? '', ['savings_deposit', 'fine', 'loan_payment'], true)) {
            // Exact record picked by the agent (e.g. after a run_sql lookup)
            $id = (int) $a['record_id'];
            $row = $a['kind'] === 'savings_deposit'
                ? $this->db->select('st.id, st.amount, st.transaction_date d, sa.account_number ref, sa.member_id')->from('savings_transactions st')
                    ->join('savings_accounts sa', 'sa.id = st.savings_account_id')->where('st.id', $id)->get()->row()
                : ($a['kind'] === 'fine'
                    ? $this->db->select('id, fine_amount amount, fine_date d, fine_code ref, member_id')->where('id', $id)->get('fines')->row()
                    : $this->db->select('lp.id, lp.total_amount amount, lp.payment_date d, l.loan_number ref, l.member_id')->from('loan_payments lp')
                        ->join('loans l', 'l.id = lp.loan_id')->where('lp.id', $id)->get()->row());
            if (!$row) {
                $card['warnings'][] = 'Record #' . $id . ' not found.';
                return $card + ['kind' => $a['kind'], 'target_id' => null, 'member_id' => null, 'label' => ''];
            }
            $m = $this->Member_model->get_by_id($row->member_id);
            $card += ['kind' => $a['kind'], 'target_id' => (int) $row->id, 'member_id' => (int) $row->member_id, 'unique' => true,
                      'label' => ucfirst(str_replace('_', ' ', $a['kind'])) . ' ₹' . number_format((float) $row->amount, 2) . ' on ' . $row->d
                                 . ' (' . $row->ref . ')' . ($m ? ' — ' . $this->_member_name($m) : '')];
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
        if (preg_match('/^\d{1,6}$/', $text)) {   // numeric member id (phones are 10 digits)
            $r = $this->db->where('id', (int) $text)->where('deleted_at', null)->get('members')->result();
            if ($r) return $r;
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
            'aicredits_model' => $get('ai_aicredits_model', 'AI_AICREDITS_MODEL', 'gpt-4o-mini'),
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
            'aicredits_key'   => $get('ai_aicredits_key', 'AI_AICREDITS_KEY'),
        ];
        if ($public) {
            $cfg['openai_key_set'] = $cfg['openai_key'] !== '';
            $cfg['gemini_key_set'] = $cfg['gemini_key'] !== '';
            $cfg['deepseek_key_set'] = $cfg['deepseek_key'] !== '';
            $cfg['openrouter_key_set'] = $cfg['openrouter_key'] !== '';
            $cfg['aicredits_key_set'] = $cfg['aicredits_key'] !== '';
            unset($cfg['openai_key'], $cfg['gemini_key'], $cfg['deepseek_key'], $cfg['openrouter_key'], $cfg['aicredits_key']);
        }
        return $cfg;
    }

    private function _client(array $override = []) {
        $c = $this->_config();
        $map = ['ai_provider' => 'provider', 'ai_local_url' => 'local_url', 'ai_local_model' => 'local_model',
                'ai_openai_model' => 'openai_model', 'ai_gemini_model' => 'gemini_model', 'ai_deepseek_model' => 'deepseek_model',
                'ai_openrouter_model' => 'openrouter_model', 'ai_aicredits_model' => 'aicredits_model',
                'ai_openai_key' => 'openai_key', 'ai_gemini_key' => 'gemini_key',
                'ai_deepseek_key' => 'deepseek_key', 'ai_openrouter_key' => 'openrouter_key', 'ai_aicredits_key' => 'aicredits_key'];
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
            case 'aicredits':
                return $this->ai_client->configure(['provider' => 'aicredits', 'model' => $c['aicredits_model'], 'api_key' => $c['aicredits_key']]);
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
