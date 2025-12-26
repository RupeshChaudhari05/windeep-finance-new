<!-- Loan Products Management -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-box mr-1"></i> Loan Products</h3>
        <div class="card-tools">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addProductModal">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="productsTable">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Product Name</th>
                        <th>Product Code</th>
                        <th class="text-right">Min Amount</th>
                        <th class="text-right">Max Amount</th>
                        <th class="text-right">Interest Rate</th>
                        <th>Tenure (Months)</th>
                        <th>Processing Fee</th>
                        <th>Status</th>
                        <th>Active Loans</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No loan products found. Click "Add Product" to create one.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($products as $product): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td>
                                <strong><?= $product->name ?? $product->product_name ?? 'Unnamed Product' ?></strong>
                                <?php if (!empty($product->description)): ?>
                                <br><small class="text-muted"><?=
                                    function_exists('character_limiter') ? character_limiter($product->description, 50)
                                    : (strlen($product->description) > 50 ? substr($product->description, 0, 47) . '...' : $product->description)
                                ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-secondary"><?= $product->code ?></span></td>
                            <td class="text-right">₹<?= number_format($product->min_amount) ?></td>
                            <td class="text-right">₹<?= number_format($product->max_amount) ?></td>
                            <td class="text-right">
                                <span class="text-success font-weight-bold"><?= $product->interest_rate ?>%</span>
                                <br><small class="text-muted"><?= $product->interest_type ?? 'Reducing' ?></small>
                            </td>
                            <td>
                                <?= $product->min_tenure ?> - <?= $product->max_tenure ?> months
                            </td>
                            <td>
                                <?= $product->processing_fee ?>
                                <?= $product->processing_fee_type == 'percentage' ? '%' : ' (Fixed)' ?>
                            </td>
                            <td>
                                <?php if ($product->is_active): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Active</span>
                                <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= $product->active_loans ?? 0 ?> loans</span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-info btn-sm btn-edit" data-product='<?= json_encode($product) ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-<?= $product->is_active ? 'warning' : 'success' ?> btn-sm btn-toggle-status" 
                                            data-id="<?= $product->id ?>" 
                                            data-status="<?= $product->is_active ?>"
                                            title="<?= $product->is_active ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas fa-<?= $product->is_active ? 'ban' : 'check' ?>"></i>
                                    </button>
                                    <?php if (($product->active_loans ?? 0) == 0): ?>
                                    <button class="btn btn-danger btn-sm btn-delete" data-id="<?= $product->id ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="productForm" method="post" action="<?= site_url('admin/loans/save_product') ?>">
                <input type="hidden" name="id" id="product_id">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title"><i class="fas fa-box mr-1"></i> <span id="modalTitle">Add</span> Loan Product</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Product Code <span class="text-danger">*</span></label>
                                <input type="text" name="code" id="code" class="form-control" required maxlength="20" placeholder="e.g., PL, HL, GL">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="min_amount" id="min_amount" class="form-control" required min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                                    <input type="number" name="max_amount" id="max_amount" class="form-control" required min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="interest_rate" id="interest_rate" class="form-control" required step="0.01" min="0" max="100">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Interest Type</label>
                                <select name="interest_type" id="interest_type" class="form-control">
                                    <option value="reducing">Reducing Balance</option>
                                    <option value="flat">Flat Rate</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>EMI Frequency</label>
                                <select name="emi_frequency" id="emi_frequency" class="form-control">
                                    <option value="monthly">Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-Weekly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Tenure (Months) <span class="text-danger">*</span></label>
                                <input type="number" name="min_tenure" id="min_tenure" class="form-control" required min="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Tenure (Months) <span class="text-danger">*</span></label>
                                <input type="number" name="max_tenure" id="max_tenure" class="form-control" required min="1">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Processing Fee</label>
                                <input type="number" name="processing_fee" id="processing_fee" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fee Type</label>
                                <select name="processing_fee_type" id="processing_fee_type" class="form-control">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount (₹)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Late Payment Fine (%)</label>
                                <div class="input-group">
                                    <input type="number" name="late_fine_rate" id="late_fine_rate" class="form-control" step="0.01" min="0" max="100">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                                <small class="text-muted">Applied on overdue EMI amount per month</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Grace Period (Days)</label>
                                <input type="number" name="grace_period" id="grace_period" class="form-control" min="0" value="0">
                                <small class="text-muted">No fine charged during grace period</small>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1" checked>
                            <label class="custom-control-label" for="is_active">Active (available for new loans)</label>
                        </div>
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
    
    // Reset form when modal is hidden
    $('#addProductModal').on('hidden.bs.modal', function() {
        $('#productForm')[0].reset();
        $('#product_id').val('');
        $('#modalTitle').text('Add');
        $('#is_active').prop('checked', true);
    });
    
    // Edit product
    $('.btn-edit').click(function() {
        var product = $(this).data('product');
        $('#modalTitle').text('Edit');
        $('#product_id').val(product.id);
        $('#name').val(product.name);
        $('#code').val(product.code);
        $('#description').val(product.description);
        $('#min_amount').val(product.min_amount);
        $('#max_amount').val(product.max_amount);
        $('#interest_rate').val(product.interest_rate);
        $('#interest_type').val(product.interest_type || 'reducing');
        $('#emi_frequency').val(product.emi_frequency || 'monthly');
        $('#min_tenure').val(product.min_tenure);
        $('#max_tenure').val(product.max_tenure);
        $('#processing_fee').val(product.processing_fee);
        $('#processing_fee_type').val(product.processing_fee_type || 'percentage');
        $('#late_fine_rate').val(product.late_fine_rate);
        $('#grace_period').val(product.grace_period);
        $('#is_active').prop('checked', product.is_active == 1);
        $('#addProductModal').modal('show');
    });
    
    // Toggle status
    $('.btn-toggle-status').click(function() {
        var productId = $(this).data('id');
        var currentStatus = $(this).data('status');
        var newStatus = currentStatus == 1 ? 0 : 1;
        var action = newStatus == 1 ? 'activate' : 'deactivate';
        
        if (confirm('Are you sure you want to ' + action + ' this product?')) {
            $.post('<?= site_url('admin/loans/toggle_product_status') ?>', {id: productId, is_active: newStatus}, function(response) {
                if (response.success) {
                    toastr.success('Product ' + action + 'd successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Operation failed');
                }
            }, 'json');
        }
    });
    
    // Delete product
    $('.btn-delete').click(function() {
        var productId = $(this).data('id');
        
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            $.post('<?= site_url('admin/loans/delete_product') ?>', {id: productId}, function(response) {
                if (response.success) {
                    toastr.success('Product deleted successfully');
                    location.reload();
                } else {
                    toastr.error(response.message || 'Failed to delete product');
                }
            }, 'json');
        }
    });
    
    // Form submission
    $('#productForm').submit(function(e) {
        e.preventDefault();
        
        $.post($(this).attr('action'), $(this).serialize(), function(response) {
            if (response.success) {
                toastr.success('Product saved successfully');
                $('#addProductModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to save product');
            }
        }, 'json');
    });
});
</script>
