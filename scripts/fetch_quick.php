<?php
$html = @file_get_contents('http://localhost/windeep_finance/admin/savings/collect/6');
if (!$html) {
    echo "Failed to fetch page\n";
    exit(1);
}
if (preg_match('/<label class="d-block">Quick Select:<\/label>.*?<\/div>/s', $html, $m)) {
    echo $m[0];
} else {
    echo "Quick Select block not found\n";
}
