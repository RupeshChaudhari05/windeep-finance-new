<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('ordinal_suffix')) {
    /**
     * Return number with English ordinal suffix (1st, 2nd, 3rd, 4th, ...)
     * @param int $number
     * @return string
     */
    function ordinal_suffix($number) {
        $n = (int) $number;
        if ($n % 100 >= 11 && $n % 100 <= 13) {
            $suffix = 'th';
        } else {
            switch ($n % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
                default:
                    $suffix = 'th';
            }
        }
        return $n . $suffix;
    }
}

if (!function_exists('format_date')) {
    /**
     * Safe date formatter - returns default when value is empty or invalid
     * @param mixed $value Timestamp, date string or null
     * @param string $format PHP date format string
     * @param string $default String to return when value is empty/invalid
     * @return string
     */
    function format_date($value, $format = 'd M Y', $default = '-') {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return $default;
        }
        // Allow passing 'now' or relative strings
        if (is_numeric($value)) {
            $ts = (int) $value;
        } else {
            $ts = @strtotime($value);
        }
        if ($ts === false || $ts === null) {
            return $default;
        }
        return date($format, $ts);
    }
}

if (!function_exists('format_date_time')) {
    function format_date_time($value, $format = 'd M Y H:i', $default = '-') {
        return format_date($value, $format, $default);
    }
}

if (!function_exists('safe_timestamp')) {
    /**
     * Return a safe unix timestamp for comparisons and calculations.
     * Returns 0 when the value is empty/invalid.
     * @param mixed $value
     * @return int
     */
    function safe_timestamp($value) {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return 0;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $ts = @strtotime($value);
        return $ts === false || $ts === null ? 0 : (int) $ts;
    }
}
