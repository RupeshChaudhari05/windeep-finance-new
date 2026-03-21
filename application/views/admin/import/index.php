<!-- Bulk Data Import Interface -->
<style>
    .import-card { transition: all 0.3s; cursor: pointer; border: 2px solid transparent; }
    .import-card:hover, .import-card.selected { border-color: #007bff; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,123,255,0.2); }
    .import-card.selected { background: #f0f7ff; }
    .import-card .card-body { padding: 1.5rem; }
    .import-card .icon-circle { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 1.5rem; }
    .preview-table { font-size: 0.85rem; }
    .preview-table th { white-space: nowrap; }
    .step-indicator { display: flex; margin-bottom: 20px; }
    .step-indicator .step { flex: 1; text-align: center; padding: 10px; position: relative; }
    .step-indicator .step .step-number { width: 35px; height: 35px; border-radius: 50%; background: #e9ecef; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 5px; }
    .step-indicator .step.active .step-number { background: #007bff; color: white; }
    .step-indicator .step.done .step-number { background: #28a745; color: white; }
    .step-indicator .step::after { content: ''; position: absolute; top: 27px; right: 0; width: 50%; height: 2px; background: #e9ecef; }
    .step-indicator .step::before { content: ''; position: absolute; top: 27px; left: 0; width: 50%; height: 2px; background: #e9ecef; }
    .step-indicator .step:first-child::before { display: none; }
    .step-indicator .step:last-child::after { display: none; }
    .step-indicator .step.done::after, .step-indicator .step.done::before { background: #28a745; }
    .step-indicator .step.active::before { background: #007bff; }
    .drop-zone { border: 3px dashed #ccc; border-radius: 10px; padding: 40px; text-align: center; transition: all 0.3s; cursor: pointer; }
    .drop-zone.dragover { border-color: #007bff; background: #f0f7ff; }
    .drop-zone:hover { border-color: #007bff; }
    .result-badge { font-size: 1.5rem; }
    .error-list { max-height: 300px; overflow-y: auto; }
</style>

<div class="row mb-3">
    <div class="col-12">
        <!-- Step Indicator -->
        <div class="step-indicator" id="stepIndicator">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <div><small>Select Type</small></div>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <div><small>Upload File</small></div>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <div><small>Preview & Validate</small></div>
            </div>
            <div class="step" id="step4">
                <div class="step-number">4</div>
                <div><small>Import</small></div>
            </div>
        </div>
    </div>
</div>

<!-- STEP 1: Select Import Type -->
<div id="section1">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-import mr-2"></i> Step 1: Select What to Import</h3>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <!-- Members -->
                        <div class="col-md-4">
                            <div class="card import-card" data-type="members" onclick="selectImportType('members')">
                                <div class="card-body text-center">
                                    <div class="icon-circle bg-primary text-white">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h5 class="card-title">Members</h5>
                                    <p class="text-muted mb-1">Import member basic info</p>
                                    <small class="text-muted">Name, Phone, Address, Bank Details, KYC</small>
                                    <div class="mt-2">
                                        <span class="badge badge-info"><?= $member_count ?> existing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Loans -->
                        <div class="col-md-4">
                            <div class="card import-card" data-type="loans" onclick="selectImportType('loans')">
                                <div class="card-body text-center">
                                    <div class="icon-circle bg-success text-white">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                    <h5 class="card-title">Loans</h5>
                                    <p class="text-muted mb-1">Import loan records</p>
                                    <small class="text-muted">Principal, Rate, Tenure, EMI Schedule</small>
                                    <div class="mt-2">
                                        <span class="badge badge-info"><?= $loan_count ?> existing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Savings Transactions -->
                        <div class="col-md-4">
                            <div class="card import-card" data-type="savings_transactions" onclick="selectImportType('savings_transactions')">
                                <div class="card-body text-center">
                                    <div class="icon-circle bg-warning text-dark">
                                        <i class="fas fa-piggy-bank"></i>
                                    </div>
                                    <h5 class="card-title">Savings Transactions</h5>
                                    <p class="text-muted mb-1">Import deposits / withdrawals</p>
                                    <small class="text-muted">Back-dated savings payments</small>
                                    <div class="mt-2">
                                        <span class="badge badge-info"><?= $savings_tx_count ?> existing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info boxes for each type -->
                    <div id="infoMembers" class="alert alert-info mt-3" style="display:none;">
                        <h6><i class="fas fa-info-circle mr-1"></i> Members Import Info</h6>
                        <ul class="mb-0">
                            <li><strong>Required fields:</strong> first_name, last_name, phone</li>
                            <li>Duplicate check is done by phone number &amp; email</li>
                            <li>member_code will be auto-generated (MEMB000001, MEMB000002...)</li>
                            <li>Default password will be set for member portal login</li>
                            <li>join_date can be any past date (back-dated entry supported)</li>
                        </ul>
                    </div>
                    <div id="infoLoans" class="alert alert-success mt-3" style="display:none;">
                        <h6><i class="fas fa-info-circle mr-1"></i> Loans Import Info</h6>
                        <ul class="mb-0">
                            <li><strong>Required fields:</strong> member_code, loan_product_id, principal_amount, interest_rate, interest_type, tenure_months, disbursement_date</li>
                            <li>Members must exist first (import members before loans)</li>
                            <li>EMI schedule will be auto-generated</li>
                            <li>Disbursement date can be any past date (back-dated)</li>
                            <li>Available loan products:
                                <?php foreach ($loan_products as $lp): ?>
                                    <span class="badge badge-secondary">ID:<?= $lp->id ?> — <?= $lp->product_name ?></span>
                                <?php endforeach; ?>
                            </li>
                        </ul>
                    </div>
                    <div id="infoSavings" class="alert alert-warning mt-3" style="display:none;">
                        <h6><i class="fas fa-info-circle mr-1"></i> Savings Transactions Import Info</h6>
                        <ul class="mb-0">
                            <li><strong>Required fields:</strong> member_code, transaction_type, amount, transaction_date</li>
                            <li>Members &amp; savings accounts must exist first</li>
                            <li>If no account_number given, system finds the member's active savings account</li>
                            <li>Transaction date can be any past date (back-dated deposits supported)</li>
                            <li>Account balance will be updated automatically</li>
                            <li>Available schemes:
                                <?php foreach ($savings_schemes as $ss): ?>
                                    <span class="badge badge-secondary">ID:<?= $ss->id ?> — <?= $ss->scheme_name ?></span>
                                <?php endforeach; ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STEP 2: Upload File -->
<div id="section2" style="display:none;">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-cloud-upload-alt mr-2"></i> Step 2: Upload Excel / CSV File</h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-outline-primary" onclick="goToStep(1)">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Download Template -->
                    <div class="text-center mb-4">
                        <a href="#" id="downloadTemplateBtn" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-download mr-2"></i> Download Excel Template
                        </a>
                        <p class="text-muted mt-2">Download the template, fill in your data, then upload below</p>
                    </div>

                    <hr>

                    <!-- Upload Area -->
                    <form id="uploadForm" enctype="multipart/form-data">
                        <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                        <input type="hidden" name="import_type" id="importTypeInput" value="">

                        <div class="drop-zone" id="dropZone" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                            <h5>Drag & Drop your file here</h5>
                            <p class="text-muted">or click to browse</p>
                            <p class="text-muted"><small>Supported: .xlsx, .xls, .csv (Max 500 rows)</small></p>
                            <input type="file" class="d-none" id="fileInput" name="import_file" accept=".xlsx,.xls,.csv">
                        </div>

                        <div id="fileInfo" class="alert alert-info mt-3" style="display:none;">
                            <i class="fas fa-file mr-1"></i>
                            <strong id="fileName"></strong>
                            <span class="float-right">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFile()">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </span>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="previewBtn" disabled>
                                <i class="fas fa-eye mr-2"></i> Preview & Validate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STEP 3: Preview & Validate -->
<div id="section3" style="display:none;">
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-search mr-2"></i> Step 3: Preview & Validation</h3>
                    <div class="card-tools">
                        <button class="btn btn-sm btn-outline-primary" onclick="goToStep(2)">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Validation Summary -->
                    <div class="row mb-3" id="validationSummary">
                        <div class="col-md-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Rows</span>
                                    <span class="info-box-number" id="totalRows">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Valid Rows</span>
                                    <span class="info-box-number" id="validRows">0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-times"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Errors</span>
                                    <span class="info-box-number" id="errorRows">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Details -->
                    <div id="errorDetails" style="display:none;">
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle mr-1"></i> Validation Errors</h6>
                            <div class="error-list" id="errorList"></div>
                        </div>
                    </div>

                    <!-- Preview Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm preview-table" id="previewTable">
                            <thead class="thead-dark" id="previewHead"></thead>
                            <tbody id="previewBody"></tbody>
                        </table>
                    </div>
                    <small class="text-muted" id="previewNote"></small>

                    <!-- Import Button -->
                    <div class="text-center mt-4">
                        <button type="button" class="btn btn-success btn-lg" id="importBtn" onclick="executeImport()">
                            <i class="fas fa-database mr-2"></i> Import Data into Database
                        </button>
                        <p class="text-muted mt-2"><small>This action cannot be undone. Records with errors will be skipped.</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STEP 4: Results -->
<div id="section4" style="display:none;">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-check-circle mr-2"></i> Step 4: Import Results</h3>
                </div>
                <div class="card-body text-center">
                    <div id="importResults">
                        <!-- Filled dynamically -->
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary btn-lg mr-2" onclick="location.reload()">
                            <i class="fas fa-redo mr-1"></i> Import More
                        </button>
                        <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary btn-lg">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Imports -->
<?php if (!empty($recent_imports)): ?>
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i> Recent Import History</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Imported</th>
                            <th>Skipped</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_imports as $log): ?>
                        <tr>
                            <td><?= date('d M Y h:i A', strtotime($log->created_at)) ?></td>
                            <td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', $log->import_type)) ?></span></td>
                            <td><?= $log->total_rows ?></td>
                            <td class="text-success font-weight-bold"><?= $log->inserted ?></td>
                            <td class="text-warning"><?= $log->skipped ?></td>
                            <td class="text-danger"><?= $log->errors ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
var selectedType = '';
var csrfName = '<?= $this->security->get_csrf_token_name() ?>';
var csrfHash = '<?= $this->security->get_csrf_hash() ?>';

// Step 1: Select import type
function selectImportType(type) {
    selectedType = type;
    
    // Highlight selected card
    $('.import-card').removeClass('selected');
    $('.import-card[data-type="' + type + '"]').addClass('selected');
    
    // Show relevant info
    $('#infoMembers, #infoLoans, #infoSavings').hide();
    if (type === 'members') $('#infoMembers').fadeIn();
    if (type === 'loans') $('#infoLoans').fadeIn();
    if (type === 'savings_transactions') $('#infoSavings').fadeIn();
    
    // Set template download link
    $('#downloadTemplateBtn').attr('href', '<?= site_url('admin/import/download_template/') ?>' + type);
    $('#importTypeInput').val(type);
    
    // Auto proceed to step 2 after short delay
    setTimeout(function() { goToStep(2); }, 400);
}

// Step navigation
function goToStep(step) {
    // Validate can proceed
    if (step >= 2 && !selectedType) {
        toastr.warning('Please select an import type first');
        return;
    }
    
    // Hide all sections
    $('#section1, #section2, #section3, #section4').hide();
    
    // Show target section
    $('#section' + step).fadeIn();
    
    // Update step indicators
    $('#stepIndicator .step').removeClass('active done');
    for (var i = 1; i < step; i++) {
        $('#step' + i).addClass('done');
    }
    $('#step' + step).addClass('active');
}

// File handling
var dropZone = document.getElementById('dropZone');

['dragenter', 'dragover'].forEach(function(eventName) {
    dropZone.addEventListener(eventName, function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add('dragover');
    });
});

['dragleave', 'drop'].forEach(function(eventName) {
    dropZone.addEventListener(eventName, function(e) {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('dragover');
    });
});

dropZone.addEventListener('drop', function(e) {
    var files = e.dataTransfer.files;
    if (files.length) {
        document.getElementById('fileInput').files = files;
        showFileInfo(files[0]);
    }
});

document.getElementById('fileInput').addEventListener('change', function() {
    if (this.files.length) {
        showFileInfo(this.files[0]);
    }
});

function showFileInfo(file) {
    var ext = file.name.split('.').pop().toLowerCase();
    if (!['xlsx', 'xls', 'csv'].includes(ext)) {
        toastr.error('Invalid file type. Please upload .xlsx, .xls or .csv');
        return;
    }
    $('#fileName').text(file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)');
    $('#fileInfo').show();
    $('#previewBtn').prop('disabled', false);
}

function clearFile() {
    document.getElementById('fileInput').value = '';
    $('#fileInfo').hide();
    $('#previewBtn').prop('disabled', true);
}

// Upload & Preview
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    var btn = $('#previewBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Processing...');
    
    $.ajax({
        url: '<?= site_url('admin/import/preview') ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-eye mr-2"></i> Preview & Validate');
            
            if (res.success) {
                // Update CSRF hash
                if (res.csrf_hash) csrfHash = res.csrf_hash;
                
                showPreview(res);
                goToStep(3);
            } else {
                toastr.error(res.message);
            }
        },
        error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-eye mr-2"></i> Preview & Validate');
            toastr.error('Upload failed. Please try again.');
        }
    });
});

function showPreview(data) {
    // Summary
    $('#totalRows').text(data.total_rows);
    $('#validRows').text(data.valid_rows);
    $('#errorRows').text(data.error_rows);
    
    // Errors
    if (data.errors && data.errors.length > 0) {
        var errorHtml = '<ul class="mb-0">';
        data.errors.forEach(function(err) {
            errorHtml += '<li>' + err + '</li>';
        });
        errorHtml += '</ul>';
        $('#errorList').html(errorHtml);
        $('#errorDetails').show();
    } else {
        $('#errorDetails').hide();
    }
    
    // Preview table
    if (data.preview && data.preview.length > 0) {
        var headers = data.headers;
        var headHtml = '<tr>';
        headHtml += '<th>#</th>';
        headers.forEach(function(h) {
            headHtml += '<th>' + h + '</th>';
        });
        headHtml += '</tr>';
        $('#previewHead').html(headHtml);
        
        var bodyHtml = '';
        data.preview.forEach(function(row, idx) {
            bodyHtml += '<tr>';
            bodyHtml += '<td>' + (idx + 1) + '</td>';
            headers.forEach(function(h) {
                bodyHtml += '<td>' + (row[h] || '<span class="text-muted">—</span>') + '</td>';
            });
            bodyHtml += '</tr>';
        });
        $('#previewBody').html(bodyHtml);
        
        if (data.total_rows > 10) {
            $('#previewNote').text('Showing first 10 of ' + data.total_rows + ' rows');
        }
    }
    
    // Enable/disable import button
    if (data.valid_rows > 0) {
        $('#importBtn').prop('disabled', false);
    } else {
        $('#importBtn').prop('disabled', true);
    }
}

// Execute Import
function executeImport() {
    if (!confirm('Are you sure you want to import the data? This action cannot be undone.')) {
        return;
    }
    
    var btn = $('#importBtn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Importing... Please wait...');
    
    var postData = {};
    postData[csrfName] = csrfHash;
    
    $.ajax({
        url: '<?= site_url('admin/import/execute') ?>',
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html('<i class="fas fa-database mr-2"></i> Import Data into Database');
            
            if (res.success) {
                showResults(res);
                goToStep(4);
            } else {
                toastr.error(res.message);
            }
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-database mr-2"></i> Import Data into Database');
            toastr.error('Import failed. Please try again.');
        }
    });
}

function showResults(res) {
    var html = '<div class="mb-4">';
    html += '<i class="fas fa-check-circle text-success fa-4x mb-3"></i>';
    html += '<h3 class="text-success">Import Completed!</h3>';
    html += '</div>';
    
    html += '<div class="row justify-content-center">';
    html += '<div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h2>' + res.inserted + '</h2><p>Imported</p></div></div></div>';
    html += '<div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body"><h2>' + res.skipped + '</h2><p>Skipped</p></div></div></div>';
    html += '<div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h2>' + res.errors + '</h2><p>Errors</p></div></div></div>';
    html += '</div>';
    
    if (res.error_details && res.error_details.length > 0) {
        html += '<div class="alert alert-danger mt-3 text-left">';
        html += '<h6><i class="fas fa-exclamation-triangle mr-1"></i> Error Details:</h6>';
        html += '<ul class="mb-0 error-list">';
        res.error_details.forEach(function(err) {
            html += '<li>' + err + '</li>';
        });
        html += '</ul></div>';
    }
    
    $('#importResults').html(html);
}
</script>
