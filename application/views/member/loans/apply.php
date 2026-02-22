<?php
$min_guarantors = isset($min_guarantors) ? (int)$min_guarantors : 0;
?>
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Apply for Loan</h3></div>
    <form method="post" action="<?= site_url('member/loans/apply') ?>" id="loanApplyForm">
        <div class="card-body">
            <?php if ($this->session->flashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?= $this->session->flashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <?= $this->session->flashdata('success') ?>
            </div>
            <?php endif; ?>

            <div class="alert alert-info py-2">
                <i class="fas fa-info-circle mr-1"></i>
                Enter the loan amount and tenure you need. The loan scheme will be assigned by admin during approval.
                <?php if ($min_guarantors > 0): ?>
                    <br><strong>Minimum <?= $min_guarantors ?> guarantor(s) required.</strong>
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Amount Requested <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                        <input type="number" name="amount_requested" id="amount_requested" class="form-control" step="0.01" min="1" value="<?= set_value('amount_requested') ?>" required>
                    </div>
                </div>
                <div class="form-group col-md-6">
                    <label>Tenure (Months) <span class="text-danger">*</span></label>
                    <input type="number" name="requested_tenure_months" id="requested_tenure_months" class="form-control" value="<?= set_value('requested_tenure_months', 12) ?>" min="1" max="360" required>
                    <small class="form-text text-muted">Number of months for repayment</small>
                </div>
            </div>

            <div class="form-group">
                <label>Purpose <span class="text-danger">*</span></label>
                <textarea name="purpose" class="form-control" rows="3" required><?= set_value('purpose') ?></textarea>
                <small class="form-text text-muted">Please describe the purpose of this loan</small>
            </div>

            <!-- Guarantors -->
            <hr>
            <h5>Guarantors <?php if ($min_guarantors > 0): ?><span class="text-danger">*</span> <small class="text-muted">(minimum <?= $min_guarantors ?> required)</small><?php else: ?><small class="text-muted">(optional)</small><?php endif; ?></h5>
            <div id="guarantors_area">
                <?php
                // Pre-create rows for min required guarantors (at least 1 row always)
                $rows_to_show = max(1, $min_guarantors);
                for ($gi = 0; $gi < $rows_to_show; $gi++):
                ?>
                <div class="form-row guarantor-row">
                    <div class="form-group col-md-8">
                        <label>Guarantor Member</label>
                        <select name="guarantor_member_id[]" class="form-control guarantor-select" <?= $gi < $min_guarantors ? 'required' : '' ?>>
                            <option value="">-- Select Member --</option>
                            <?php foreach ($members_list as $m): ?>
                                <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= htmlspecialchars($m->first_name . ' ' . $m->last_name) ?> (<?= $m->phone ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-guarantor"><i class="fas fa-times"></i></button>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="form-group">
                <button type="button" id="addGuarantor" class="btn btn-sm btn-outline-primary"><i class="fas fa-plus mr-1"></i>Add Guarantor</button>
            </div>

            <script>
            (function(){
                var minGuarantors = <?= $min_guarantors ?>;

                // Guarantor add/remove
                document.getElementById('addGuarantor').addEventListener('click', function(){
                    var container = document.getElementById('guarantors_area');
                    var template = document.querySelector('.guarantor-row');
                    var clone = template.cloneNode(true);
                    clone.querySelector('select').value = '';
                    clone.querySelector('select').removeAttribute('required');
                    container.appendChild(clone);
                });
                document.getElementById('guarantors_area').addEventListener('click', function(e){
                    if (e.target && (e.target.matches('.btn-remove-guarantor') || e.target.closest('.btn-remove-guarantor'))) {
                        var rows = document.querySelectorAll('.guarantor-row');
                        if (rows.length > minGuarantors && rows.length > 1) {
                            var row = e.target.closest('.guarantor-row');
                            if (row) row.remove();
                        }
                    }
                });

                // Form validation
                document.getElementById('loanApplyForm').addEventListener('submit', function(e){
                    if (minGuarantors > 0) {
                        var selects = document.querySelectorAll('.guarantor-select');
                        var filled = 0;
                        selects.forEach(function(s){ if (s.value) filled++; });
                        if (filled < minGuarantors) {
                            e.preventDefault();
                            alert('Please select at least ' + minGuarantors + ' guarantor(s).');
                        }
                    }
                });
            })();
            </script>
        </div>
        <div class="card-footer">
            <button class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i>Submit Application</button>
            <a href="<?= site_url('member/loans') ?>" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>