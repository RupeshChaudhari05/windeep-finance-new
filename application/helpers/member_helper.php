<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Helper: Member display utilities

if (!function_exists('member_profile_image_url')) {
    function member_profile_image_url($member, $size = null) {
        $ci = get_instance();
        // Accept either profile_image or profile_photo
        $img = $member->profile_image ?? ($member->profile_photo ?? null);
        if (!empty($img)) {
            $path = FCPATH . 'uploads/profile_images/' . $img;
            if (file_exists($path)) {
                // Optionally we could append sizing query, but we return raw URL
                return base_url('uploads/profile_images/' . $img);
            }
        }
        return null;
    }
}

if (!function_exists('member_avatar_html')) {
    function member_avatar_html($member, $size = 32, $class = '') {
        $ci = get_instance();
        $url = member_profile_image_url($member, $size);
        $w = (int)$size;
        $h = $w;
        if ($url) {
            $classAttr = $class ? ' ' . html_escape($class) : '';
            return '<img src="' . html_escape($url) . '" class="img-circle' . $classAttr . '" style="width: ' . $w . 'px; height: ' . $h . 'px; object-fit: cover;">';
        }
        // initials fallback
        $initial = strtoupper(substr(trim($member->first_name ?? ''), 0, 1) ?: substr($member->member_code ?? '-', 0, 1));
        $bg = 'bg-secondary';
        $html = '<div class="img-circle ' . $bg . ' text-white d-flex align-items-center justify-content-center mr-2" style="width: ' . $w . 'px; height: ' . $h . 'px;">' . html_escape($initial) . '</div>';
        return $html;
    }
}

if (!function_exists('member_formatted_income')) {
    function member_formatted_income($member) {
        if (!empty($member->monthly_income)) {
            return '₹' . number_format($member->monthly_income);
        }
        return '-';
    }
}

if (!function_exists('member_formatted_address')) {
    function member_formatted_address($member) {
        $parts = [];
        if (!empty($member->address_line1)) $parts[] = html_escape($member->address_line1);
        if (!empty($member->address_line2)) $parts[] = html_escape($member->address_line2);
        $city = $member->city ?? '';
        $state = $member->state ?? '';
        $line3 = trim(($city ? $city : '') . ($state ? ', ' . $state : ''));
        if (!empty($line3)) $parts[] = html_escape($line3);
        if (!empty($member->pincode)) $parts[] = html_escape($member->pincode);
        if (empty($parts)) return '-';
        return implode('<br>', $parts);
    }
}
