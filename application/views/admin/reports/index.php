<?php
/**
 * Report Center — every report in one searchable page.
 * Reports marked "Excel" download a styled workbook (title banner, KPI strip,
 * frozen header, filters, totals) from the same rows shown on screen.
 */
$groups = [
    ['Daily & Collections', 'fa-hand-holding-usd', 'success', [
        ['Day Book', 'admin/reports/day_book', 'fa-calendar-day', 'Money in / out per day by head — EMI, savings, fines, fees, disbursements, expenses', true, true],
        ['Collection Report', 'admin/reports/collection', 'fa-receipt', 'Every loan repayment and savings deposit received in a period', true, false],
        ['Collection Efficiency', 'admin/reports/collection_efficiency', 'fa-bullseye', 'EMI demand of each month vs collected, on-time %', true, true],
        ['Cash Book', 'admin/reports/cash-book', 'fa-book', 'Cash-in-hand ledger with running balance (switch to Bank Book)', true, true],
        ['Other Transactions', 'admin/reports/other-transactions', 'fa-file-invoice', 'Membership, joining, processing fees and other receipts', true, false],
    ]],
    ['Dues & Recovery', 'fa-user-clock', 'danger', [
        ['Member Dues', 'admin/reports/member_dues', 'fa-user-clock', 'Who owes what: overdue EMIs + savings dues + open fines per member', true, true],
        ['Demand Report', 'admin/reports/demand', 'fa-bell', 'EMIs and savings due in a month', true, false],
        ['Ageing Analysis', 'admin/reports/ageing', 'fa-hourglass-half', 'Overdue loans in 0-30 / 31-60 / 61-90 / 90+ day buckets', true, false],
        ['NPA Report', 'admin/reports/npa', 'fa-exclamation-triangle', 'Loans overdue beyond the NPA threshold', true, false],
        ['Fine Register', 'admin/reports/fines', 'fa-gavel', 'All fines with status, collected, waived and balance', true, true],
    ]],
    ['Loans', 'fa-file-contract', 'primary', [
        ['Loan Portfolio', 'admin/reports/portfolio', 'fa-briefcase', 'Product-wise outstanding, overdue and PAR 30 / PAR 90', true, true],
        ['Outstanding Loans', 'admin/reports/outstanding', 'fa-file-invoice-dollar', 'Every active loan with outstanding and overdue amount', true, false],
        ['Disbursement Report', 'admin/reports/disbursement', 'fa-money-bill-wave', 'Loans disbursed in a period (actual money incl. parts)', true, false],
        ['Processing Fee Check', 'admin/reports/processing_fees', 'fa-file-invoice-dollar', 'Every loan (old, closed, active): fee charged, fee entry and ledger — fix missing fees', true, true],
        ['Guarantor Exposure', 'admin/reports/guarantor', 'fa-user-shield', 'Guarantors and the loans they stand for', true, false],
    ]],
    ['Members & Savings', 'fa-users', 'info', [
        ['Savings Register', 'admin/reports/savings_register', 'fa-piggy-bank', 'Account-wise balance, deposits, withdrawals and months due', true, true],
        ['Member Statement', 'admin/reports/member-statement', 'fa-file-alt', 'Full statement of one member — ledger, savings, loans, fines', true, false],
        ['Member Summary', 'admin/reports/member-summary', 'fa-users', 'All members with savings, loans and fines at a glance', true, false],
        ['KYC Pending', 'admin/reports/kyc-pending', 'fa-id-card', 'Members with missing documents or unverified KYC', true, true],
    ]],
    ['Accounts', 'fa-calculator', 'warning', [
        ['Income Analysis', 'admin/reports/income', 'fa-chart-line', 'Month-wise interest, fees, fines, expenses and net surplus', true, true],
        ['Profit & Loss', 'admin/reports/profit-loss', 'fa-chart-pie', 'Income and expenditure for a period', true, false],
        ['Balance Sheet', 'admin/reports/balance-sheet', 'fa-balance-scale-right', 'Assets, liabilities and capital as on a date', true, false],
        ['Trial Balance', 'admin/reports/trial-balance', 'fa-list-ol', 'Debit and credit balance of every account', true, false],
        ['General Ledger', 'admin/reports/general-ledger', 'fa-book-open', 'Every voucher line for a period or account', true, false],
        ['Account Statement', 'admin/reports/account-statement', 'fa-file-invoice', 'One account with opening and running balance', true, false],
        ['Bank Reconciliation', 'admin/reports/bank-reconciliation', 'fa-university', 'Statement lines mapped vs not mapped, books vs statement', true, true],
        ['Monthly Summary', 'admin/reports/monthly-summary', 'fa-calendar-alt', 'One-page summary of a month', true, false],
    ]],
    ['Exports & Audit', 'fa-file-export', 'secondary', [
        ['All Loans workbook', 'admin/reports/export_loans_full', 'fa-file-excel', 'Loans + installments + fines in one Excel file', true, false],
        ['All Savings workbook', 'admin/reports/export_savings_full', 'fa-file-excel', 'Savings accounts + transactions in one Excel file', true, false],
        ['Members list (CSV)', 'admin/reports/export_members', 'fa-file-csv', 'All member details for re-import / backup', false, false],
        ['Audit Trail', 'admin/reports/audit-log', 'fa-history', 'Who changed what and when', false, false],
        ['Custom Report', 'admin/reports/custom', 'fa-sliders-h', 'Build your own report', false, false],
    ]],
];
$t = $today_summary ?? [];
$m = $mtd_summary ?? [];
?>
<style>
.rc-tile{display:flex;align-items:flex-start;padding:12px 14px;border:1px solid #e3e8ef;border-radius:8px;background:#fff;color:#212529;height:100%;transition:all .15s}
.rc-tile:hover{border-color:#2E5C8A;box-shadow:0 3px 10px rgba(46,92,138,.15);text-decoration:none;color:#212529;transform:translateY(-1px)}
.rc-ico{width:38px;height:38px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex:0 0 38px;margin-right:12px;font-size:1rem}
.rc-tile .t{font-weight:600;font-size:.95rem}
.rc-tile .d{font-size:.78rem;color:#6c7a89;line-height:1.3}
.rc-badge{font-size:.62rem;vertical-align:middle;margin-left:4px}
.rc-stat{background:#fff;border-radius:8px;padding:10px 14px;border-left:4px solid #2E5C8A;box-shadow:0 1px 2px rgba(0,0,0,.06)}
.rc-stat .l{font-size:.7rem;text-transform:uppercase;color:#6c7a89;font-weight:600}
.rc-stat .v{font-size:1.15rem;font-weight:700;color:#1F3A5F}
</style>

<div class="row mb-3">
    <div class="col-12 mb-2 d-flex align-items-center flex-wrap">
        <h5 class="mb-0 mr-3"><i class="fas fa-calendar-day text-primary mr-1"></i> Today <small class="text-muted"><?= format_date(date('Y-m-d')) ?></small></h5>
    </div>
    <?php foreach ([['Collections', $t['collections'] ?? 0, 'success'], ['Disbursed', $t['disbursements'] ?? 0, 'danger'],
                    ['New members', $t['new_members'] ?? 0, 'info', 'int'], ['Loan applications', $t['loan_applications'] ?? 0, 'warning', 'int'],
                    ['Month: savings', $m['savings_collection'] ?? 0, 'success'], ['Month: loan repayments', $m['loan_collection'] ?? 0, 'primary'],
                    ['Month: disbursed', $m['loan_disbursed'] ?? 0, 'danger'], ['Month: interest earned', $m['interest_earned'] ?? 0, 'warning']] as $s): ?>
    <div class="col-6 col-md-3 col-xl-3 mb-2">
        <div class="rc-stat" style="border-left-color:var(--<?= $s[2] ?>)">
            <div class="l"><?= $s[0] ?></div>
            <div class="v"><?= ($s[3] ?? '') === 'int' ? number_format((int) $s[1]) : format_amount($s[1], 0) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="card card-primary card-outline">
    <div class="card-header d-flex flex-wrap align-items-center">
        <h3 class="card-title mb-0 mr-auto"><i class="fas fa-chart-bar mr-1"></i> Report Center</h3>
        <div class="input-group input-group-sm mt-2 mt-md-0" style="max-width:320px">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div>
            <input type="text" id="rcSearch" class="form-control" placeholder="Find a report… (e.g. fine, dues, bank)">
        </div>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-3">
            <span class="badge badge-success rc-badge">Excel</span> = styled Excel download with the same figures as the screen.
            <span class="badge badge-info rc-badge">New</span> = added in this update.
        </p>
        <?php foreach ($groups as $g): ?>
        <div class="rc-group mb-3">
            <h6 class="text-<?= $g[2] ?> text-uppercase font-weight-bold mb-2" style="letter-spacing:.04em"><i class="fas <?= $g[1] ?> mr-1"></i> <?= $g[0] ?></h6>
            <div class="row">
                <?php foreach ($g[3] as $r): ?>
                <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-2 rc-item" data-q="<?= html_escape(strtolower($r[0] . ' ' . $r[3] . ' ' . $g[0])) ?>">
                    <a class="rc-tile" href="<?= site_url($r[1]) ?>">
                        <span class="rc-ico bg-<?= $g[2] ?>"><i class="fas <?= $r[2] ?> text-white"></i></span>
                        <span>
                            <span class="t"><?= $r[0] ?></span>
                            <?php if ($r[4]): ?><span class="badge badge-success rc-badge">Excel</span><?php endif; ?>
                            <?php if ($r[5]): ?><span class="badge badge-info rc-badge">New</span><?php endif; ?>
                            <span class="d d-block"><?= $r[3] ?></span>
                        </span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <p id="rcNone" class="text-muted text-center py-3 mb-0" style="display:none">No report matches your search.</p>
    </div>
</div>

<script>
$(function () {
    $('#rcSearch').on('input', function () {
        var q = $(this).val().toLowerCase().trim(), shown = 0;
        $('.rc-item').each(function () {
            var ok = !q || $(this).data('q').indexOf(q) !== -1;
            $(this).toggle(ok); if (ok) shown++;
        });
        $('.rc-group').each(function () { $(this).toggle($(this).find('.rc-item:visible').length > 0); });
        $('#rcNone').toggle(shown === 0);
    }).focus();
});
</script>
