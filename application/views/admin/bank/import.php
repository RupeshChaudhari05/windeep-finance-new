<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-upload mr-1"></i> Import Bank Statement</h3>
            </div>
            <form action="<?= site_url('admin/bank/upload') ?>" method="post" enctype="multipart/form-data" id="importForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Upload Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Supported formats: CSV, Excel (.xlsx, .xls)</li>
                            <li>File should contain: Date, Description, Credit, Debit, Balance columns</li>
                            <li>Maximum file size: 10MB</li>
                            <li>System will auto-match transactions where possible</li>
                        </ul>
                    </div>
                    
                    <div class="form-group">
                        <label for="bank_account_id">Bank Account <span class="text-danger">*</span></label>
                        <select class="form-control" id="bank_account_id" name="bank_account_id" required>
                            <option value="">Select Bank Account</option>
                            <?php foreach ($bank_accounts as $acc): ?>
                                <option value="<?= $acc->id ?>">
                                    <?= $acc->account_name ?> - <?= $acc->account_number ?> (<?= $acc->bank_name ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="statement_date">Statement Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="statement_date" name="statement_date" 
                               value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>">
                        <small class="form-text text-muted">Date of the bank statement</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="statement_file">Upload Statement File <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="statement_file" name="statement_file" 
                                   accept=".csv,.xlsx,.xls" required>
                            <label class="custom-file-label" for="statement_file">Choose file</label>
                        </div>
                        <small class="form-text text-muted">CSV or Excel format, max 10MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                                  placeholder="Any notes about this import (optional)"></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Upload & Import
                    </button>
                    <a href="<?= site_url('admin/dashboard') ?>" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Sample Format</h3>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Expected CSV Columns:</strong></p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Credit</th>
                                <th>Debit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>01/01/2024</td>
                                <td>UPI-John Doe</td>
                                <td>5000</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>02/01/2024</td>
                                <td>NEFT TRANSFER</td>
                                <td></td>
                                <td>10000</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr>
                <p class="mb-2"><strong>Auto-Match Rules:</strong></p>
                <ul class="small">
                    <li>Matches based on amount and date proximity</li>
                    <li>Member phone numbers in description</li>
                    <li>UPI IDs linked to members</li>
                    <li>Reference numbers from receipts</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Recent Imports -->
<?php if (!empty($recent_imports)): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Recent Imports</h3>
        <div class="card-tools">
            <a href="<?= site_url('admin/bank/unmatched') ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-exclamation-triangle mr-1"></i> View Unmatched
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Import Code</th>
                    <th>Bank Account</th>
                    <th>Statement Date</th>
                    <th>Transactions</th>
                    <th>Matched</th>
                    <th>Unmatched</th>
                    <th>Imported By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_imports as $import): ?>
                <tr>
                    <td>
                        <a href="<?= site_url('admin/bank/view_import/' . $import->id) ?>">
                            <?= $import->import_code ?>
                        </a>
                    </td>
                    <td><?= $import->account_name ?></td>
                    <td><?= date('d M Y', strtotime($import->statement_date)) ?></td>
                    <td><?= $import->total_transactions ?></td>
                    <td>
                        <span class="badge badge-success">
                            <?= $import->matched_count ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($import->unmatched_count > 0): ?>
                            <span class="badge badge-warning">
                                <?= $import->unmatched_count ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $import->imported_by_name ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($import->created_at)) ?></small>
                    </td>
                    <td>
                        <a href="<?= site_url('admin/bank/view_import/' . $import->id) ?>" class="btn btn-xs btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function() {
    // Custom file input label
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
    });
    
    // Form validation
    $('#importForm').on('submit', function(e) {
        var file = $('#statement_file').val();
        if (!file) {
            e.preventDefault();
            Swal.fire('Error', 'Please select a file to upload', 'error');
            return false;
        }
        
        Swal.fire({
            title: 'Uploading...',
            text: 'Please wait while we import the bank statement',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => { Swal.showLoading(); }
        });
    });
});
</script>
