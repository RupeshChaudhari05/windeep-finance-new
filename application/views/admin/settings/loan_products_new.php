<!-- Loan Products Settings - With Dynamic Interest Rates -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i> Loan Products</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" id="productsTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Interest Rate Range</th>
                            <th>Default Rate</th>
                            <th>Amount Range</th>
                            <th>Max Term</th>
                            <th>Late Fee</th>
                            <th>Processing Fee</th>
                            <th>Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($products as $product): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><code><?= $product->product_code ?? 'LP' . $product->id ?></code></td>
                            <td>
                                <strong><?= $product->product_name ?></strong>
                                <?php if (!empty($product->description)): ?>
                                <br><small class="text-muted"><?= character_limiter($product->description, 40) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info">
                                    <?= number_format($product->min_interest_rate ?? $product->interest_rate, 1) ?>% - 
                                    <?= number_format($product->max_interest_rate ?? $product->interest_rate, 1) ?>%
                                </span>
                                <small class="text-muted d-block"><?= $product->interest_type ?? 'reducing' ?></small>
                            </td>
                            <td>
                                <strong class="text-primary"><?= number_format($product->default_interest_rate ?? $product->interest_rate, 2) ?>%</strong>
                            </td>
                            <td>
                                <?= format_amount($product->min_amount ?? 0) ?> - 
                                <?= format_amount($product->max_amount ?? 0, 0) ?>
                            </td>
                            <td><?= $product->max_tenure_months ?? $product->max_term ?? '-' ?> months</td>
                            <td>
                                <?php 
                                $late_fee_type = $product->late_fee_type ?? 'fixed';
                                if ($late_fee_type == 'fixed_plus_daily'): ?>
                                <span class="badge badge-warning">
                                    <?= format_amount($product->late_fee_value ?? 0, 0) ?> + <?= format_amount($product->late_fee_per_day ?? 0, 0) ?>/day
                                </span>
                                <?php elseif ($late_fee_type == 'per_day'): ?>
                                <span class="badge badge-warning"><?= format_amount($product->late_fee_per_day ?? 0, 0) ?>/day</span>
                                <?php elseif ($late_fee_type == 'percentage'): ?>
                                <span class="badge badge-warning"><?= number_format($product->late_fee_value ?? 0) ?>%</span>
                                <?php else: ?>
                                <span class="badge badge-secondary"><?= format_amount($product->late_fee_value ?? 0) ?></span>
                                <?php endif; ?>
                                <?php if (($product->grace_period_days ?? 0) > 0): ?>
                                <small class="text-muted d-block"><?= $product->grace_period_days ?> days grace</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($product->processing_fee_type ?? 'percentage') == 'percentage'): ?>
                                <?= number_format($product->processing_fee ?? $product->processing_fee_value ?? 0) ?>%
                                <?php else: ?>
                                <?= format_amount($product->processing_fee ?? $product->processing_fee_value ?? 0) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product->is_active ?? 1): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                                <?php else: ?>
                                <span class="badge badge-secondary"><i class="fas fa-times"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-warning btn-edit" data-product='<?= json_encode($product) ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-<?= ($product->is_active ?? 1) ? 'secondary' : 'success' ?> btn-toggle" 
                                            data-id="<?= $product->id ?>" data-status="<?= $product->is_active ?? 1 ?>">
                                        <i class="fas fa-<?= ($product->is_active ?? 1) ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Product Summary Cards -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= count(array_filter($products, function($p) { return $p->is_active ?? 1; })) ?></h3>
                <p>Active Products</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= count($products) ?></h3>
                <p>Total Products</p>
            </div>
            <div class="icon"><i class="fas fa-box"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <?php 
                $avg_rate = 0;
                if (count($products) > 0) {
                    $rates = array_map(function($p) { 
                        return $p->default_interest_rate ?? $p->interest_rate ?? 0; 
                    }, $products);
                    $avg_rate = array_sum($rates) / count($rates);
                }
                ?>
                <h3><?= number_format($avg_rate, 1) ?>%</h3>
                <p>Avg Interest Rate</p>
            </div>
            <div class="icon"><i class="fas fa-percentage"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <?php $max_amount = count($products) > 0 ? max(array_column($products, 'max_amount')) : 0; ?>
                <h3><?= format_amount($max_amount / 100000, 1, 0) ?>L</h3>
                <p>Max Loan Amount</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="post" action="<?= site_url('admin/settings/save_loan_product') ?>" id="productForm">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <input type="hidden" name="id" id="product_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-hand-holding-usd mr-1"></i> <span id="modalTitle">Add</span> Loan Product</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Product Code <span class="text-danger">*</span></label>
                                <input type="text" name="product_code" id="product_code" class="form-control" 
                                       placeholder="e.g., PL001" required>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="product_name" id="product_name" class="form-control" 
                                       placeholder="e.g., Personal Loan" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-primary"><i class="fas fa-percentage mr-1"></i> Interest Rate Settings</h6>
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Admin can assign any rate between min and max when approving loans.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Min Interest Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" name="min_interest_rate" id="min_interest_rate" 
                                       class="form-control" step="0.01" value="12" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Max Interest Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" name="max_interest_rate" id="max_interest_rate" 
                                       class="form-control" step="0.01" value="24" min="0" max="100" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Default Rate (%) <span class="text-danger">*</span></label>
                                <input type="number" name="default_interest_rate" id="default_interest_rate" 
                                       class="form-control" step="0.01" value="18" min="0" max="100" required>
                                <small class="text-muted">Pre-filled when approving</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Interest Type</label>
                                <select name="interest_type" id="interest_type" class="form-control">
                                    <option value="reducing">Reducing Balance</option>
                                    <option value="flat">Flat Rate</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-primary"><i class="fas fa-rupee-sign mr-1"></i> Amount & Tenure</h6>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Min Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" name="min_amount" id="min_amount" class="form-control" 
                                       step="1000" value="10000" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Max Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" name="max_amount" id="max_amount" class="form-control" 
                                       step="1000" value="500000" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Min Tenure (Months)</label>
                                <input type="number" name="min_tenure_months" id="min_tenure_months" 
                                       class="form-control" value="3" min="1">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Max Tenure (Months) <span class="text-danger">*</span></label>
                                <input type="number" name="max_tenure_months" id="max_tenure_months" 
                                       class="form-control" value="60" min="1" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-primary"><i class="fas fa-file-invoice-dollar mr-1"></i> Processing Fee</h6>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fee Type</label>
                                <select name="processing_fee_type" id="processing_fee_type" class="form-control">
                                    <option value="percentage">Percentage of Loan</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Processing Fee</label>
                                <input type="number" name="processing_fee_value" id="processing_fee_value" 
                                       class="form-control" step="0.01" value="2">
                                <small class="text-muted" id="fee_hint">% of loan amount</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="text-danger"><i class="fas fa-gavel mr-1"></i> Late Fee Settings (Indian Banking Style)</h6>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Late Fee Type</label>
                                <select name="late_fee_type" id="late_fee_type" class="form-control">
                                    <option value="fixed">Fixed Amount Only</option>
                                    <option value="percentage">Percentage of EMI</option>
                                    <option value="per_day">Per Day Only</option>
                                    <option value="fixed_plus_daily" selected>Fixed + Daily (Recommended)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3" id="lateFeeValueGroup">
                            <div class="form-group">
                                <label>Initial Late Fee (<?= get_currency_symbol() ?>)</label>
                                <input type="number" name="late_fee_value" id="late_fee_value" 
                                       class="form-control" step="0.01" value="100">
                                <small class="text-muted">One-time fine after grace</small>
                            </div>
                        </div>
                        <div class="col-md-3" id="lateFeeDailyGroup">
                            <div class="form-group">
                                <label>Per Day Fine (<?= get_currency_symbol() ?>)</label>
                                <input type="number" name="late_fee_per_day" id="late_fee_per_day" 
                                       class="form-control" step="0.01" value="10">
                                <small class="text-muted">Added daily</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Grace Period (Days)</label>
                                <input type="number" name="grace_period_days" id="grace_period_days" 
                                       class="form-control" value="5" min="0">
                                <small class="text-muted">Days before fine applies</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Late Fee Preview -->
                    <div class="card bg-light">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">After 10 days late:</small>
                                    <div class="h6 text-danger mb-0" id="latePreview10"><?= get_currency_symbol() ?>0</div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">After 30 days late:</small>
                                    <div class="h6 text-danger mb-0" id="latePreview30"><?= get_currency_symbol() ?>0</div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">After 60 days late:</small>
                                    <div class="h6 text-danger mb-0" id="latePreview60"><?= get_currency_symbol() ?>0</div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">After 90 days late:</small>
                                    <div class="h6 text-danger mb-0" id="latePreview90"><?= get_currency_symbol() ?>0</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"
                                  placeholder="Product description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#productsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
    
    // Processing fee type change
    $('#processing_fee_type').change(function() {
        $('#fee_hint').text($(this).val() == 'percentage' ? '% of loan amount' : 'Fixed amount in <?= get_currency_symbol() ?>');
    });
    
    // Late fee type change
    $('#late_fee_type').change(function() {
        var type = $(this).val();
        $('#lateFeeValueGroup').toggle(type != 'per_day');
        $('#lateFeeDailyGroup').toggle(type == 'per_day' || type == 'fixed_plus_daily');
        updateLatePreview();
    });
    
    // Update late fee preview
    $('#late_fee_value, #late_fee_per_day, #grace_period_days, #late_fee_type').on('input change', function() {
        updateLatePreview();
    });
    
    function updateLatePreview() {
        var type = $('#late_fee_type').val();
        var fixed = parseFloat($('#late_fee_value').val()) || 0;
        var daily = parseFloat($('#late_fee_per_day').val()) || 0;
        var grace = parseInt($('#grace_period_days').val()) || 0;
        var sampleEmi = 5000; // Sample EMI for percentage calc
        
        [10, 30, 60, 90].forEach(function(days) {
            var effectiveDays = Math.max(0, days - grace);
            var fee = 0;
            
            switch(type) {
                case 'fixed':
                    fee = effectiveDays > 0 ? fixed : 0;
                    break;
                case 'percentage':
                    fee = effectiveDays > 0 ? (sampleEmi * fixed / 100) : 0;
                    break;
                case 'per_day':
                    fee = effectiveDays * daily;
                    break;
                case 'fixed_plus_daily':
                    fee = effectiveDays > 0 ? fixed + (effectiveDays * daily) : 0;
                    break;
            }
            
            $('#latePreview' + days).text('<?= get_currency_symbol() ?>' + fee.toFixed(2));
        });
    }
    
    // Edit product
    $('.btn-edit').click(function() {
        var product = $(this).data('product');
        $('#modalTitle').text('Edit');
        $('#product_id').val(product.id);
        $('#product_code').val(product.product_code || 'LP' + product.id);
        $('#product_name').val(product.product_name);
        $('#min_interest_rate').val(product.min_interest_rate || product.interest_rate);
        $('#max_interest_rate').val(product.max_interest_rate || product.interest_rate);
        $('#default_interest_rate').val(product.default_interest_rate || product.interest_rate);
        $('#interest_type').val(product.interest_type || 'reducing');
        $('#min_amount').val(product.min_amount);
        $('#max_amount').val(product.max_amount);
        $('#min_tenure_months').val(product.min_tenure_months || 3);
        $('#max_tenure_months').val(product.max_tenure_months || product.max_term);
        $('#processing_fee_type').val(product.processing_fee_type || 'percentage').trigger('change');
        $('#processing_fee_value').val(product.processing_fee_value || product.processing_fee);
        $('#late_fee_type').val(product.late_fee_type || 'fixed_plus_daily').trigger('change');
        $('#late_fee_value').val(product.late_fee_value || 100);
        $('#late_fee_per_day').val(product.late_fee_per_day || 10);
        $('#grace_period_days').val(product.grace_period_days || 5);
        $('#description').val(product.description);
        updateLatePreview();
        $('#addProductModal').modal('show');
    });
    
    // Reset modal on close
    $('#addProductModal').on('hidden.bs.modal', function() {
        $('#productForm')[0].reset();
        $('#product_id').val('');
        $('#modalTitle').text('Add');
        $('#late_fee_type').val('fixed_plus_daily').trigger('change');
        updateLatePreview();
    });
    
    // Toggle status
    $('.btn-toggle').click(function() {
        var id = $(this).data('id');
        var status = $(this).data('status') == 1 ? 0 : 1;
        $.post('<?= site_url('admin/settings/toggle_loan_product') ?>', {
            id: id, 
            is_active: status,
            <?= $this->security->get_csrf_token_name() ?>: '<?= $this->security->get_csrf_hash() ?>'
        }, function(response) {
            if (response.success) {
                toastr.success('Product status updated');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to update');
            }
        }, 'json');
    });
    
    // Initial preview
    updateLatePreview();
});
</script>
