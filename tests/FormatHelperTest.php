<?php
use PHPUnit\Framework\TestCase;

// Minimal helper include pattern used by other tests
require_once __DIR__ . '/../application/helpers/format_helper.php';

class FormatHelperTest extends TestCase {

    public function test_aadhaar_sanitization_and_validation() {
        $raw = '1234 5678 9012';
        $this->assertTrue(validate_aadhaar($raw));
        $this->assertEquals('XXXX XXXX 9012', mask_aadhaar($raw));

        $bad = '1234-567';
        $this->assertFalse(validate_aadhaar($bad));
    }

    public function test_pan_sanitization_and_validation() {
        $raw = 'abcde1234f';
        $this->assertTrue(validate_pan($raw));
        $this->assertEquals('ABC*****F', mask_pan($raw));

        $bad = 'AAAA11111A';
        $this->assertFalse(validate_pan($bad));
    }
}