<?php
$html = @file_get_contents('http://localhost/windeep_finance/admin/savings/collect/6');
if (!$html) {
    echo "Failed to fetch page\n";
    exit(1);
}
$pos = strpos($html,'Collect Savings Payment');
if ($pos === false) {
    echo "Heading not found\n";
    echo substr($html,0,1000);
    exit(0);
}
$start = max(0,$pos-200);
echo substr($html,$start,1200);
file_put_contents('tmp_full_collect.html',$html);
