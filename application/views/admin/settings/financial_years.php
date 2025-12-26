<!-- Financial Years Management -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-1"></i> Financial Years</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addYearModal">
                        <i class="fas fa-plus"></i> Add Year
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Year Name</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($years)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No financial years configured</p>
                            </td>
                        </tr>
                        <?php else: foreach ($years as $year): ?>
                        <tr class="<?= $year->is_active ? 'table-success' : '' ?>">
                            <td>
                                <strong><?= $year->year_name ?></strong>
                                <?php if ($year->is_active): ?>
                                <span class="badge badge-success ml-2">Current</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($year->start_date)) ?></td>
                            <td><?= date('d M Y', strtotime($year->end_date)) ?></td>
                            <td>
                                <?php if ($year->is_closed): ?>
                                <span class="badge badge-secondary">Closed</span>
                                <?php elseif ($year->is_active): ?>
                                <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                <span class="badge badge-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d M Y', strtotime($year->created_at)) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$year->is_active && !$year->is_closed): ?>
                                    <button class="btn btn-success btn-activate" data-id="<?= $year->id ?>" title="Activate">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($year->is_active): ?>
                                    <button class="btn btn-warning btn-close-year" data-id="<?= $year->id ?>" title="Close Year">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-info btn-edit" data-year='<?= json_encode($year) ?>' title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Current Year Info -->
        <?php if ($active): ?>
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-check mr-1"></i> Current Financial Year</h3>
            </div>
            <div class="card-body">
                <h4 class="text-center"><?= $active->year_name ?></h4>
                <hr>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td>Start Date:</td>
                        <td class="text-right"><?= date('d M Y', strtotime($active->start_date)) ?></td>
                    </tr>
                    <tr>
                        <td>End Date:</td>
                        <td class="text-right"><?= date('d M Y', strtotime($active->end_date)) ?></td>
                    </tr>
                    <tr>
                        <td>Days Remaining:</td>
                        <td class="text-right">
                            <?php 
                            $remaining = floor((strtotime($active->end_date) - time()) / 86400);
                            echo $remaining > 0 ? $remaining . ' days' : 'Ended';
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Help Card -->
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="fas fa-info-circle text-info"></i> Financial Year Info</h6>
                <ul class="list-unstyled mb-0">
                    <li><small>• Only one financial year can be active at a time</small></li>
                    <li><small>• Closing a year locks all transactions for that period</small></li>
                    <li><small>• Year-end processing should be completed before closing</small></li>
                    <li><small>• Standard financial year: April 1 to March 31</small></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Year Modal -->
<div class="modal fade" id="addYearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= site_url('admin/settings/create_financial_year') ?>">
                <input type="hidden" name="id" id="year_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-calendar-alt mr-1"></i> <span id="modalTitle">Add</span> Financial Year</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Year Name <span class="text-danger">*</span></label>
                        <input type="text" name="year_name" id="year_name" class="form-control" placeholder="e.g., FY 2025-26" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="is_active" id="is_active" class="custom-control-input" value="1">
                            <label class="custom-control-label" for="is_active">Set as Active Year</label>
                        </div>
                        <small class="text-muted">This will deactivate the current active year</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Quick fill for FY
    $('#start_date').change(function() {
        var start = new Date($(this).val());
        if (start) {
            var end = new Date(start);
            end.setFullYear(end.getFullYear() + 1);
            end.setDate(end.getDate() - 1);
            $('#end_date').val(end.toISOString().split('T')[0]);
            
            var yearName = 'FY ' + start.getFullYear() + '-' + (start.getFullYear() + 1).toString().slice(-2);
            if (!$('#year_name').val()) {
                $('#year_name').val(yearName);
            }
        }
    });
    
    // Edit year
    $('.btn-edit').click(function() {
        var year = $(this).data('year');
        $('#modalTitle').text('Edit');
        $('#year_id').val(year.id);
        $('#year_name').val(year.year_name);
        $('#start_date').val(year.start_date);
        $('#end_date').val(year.end_date);
        $('#is_active').prop('checked', year.is_active == 1);
        $('#addYearModal').modal('show');
    });
    
    // Activate year
    $('.btn-activate').click(function() {
        if (confirm('Activate this financial year? The current active year will be deactivated.')) {
            $.post('<?= site_url('admin/settings/activate_year') ?>', {id: $(this).data('id')}, function(response) {
                if (response.success) {
                    toastr.success('Financial year activated');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            }, 'json');
        }
    });
    
    // Close year
    $('.btn-close-year').click(function() {
        if (confirm('Close this financial year? This will lock all transactions for this period. This action cannot be undone.')) {
            $.post('<?= site_url('admin/settings/close_year') ?>', {id: $(this).data('id')}, function(response) {
                if (response.success) {
                    toastr.success('Financial year closed');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            }, 'json');
        }
    });
    
    // Reset modal
    $('#addYearModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#year_id').val('');
        $('#modalTitle').text('Add');
    });
});
</script>
