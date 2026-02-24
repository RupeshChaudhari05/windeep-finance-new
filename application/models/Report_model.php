<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Report_model - Reports & Analytics
 */
class Report_model extends MY_Model {
    
    protected $table = 'members';
    
    /**
     * Get Dashboard Statistics
     */
    public function get_dashboard_stats() {
        $stats = [];
        
        // Member Stats
        $stats['total_members'] = $this->db->where('status', 'active')
                                           ->count_all_results('members');
        
        $stats['new_members_month'] = $this->db->where('status', 'active')
                                                ->where('MONTH(created_at)', date('m'))
                                                ->where('YEAR(created_at)', date('Y'))
                                                ->count_all_results('members');
        
        // Savings Stats
        $stats['total_savings'] = $this->db->select_sum('current_balance')
                                           ->where('status', 'active')
                                           ->get('savings_accounts')
                                           ->row()
                                           ->current_balance ?? 0;
        
        $stats['savings_this_month'] = $this->db->select_sum('amount')
                                                 ->where('transaction_type', 'deposit')
                                                 ->where('MONTH(created_at)', date('m'))
                                                 ->where('YEAR(created_at)', date('Y'))
                                                 ->get('savings_transactions')
                                                 ->row()
                                                 ->amount ?? 0;
        
        // Loan Stats
        $stats['total_outstanding'] = $this->db->select_sum('outstanding_principal')
                                               ->where('status', 'active')
                                               ->get('loans')
                                               ->row()
                                               ->outstanding_principal ?? 0;
        
        $stats['active_loans'] = $this->db->where('status', 'active')
                                          ->count_all_results('loans');
        
        $stats['disbursed_this_month'] = $this->db->select_sum('principal_amount')
                                                   ->where('MONTH(disbursement_date)', date('m'))
                                                   ->where('YEAR(disbursement_date)', date('Y'))
                                                   ->get('loans')
                                                   ->row()
                                                   ->principal_amount ?? 0;
        
        $stats['collected_this_month'] = $this->db->select_sum('total_amount')
                                                   ->where('MONTH(payment_date)', date('m'))
                                                   ->where('YEAR(payment_date)', date('Y'))
                                                   ->where('is_reversed', 0)
                                                   ->get('loan_payments')
                                                   ->row()
                                                   ->total_amount ?? 0;
        
        // Overdue Stats
        $stats['overdue_amount'] = $this->db->select('SUM(li.emi_amount - li.total_paid) as overdue')
                                            ->from('loan_installments li')
                                            ->join('loans l', 'l.id = li.loan_id')
                                            ->where('l.status', 'active')
                                            ->where('li.status', 'pending')
                                            ->where('li.due_date <', date('Y-m-d'))
                                            ->get()
                                            ->row()
                                            ->overdue ?? 0;
        
        $stats['overdue_members'] = $this->db->select('COUNT(DISTINCT l.member_id) as count')
                                              ->from('loan_installments li')
                                              ->join('loans l', 'l.id = li.loan_id')
                                              ->where('l.status', 'active')
                                              ->where('li.status', 'pending')
                                              ->where('li.due_date <', date('Y-m-d'))
                                              ->get()
                                              ->row()
                                              ->count ?? 0;
        
        // Pending Applications
        $stats['pending_applications'] = $this->db->where_in('status', ['pending', 'under_review', 'guarantor_pending'])
                                                   ->count_all_results('loan_applications');
        
        // Fine Stats
        $stats['pending_fines'] = $this->db->select_sum('balance_amount')
                                           ->where_in('status', ['pending', 'partial'])
                                           ->get('fines')
                                           ->row()
                                           ->balance_amount ?? 0;
        
        return $stats;
    }
    
    /**
     * Get Fee Summary (membership fees, other member fees from bank transaction mappings and general ledger)
     */
    public function get_fee_summary() {
        $summary = [
            'membership_fee' => 0,
            'other_member_fee' => 0,
        ];

        // From bank_transactions mapped as membership_fee
        $membership = $this->db->select_sum('amount')
            ->where('transaction_category', 'membership_fee')
            ->where('mapping_status', 'mapped')
            ->get('bank_transactions')
            ->row();
        $summary['membership_fee'] = $membership->amount ?? 0;

        // From member_other_transactions table
        $fees = $this->db->select('transaction_type, SUM(amount) as total')
            ->group_by('transaction_type')
            ->get('member_other_transactions')
            ->result();
        foreach ($fees as $fee) {
            if ($fee->transaction_type === 'membership_fee') {
                $summary['membership_fee'] += floatval($fee->total);
            } else {
                $summary['other_member_fee'] += floatval($fee->total);
            }
        }

        // From transaction_mappings with type 'other' (other fees)
        $other = $this->db->select_sum('amount')
            ->where('mapping_type', 'other')
            ->where('is_reversed', 0)
            ->get('transaction_mappings')
            ->row();
        $summary['other_member_fee'] += floatval($other->amount ?? 0);

        return $summary;
    }

    /**
     * Get Collection Report
     */
    public function get_collection_report($from_date, $to_date, $type = 'all') {
        $report = [
            'summary' => [],
            'details' => [],
            'by_date' => []
        ];
        
        // Loan Collections
        if ($type === 'all' || $type === 'loan') {
            $report['summary']['loan'] = $this->db->select('
                COUNT(*) as count,
                SUM(principal_component) as principal,
                SUM(interest_component) as interest,
                SUM(fine_component) as fine,
                SUM(total_amount) as total
            ')
            ->where('payment_date >=', $from_date)
            ->where('payment_date <=', $to_date)
            ->where('is_reversed', 0)
            ->get('loan_payments')
            ->row();
            
            $report['details']['loan'] = $this->db->select('
                lp.*, l.loan_number, m.member_code, m.first_name, m.last_name
            ')
            ->from('loan_payments lp')
            ->join('loans l', 'l.id = lp.loan_id')
            ->join('members m', 'm.id = l.member_id')
            ->where('lp.payment_date >=', $from_date)
            ->where('lp.payment_date <=', $to_date)
            ->where('lp.is_reversed', 0)
            ->order_by('lp.payment_date', 'ASC')
            ->get()
            ->result();
        }
        
        // Savings Collections
        if ($type === 'all' || $type === 'savings') {
            $report['summary']['savings'] = $this->db->select('
                COUNT(*) as count,
                SUM(amount) as total
            ')
            ->from('savings_transactions')
            ->where('transaction_type', 'deposit')
            ->where('DATE(created_at) >=', $from_date)
            ->where('DATE(created_at) <=', $to_date)
            ->get()
            ->row();
            
            $report['details']['savings'] = $this->db->select('
                st.*, sa.account_number, m.member_code, m.first_name, m.last_name
            ')
            ->from('savings_transactions st')
            ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
            ->join('members m', 'm.id = sa.member_id')
            ->where('st.transaction_type', 'deposit')
            ->where('DATE(st.created_at) >=', $from_date)
            ->where('DATE(st.created_at) <=', $to_date)
            ->order_by('st.created_at', 'ASC')
            ->get()
            ->result();
        }
        
        return $report;
    }
    
    /**
     * Get Disbursement Report
     */
    public function get_disbursement_report($from_date, $to_date) {
        $report = [];
        
        $report['summary'] = $this->db->select('
            COUNT(*) as count,
            SUM(principal_amount) as total_disbursed,
            SUM(processing_fee) as total_fees
        ')
        ->where('disbursement_date >=', $from_date)
        ->where('disbursement_date <=', $to_date)
        ->get('loans')
        ->row();
        
        $report['by_product'] = $this->db->select('
            lp.product_name,
            COUNT(*) as count,
            SUM(l.principal_amount) as total_disbursed
        ')
        ->from('loans l')
        ->join('loan_products lp', 'lp.id = l.loan_product_id')
        ->where('l.disbursement_date >=', $from_date)
        ->where('l.disbursement_date <=', $to_date)
        ->group_by('l.loan_product_id')
        ->get()
        ->result();
        
        $report['details'] = $this->db->select('
            l.*, lp.product_name, m.member_code, m.first_name, m.last_name
        ')
        ->from('loans l')
        ->join('loan_products lp', 'lp.id = l.loan_product_id')
        ->join('members m', 'm.id = l.member_id')
        ->where('l.disbursement_date >=', $from_date)
        ->where('l.disbursement_date <=', $to_date)
        ->order_by('l.disbursement_date', 'ASC')
        ->get()
        ->result();
        
        return $report;
    }
    
    /**
     * Get Outstanding Report
     */
    public function get_outstanding_report() {
        return $this->db->select('
            l.*, lp.product_name, 
            m.member_code, m.first_name, m.last_name, m.phone,
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status = "pending" AND due_date < CURDATE()) as overdue_count,
            (SELECT SUM(emi_amount - total_paid) FROM loan_installments WHERE loan_id = l.id AND status = "pending" AND due_date < CURDATE()) as overdue_amount
        ')
        ->from('loans l')
        ->join('loan_products lp', 'lp.id = l.loan_product_id')
        ->join('members m', 'm.id = l.member_id')
        ->where('l.status', 'active')
        ->order_by('l.outstanding_principal', 'DESC')
        ->get()
        ->result();
    }
    
    /**
     * Get NPA Report (Non-Performing Assets)
     */
    public function get_npa_report($days_threshold = 90) {
        return $this->db->select('
            l.*, lp.product_name, 
            m.member_code, m.first_name, m.last_name, m.phone,
            MIN(li.due_date) as first_overdue_date,
            DATEDIFF(CURDATE(), MIN(li.due_date)) as days_overdue,
            SUM(li.emi_amount - li.total_paid) as total_overdue
        ')
        ->from('loans l')
        ->join('loan_products lp', 'lp.id = l.loan_product_id')
        ->join('members m', 'm.id = l.member_id')
        ->join('loan_installments li', 'li.loan_id = l.id')
        ->where('l.status', 'active')
        ->where('li.status', 'pending')
        ->where('li.due_date <', date('Y-m-d', safe_timestamp('-' . $days_threshold . ' days')))
        ->group_by('l.id')
        ->order_by('days_overdue', 'DESC')
        ->get()
        ->result();
    }
    
    /**
     * Get Member Statement
     */
    public function get_member_statement($member_id, $from_date = null, $to_date = null) {
        $member = $this->db->where('id', $member_id)->get('members')->row();
        
        if (!$member) return null;
        
        $statement = [
            'member' => $member,
            'savings' => [],
            'loans' => [],
            'fines' => [],
            'ledger' => []
        ];
        
        // Savings transactions
        $this->db->select('st.*, sa.account_number');
        $this->db->from('savings_transactions st');
        $this->db->join('savings_accounts sa', 'sa.id = st.savings_account_id');
        $this->db->where('sa.member_id', $member_id);
        
        if ($from_date) $this->db->where('DATE(st.created_at) >=', $from_date);
        if ($to_date) $this->db->where('DATE(st.created_at) <=', $to_date);
        
        $statement['savings'] = $this->db->order_by('st.created_at', 'ASC')->get()->result();
        
        // Loan payments
        $this->db->select('lp.*, l.loan_number');
        $this->db->from('loan_payments lp');
        $this->db->join('loans l', 'l.id = lp.loan_id');
        $this->db->where('l.member_id', $member_id);
        
        if ($from_date) $this->db->where('lp.payment_date >=', $from_date);
        if ($to_date) $this->db->where('lp.payment_date <=', $to_date);
        
        $statement['loans'] = $this->db->order_by('lp.payment_date', 'ASC')->get()->result();
        
        // Fines
        $this->db->where('member_id', $member_id);
        if ($from_date) $this->db->where('fine_date >=', $from_date);
        if ($to_date) $this->db->where('fine_date <=', $to_date);
        
        $statement['fines'] = $this->db->order_by('fine_date', 'ASC')->get('fines')->result();
        
        // Ledger
        $this->load->model('Ledger_model');
        $statement['ledger'] = $this->Ledger_model->get_member_ledger($member_id, $from_date, $to_date);
        
        return $statement;
    }
    
    /**
     * Get Demand Report (Upcoming Dues)
     */
    public function get_demand_report($month) {
        $report = [
            'savings' => [],
            'loans' => [],
            'summary' => []
        ];
        
        // Savings Demand
        $report['savings'] = $this->db->select('
            sch.*, sa.account_number, m.member_code, m.first_name, m.last_name, m.phone
        ')
        ->from('savings_schedule sch')
        ->join('savings_accounts sa', 'sa.id = sch.savings_account_id')
        ->join('members m', 'm.id = sa.member_id')
        ->where('sch.due_month', $month)
        ->where_in('sch.status', ['pending', 'partial'])
        ->order_by('m.member_code', 'ASC')
        ->get()
        ->result();
        
        // Loan Demand
        $report['loans'] = $this->db->select('
            li.*, l.loan_number, m.member_code, m.first_name, m.last_name, m.phone
        ')
        ->from('loan_installments li')
        ->join('loans l', 'l.id = li.loan_id')
        ->join('members m', 'm.id = l.member_id')
        ->where('MONTH(li.due_date)', date('m', safe_timestamp($month)))
        ->where('YEAR(li.due_date)', date('Y', safe_timestamp($month)))
        ->where_in('li.status', ['pending', 'partial'])
        ->order_by('m.member_code', 'ASC')
        ->get()
        ->result();
        
        // Summary
        $report['summary'] = [
            'savings_due' => array_sum(array_column($report['savings'], 'due_amount')) - array_sum(array_column($report['savings'], 'paid_amount')),
            'loan_due' => array_sum(array_column($report['loans'], 'emi_amount')) - array_sum(array_column($report['loans'], 'total_paid')),
            'savings_members' => count(array_unique(array_column($report['savings'], 'member_code'))),
            'loan_members' => count(array_unique(array_column($report['loans'], 'member_code')))
        ];
        
        return $report;
    }
    
    /**
     * Get Guarantor Exposure Report
     */
    public function get_guarantor_report() {
        return $this->db->select('
            m.member_code, m.first_name, m.last_name, m.phone,
            COUNT(lg.id) as guarantee_count,
            SUM(lg.guarantee_amount) as total_exposure,
            SUM(CASE WHEN l.status = "active" THEN l.outstanding_principal ELSE 0 END) as active_exposure
        ')
        ->from('loan_guarantors lg')
        ->join('members m', 'm.id = lg.guarantor_member_id')
        ->join('loans l', 'l.id = lg.loan_id', 'left')
        ->where('lg.consent_status', 'approved')
        ->where('lg.is_released', 0)
        ->group_by('lg.guarantor_member_id')
        ->order_by('total_exposure', 'DESC')
        ->get()
        ->result();
    }
    
    /**
     * Get Monthly Summary
     */
    public function get_monthly_summary($year, $month) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', safe_timestamp($start_date));
        
        return [
            'new_members' => $this->db->where('DATE(created_at) >=', $start_date)
                                      ->where('DATE(created_at) <=', $end_date)
                                      ->count_all_results('members'),
            
            'new_savings_accounts' => $this->db->where('DATE(created_at) >=', $start_date)
                                               ->where('DATE(created_at) <=', $end_date)
                                               ->count_all_results('savings_accounts'),
            
            'savings_collected' => $this->db->select_sum('amount')
                                            ->where('transaction_type', 'deposit')
                                            ->where('DATE(created_at) >=', $start_date)
                                            ->where('DATE(created_at) <=', $end_date)
                                            ->get('savings_transactions')
                                            ->row()
                                            ->amount ?? 0,
            
            'loans_disbursed_count' => $this->db->where('disbursement_date >=', $start_date)
                                                 ->where('disbursement_date <=', $end_date)
                                                 ->count_all_results('loans'),
            
            'loans_disbursed_amount' => $this->db->select_sum('principal_amount')
                                                  ->where('disbursement_date >=', $start_date)
                                                  ->where('disbursement_date <=', $end_date)
                                                  ->get('loans')
                                                  ->row()
                                                  ->principal_amount ?? 0,
            
            'loans_collected' => $this->db->select_sum('total_amount')
                                          ->where('payment_date >=', $start_date)
                                          ->where('payment_date <=', $end_date)
                                          ->where('is_reversed', 0)
                                          ->get('loan_payments')
                                          ->row()
                                          ->total_amount ?? 0,
            
            'fines_applied' => $this->db->select_sum('fine_amount')
                                        ->where('fine_date >=', $start_date)
                                        ->where('fine_date <=', $end_date)
                                        ->get('fines')
                                        ->row()
                                        ->fine_amount ?? 0,
            
            'fines_collected' => $this->db->select_sum('paid_amount')
                                          ->where('updated_at >=', $start_date)
                                          ->where('updated_at <=', $end_date . ' 23:59:59')
                                          ->where_in('status', ['paid', 'partial'])
                                          ->get('fines')
                                          ->row()
                                          ->paid_amount ?? 0
        ];
    }
    
    /**
     * Get Chart Data - Monthly Trend
     */
    public function get_monthly_trend($year) {
        $data = [
            'labels' => [],
            'savings' => [],
            'loans_disbursed' => [],
            'loans_collected' => []
        ];
        
        for ($m = 1; $m <= 12; $m++) {
            $month = str_pad($m, 2, '0', STR_PAD_LEFT);
            $data['labels'][] = date('M', mktime(0, 0, 0, $m, 1));
            
            $data['savings'][] = $this->db->select_sum('amount')
                                          ->where('transaction_type', 'deposit')
                                          ->where('MONTH(created_at)', $m)
                                          ->where('YEAR(created_at)', $year)
                                          ->get('savings_transactions')
                                          ->row()
                                          ->amount ?? 0;
            
            $data['loans_disbursed'][] = $this->db->select_sum('principal_amount')
                                                   ->where('MONTH(disbursement_date)', $m)
                                                   ->where('YEAR(disbursement_date)', $year)
                                                   ->get('loans')
                                                   ->row()
                                                   ->principal_amount ?? 0;
            
            $data['loans_collected'][] = $this->db->select_sum('total_amount')
                                                   ->where('MONTH(payment_date)', $m)
                                                   ->where('YEAR(payment_date)', $year)
                                                   ->where('is_reversed', 0)
                                                   ->get('loan_payments')
                                                   ->row()
                                                   ->total_amount ?? 0;
        }
        
        return $data;
    }
    
    /**
     * Get Member Summary Report
     */
    public function get_member_summary_report() {
        return $this->db->select('
            m.*, 
            (SELECT COUNT(*) FROM savings_accounts WHERE member_id = m.id) as savings_accounts,
            (SELECT SUM(current_balance) FROM savings_accounts WHERE member_id = m.id) as total_savings,
            (SELECT COUNT(*) FROM loans WHERE member_id = m.id) as total_loans,
            (SELECT SUM(outstanding_principal) FROM loans WHERE member_id = m.id) as outstanding_loans,
            (SELECT SUM(balance_amount) FROM fines WHERE member_id = m.id AND status IN ("pending", "partial")) as pending_fines
        ')
        ->from('members m')
        ->where('m.status', 'active')
        ->order_by('m.member_code', 'ASC')
        ->get()
        ->result();
    }
    
    /**
     * Get KYC Pending Report
     */
    public function get_kyc_pending_report() {
        return $this->db->select('m.*, COUNT(f.id) as pending_documents')
                        ->from('members m')
                        ->join('member_documents f', 'f.member_id = m.id AND f.status = "pending"', 'left')
                        ->where('m.status', 'active')
                        ->group_by('m.id')
                        ->having('pending_documents > 0 OR m.id_proof_type IS NULL')
                        ->order_by('m.member_code', 'ASC')
                        ->get()
                        ->result();
    }
    
    /**
     * Get Ageing Report
     */
    public function get_ageing_report() {
        return $this->db->select('
            l.*, lp.product_name, m.member_code, m.first_name, m.last_name,
            DATEDIFF(CURDATE(), MIN(li.due_date)) as days_overdue,
            SUM(li.emi_amount - li.total_paid) as overdue_amount,
            CASE 
                WHEN DATEDIFF(CURDATE(), MIN(li.due_date)) <= 30 THEN "0-30 days"
                WHEN DATEDIFF(CURDATE(), MIN(li.due_date)) <= 60 THEN "31-60 days"
                WHEN DATEDIFF(CURDATE(), MIN(li.due_date)) <= 90 THEN "61-90 days"
                ELSE "90+ days"
            END as ageing_bucket
        ')
        ->from('loans l')
        ->join('loan_products lp', 'lp.id = l.loan_product_id')
        ->join('members m', 'm.id = l.member_id')
        ->join('loan_installments li', 'li.loan_id = l.id')
        ->where('l.status', 'active')
        ->where('li.status', 'pending')
        ->where('li.due_date <', date('Y-m-d'))
        ->group_by('l.id')
        ->order_by('days_overdue', 'DESC')
        ->get()
        ->result();
    }
    
    /**
     * Get Cash Book
     */
    public function get_cash_book($from_date, $to_date) {
        $report = [];
        
        // Cash receipts - using raw SQL since CI3 doesn't support union_all()
        $receipts_sql = "
            (SELECT DATE(st.created_at) as date, 'Receipt' as type,
                    COALESCE(st.description, 'Savings Deposit') as description,
                    st.amount as debit, 0 as credit
             FROM savings_transactions st
             WHERE st.transaction_type = 'deposit'
               AND DATE(st.created_at) >= ? AND DATE(st.created_at) <= ?)
            UNION ALL
            (SELECT DATE(lp.payment_date) as date, 'Receipt' as type,
                    CONCAT('Loan Payment - ', l.loan_number) as description,
                    lp.total_amount as debit, 0 as credit
             FROM loan_payments lp
             JOIN loans l ON l.id = lp.loan_id
             WHERE lp.payment_date >= ? AND lp.payment_date <= ?
               AND lp.is_reversed = 0)
            ORDER BY date ASC";
        
        $report['receipts'] = $this->db->query($receipts_sql, [$from_date, $to_date, $from_date, $to_date])->result();
        
        // Cash payments
        $payments_sql = "
            (SELECT DATE(st.created_at) as date, 'Payment' as type,
                    COALESCE(st.description, 'Savings Withdrawal') as description,
                    0 as debit, st.amount as credit
             FROM savings_transactions st
             WHERE st.transaction_type = 'withdrawal'
               AND DATE(st.created_at) >= ? AND DATE(st.created_at) <= ?)
            UNION ALL
            (SELECT DATE(l.disbursement_date) as date, 'Payment' as type,
                    CONCAT('Loan Disbursement - ', l.loan_number) as description,
                    0 as debit, l.principal_amount as credit
             FROM loans l
             WHERE l.disbursement_date >= ? AND l.disbursement_date <= ?
               AND l.disbursement_date IS NOT NULL)
            ORDER BY date ASC";
        
        $report['payments'] = $this->db->query($payments_sql, [$from_date, $to_date, $from_date, $to_date])->result();
        
        return $report;
    }
    
    /**
     * Get Bank Reconciliation
     */
    public function get_bank_reconciliation() {
        // This is a placeholder - would need bank statement data
        return [];
    }

    /**
     * Get Weekly Summary Report
     */
    public function get_weekly_summary() {
        $start_date = date('Y-m-d', strtotime('last monday'));
        $end_date = date('Y-m-d', strtotime('sunday'));

        $summary = [];

        // New members this week
        $summary[] = [
            'metric' => 'New Members',
            'value' => $this->db->where('status', 'active')
                               ->where('DATE(created_at) >=', $start_date)
                               ->where('DATE(created_at) <=', $end_date)
                               ->count_all_results('members'),
            'type' => 'count'
        ];

        // New loans this week
        $summary[] = [
            'metric' => 'New Loans',
            'value' => $this->db->where('DATE(created_at) >=', $start_date)
                               ->where('DATE(created_at) <=', $end_date)
                               ->count_all_results('loans'),
            'type' => 'count'
        ];

        // Loan disbursements this week
        $summary[] = [
            'metric' => 'Loan Disbursements',
            'value' => $this->db->select_sum('disbursed_amount')
                               ->where('DATE(disbursement_date) >=', $start_date)
                               ->where('DATE(disbursement_date) <=', $end_date)
                               ->get('loans')
                               ->row()
                               ->disbursed_amount ?? 0,
            'type' => 'currency'
        ];

        // Savings deposits this week
        $summary[] = [
            'metric' => 'Savings Deposits',
            'value' => $this->db->select_sum('amount')
                               ->where('transaction_type', 'deposit')
                               ->where('DATE(created_at) >=', $start_date)
                               ->where('DATE(created_at) <=', $end_date)
                               ->get('savings_transactions')
                               ->row()
                               ->amount ?? 0,
            'type' => 'currency'
        ];

        // Loan repayments this week
        $repay_row = $this->db->select_sum('total_amount')
                               ->where('payment_date >=', $start_date)
                               ->where('payment_date <=', $end_date)
                               ->where('is_reversed', 0)
                               ->get('loan_payments')
                               ->row();
        $summary[] = [
            'metric' => 'Loan Repayments',
            'value' => $repay_row->total_amount ?? 0,
            'type' => 'currency'
        ];

        // Pending fines
        $fines_row = $this->db->select_sum('balance_amount')
                               ->where('status', 'pending')
                               ->get('fines')
                               ->row();
        $summary[] = [
            'metric' => 'Pending Fines',
            'value' => $fines_row->balance_amount ?? 0,
            'type' => 'currency'
        ];

        // Overdue payments
        $overdue_count = $this->db->where('due_date <', date('Y-m-d'))
                                 ->where('status', 'pending')
                                 ->count_all_results('loan_installments');

        $summary[] = [
            'metric' => 'Overdue Installments',
            'value' => $overdue_count,
            'type' => 'count'
        ];

        return $summary;
    }
}
