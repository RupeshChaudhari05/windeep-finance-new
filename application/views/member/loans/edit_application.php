<?php $min_guarantors = isset($min_guarantors) ? (int)$min_guarantors : 1; ?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Edit Loan Application</h3></div>
    <form method="post" action="<?= site_url('member/loans/update_application/' . $application->id) ?>" id="editAppForm">
        <div class="card-body">

            <?php if (!empty($application->revision_remarks)): ?>
            <div class="alert alert-info">
                <strong><i class="fas fa-info-circle"></i> Requested Changes:</strong>
                <div><?= nl2br(htmlspecialchars($application->revision_remarks)) ?></div>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Amount Requested (₹)</label>
                    <input type="number" name="amount_requested" class="form-control" step="0.01" min="1" value="<?= set_value('amount_requested', $application->requested_amount) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Tenure (Months)</label>
                    <input type="number" name="requested_tenure_months" class="form-control" value="<?= set_value('requested_tenure_months', $application->requested_tenure_months) ?>" min="1" max="360" required>
                    <small class="form-text text-muted">Admin will set final tenure during approval.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Purpose</label>
                <textarea name="purpose" class="form-control" rows="3" required><?= set_value('purpose', $application->purpose) ?></textarea>
            </div>

            <!-- Guarantors -->
            <hr>
            <h5>Guarantors <span class="text-danger">*</span> <small class="text-muted">(minimum <?= $min_guarantors ?> required)</small></h5>
            <div id="guarantors_area">
                <?php
                $existing_count = !empty($guarantors) ? count($guarantors) : 0;
                $rows_needed = max($min_guarantors, $existing_count);
                ?>
                <?php for ($i = 0; $i < $rows_needed; $i++): ?>
                    <?php $g = isset($guarantors[$i]) ? $guarantors[$i] : null; ?>
                    <div class="form-row guarantor-row">
                        <div class="form-group col-md-10">
                            <label>Guarantor Member <?= $i + 1 ?></label>
                            <select name="guarantor_member_id[]" class="form-control guarantor-select" <?= $i < $min_guarantors ? 'required' : '' ?>>
                                <option value="">-- Select Member --</option>
                                <?php foreach ($members_list as $m): ?>
                                    <option value="<?= $m->id ?>" <?= ($g && $m->id == $g->guarantor_member_id) ? 'selected' : '' ?>>
                                        <?= $m->member_code ?> - <?= htmlspecialchars($m->first_name . ' ' . $m->last_name) ?> (<?= $m->phone ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <?php if ($i >= $min_guarantors): ?>
                                <button type="button" class="btn btn-danger btn-sm btn-remove-guarantor"><i class="fas fa-times"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="form-group">
                <button type="button" id="addGuarantor" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus"></i> Add Guarantor</button>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Save Changes & Resubmit</button>
            <a href="<?= site_url('member/loans/application/' . $application->id) ?>" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

<script>
$(function(){
    var minG = <?= $min_guarantors ?>;

    // Add guarantor row
    $('#addGuarantor').click(function(){
        var idx = $('.guarantor-row').length;
        var row = $('.guarantor-row:first').clone();
        row.find('select').val('').removeAttr('required');
        row.find('label').text('Guarantor Member ' + (idx + 1));
        // Add remove button if not already there
        var btnCol = row.find('.col-md-2');
        btnCol.html('<button type="button" class="btn btn-danger btn-sm btn-remove-guarantor"><i class="fas fa-times"></i></button>');
        $('#guarantors_area').append(row);
    });

    // Remove guarantor row
    $('#guarantors_area').on('click', '.btn-remove-guarantor', function(){
        if ($('.guarantor-row').length > minG) {
            $(this).closest('.guarantor-row').remove();
        } else {
            alert('Minimum ' + minG + ' guarantor(s) required.');
        }
    });

    // Validate min guarantors on submit
    $('#editAppForm').submit(function(e){
        var filled = 0;
        $('.guarantor-select').each(function(){
            if ($(this).val()) filled++;
        });
        if (filled < minG) {
            e.preventDefault();
            alert('Please select at least ' + minG + ' guarantor(s).');
        }
    });
});
</script>