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

if (!function_exists('sanitize_aadhaar')) {
    /**
     * Remove non-digit characters from Aadhaar and return null when empty
     */
    function sanitize_aadhaar($value) {
        if ($value === null) return null;
        $v = preg_replace('/\D+/', '', (string) $value);
        return $v === '' ? null : $v;
    }
}

if (!function_exists('validate_aadhaar')) {
    /**
     * Validate Aadhaar: exactly 12 digits
     */
    function validate_aadhaar($value) {
        $v = sanitize_aadhaar($value);
        return $v !== null && preg_match('/^\d{12}$/', $v) === 1;
    }
}

if (!function_exists('mask_aadhaar')) {
    /**
     * Mask Aadhaar for display (show only last 4 digits)
     */
    function mask_aadhaar($value) {
        $v = sanitize_aadhaar($value);
        if (!$v) return '-';
        $last4 = substr($v, -4);
        return 'XXXX XXXX ' . $last4;
    }
}

if (!function_exists('sanitize_pan')) {
    /**
     * Normalize PAN to uppercase without spaces
     */
    function sanitize_pan($value) {
        if ($value === null) return null;
        $v = strtoupper(preg_replace('/\s+/', '', (string) $value));
        return $v === '' ? null : $v;
    }
}

if (!function_exists('validate_pan')) {
    /**
     * Validate PAN: 5 letters, 4 digits, 1 letter
     */
    function validate_pan($value) {
        $v = sanitize_pan($value);
        return $v !== null && preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $v) === 1;
    }
}

if (!function_exists('mask_pan')) {
    /**
     * Mask PAN for display (show first 3 chars + last char)
     */
    function mask_pan($value) {
        $v = sanitize_pan($value);
        if (!$v) return '-';
        $start = substr($v, 0, 3);
        $end = substr($v, -1);
        return $start . '*****' . $end;
    }
}

if (!function_exists('sanitize_phone')) {
    /**
     * Remove non-digit characters from phone and return null when empty
     */
    function sanitize_phone($value) {
        if ($value === null) return null;
        $v = preg_replace('/\D+/', '', (string) $value);
        return $v === '' ? null : $v;
    }
}

if (!function_exists('normalize_phone')) {
    /**
     * Normalize phone to canonical form.
     * - strips non-digits
     * - if >10 digits, keeps last 10 digits (common for country codes like +91)
     * - returns null when invalid
     */
    function normalize_phone($value) {
        $v = sanitize_phone($value);
        if ($v === null) return null;
        // If contains country code, prefer last 10 digits
        if (strlen($v) >= 10) {
            return substr($v, -10);
        }
        return $v;
    }
}

if (!function_exists('validate_phone')) {
    /**
     * Validate phone is 10 digits (after normalization)
     */
    function validate_phone($value) {
        $v = normalize_phone($value);
        return $v !== null && preg_match('/^[0-9]{10}$/', $v) === 1;
    }
}
