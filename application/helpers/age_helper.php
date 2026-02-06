<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Age helper
 */
if (!function_exists('calculate_age')) {
    function calculate_age($dob) {
        if (empty($dob)) return null;

        try {
            $dt = new DateTime($dob);
        } catch (Exception $e) {
            $ts = strtotime($dob);
            if ($ts === FALSE) return null;
            $dt = (new DateTime())->setTimestamp($ts);
        }

        $now = new DateTime();
        return $now->diff($dt)->y;
    }
}

if (!function_exists('is_age_at_least')) {
    function is_age_at_least($dob, $min = 18) {
        $age = calculate_age($dob);
        if ($age === null) return false;
        return ($age >= $min);
    }
}
