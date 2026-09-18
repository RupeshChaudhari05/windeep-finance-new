<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Builders for guarantor / loan-request notification text.
 *
 * Notifications used to carry only the application number, so a list of them
 * was indistinguishable — you could not tell who applied, who guaranteed, for
 * how much, or when. These assemble the names, amount and date once so every
 * path (member portal, public consent link) reads the same.
 */

if (!function_exists('notif_member_name')) {
    /** "First Middle Last (MEMB000123)" from a member row or id. */
    function notif_member_name($member, $with_code = true)
    {
        if (empty($member)) {
            return 'Unknown member';
        }

        if (!is_object($member) && !is_array($member)) {
            $CI =& get_instance();
            $CI->load->model('Member_model');
            $member = $CI->Member_model->get_by_id($member);
            if (empty($member)) {
                return 'Unknown member';
            }
        }

        $CI =& get_instance();
        $CI->load->helper('format');
        $name = function_exists('member_full_name')
              ? member_full_name($member)
              : trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

        $code = is_object($member) ? ($member->member_code ?? '') : ($member['member_code'] ?? '');

        if ($with_code && $code !== '') {
            return trim($name) . ' (' . $code . ')';
        }
        return trim($name) !== '' ? trim($name) : 'Unknown member';
    }
}

if (!function_exists('notif_guarantor_names')) {
    /** Comma-separated guarantor names for an application, with consent state. */
    function notif_guarantor_names($application_id, $only_status = null)
    {
        $CI =& get_instance();
        $CI->db->select('g.consent_status, m.first_name, m.middle_name, m.last_name, m.member_code')
               ->from('loan_guarantors g')
               ->join('members m', 'm.id = g.guarantor_member_id', 'left')
               ->where('g.loan_application_id', $application_id);
        if ($only_status !== null) {
            $CI->db->where('g.consent_status', $only_status);
        }
        $rows = $CI->db->get()->result();

        $names = [];
        foreach ($rows as $r) {
            $names[] = notif_member_name($r);
        }
        return $names ? implode(', ', $names) : 'none recorded';
    }
}

if (!function_exists('notif_loan_request_summary')) {
    /**
     * A one-line summary of who is asking for what.
     *
     * @param object $application  loan_applications row
     */
    function notif_loan_request_summary($application)
    {
        $CI =& get_instance();
        $CI->load->helper('format');

        $applicant = notif_member_name($application->member_id ?? null);
        $amount    = isset($application->requested_amount) && $application->requested_amount !== null
                   ? format_amount($application->requested_amount)
                   : null;
        $tenure    = !empty($application->requested_tenure_months)
                   ? $application->requested_tenure_months . ' months'
                   : null;

        $bits = ['Applicant: ' . $applicant];
        if ($amount) { $bits[] = 'Amount: ' . $amount; }
        if ($tenure) { $bits[] = 'Tenure: ' . $tenure; }
        $bits[] = 'Application: ' . ($application->application_number ?? ('#' . ($application->id ?? '?')));
        $bits[] = 'Date: ' . date('d M Y, h:i A');

        return implode(' | ', $bits);
    }
}
