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
        
        // Counted on the membership join date, not when the row was keyed in.
        // Written as a date range with escaping off: CodeIgniter cannot parse a
        // NESTED function like MONTH(COALESCE(a, DATE(b))) as a field name and
        // silently drops the "=", producing invalid SQL.
        $month_start = date('Y-m-01');
        $month_end   = date('Y-m-t');
        $stats['new_members_month'] = $this->db->where('status', 'active')
                                                ->where(
                                                    'COALESCE(join_date, DATE(created_at)) BETWEEN '
                                                    . $this->db->escape($month_start) . ' AND '
                                                    . $this->db->escape($month_end),
                                                    null, false
                                                )
                                                ->count_all_results('members');
        
        // Savings Stats
        $stats['total_savings'] = $this->db->select_sum('current_balance')
                                           ->where('status', 'active')
                                           ->get('savings_accounts')
                                           ->row()
                                           ->current_balance ?? 0;

        $stats['removed_savings_transactions_count'] = $this->db->where('module', 'admin_adjustments')
                                                                  ->where('action', 'delete')
                                                                  ->where('table_name', 'savings_transactions')
                                                                  ->count_all_results('audit_logs');
        
        // Collected this month = deposits DATED this month, matching the loan
        // side (which uses payment_date) and the dashboard drill-down.
        $stats['savings_this_month'] = $this->db->select_sum('amount')
                                                 ->where('transaction_type', 'deposit')
                                                 ->where('MONTH(transaction_date)', date('m'))
                                                 ->where('YEAR(transaction_date)', date('Y'))
                                                 ->where('COALESCE(is_reversed, 0) = 0', null, false)
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
        
        $stats['disbursed_this_month'] = $this->disbursed_sum(date('Y-m-01'), date('Y-m-t'));
        
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
                                            ->where_in('li.status', ['pending', 'overdue', 'partial'])
                                            ->where('li.due_date <', date('Y-m-d'))
                                            ->get()
                                            ->row()
                                            ->overdue ?? 0;
        
        $stats['overdue_members'] = $this->db->select('COUNT(DISTINCT l.member_id) as count')
                                              ->from('loan_installments li')
                                              ->join('loans l', 'l.id = li.loan_id')
                                              ->where('l.status', 'active')
                                              ->where_in('li.status', ['pending', 'overdue', 'partial'])
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
            ->where('is_reversed', 0)
            ->where('transaction_date >=', $from_date)
            ->where('transaction_date <=', $to_date)
            ->get()
            ->row();
            
            $report['details']['savings'] = $this->db->select('
                st.*, sa.account_number, m.member_code, m.first_name, m.last_name
            ')
            ->from('savings_transactions st')
            ->join('savings_accounts sa', 'sa.id = st.savings_account_id')
            ->join('members m', 'm.id = sa.member_id')
            ->where('st.transaction_type', 'deposit')
            ->where('st.is_reversed', 0)
            ->where('st.transaction_date >=', $from_date)
            ->where('st.transaction_date <=', $to_date)
            ->order_by('st.transaction_date', 'ASC')
            ->get()
            ->result();
        }
        
        return $report;
    }
    
    /**
     * Get Disbursement Report
     */
    /**
     * Money actually disbursed in a period: every disbursement part by its own date (a loan disbursed in parts counts
     * 1 L in June and 4 L in August), plus loans created outside the disbursement screen that have no parts.
     */
    public function disbursed_sum($from, $to, $product_id = null) {
        $p = $product_id ? ' AND l.loan_product_id = ' . (int) $product_id : '';
        $row = $this->db->query("SELECT COALESCE(SUM(x.amt), 0) s FROM (
                SELECT dt.disbursement_amount amt, dt.disbursement_date d FROM disbursement_tracking dt JOIN loans l ON l.id = dt.loan_id
                 WHERE dt.status = 'completed'{$p}
                UNION ALL
                SELECT l.principal_amount, l.disbursement_date FROM loans l
                 WHERE NOT EXISTS (SELECT 1 FROM disbursement_tracking t WHERE t.loan_id = l.id){$p}
            ) x WHERE x.d BETWEEN ? AND ?", [$from, $to])->row();
        return round((float) ($row->s ?? 0), 2);
    }
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
        // Actual money released in the period (parts of part-disbursed loans by their own date)
        $report['summary']->total_disbursed = $this->disbursed_sum($from_date, $to_date);

        $report['by_product'] = $this->db->select('
            lp.product_name, l.loan_product_id,
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
        foreach ($report['by_product'] as $row) {
            $row->total_disbursed = $this->disbursed_sum($from_date, $to_date, $row->loan_product_id);
        }
        
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
            (SELECT COUNT(*) FROM loan_installments WHERE loan_id = l.id AND status IN ("pending","overdue","partial") AND due_date < CURDATE()) as overdue_count,
            (SELECT SUM(emi_amount - total_paid) FROM loan_installments WHERE loan_id = l.id AND status IN ("pending","overdue","partial") AND due_date < CURDATE()) as overdue_amount
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
        ->where_in('li.status', ['pending', 'overdue', 'partial'])
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
        ->where_in('sch.status', ['pending', 'partial', 'overdue'])
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
        ->where_in('li.status', ['pending', 'partial', 'overdue'])
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
            
            'loans_disbursed_amount' => $this->disbursed_sum($start_date, $end_date),
            
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
                                          ->where('MONTH(transaction_date)', $m)
                                          ->where('YEAR(transaction_date)', $year)
                                          ->where('COALESCE(is_reversed, 0) = 0', null, false)
                                          ->get('savings_transactions')
                                          ->row()
                                          ->amount ?? 0;
            
            $data['loans_disbursed'][] = $this->disbursed_sum(sprintf('%04d-%02d-01', $year, $m), date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $m))));
            
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
        // member_documents does not exist in this schema — KYC is judged from the member's own fields
        return $this->kyc_pending_list();
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
        ->where_in('li.status', ['pending', 'overdue', 'partial'])
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
            (SELECT st.transaction_date as date, 'Receipt' as type,
                    COALESCE(st.narration, 'Savings Deposit') as description,
                    st.amount as debit, 0 as credit
             FROM savings_transactions st
             WHERE st.transaction_type = 'deposit' AND st.is_reversed = 0
               AND st.transaction_date >= ? AND st.transaction_date <= ?)
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
            (SELECT st.transaction_date as date, 'Payment' as type,
                    COALESCE(st.narration, 'Savings Withdrawal') as description,
                    0 as debit, st.amount as credit
             FROM savings_transactions st
             WHERE st.transaction_type = 'withdrawal' AND st.is_reversed = 0
               AND st.transaction_date >= ? AND st.transaction_date <= ?)
            UNION ALL
            (SELECT DATE(l.disbursement_date) as date, 'Payment' as type,
                    CONCAT('Loan Disbursement - ', l.loan_number) as description,
                    0 as debit, COALESCE(l.disbursed_amount, l.principal_amount) as credit
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
            'value' => $this->disbursed_sum($start_date, $end_date),
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
                               ->where_in('status', ['pending', 'partial'])
                               ->get('fines')
                               ->row();
        $summary[] = [
            'metric' => 'Pending Fines',
            'value' => $fines_row->balance_amount ?? 0,
            'type' => 'currency'
        ];

        // Overdue payments
        $overdue_count = $this->db->where('due_date <', date('Y-m-d'))
                                 ->where_in('status', ['pending', 'overdue', 'partial'])
                                 ->count_all_results('loan_installments');

        $summary[] = [
            'metric' => 'Overdue Installments',
            'value' => $overdue_count,
            'type' => 'count'
        ];

        return $summary;
    }

    // ═══════════════════════════════════════════════════════════
    // Office Expenses (from bank mapping - internal transactions)
    // ═══════════════════════════════════════════════════════════

    /**
     * Get Office Expense Summary
     * Pulls data from internal_transactions table (bank_charge, etc.) 
     * and transaction_mappings where related_type='internal'
     */
    public function get_office_expense_summary() {
        $summary = [
            'total_expenses' => 0,
            'this_month' => 0,
            'by_type' => []
        ];

        // From internal_transactions
        if ($this->db->table_exists('internal_transactions')) {
            // Total expenses (debit-side internal transactions)
            $expense_types = ['bank_charge', 'dividend_paid', 'other'];
            $total = $this->db->select('COALESCE(SUM(amount), 0) as total')
                              ->where('status', 'completed')
                              ->where_in('transaction_type', $expense_types)
                              ->get('internal_transactions')
                              ->row();
            $summary['total_expenses'] = floatval($total->total ?? 0);

            // This month
            $monthly = $this->db->select('COALESCE(SUM(amount), 0) as total')
                                ->where('status', 'completed')
                                ->where_in('transaction_type', $expense_types)
                                ->where('MONTH(transaction_date)', date('m'))
                                ->where('YEAR(transaction_date)', date('Y'))
                                ->get('internal_transactions')
                                ->row();
            $summary['this_month'] = floatval($monthly->total ?? 0);

            // By type
            $by_type = $this->db->select('transaction_type, COALESCE(SUM(amount), 0) as total')
                                ->where('status', 'completed')
                                ->group_by('transaction_type')
                                ->get('internal_transactions')
                                ->result();
            foreach ($by_type as $t) {
                $summary['by_type'][$t->transaction_type] = floatval($t->total);
            }
        }

        // Also check transaction_mappings for 'expense' type entries
        $expense_mappings = $this->db->select('COALESCE(SUM(amount), 0) as total')
                                      ->where('related_type', 'internal')
                                      ->where('is_reversed', 0)
                                      ->get('transaction_mappings')
                                      ->row();
        if (floatval($expense_mappings->total ?? 0) > $summary['total_expenses']) {
            $summary['total_expenses'] = floatval($expense_mappings->total);
        }

        return $summary;
    }

    // ═══════════════════════════════════════════════════════════
    // Interest Earned Analytics
    // ═══════════════════════════════════════════════════════════

    /**
     * Get Total Interest Earned from Loan Payments
     */
    public function get_interest_earned_stats() {
        $stats = [];

        // Total interest earned all time
        $total = $this->db->select('COALESCE(SUM(interest_component), 0) as total')
                          ->where('is_reversed', 0)
                          ->get('loan_payments')
                          ->row();
        $stats['total_interest'] = floatval($total->total ?? 0);

        // This month
        $monthly = $this->db->select('COALESCE(SUM(interest_component), 0) as total')
                            ->where('is_reversed', 0)
                            ->where('MONTH(payment_date)', date('m'))
                            ->where('YEAR(payment_date)', date('Y'))
                            ->get('loan_payments')
                            ->row();
        $stats['this_month_interest'] = floatval($monthly->total ?? 0);

        // This year
        $yearly = $this->db->select('COALESCE(SUM(interest_component), 0) as total')
                           ->where('is_reversed', 0)
                           ->where('YEAR(payment_date)', date('Y'))
                           ->get('loan_payments')
                           ->row();
        $stats['this_year_interest'] = floatval($yearly->total ?? 0);

        // Active loans count with outstanding
        $stats['active_loan_count'] = $this->db->where('status', 'active')
                                                ->count_all_results('loans');

        return $stats;
    }

    /**
     * Get Monthly Interest Breakdown for a Given Year
     */
    public function get_monthly_interest_report($year = null) {
        $year = $year ?: date('Y');

        $result = $this->db->select("
            MONTH(payment_date) as month_num,
            DATE_FORMAT(payment_date, '%b') as month_name,
            COALESCE(SUM(interest_component), 0) as interest,
            COALESCE(SUM(principal_component), 0) as principal,
            COALESCE(SUM(total_amount), 0) as total_collected,
            COUNT(*) as payment_count
        ")
        ->where('is_reversed', 0)
        ->where('YEAR(payment_date)', $year)
        ->group_by('MONTH(payment_date)')
        ->order_by('MONTH(payment_date)', 'ASC')
        ->get('loan_payments')
        ->result();

        // Fill in all 12 months
        $months = [];
        $month_names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = [
                'month_num' => $i,
                'month_name' => $month_names[$i - 1],
                'interest' => 0,
                'principal' => 0,
                'total_collected' => 0,
                'payment_count' => 0
            ];
        }

        foreach ($result as $row) {
            $m = (int) $row->month_num;
            $months[$m] = [
                'month_num' => $m,
                'month_name' => $row->month_name,
                'interest' => floatval($row->interest),
                'principal' => floatval($row->principal),
                'total_collected' => floatval($row->total_collected),
                'payment_count' => (int) $row->payment_count
            ];
        }

        return array_values($months);
    }

    /**
     * Get Yearly Interest Summary (last N years)
     */
    public function get_yearly_interest_summary($num_years = 5) {
        $result = $this->db->select("
            YEAR(payment_date) as year,
            COALESCE(SUM(interest_component), 0) as interest,
            COALESCE(SUM(principal_component), 0) as principal,
            COALESCE(SUM(total_amount), 0) as total
        ")
        ->where('is_reversed', 0)
        ->where('YEAR(payment_date) >=', date('Y') - $num_years + 1)
        ->group_by('YEAR(payment_date)')
        ->order_by('YEAR(payment_date)', 'ASC')
        ->get('loan_payments')
        ->result();

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    // Profit & Loss Summary (Net Earnings)
    // ═══════════════════════════════════════════════════════════

    /**
     * Get Profit & Loss Summary
     *
     * Industry-standard P&L for cooperative banking:
     * REVENUE  = Membership Fee + Other Member Fee + Collected Fines + Interest on Loan
     * EXPENSES = Office Expenses + Bonus Distributed
     * NET PROFIT = REVENUE - EXPENSES
     *
     * Returns all-time and current-month figures for each line item.
     */
    public function get_profit_loss_summary() {
        $current_month = date('m');
        $current_year  = date('Y');

        // ── REVENUE ─────────────────────────────────────────────

        // 1. Membership Fee (reuse existing logic)
        $fee_summary = $this->get_fee_summary();
        $membership_fee_total = floatval($fee_summary['membership_fee']);
        $other_fee_total      = floatval($fee_summary['other_member_fee']);

        // Membership fee - this month
        $mf_month = 0;
        if ($this->db->table_exists('member_other_transactions')) {
            $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                          ->where('transaction_type', 'membership_fee')
                          ->where('MONTH(transaction_date)', $current_month)
                          ->where('YEAR(transaction_date)', $current_year)
                          ->get('member_other_transactions')->row();
            $mf_month += floatval($r->total ?? 0);
        }
        $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                      ->where('transaction_category', 'membership_fee')
                      ->where('mapping_status', 'mapped')
                      ->where('MONTH(transaction_date)', $current_month)
                      ->where('YEAR(transaction_date)', $current_year)
                      ->get('bank_transactions')->row();
        $mf_month += floatval($r->total ?? 0);

        // Other member fee - this month
        $of_month = 0;
        if ($this->db->table_exists('member_other_transactions')) {
            $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                          ->where('transaction_type !=', 'membership_fee')
                          ->where('MONTH(transaction_date)', $current_month)
                          ->where('YEAR(transaction_date)', $current_year)
                          ->get('member_other_transactions')->row();
            $of_month += floatval($r->total ?? 0);
        }
        $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                      ->where('mapping_type', 'other')
                      ->where('is_reversed', 0)
                      ->where('MONTH(mapped_at)', $current_month)
                      ->where('YEAR(mapped_at)', $current_year)
                      ->get('transaction_mappings')->row();
        $of_month += floatval($r->total ?? 0);

        // 2. Collected Fines (paid_amount from fines with status paid/partial)
        $fines_total_row = $this->db->select('COALESCE(SUM(paid_amount), 0) as total')
                                    ->where_in('status', ['paid', 'partial'])
                                    ->get('fines')->row();
        $fines_collected_total = floatval($fines_total_row->total ?? 0);

        $fines_month_row = $this->db->select('COALESCE(SUM(paid_amount), 0) as total')
                                    ->where_in('status', ['paid', 'partial'])
                                    ->where('MONTH(fine_date)', $current_month)
                                    ->where('YEAR(fine_date)', $current_year)
                                    ->get('fines')->row();
        $fines_collected_month = floatval($fines_month_row->total ?? 0);

        // 3. Interest on Loan (from loan_payments)
        $interest_stats = $this->get_interest_earned_stats();
        $interest_total = floatval($interest_stats['total_interest']);
        $interest_month = floatval($interest_stats['this_month_interest']);

        // ── EXPENSES ────────────────────────────────────────────

        // 4. Office Expenses (reuse existing logic)
        $expense_summary = $this->get_office_expense_summary();
        $expenses_total = floatval($expense_summary['total_expenses']);
        $expenses_month = floatval($expense_summary['this_month']);

        // 5. Bonus Distributed
        $bonus_total = 0;
        $bonus_month = 0;
        if ($this->db->table_exists('bonus_transactions')) {
            $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                          ->where('status', 'credited')
                          ->get('bonus_transactions')->row();
            $bonus_total = floatval($r->total ?? 0);

            $r = $this->db->select('COALESCE(SUM(amount), 0) as total')
                          ->where('status', 'credited')
                          ->where('MONTH(created_at)', $current_month)
                          ->where('YEAR(created_at)', $current_year)
                          ->get('bonus_transactions')->row();
            $bonus_month = floatval($r->total ?? 0);
        }

        // ── TOTALS ──────────────────────────────────────────────

        $total_revenue  = $membership_fee_total + $other_fee_total + $fines_collected_total + $interest_total;
        $total_expenses = $expenses_total + $bonus_total;
        $net_profit     = $total_revenue - $total_expenses;

        $month_revenue  = $mf_month + $of_month + $fines_collected_month + $interest_month;
        $month_expenses = $expenses_month + $bonus_month;
        $month_profit   = $month_revenue - $month_expenses;

        return [
            'revenue' => [
                'membership_fee'  => ['total' => $membership_fee_total, 'this_month' => $mf_month],
                'other_member_fee'=> ['total' => $other_fee_total,      'this_month' => $of_month],
                'fines_collected' => ['total' => $fines_collected_total, 'this_month' => $fines_collected_month],
                'interest_earned' => ['total' => $interest_total,       'this_month' => $interest_month],
            ],
            'expenses' => [
                'office_expenses' => ['total' => $expenses_total, 'this_month' => $expenses_month],
                'bonus_paid'      => ['total' => $bonus_total,    'this_month' => $bonus_month],
            ],
            'summary' => [
                'total_revenue'   => $total_revenue,
                'total_expenses'  => $total_expenses,
                'net_profit'      => $net_profit,
                'month_revenue'   => $month_revenue,
                'month_expenses'  => $month_expenses,
                'month_profit'    => $month_profit,
                'profit_margin'   => $total_revenue > 0 ? round(($net_profit / $total_revenue) * 100, 2) : 0,
            ],
        ];
    }

    // =====================================================================
    // REPORT CENTER — queries behind the spec-driven report pages
    // (screen + styled Excel from the same rows; see libraries/Excel_report.php)
    // =====================================================================

    /** Real-money category of a general-ledger reference type (cash/bank legs). */
    private function _gl_money_case() {
        return "CASE
            WHEN gl.reference_type LIKE 'rev\\_%' THEN 'reversal'
            WHEN gl.reference_type IN ('loan_payment','loan_foreclosure','loan_part_payment','interest_only') THEN 'emi'
            WHEN gl.reference_type IN ('savings_transaction','savings_deposit') THEN 'savings'
            WHEN gl.reference_type = 'savings_withdrawal' THEN 'withdrawal'
            WHEN gl.reference_type IN ('fine_payment','fine_income') THEN 'fines'
            WHEN gl.reference_type IN ('member_transaction','membership_fee','processing_fee') THEN 'fees'
            WHEN gl.reference_type IN ('loan_disbursement','disbursement_part','loan_topup') THEN 'disbursed'
            WHEN gl.reference_type IN ('expense','bank_expense') OR gl.reference_type LIKE 'expense%' THEN 'expense'
            ELSE 'other' END";
    }

    private function _account_ids(array $codes) {
        $rows = $this->db->select('id')->where_in('account_code', $codes)->get('chart_of_accounts')->result();
        return array_map(function ($r) { return (int) $r->id; }, $rows) ?: [0];
    }

    /**
     * Today / month-to-date tiles on the Reports home.
     */
    public function quick_summary($from, $to) {
        $q = function ($sql, $p) { $r = $this->db->query($sql, $p)->row(); return (float) ($r->s ?? 0); };
        return [
            'collections'        => $q("SELECT COALESCE(SUM(total_amount),0) s FROM loan_payments WHERE is_reversed = 0 AND payment_date BETWEEN ? AND ?", [$from, $to])
                                  + $q("SELECT COALESCE(SUM(amount),0) s FROM savings_transactions WHERE is_reversed = 0 AND transaction_type = 'deposit' AND transaction_date BETWEEN ? AND ?", [$from, $to]),
            'loan_collection'    => $q("SELECT COALESCE(SUM(total_amount),0) s FROM loan_payments WHERE is_reversed = 0 AND payment_date BETWEEN ? AND ?", [$from, $to]),
            'savings_collection' => $q("SELECT COALESCE(SUM(amount),0) s FROM savings_transactions WHERE is_reversed = 0 AND transaction_type = 'deposit' AND transaction_date BETWEEN ? AND ?", [$from, $to]),
            'interest_earned'    => $q("SELECT COALESCE(SUM(interest_component),0) s FROM loan_payments WHERE is_reversed = 0 AND payment_date BETWEEN ? AND ?", [$from, $to]),
            'fines_collected'    => $q("SELECT COALESCE(SUM(paid_amount),0) s FROM fines WHERE payment_date BETWEEN ? AND ?", [$from, $to]),
            'disbursements'      => $this->disbursed_sum($from, $to),
            'loan_disbursed'     => $this->disbursed_sum($from, $to),
            'new_members'        => (int) $q("SELECT COUNT(*) s FROM members WHERE DATE(created_at) BETWEEN ? AND ?", [$from, $to]),
            'loan_applications'  => (int) $q("SELECT COUNT(*) s FROM loan_applications WHERE DATE(created_at) BETWEEN ? AND ?", [$from, $to]),
        ];
    }

    /** Fine register: every fine with its member, source and settlement. */
    public function fine_register($from, $to, $status = '', $type = '') {
        $this->db->select("f.*, m.member_code, CONCAT(m.first_name, ' ', m.last_name) AS member_name, m.phone,
                           fr.rule_name, COALESCE(l.loan_number, l2.loan_number, sa.account_number, '') AS reference_no", false)
                 ->from('fines f')
                 ->join('members m', 'm.id = f.member_id', 'left')
                 ->join('fine_rules fr', 'fr.id = f.fine_rule_id', 'left')
                 ->join('loans l', 'l.id = f.loan_id', 'left')
                 ->join('loan_installments li', "li.id = f.related_id AND f.related_type = 'loan_installment'", 'left')
                 ->join('loans l2', 'l2.id = li.loan_id', 'left')
                 ->join('savings_schedule ss', "ss.id = f.related_id AND f.related_type = 'savings_schedule'", 'left')
                 ->join('savings_accounts sa', 'sa.id = ss.savings_account_id', 'left')
                 ->where('f.fine_date >=', $from)->where('f.fine_date <=', $to);
        if ($status === 'open') $this->db->where_in('f.status', ['pending', 'partial']);
        elseif ($status !== '') $this->db->where('f.status', $status);
        if ($type !== '') $this->db->where('f.fine_type', $type);
        return $this->db->order_by('f.fine_date', 'DESC')->order_by('f.id', 'DESC')->get()->result_array();
    }

    /** Member-wise dues as on a date: overdue EMIs + savings dues + open fines. */
    public function member_dues($as_on) {
        $sql = "
          SELECT m.id, m.member_code, CONCAT(m.first_name, ' ', m.last_name) AS member_name, m.phone,
                 COALESCE(ld.cnt, 0) AS emi_count, COALESCE(ld.amt, 0) AS emi_due, ld.oldest AS emi_oldest,
                 COALESCE(sd.cnt, 0) AS sav_count, COALESCE(sd.amt, 0) AS savings_due, sd.oldest AS sav_oldest,
                 COALESCE(fd.amt, 0) AS fine_due,
                 COALESCE(ld.amt, 0) + COALESCE(sd.amt, 0) + COALESCE(fd.amt, 0) AS total_due,
                 COALESCE(lp.cnt, 0) AS loans_without_payments
          FROM members m
          LEFT JOIN (SELECT l.member_id, COUNT(*) cnt, SUM(GREATEST(li.emi_amount - li.total_paid, 0)) amt, MIN(li.due_date) oldest
                       FROM loan_installments li JOIN loans l ON l.id = li.loan_id
                      WHERE l.status = 'active' AND li.status IN ('pending','overdue','partial') AND li.due_date <= ?
                      GROUP BY l.member_id) ld ON ld.member_id = m.id
          LEFT JOIN (SELECT sa.member_id, COUNT(*) cnt, SUM(GREATEST(ss.due_amount - ss.paid_amount, 0)) amt, MIN(ss.due_date) oldest
                       FROM savings_schedule ss JOIN savings_accounts sa ON sa.id = ss.savings_account_id
                      WHERE sa.status = 'active' AND ss.status IN ('pending','partial','overdue') AND ss.due_date <= ?
                      GROUP BY sa.member_id) sd ON sd.member_id = m.id
          LEFT JOIN (SELECT member_id, SUM(balance_amount) amt FROM fines WHERE status IN ('pending','partial') GROUP BY member_id) fd ON fd.member_id = m.id
          LEFT JOIN (SELECT l.member_id, COUNT(*) cnt FROM loans l
                      WHERE l.status = 'active' AND NOT EXISTS (SELECT 1 FROM loan_payments p WHERE p.loan_id = l.id AND p.is_reversed = 0)
                      GROUP BY l.member_id) lp ON lp.member_id = m.id
          WHERE COALESCE(ld.amt, 0) + COALESCE(sd.amt, 0) + COALESCE(fd.amt, 0) > 0
          ORDER BY total_due DESC";
        $rows = $this->db->query($sql, [$as_on, $as_on])->result_array();
        foreach ($rows as &$r) {
            $oldest = min(array_filter([$r['emi_oldest'], $r['sav_oldest']]) ?: [null]);
            $r['oldest_due'] = $oldest;
            $r['days_overdue'] = $oldest ? max(0, (int) floor((strtotime($as_on) - strtotime($oldest)) / 86400)) : 0;
            $r['flag'] = $r['loans_without_payments'] > 0 ? 'Loan has no payments recorded — check import' : '';
        }
        return $rows;
    }

    /** Portfolio by product with PAR (portfolio at risk) buckets. */
    public function loan_portfolio() {
        $sql = "
          SELECT lp.product_name,
                 COUNT(*) AS loans,
                 SUM(COALESCE(l.disbursed_amount, l.principal_amount)) AS disbursed,
                 SUM(l.principal_amount) AS sanctioned,
                 SUM(l.outstanding_principal) AS outstanding_principal,
                 SUM(l.outstanding_interest) AS outstanding_interest,
                 SUM(COALESCE(od.amt, 0)) AS overdue_amount,
                 SUM(CASE WHEN od.days > 0  THEN l.outstanding_principal ELSE 0 END) AS par_1,
                 SUM(CASE WHEN od.days > 30 THEN l.outstanding_principal ELSE 0 END) AS par_30,
                 SUM(CASE WHEN od.days > 90 THEN l.outstanding_principal ELSE 0 END) AS par_90,
                 SUM(CASE WHEN od.days > 0  THEN 1 ELSE 0 END) AS loans_overdue
            FROM loans l
            JOIN loan_products lp ON lp.id = l.loan_product_id
       LEFT JOIN (SELECT loan_id, SUM(GREATEST(emi_amount - total_paid, 0)) amt, DATEDIFF(CURDATE(), MIN(due_date)) days
                    FROM loan_installments WHERE status IN ('pending','overdue','partial') AND due_date < CURDATE()
                   GROUP BY loan_id) od ON od.loan_id = l.id
           WHERE l.status = 'active'
        GROUP BY lp.id, lp.product_name
        ORDER BY outstanding_principal DESC";
        $rows = $this->db->query($sql)->result_array();
        foreach ($rows as &$r) {
            $op = (float) $r['outstanding_principal'];
            $r['par_30_pct'] = $op > 0 ? round($r['par_30'] / $op * 100, 2) : 0;
            $r['par_90_pct'] = $op > 0 ? round($r['par_90'] / $op * 100, 2) : 0;
        }
        return $rows;
    }

    public function loan_status_summary() {
        return $this->db->query("
          SELECT l.status, COUNT(*) loans, SUM(COALESCE(l.disbursed_amount, l.principal_amount)) disbursed,
                 SUM(l.total_principal_paid) principal_paid, SUM(l.total_interest_paid) interest_paid,
                 SUM(l.outstanding_principal) outstanding_principal
            FROM loans l GROUP BY l.status ORDER BY loans DESC")->result_array();
    }

    /** Month-wise income & expense from the general ledger. */
    public function income_by_month($from, $to) {
        $rows = $this->db->query("
          SELECT DATE_FORMAT(gl.voucher_date, '%Y-%m') ym, c.account_code, c.account_type,
                 SUM(gl.credit_amount - gl.debit_amount) net
            FROM general_ledger gl JOIN chart_of_accounts c ON c.id = gl.account_id
           WHERE c.account_type IN ('income','expense') AND gl.voucher_date BETWEEN ? AND ?
        GROUP BY ym, c.account_code, c.account_type ORDER BY ym", [$from, $to])->result();
        $out = [];
        $cur = strtotime(date('Y-m-01', strtotime($from)));
        while ($cur <= strtotime($to)) {
            $k = date('Y-m', $cur);
            $out[$k] = ['month' => date('M Y', $cur), 'interest' => 0, 'processing' => 0, 'fines' => 0, 'membership' => 0,
                        'other_income' => 0, 'total_income' => 0, 'expenses' => 0, 'write_off' => 0, 'net' => 0];
            $cur = strtotime('+1 month', $cur);
        }
        foreach ($rows as $r) {
            if (!isset($out[$r->ym])) continue;
            $o =& $out[$r->ym];
            $v = (float) $r->net;
            if ($r->account_type === 'income') {
                $col = ['3100' => 'interest', '3200' => 'processing', '3300' => 'fines', '3400' => 'membership'][$r->account_code] ?? 'other_income';
                $o[$col] += $v;
                $o['total_income'] += $v;
            } else {
                if ($r->account_code === '4300') $o['write_off'] += -$v; else $o['expenses'] += -$v;
            }
            $o['net'] = $o['total_income'] - $o['expenses'] - $o['write_off'];
            unset($o);
        }
        return array_values($out);
    }

    /** Savings accounts register with dues. */
    public function savings_register($status = 'active') {
        $this->db->select("sa.account_number, sa.status, sa.monthly_amount, sa.start_date, sa.current_balance, sa.total_deposited,
                           sa.total_interest_earned, m.member_code, CONCAT(m.first_name, ' ', m.last_name) AS member_name, m.phone,
                           ss2.scheme_name,
                           (SELECT MAX(st.transaction_date) FROM savings_transactions st WHERE st.savings_account_id = sa.id AND st.transaction_type = 'deposit' AND st.is_reversed = 0) AS last_deposit,
                           (SELECT COALESCE(SUM(st.amount),0) FROM savings_transactions st WHERE st.savings_account_id = sa.id AND st.transaction_type = 'withdrawal' AND st.is_reversed = 0) AS withdrawn,
                           (SELECT COUNT(*) FROM savings_schedule s WHERE s.savings_account_id = sa.id AND s.status IN ('pending','partial','overdue') AND s.due_date < CURDATE()) AS months_due,
                           (SELECT COALESCE(SUM(GREATEST(s.due_amount - s.paid_amount, 0)),0) FROM savings_schedule s WHERE s.savings_account_id = sa.id AND s.status IN ('pending','partial','overdue') AND s.due_date < CURDATE()) AS amount_due", false)
                 ->from('savings_accounts sa')
                 ->join('members m', 'm.id = sa.member_id', 'left')
                 ->join('savings_schemes ss2', 'ss2.id = sa.scheme_id', 'left');
        if ($status !== '') $this->db->where('sa.status', $status);
        return $this->db->order_by('m.member_code', 'ASC')->get()->result_array();
    }

    /** Day book: real money in/out per day by head (cash + bank legs of the GL). */
    public function day_book($from, $to) {
        $ids = implode(',', $this->_account_ids(['1101', '1102']));
        $case = $this->_gl_money_case();
        $rows = $this->db->query("
          SELECT gl.voucher_date d, $case cat, c.account_code code,
                 SUM(gl.debit_amount) dr, SUM(gl.credit_amount) cr
            FROM general_ledger gl JOIN chart_of_accounts c ON c.id = gl.account_id
           WHERE gl.account_id IN ($ids) AND gl.voucher_date BETWEEN ? AND ?
        GROUP BY gl.voucher_date, cat, c.account_code ORDER BY gl.voucher_date", [$from, $to])->result();
        $open = $this->db->query("SELECT COALESCE(SUM(debit_amount - credit_amount),0) s FROM general_ledger WHERE account_id IN ($ids) AND voucher_date < ?", [$from])->row()->s;
        $days = [];
        foreach ($rows as $r) {
            $d =& $days[$r->d];
            if (!$d) $d = ['date' => $r->d, 'emi' => 0, 'savings' => 0, 'fines' => 0, 'fees' => 0, 'other_in' => 0, 'total_in' => 0,
                           'disbursed' => 0, 'withdrawal' => 0, 'expense' => 0, 'other_out' => 0, 'total_out' => 0,
                           'cash_net' => 0, 'bank_net' => 0, 'net' => 0, 'closing' => 0];
            $dr = (float) $r->dr; $cr = (float) $r->cr;
            if (in_array($r->cat, ['emi', 'savings', 'fines', 'fees'], true)) { $d[$r->cat] += $dr; $d['other_out'] += $cr; }
            elseif (in_array($r->cat, ['disbursed', 'withdrawal', 'expense'], true)) { $d[$r->cat] += $cr; $d['other_in'] += $dr; }
            else { $d['other_in'] += $dr; $d['other_out'] += $cr; }
            $d['total_in'] += $dr; $d['total_out'] += $cr;
            $d[$r->code === '1101' ? 'cash_net' : 'bank_net'] += $dr - $cr;
            unset($d);
        }
        $bal = (float) $open;
        foreach ($days as &$d) { $d['net'] = $d['total_in'] - $d['total_out']; $bal += $d['net']; $d['closing'] = round($bal, 2); }
        return ['opening' => round((float) $open, 2), 'rows' => array_values($days), 'closing' => round($bal, 2)];
    }

    /** Cash book / bank book from the general ledger with running balance. */
    public function cash_bank_book($code, $from, $to) {
        $acc = $this->db->where('account_code', $code)->get('chart_of_accounts')->row();
        if (!$acc) return ['account' => null, 'opening' => 0, 'rows' => [], 'closing' => 0];
        $open = (float) $this->db->query("SELECT COALESCE(SUM(debit_amount - credit_amount),0) s FROM general_ledger WHERE account_id = ? AND voucher_date < ?", [$acc->id, $from])->row()->s;
        $rows = $this->db->query("
          SELECT gl.voucher_date, gl.voucher_number, gl.voucher_type, gl.narration, gl.reference_type, gl.debit_amount, gl.credit_amount,
                 m.member_code, CONCAT(m.first_name, ' ', m.last_name) member_name
            FROM general_ledger gl LEFT JOIN members m ON m.id = gl.member_id
           WHERE gl.account_id = ? AND gl.voucher_date BETWEEN ? AND ?
        ORDER BY gl.voucher_date, gl.id", [$acc->id, $from, $to])->result_array();
        $bal = $open;
        foreach ($rows as &$r) { $bal += (float) $r['debit_amount'] - (float) $r['credit_amount']; $r['balance'] = round($bal, 2); }
        return ['account' => $acc, 'opening' => round($open, 2), 'rows' => $rows, 'closing' => round($bal, 2)];
    }

    /** Bank statement reconciliation: statement lines vs what is mapped in the books. */
    public function bank_reconciliation_report($from, $to) {
        $summary = $this->db->query("
          SELECT COALESCE(ba.account_name, CONCAT('Account #', bt.bank_account_id)) account_name, ba.account_number,
                 COUNT(*) line_count,
                 SUM(CASE WHEN bt.transaction_type = 'credit' THEN bt.amount ELSE 0 END) credits,
                 SUM(CASE WHEN bt.transaction_type = 'debit'  THEN bt.amount ELSE 0 END) debits,
                 SUM(CASE WHEN bt.mapping_status IN ('mapped','split') THEN 1 ELSE 0 END) mapped_lines,
                 SUM(CASE WHEN bt.mapping_status IN ('mapped','split','partial') THEN COALESCE(bt.mapped_amount, bt.amount) ELSE 0 END) mapped_amount,
                 SUM(CASE WHEN bt.mapping_status = 'ignored' THEN bt.amount ELSE 0 END) ignored_amount,
                 SUM(CASE WHEN bt.mapping_status IN ('unmapped','partial') THEN COALESCE(NULLIF(bt.unmapped_amount,0), bt.amount) ELSE 0 END) unmapped_amount,
                 SUM(CASE WHEN bt.mapping_status IN ('unmapped','partial') THEN 1 ELSE 0 END) unmapped_lines
            FROM bank_transactions bt LEFT JOIN bank_accounts ba ON ba.id = bt.bank_account_id
           WHERE bt.transaction_date BETWEEN ? AND ?
        GROUP BY bt.bank_account_id, ba.account_name, ba.account_number", [$from, $to])->result_array();
        $unmapped = $this->db->query("
          SELECT bt.id, bt.transaction_date, bt.transaction_type, bt.amount, bt.mapping_status,
                 COALESCE(NULLIF(bt.unmapped_amount,0), bt.amount) pending_amount, bt.description, bt.reference_number, bt.utr_number,
                 m.member_code, CONCAT(m.first_name, ' ', m.last_name) member_name
            FROM bank_transactions bt LEFT JOIN members m ON m.id = bt.detected_member_id
           WHERE bt.transaction_date BETWEEN ? AND ? AND bt.mapping_status IN ('unmapped','partial')
        ORDER BY bt.transaction_date, bt.id", [$from, $to])->result_array();
        $gl_bank = (float) $this->db->query("SELECT COALESCE(SUM(gl.debit_amount - gl.credit_amount),0) s FROM general_ledger gl
              JOIN chart_of_accounts c ON c.id = gl.account_id AND c.account_code = '1102' WHERE gl.voucher_date <= ?", [$to])->row()->s;
        $stmt = $this->db->query("SELECT COALESCE(NULLIF(balance_after,0), NULLIF(running_balance,0)) b FROM bank_transactions
              WHERE transaction_date <= ? AND COALESCE(NULLIF(balance_after,0), NULLIF(running_balance,0)) IS NOT NULL ORDER BY transaction_date DESC, id DESC LIMIT 1", [$to])->row();
        return ['summary' => $summary, 'unmapped' => $unmapped, 'gl_bank' => round($gl_bank, 2),
                'statement_balance' => $stmt ? (float) $stmt->b : null];
    }

    /** Members whose KYC is not verified / incomplete. */
    public function kyc_pending_list() {
        $rows = $this->db->select('id, member_code, first_name, last_name, phone, email, join_date, kyc_verified,
                                   aadhaar_number, pan_number, id_proof_type, id_proof_number, aadhaar_doc, pan_doc, address_proof_doc, photo, profile_image')
                         ->where('status', 'active')->where('deleted_at IS NULL', null, false)
                         ->order_by('member_code', 'ASC')->get('members')->result();
        $out = [];
        foreach ($rows as $m) {
            $missing = [];
            if (empty($m->aadhaar_number)) $missing[] = 'Aadhaar no.';
            if (empty($m->pan_number)) $missing[] = 'PAN';
            if (empty($m->aadhaar_doc)) $missing[] = 'Aadhaar copy';
            if (empty($m->pan_doc)) $missing[] = 'PAN copy';
            if (empty($m->address_proof_doc)) $missing[] = 'Address proof';
            if (empty($m->photo) && empty($m->profile_image)) $missing[] = 'Photo';
            if ((int) $m->kyc_verified === 1 && !$missing) continue;
            $m->pending_documents = count($missing);
            $m->missing = $missing ? implode(', ', $missing) : 'All documents present — verification pending';
            $m->member_name = trim($m->first_name . ' ' . $m->last_name);
            $out[] = $m;
        }
        return $out;
    }

    /** Demand vs collection by month: EMIs falling due in each month and how much of them is paid. */
    public function collection_efficiency($months = 12) {
        $from = date('Y-m-01', strtotime('-' . ((int) $months - 1) . ' months'));
        $rows = $this->db->query("
          SELECT DATE_FORMAT(li.due_date, '%Y-%m') ym, COUNT(*) emis,
                 SUM(li.emi_amount) demand, SUM(LEAST(li.total_paid, li.emi_amount)) collected,
                 SUM(CASE WHEN li.status = 'paid' THEN 1 ELSE 0 END) paid_emis,
                 SUM(CASE WHEN li.status = 'paid' AND li.paid_date <= li.due_date THEN 1 ELSE 0 END) on_time
            FROM loan_installments li JOIN loans l ON l.id = li.loan_id
           WHERE li.due_date BETWEEN ? AND LAST_DAY(CURDATE())
             AND li.status NOT IN ('cancelled','skipped','waived')
        GROUP BY ym ORDER BY ym", [$from])->result_array();
        foreach ($rows as &$r) {
            $r['month'] = date('M Y', strtotime($r['ym'] . '-01'));
            $r['pending'] = (float) $r['demand'] - (float) $r['collected'];
            $r['efficiency'] = $r['demand'] > 0 ? round($r['collected'] / $r['demand'] * 100, 2) : 0;
            $r['on_time_pct'] = $r['emis'] > 0 ? round($r['on_time'] / $r['emis'] * 100, 2) : 0;
        }
        return $rows;
    }
}
