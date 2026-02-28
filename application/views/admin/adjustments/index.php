<!-- Admin Adjustments Console - Super Admin Only -->
<!-- Member search → overview → drill-down editable tables for loans, savings, fines -->

<style>
/* Editable Cell Styles */
.cell-editable {
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
}
.cell-editable:hover {
    background-color: #fff3cd !important;
}
.cell-editable.editing {
    background-color: #d4edda !important;
    padding: 2px !important;
}
.cell-editable .edit-input {
    width: 100%;
    border: 2px solid #28a745;
    border-radius: 3px;
    padding: 2px 5px;
    font-size: 13px;
}
.cell-editable .cell-value {
    display: inline-block;
    min-width: 40px;
    min-height: 18px;
}
.cell-editable .edit-icon {
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 10px;
    color: #6c757d;
    margin-left: 4px;
}
.cell-editable:hover .edit-icon {
    opacity: 1;
}
.cell-changed {
    background-color: #d4edda !important;
    font-weight: bold;
}
.cell-changed::after {
    content: ' *';
    color: #28a745;
    font-size: 10px;
}

/* Table Styles */
.adj-table {
    font-size: 12px;
}
.adj-table th {
    white-space: nowrap;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    z-index: 10;
    background: #343a40;
    color: #fff;
}
.adj-table td {
    white-space: nowrap;
    vertical-align: middle !important;
}
.adj-table .row-overdue {
    background-color: #f8d7da !important;
}
.adj-table .row-paid {
    background-color: #d4edda !important;
}
.adj-table .row-partial {
    background-color: #fff3cd !important;
}
.adj-table .row-skipped {
    background-color: #e2e3e5 !important;
}
.adj-table .row-interest-only {
    background-color: #cce5ff !important;
}

/* Overview Cards */
.overview-card {
    border-left: 4px solid;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.overview-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.overview-card.card-loan { border-color: #007bff; }
.overview-card.card-savings { border-color: #28a745; }
.overview-card.card-fine { border-color: #ffc107; }

/* Drill-down panel */
.drill-panel {
    max-height: 600px;
    overflow-y: auto;
}

/* Member Info */
.member-badge {
    font-size: 14px;
}

/* Sticky header helper */
.table-responsive-adj {
    max-height: 550px;
    overflow: auto;
}
</style>

<div class="row">
    <!-- Search Panel -->
    <div class="col-12">
        <div class="card card-dark">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield mr-2"></i> Admin Adjustments Console
                </h3>
                <div class="card-tools">
                    <span class="badge badge-danger">Super Admin Only</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label><i class="fas fa-search mr-1"></i> Search Member</label>
                        <select class="form-control" id="memberSearch" style="width:100%">
                            <option value="">Type member name, code, phone, or aadhaar...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary btn-block" id="btnLoadMember" disabled>
                            <i class="fas fa-folder-open mr-1"></i> Load Member Data
                        </button>
                    </div>
                </div>
                <small class="text-muted mt-1 d-block">
                    <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                    All changes on this page are audit-logged. Every edit requires a reason and is permanently recorded.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Member Overview Section (Hidden until member selected) -->
<div id="memberOverview" class="d-none">
    <!-- Member Info Bar -->
    <div class="row">
        <div class="col-12">
            <div class="callout callout-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-user mr-2"></i>
                            <span id="memberName">-</span>
                            <span class="badge badge-secondary ml-2" id="memberCode">-</span>
                            <span class="badge ml-1" id="memberStatus">-</span>
                        </h5>
                        <small class="text-muted">
                            <i class="fas fa-phone mr-1"></i> <span id="memberPhone">-</span>
                            <span class="mx-2">|</span>
                            <i class="fas fa-envelope mr-1"></i> <span id="memberEmail">-</span>
                        </small>
                    </div>
                    <div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="refreshOverview()">
                            <i class="fas fa-sync-alt mr-1"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Loans</span>
                    <span class="info-box-number" id="statLoans">0</span>
                    <span class="progress-description" id="statActiveLoans">0 active</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-danger">
                <span class="info-box-icon"><i class="fas fa-rupee-sign"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Outstanding</span>
                    <span class="info-box-number" id="statOutstanding">0</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Savings</span>
                    <span class="info-box-number" id="statSavingsBalance">0</span>
                    <span class="progress-description" id="statSavingsAccounts">0 accounts</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Fines</span>
                    <span class="info-box-number" id="statFinesAmount">0</span>
                    <span class="progress-description" id="statFinesCount">0 fines</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs: Loans | Savings | Fines -->
    <div class="card card-primary card-outline card-tabs">
        <div class="card-header p-0 pt-1">
            <ul class="nav nav-tabs" id="mainTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="tab-loans" data-toggle="tab" href="#panel-loans" role="tab">
                        <i class="fas fa-hand-holding-usd mr-1"></i> Loans
                        <span class="badge badge-primary ml-1" id="tabLoanCount">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-savings" data-toggle="tab" href="#panel-savings" role="tab">
                        <i class="fas fa-piggy-bank mr-1"></i> Savings
                        <span class="badge badge-success ml-1" id="tabSavingsCount">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="tab-fines" data-toggle="tab" href="#panel-fines" role="tab">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Fines
                        <span class="badge badge-warning ml-1" id="tabFineCount">0</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="mainTabContent">
                
                <!-- LOANS TAB -->
                <div class="tab-pane fade show active" id="panel-loans" role="tabpanel">
                    <div id="loansListContainer">
                        <p class="text-muted">Select a member to view loans</p>
                    </div>
                    <!-- Drill-down: Loan Installments -->
                    <div id="loanDrillPanel" class="d-none mt-3">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-table mr-1"></i>
                                    Installment Schedule - <span id="drillLoanNumber">-</span>
                                </h3>
                                <div class="card-tools">
                                    <button class="btn btn-tool btn-sm" id="btnRecalcLoan" title="Recalculate loan totals from installments">
                                        <i class="fas fa-calculator"></i> Recalculate
                                    </button>
                                    <button class="btn btn-tool btn-sm" id="btnAuditLoan" title="View audit history">
                                        <i class="fas fa-history"></i> Audit Log
                                    </button>
                                    <button class="btn btn-tool btn-sm" onclick="closeLoanDrill()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Loan Header (editable) -->
                            <div class="card-body py-2" id="loanHeaderInfo">
                            </div>
                            <!-- Installments Grid -->
                            <div class="table-responsive-adj">
                                <table class="table table-bordered table-sm adj-table mb-0" id="installmentsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Due Date</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>EMI</th>
                                            <th>Prin. Paid</th>
                                            <th>Int. Paid</th>
                                            <th>Fine</th>
                                            <th>Fine Paid</th>
                                            <th>Total Paid</th>
                                            <th>Status</th>
                                            <th>Paid Date</th>
                                            <th>Late</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot class="bg-light font-weight-bold">
                                        <tr id="installmentsTotals"></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Payments History for selected loan -->
                        <div class="card card-outline card-secondary mt-2">
                            <div class="card-header py-2">
                                <h3 class="card-title"><i class="fas fa-receipt mr-1"></i> Payment Records</h3>
                                <div class="card-tools">
                                    <button class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm table-striped mb-0" id="paymentsTable">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Principal</th>
                                            <th>Interest</th>
                                            <th>Fine</th>
                                            <th>Mode</th>
                                            <th>Narration</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SAVINGS TAB -->
                <div class="tab-pane fade" id="panel-savings" role="tabpanel">
                    <div id="savingsListContainer">
                        <p class="text-muted">Select a member to view savings</p>
                    </div>
                    <!-- Drill-down: Savings Schedule -->
                    <div id="savingsDrillPanel" class="d-none mt-3">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-table mr-1"></i>
                                    Savings Schedule - <span id="drillSavingsName">-</span>
                                </h3>
                                <div class="card-tools">
                                    <button class="btn btn-tool btn-sm" id="btnRecalcSavings" title="Recalculate balance from transactions">
                                        <i class="fas fa-calculator"></i> Recalculate
                                    </button>
                                    <button class="btn btn-tool btn-sm" id="btnAuditSavings" title="View audit history">
                                        <i class="fas fa-history"></i> Audit Log
                                    </button>
                                    <button class="btn btn-tool btn-sm" onclick="closeSavingsDrill()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body py-2" id="savingsHeaderInfo">
                            </div>
                            <div class="table-responsive-adj">
                                <table class="table table-bordered table-sm adj-table mb-0" id="savingsScheduleTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Month</th>
                                            <th>Due Date</th>
                                            <th>Due Amount</th>
                                            <th>Paid Amount</th>
                                            <th>Fine</th>
                                            <th>Fine Paid</th>
                                            <th>Status</th>
                                            <th>Paid Date</th>
                                            <th>Late</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FINES TAB -->
                <div class="tab-pane fade" id="panel-fines" role="tabpanel">
                    <div id="finesListContainer">
                        <p class="text-muted">Select a member to view fines</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Adjustment Reason Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit mr-1"></i> Adjust Value</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2">
                    <strong>Field:</strong> <span id="adjFieldName">-</span><br>
                    <strong>Current Value:</strong> <span id="adjOldValue">-</span>
                </div>
                <div class="form-group">
                    <label>New Value <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="adjNewValue" placeholder="Enter new value">
                </div>
                <div class="form-group">
                    <label>Reason for Adjustment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="adjReason" rows="3" placeholder="Explain why this adjustment is needed (mandatory)..."></textarea>
                    <small class="text-muted">This will be permanently recorded in the audit trail.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="btnConfirmAdjust">
                    <i class="fas fa-check mr-1"></i> Confirm Adjustment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audit Log Modal -->
<div class="modal fade" id="auditModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title"><i class="fas fa-history mr-1"></i> Audit History</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-striped mb-0" id="auditTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Old Values</th>
                            <th>New Values</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Wait until all scripts (including Select2 from footer) are loaded
$(window).on('load', function() {
    var CS = '<?= get_currency_symbol() ?>';
    var BASE = '<?= site_url('admin/adjustments/') ?>';
    var CSRF_NAME = '<?= $this->security->get_csrf_token_name() ?>';
    var CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
    
    var selectedMemberId = null;
    var memberData = {};
    var currentLoanId = null;
    var currentSavingsId = null;
    
    // Pending adjustment context
    var adjContext = {};

    // ─── Member Search (Select2) ───
    $('#memberSearch').select2({
        placeholder: 'Type member name, code, phone, or aadhaar...',
        minimumInputLength: 1,
        allowClear: true,
        width: '100%',
        ajax: {
            url: BASE + 'search_members',
            dataType: 'json',
            delay: 250,
            data: function(params) { return { q: params.term, limit: 20 }; },
            processResults: function(resp) {
                var items = resp && resp.results ? resp.results : [];
                return { results: items };
            }
        },
        templateResult: function(item) {
            if (!item.id) return item.text;
            var html = '<span>' + item.text + '</span>';
            if (item.status && item.status !== 'active') {
                html += ' <span class="badge badge-secondary">' + item.status + '</span>';
            }
            return $(html);
        }
    }).on('select2:select', function(e) {
        selectedMemberId = e.params.data.id;
        $('#btnLoadMember').prop('disabled', false);
    }).on('select2:clear', function() {
        selectedMemberId = null;
        $('#btnLoadMember').prop('disabled', true);
    });

    $('#btnLoadMember').on('click', function() {
        if (!selectedMemberId) return;
        loadMemberOverview(selectedMemberId);
    });

    // ─── Load Member Overview ───
    window.refreshOverview = function() {
        if (selectedMemberId) loadMemberOverview(selectedMemberId);
    };

    function loadMemberOverview(memberId) {
        $('#btnLoadMember').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Loading...');
        
        $.getJSON(BASE + 'member_overview/' + memberId, function(resp) {
            if (!resp.success) {
                toastr.error(resp.message || 'Failed to load member');
                return;
            }
            
            memberData = resp;
            
            // Populate member info
            $('#memberName').text(resp.member.name);
            $('#memberCode').text(resp.member.member_code);
            $('#memberPhone').text(resp.member.phone);
            $('#memberEmail').text(resp.member.email || '-');
            
            var statusClass = resp.member.status === 'active' ? 'badge-success' : 'badge-secondary';
            $('#memberStatus').attr('class', 'badge ml-1 ' + statusClass).text(resp.member.status);
            
            // Stats
            $('#statLoans').text(resp.summary.total_loans);
            $('#statActiveLoans').text(resp.summary.active_loans + ' active');
            $('#statOutstanding').text(CS + Number(resp.summary.total_outstanding).toLocaleString('en-IN'));
            $('#statSavingsBalance').text(CS + Number(resp.summary.total_savings_balance).toLocaleString('en-IN'));
            $('#statSavingsAccounts').text(resp.summary.total_savings_accounts + ' accounts');
            $('#statFinesAmount').text(CS + Number(resp.summary.pending_fine_amount).toLocaleString('en-IN'));
            $('#statFinesCount').text(resp.summary.pending_fines + ' pending');
            
            // Tab counts
            $('#tabLoanCount').text(resp.loans.length);
            $('#tabSavingsCount').text(resp.savings.length);
            $('#tabFineCount').text(resp.fines.length);
            
            // Render lists
            renderLoansList(resp.loans);
            renderSavingsList(resp.savings);
            renderFinesList(resp.fines);
            
            // Close any open drill-downs
            closeLoanDrill();
            closeSavingsDrill();
            
            $('#memberOverview').removeClass('d-none');
        }).fail(function(xhr, status, error) {
            console.error('Member overview error:', status, error, xhr.responseText);
            toastr.error('Failed to load member data. Check console for details.');
        }).always(function() {
            $('#btnLoadMember').prop('disabled', false).html('<i class="fas fa-folder-open mr-1"></i> Load Member Data');
        });
    }

    // ─── Render Loans List ───
    function renderLoansList(loans) {
        if (!loans.length) {
            $('#loansListContainer').html('<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i> No loans found</div>');
            return;
        }
        
        var html = '<div class="row">';
        loans.forEach(function(loan) {
            var sc = {active:'primary', closed:'secondary', foreclosed:'dark', written_off:'danger', npa:'danger'};
            var cls = sc[loan.status] || 'secondary';
            
            html += '<div class="col-md-6 col-lg-4 mb-2">' +
                '<div class="card overview-card card-loan mb-0" onclick="openLoanDrill(' + loan.id + ')">' +
                '<div class="card-body py-2 px-3">' +
                '<div class="d-flex justify-content-between">' +
                '<strong>' + loan.loan_number + '</strong>' +
                '<span class="badge badge-' + cls + '">' + loan.status.toUpperCase() + '</span>' +
                '</div>' +
                '<small class="text-muted">' + (loan.product_name || '') + '</small>' +
                '<div class="d-flex justify-content-between mt-1">' +
                '<small>Principal: ' + CS + Number(loan.principal_amount).toLocaleString('en-IN') + '</small>' +
                '<small>EMI: ' + CS + Number(loan.emi_amount).toLocaleString('en-IN') + '</small>' +
                '</div>' +
                '<div class="d-flex justify-content-between">' +
                '<small class="text-danger">O/S: ' + CS + Number(loan.outstanding_principal).toLocaleString('en-IN') + '</small>' +
                '<small>Tenure: ' + loan.tenure_months + 'M</small>' +
                '</div>' +
                '</div></div></div>';
        });
        html += '</div>';
        $('#loansListContainer').html(html);
    }

    // ─── Render Savings List ───
    function renderSavingsList(savings) {
        if (!savings.length) {
            $('#savingsListContainer').html('<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i> No savings accounts</div>');
            return;
        }
        
        var html = '<div class="row">';
        savings.forEach(function(acc) {
            var cls = acc.status === 'active' ? 'success' : 'secondary';
            html += '<div class="col-md-6 col-lg-4 mb-2">' +
                '<div class="card overview-card card-savings mb-0" onclick="openSavingsDrill(' + acc.id + ')">' +
                '<div class="card-body py-2 px-3">' +
                '<div class="d-flex justify-content-between">' +
                '<strong>' + (acc.account_number || 'SA-' + acc.id) + '</strong>' +
                '<span class="badge badge-' + cls + '">' + (acc.status || 'active').toUpperCase() + '</span>' +
                '</div>' +
                '<small class="text-muted">' + (acc.scheme_name || '') + '</small>' +
                '<div class="mt-1">' +
                '<strong class="text-success">' + CS + Number(acc.current_balance || 0).toLocaleString('en-IN') + '</strong>' +
                '</div>' +
                '</div></div></div>';
        });
        html += '</div>';
        $('#savingsListContainer').html(html);
    }

    // ─── Render Fines List (Editable Table) ───
    function renderFinesList(fines) {
        if (!fines.length) {
            $('#finesListContainer').html('<div class="text-center text-muted py-3"><i class="fas fa-info-circle mr-1"></i> No fines</div>');
            return;
        }
        
        var html = '<div class="table-responsive"><table class="table table-bordered table-sm adj-table">' +
            '<thead class="thead-dark"><tr>' +
            '<th>Code</th><th>Date</th><th>Type</th><th>Fine Amount</th><th>Paid</th><th>Waived</th><th>Balance</th><th>Status</th><th>Remarks</th>' +
            '</tr></thead><tbody>';
        
        fines.forEach(function(f) {
            var rc = {pending:'',partial:'row-partial',paid:'row-paid',waived:'row-skipped',cancelled:'row-skipped'};
            html += '<tr class="' + (rc[f.status] || '') + '">' +
                '<td>' + f.fine_code + '</td>' +
                '<td>' + f.fine_date + '</td>' +
                '<td><span class="badge badge-secondary">' + f.fine_type + '</span></td>' +
                '<td class="cell-editable" data-type="fine" data-id="' + f.id + '" data-field="fine_amount" data-value="' + f.fine_amount + '">' +
                    '<span class="cell-value">' + CS + Number(f.fine_amount).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>' +
                '<td class="cell-editable" data-type="fine" data-id="' + f.id + '" data-field="paid_amount" data-value="' + f.paid_amount + '">' +
                    '<span class="cell-value">' + CS + Number(f.paid_amount).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>' +
                '<td class="cell-editable" data-type="fine" data-id="' + f.id + '" data-field="waived_amount" data-value="' + f.waived_amount + '">' +
                    '<span class="cell-value">' + CS + Number(f.waived_amount || 0).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>' +
                '<td class="font-weight-bold">' + CS + Number(f.balance_amount).toLocaleString('en-IN', {minimumFractionDigits:2}) + '</td>' +
                '<td class="cell-editable" data-type="fine" data-id="' + f.id + '" data-field="status" data-value="' + f.status + '">' +
                    '<span class="cell-value">' + getStatusBadge(f.status) + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>' +
                '<td class="cell-editable" data-type="fine" data-id="' + f.id + '" data-field="remarks" data-value="' + (f.remarks||'') + '">' +
                    '<span class="cell-value">' + (f.remarks || '-') + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>' +
                '</tr>';
        });
        
        html += '</tbody></table></div>';
        $('#finesListContainer').html(html);
    }

    // ─── Open Loan Drill-Down ───
    window.openLoanDrill = function(loanId) {
        currentLoanId = loanId;
        
        $('#loanDrillPanel').removeClass('d-none');
        $('#installmentsTable tbody').html('<tr><td colspan="14" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        
        $.getJSON(BASE + 'loan_installments/' + loanId, function(resp) {
            if (!resp.success) {
                toastr.error(resp.message); return;
            }
            
            var loan = resp.loan;
            $('#drillLoanNumber').text(loan.loan_number + ' (' + loan.product_name + ')');
            
            // Render loan header
            var hdr = '<div class="row">';
            var editableFields = [
                {label:'Principal', field:'outstanding_principal', val:loan.outstanding_principal, type:'loan'},
                {label:'O/S Interest', field:'outstanding_interest', val:loan.outstanding_interest, type:'loan'},
                {label:'O/S Fine', field:'outstanding_fine', val:loan.outstanding_fine, type:'loan'},
                {label:'Total Paid', field:'total_amount_paid', val:loan.total_amount_paid, type:'loan'},
                {label:'EMI Amount', field:'emi_amount', val:loan.emi_amount, type:'loan'},
                {label:'Tenure', field:'tenure_months', val:loan.tenure_months, type:'loan'},
                {label:'Status', field:'status', val:loan.status, type:'loan'}
            ];
            editableFields.forEach(function(f) {
                var display = f.field === 'status' ? getStatusBadge(f.val) : 
                             (f.field === 'tenure_months' ? f.val + ' months' : CS + Number(f.val).toLocaleString('en-IN', {minimumFractionDigits:2}));
                hdr += '<div class="col-lg-auto col-md-3 col-sm-4 mb-1">' +
                    '<small class="text-muted d-block">' + f.label + '</small>' +
                    '<span class="cell-editable font-weight-bold" data-type="' + f.type + '" data-id="' + loan.id + '" data-field="' + f.field + '" data-value="' + f.val + '">' +
                    '<span class="cell-value">' + display + '</span> <i class="fas fa-pencil-alt edit-icon"></i>' +
                    '</span></div>';
            });
            hdr += '</div>';
            $('#loanHeaderInfo').html(hdr);
            
            // Render installments
            var tbody = '';
            var totals = {principal:0, interest:0, emi:0, ppaid:0, ipaid:0, fine:0, fpaid:0, tpaid:0};
            
            resp.installments.forEach(function(inst) {
                var rowClass = '';
                if (inst.status === 'paid') rowClass = 'row-paid';
                else if (inst.status === 'partial') rowClass = 'row-partial';
                else if (inst.status === 'overdue') rowClass = 'row-overdue';
                else if (inst.status === 'skipped') rowClass = 'row-skipped';
                else if (inst.status === 'interest_only') rowClass = 'row-interest-only';
                
                totals.principal += Number(inst.principal_amount);
                totals.interest += Number(inst.interest_amount);
                totals.emi += Number(inst.emi_amount);
                totals.ppaid += Number(inst.principal_paid);
                totals.ipaid += Number(inst.interest_paid);
                totals.fine += Number(inst.fine_amount);
                totals.fpaid += Number(inst.fine_paid);
                totals.tpaid += Number(inst.total_paid);
                
                tbody += '<tr class="' + rowClass + '">' +
                    '<td>' + inst.installment_number + '</td>' +
                    editableCell('loan_installment', inst.id, 'due_date', inst.due_date, inst.due_date) +
                    editableCell('loan_installment', inst.id, 'principal_amount', inst.principal_amount, fmtAmt(inst.principal_amount)) +
                    editableCell('loan_installment', inst.id, 'interest_amount', inst.interest_amount, fmtAmt(inst.interest_amount)) +
                    editableCell('loan_installment', inst.id, 'emi_amount', inst.emi_amount, fmtAmt(inst.emi_amount)) +
                    editableCell('loan_installment', inst.id, 'principal_paid', inst.principal_paid, fmtAmt(inst.principal_paid)) +
                    editableCell('loan_installment', inst.id, 'interest_paid', inst.interest_paid, fmtAmt(inst.interest_paid)) +
                    editableCell('loan_installment', inst.id, 'fine_amount', inst.fine_amount, fmtAmt(inst.fine_amount)) +
                    editableCell('loan_installment', inst.id, 'fine_paid', inst.fine_paid, fmtAmt(inst.fine_paid)) +
                    editableCell('loan_installment', inst.id, 'total_paid', inst.total_paid, fmtAmt(inst.total_paid)) +
                    editableCell('loan_installment', inst.id, 'status', inst.status, getStatusBadge(inst.status)) +
                    editableCell('loan_installment', inst.id, 'paid_date', inst.paid_date || '', inst.paid_date || '-') +
                    '<td>' + (inst.is_late == 1 ? '<span class="text-danger">' + inst.days_late + 'd</span>' : '-') + '</td>' +
                    editableCell('loan_installment', inst.id, 'remarks', inst.remarks || '', inst.remarks || '-') +
                    '</tr>';
            });
            
            $('#installmentsTable tbody').html(tbody);
            
            // Totals row
            $('#installmentsTotals').html(
                '<td><strong>Total</strong></td><td></td>' +
                '<td>' + fmtAmt(totals.principal) + '</td>' +
                '<td>' + fmtAmt(totals.interest) + '</td>' +
                '<td>' + fmtAmt(totals.emi) + '</td>' +
                '<td>' + fmtAmt(totals.ppaid) + '</td>' +
                '<td>' + fmtAmt(totals.ipaid) + '</td>' +
                '<td>' + fmtAmt(totals.fine) + '</td>' +
                '<td>' + fmtAmt(totals.fpaid) + '</td>' +
                '<td>' + fmtAmt(totals.tpaid) + '</td>' +
                '<td></td><td></td><td></td><td></td>'
            );
            
            // Payments
            var ptbody = '';
            resp.payments.forEach(function(p) {
                ptbody += '<tr>' +
                    '<td><small>' + p.payment_code + '</small></td>' +
                    '<td>' + p.payment_date + '</td>' +
                    '<td><span class="badge badge-secondary">' + p.payment_type + '</span></td>' +
                    '<td class="font-weight-bold">' + fmtAmt(p.total_amount) + '</td>' +
                    '<td>' + fmtAmt(p.principal_component) + '</td>' +
                    '<td>' + fmtAmt(p.interest_component) + '</td>' +
                    '<td>' + fmtAmt(p.fine_component) + '</td>' +
                    '<td>' + (p.payment_mode || '') + '</td>' +
                    '<td><small>' + (p.narration || '') + '</small></td>' +
                    '</tr>';
            });
            if (!resp.payments.length) {
                ptbody = '<tr><td colspan="9" class="text-center text-muted">No payment records</td></tr>';
            }
            $('#paymentsTable tbody').html(ptbody);
            
            // Scroll to drill panel
            $('html, body').animate({scrollTop: $('#loanDrillPanel').offset().top - 70}, 300);
        }).fail(function(xhr, status, error) {
            console.error('Loan drill error:', status, error, xhr.responseText);
            toastr.error('Failed to load loan installments');
            $('#installmentsTable tbody').html('<tr><td colspan="14" class="text-center text-danger py-3">Error loading data</td></tr>');
        });
    };

    window.closeLoanDrill = function() {
        currentLoanId = null;
        $('#loanDrillPanel').addClass('d-none');
    };

    // ─── Open Savings Drill-Down ───
    window.openSavingsDrill = function(accountId) {
        currentSavingsId = accountId;
        
        $('#savingsDrillPanel').removeClass('d-none');
        $('#savingsScheduleTable tbody').html('<tr><td colspan="10" class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        
        $.getJSON(BASE + 'savings_schedule/' + accountId, function(resp) {
            if (!resp.success) {
                toastr.error(resp.message); return;
            }
            
            var acc = resp.account;
            $('#drillSavingsName').text((acc.account_number || 'SA-' + acc.id) + ' (' + acc.scheme_name + ')');
            
            // Savings header
            var hdr = '<div class="row">';
            var sFields = [
                {label:'Balance', field:'current_balance', val:acc.current_balance},
                {label:'Total Deposited', field:'total_deposited', val:acc.total_deposited},
                {label:'Interest Earned', field:'total_interest_earned', val:acc.total_interest_earned},
                {label:'Fines Paid', field:'total_fines_paid', val:acc.total_fines_paid},
                {label:'Status', field:'status', val:acc.status || 'active'}
            ];
            sFields.forEach(function(f) {
                var display = f.field === 'status' ? getStatusBadge(f.val) : CS + Number(f.val || 0).toLocaleString('en-IN', {minimumFractionDigits:2});
                hdr += '<div class="col-lg-auto col-md-3 mb-1">' +
                    '<small class="text-muted d-block">' + f.label + '</small>' +
                    '<span class="cell-editable font-weight-bold" data-type="savings_account" data-id="' + acc.id + '" data-field="' + f.field + '" data-value="' + (f.val||'') + '">' +
                    '<span class="cell-value">' + display + '</span> <i class="fas fa-pencil-alt edit-icon"></i>' +
                    '</span></div>';
            });
            hdr += '</div>';
            $('#savingsHeaderInfo').html(hdr);
            
            // Schedule table
            var tbody = '';
            resp.schedule.forEach(function(s) {
                var rowClass = '';
                if (s.status === 'paid') rowClass = 'row-paid';
                else if (s.status === 'partial') rowClass = 'row-partial';
                else if (s.status === 'overdue') rowClass = 'row-overdue';
                
                var monthLabel = s.due_month;
                
                tbody += '<tr class="' + rowClass + '">' +
                    '<td>' + monthLabel + '</td>' +
                    editableCell('savings_schedule', s.id, 'due_date', s.due_date, s.due_date) +
                    editableCell('savings_schedule', s.id, 'due_amount', s.due_amount, fmtAmt(s.due_amount)) +
                    editableCell('savings_schedule', s.id, 'paid_amount', s.paid_amount, fmtAmt(s.paid_amount)) +
                    editableCell('savings_schedule', s.id, 'fine_amount', s.fine_amount, fmtAmt(s.fine_amount)) +
                    editableCell('savings_schedule', s.id, 'fine_paid', s.fine_paid, fmtAmt(s.fine_paid)) +
                    editableCell('savings_schedule', s.id, 'status', s.status, getStatusBadge(s.status)) +
                    editableCell('savings_schedule', s.id, 'paid_date', s.paid_date || '', s.paid_date || '-') +
                    '<td>' + (s.is_late == 1 ? '<span class="text-danger">' + s.days_late + 'd</span>' : '-') + '</td>' +
                    editableCell('savings_schedule', s.id, 'remarks', s.remarks || '', s.remarks || '-') +
                    '</tr>';
            });
            
            if (!resp.schedule.length) {
                tbody = '<tr><td colspan="10" class="text-center text-muted">No schedule entries</td></tr>';
            }
            
            $('#savingsScheduleTable tbody').html(tbody);
            
            $('html, body').animate({scrollTop: $('#savingsDrillPanel').offset().top - 70}, 300);
        }).fail(function(xhr, status, error) {
            console.error('Savings drill error:', status, error, xhr.responseText);
            toastr.error('Failed to load savings schedule');
            $('#savingsScheduleTable tbody').html('<tr><td colspan="10" class="text-center text-danger py-3">Error loading data</td></tr>');
        });
    };

    window.closeSavingsDrill = function() {
        currentSavingsId = null;
        $('#savingsDrillPanel').addClass('d-none');
    };

    // ─── Editable Cell Click Handler ───
    $(document).on('click', '.cell-editable', function(e) {
        if ($(this).hasClass('editing')) return;
        
        var $cell = $(this);
        var type = $cell.data('type');
        var id = $cell.data('id');
        var field = $cell.data('field');
        var value = $cell.data('value');
        
        adjContext = {
            type: type,
            id: id,
            field: field,
            oldValue: value,
            $cell: $cell
        };
        
        var fieldLabel = field.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        
        $('#adjFieldName').text(fieldLabel + ' (' + type.replace(/_/g, ' ') + ' #' + id + ')');
        $('#adjOldValue').text(value);
        $('#adjNewValue').val(value);
        $('#adjReason').val('');
        $('#adjustModal').modal('show');
        
        setTimeout(function() { $('#adjNewValue').focus().select(); }, 500);
    });

    // ─── Confirm Adjustment ───
    $('#btnConfirmAdjust').on('click', function() {
        var newValue = $('#adjNewValue').val();
        var reason = $.trim($('#adjReason').val());
        
        if (!reason || reason.length < 5) {
            toastr.error('Reason is required (min 5 characters)');
            $('#adjReason').focus();
            return;
        }
        
        var url = '';
        var postData = {
            field: adjContext.field,
            new_value: newValue,
            reason: reason
        };
        postData[CSRF_NAME] = CSRF_HASH;
        
        switch (adjContext.type) {
            case 'loan_installment':
                url = BASE + 'adjust_loan_installment';
                postData.installment_id = adjContext.id;
                break;
            case 'loan':
                url = BASE + 'adjust_loan';
                postData.loan_id = adjContext.id;
                break;
            case 'savings_schedule':
                url = BASE + 'adjust_savings_schedule';
                postData.schedule_id = adjContext.id;
                break;
            case 'savings_account':
                url = BASE + 'adjust_savings_account';
                postData.account_id = adjContext.id;
                break;
            case 'fine':
                url = BASE + 'adjust_fine';
                postData.fine_id = adjContext.id;
                break;
            default:
                toastr.error('Unknown adjustment type');
                return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
        
        $.post(url, postData, function(resp) {
            if (resp.success) {
                toastr.success(resp.message);
                
                // Update cell visually
                adjContext.$cell.data('value', newValue);
                
                // Determine display value
                var display = newValue;
                if (adjContext.field === 'status') {
                    display = getStatusBadge(newValue);
                } else if (['principal_amount','interest_amount','emi_amount','principal_paid','interest_paid',
                            'fine_amount','fine_paid','total_paid','due_amount','paid_amount',
                            'outstanding_principal','outstanding_interest','outstanding_fine',
                            'total_amount_paid','total_principal_paid','total_interest_paid','total_fine_paid',
                            'current_balance','total_deposited','total_interest_earned','total_fines_paid',
                            'waived_amount','balance_amount'].indexOf(adjContext.field) >= 0) {
                    display = fmtAmt(newValue);
                } else if (adjContext.field === 'tenure_months') {
                    display = newValue + ' months';
                }
                
                adjContext.$cell.find('.cell-value').html(display);
                adjContext.$cell.addClass('cell-changed');
                
                // Update CSRF hash for next request
                if (resp.csrf_hash) CSRF_HASH = resp.csrf_hash;
                
                $('#adjustModal').modal('hide');
                
                // Refresh data if loan or savings totals affected
                if (adjContext.type === 'loan_installment' && currentLoanId) {
                    setTimeout(function() { openLoanDrill(currentLoanId); }, 500);
                } else if (adjContext.type === 'savings_schedule' && currentSavingsId) {
                    setTimeout(function() { openSavingsDrill(currentSavingsId); }, 500);
                }
            } else {
                toastr.error(resp.message || 'Failed to apply adjustment');
            }
        }, 'json').fail(function(xhr) {
            toastr.error('Server error: ' + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText));
        }).always(function() {
            $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Confirm Adjustment');
        });
    });

    // ─── Recalculate Buttons ───
    $('#btnRecalcLoan').on('click', function() {
        if (!currentLoanId) return;
        Swal.fire({
            title: 'Recalculate Loan?',
            text: 'This will recalculate outstanding amounts from installment data.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Recalculate'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.getJSON(BASE + 'recalc_loan/' + currentLoanId, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        openLoanDrill(currentLoanId);
                        refreshOverview();
                    } else {
                        toastr.error(resp.message);
                    }
                });
            }
        });
    });

    $('#btnRecalcSavings').on('click', function() {
        if (!currentSavingsId) return;
        Swal.fire({
            title: 'Recalculate Balance?',
            text: 'This will recalculate savings balance from transaction records.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Recalculate'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.getJSON(BASE + 'recalc_savings/' + currentSavingsId, function(resp) {
                    if (resp.success) {
                        toastr.success(resp.message);
                        openSavingsDrill(currentSavingsId);
                        refreshOverview();
                    } else {
                        toastr.error(resp.message);
                    }
                });
            }
        });
    });

    // ─── Audit History Buttons ───
    $('#btnAuditLoan').on('click', function() {
        if (!currentLoanId) return;
        showAuditHistory('loans', currentLoanId);
    });

    $('#btnAuditSavings').on('click', function() {
        if (!currentSavingsId) return;
        showAuditHistory('savings_accounts', currentSavingsId);
    });

    function showAuditHistory(table, recordId) {
        $.getJSON(BASE + 'audit_history', {table: table, record_id: recordId}, function(resp) {
            if (!resp.success) {
                toastr.error('Failed to load audit history');
                return;
            }
            
            var tbody = '';
            if (!resp.logs.length) {
                tbody = '<tr><td colspan="6" class="text-center text-muted py-3">No adjustment history found</td></tr>';
            } else {
                resp.logs.forEach(function(log) {
                    var oldVals = '';
                    var newVals = '';
                    try {
                        var o = JSON.parse(log.old_values || '{}');
                        var n = JSON.parse(log.new_values || '{}');
                        for (var k in o) { oldVals += k + ': ' + o[k] + '<br>'; }
                        for (var k in n) { newVals += k + ': ' + n[k] + '<br>'; }
                    } catch(e) {}
                    
                    tbody += '<tr>' +
                        '<td><small>' + log.created_at + '</small></td>' +
                        '<td>' + (log.admin_name || 'System') + '</td>' +
                        '<td><span class="badge badge-info">' + log.action + '</span></td>' +
                        '<td><small class="text-danger">' + oldVals + '</small></td>' +
                        '<td><small class="text-success">' + newVals + '</small></td>' +
                        '<td><small>' + (log.remarks || '') + '</small></td>' +
                        '</tr>';
                });
            }
            
            $('#auditTable tbody').html(tbody);
            $('#auditModal').modal('show');
        });
    }

    // ─── Helper Functions ───
    function editableCell(type, id, field, value, displayHtml) {
        return '<td class="cell-editable" data-type="' + type + '" data-id="' + id + '" data-field="' + field + '" data-value="' + escAttr(value) + '">' +
               '<span class="cell-value">' + displayHtml + '</span><i class="fas fa-pencil-alt edit-icon"></i></td>';
    }
    
    function fmtAmt(val) {
        return CS + Number(val || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    
    function getStatusBadge(status) {
        var colors = {
            'active':'success', 'paid':'success', 'closed':'secondary',
            'pending':'primary', 'upcoming':'info',
            'partial':'warning', 'overdue':'danger',
            'skipped':'dark', 'interest_only':'info',
            'waived':'secondary', 'cancelled':'dark',
            'foreclosed':'danger', 'npa':'danger', 'written_off':'danger'
        };
        var cls = colors[status] || 'secondary';
        return '<span class="badge badge-' + cls + '">' + (status || '-') + '</span>';
    }
    
    function escAttr(val) {
        if (val === null || val === undefined) return '';
        return String(val).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
});
</script>
