<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Edit Loan Application</h3></div>
    <form method="post" action="<?= site_url('member/loans/update_application/' . $application->id) ?>">
        <div class="card-body">
            <div class="form-group">
                <label>Loan Product</label>
                <select name="loan_product_id" id="loan_product_id" class="form-control" required>
                    <?php foreach ($loan_products as $p): ?>
                    <option value="<?= $p->id ?>" data-min="<?= $p->min_tenure_months ?>" data-max="<?= $p->max_tenure_months ?>" <?= $p->id == $application->loan_product_id ? 'selected' : '' ?>><?= $p->product_name ?> - <?= $p->interest_rate ?>%</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Amount Requested</label>
                    <input type="number" name="amount_requested" class="form-control" step="0.01" value="<?= set_value('amount_requested', $application->requested_amount) ?>" required>
                </div>
                <div class="form-group col-md-6">
                    <label>Tenure (Months)</label>
                    <input type="number" name="requested_tenure_months" id="requested_tenure_months" class="form-control" value="<?= set_value('requested_tenure_months', $application->requested_tenure_months) ?>" min="1" required>
                    <small class="form-text text-muted" id="tenure_help">Choose tenure between product min and max.</small>
                </div>
            </div>

            <!-- Guarantors -->
            <hr>
            <h5>Guarantors <small class="text-muted">(optional)</small></h5>
            <div id="guarantors_area">
                <?php if (!empty($guarantors)): ?>
                    <?php foreach ($guarantors as $g): ?>
                        <div class="form-row guarantor-row">
                            <div class="form-group col-md-6">
                                <label>Guarantor Member</label>
                                <select name="guarantor_member_id[]" class="form-control guarantor-select">
                                    <option value="">-- Select Member --</option>
                                    <?php foreach ($this->db->where('status','active')->get('members')->result() as $m): ?>
                                        <option value="<?= $m->id ?>" <?= $m->id == $g->guarantor_member_id ? 'selected' : '' ?>><?= $m->member_code ?> - <?= htmlspecialchars($m->first_name . ' ' . $m->last_name) ?> (<?= $m->phone ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Guarantee Amount</label>
                                <input type="number" name="guarantee_amount[]" class="form-control" step="0.01" value="<?= $g->guarantee_amount ?>">
                            </div>
                            <div class="form-group col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-remove-guarantor">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-row guarantor-row">
                        <div class="form-group col-md-6">
                            <label>Guarantor Member</label>
                            <select name="guarantor_member_id[]" class="form-control guarantor-select">
                                <option value="">-- Select Member --</option>
                                <?php foreach ($this->db->where('status','active')->get('members')->result() as $m): ?>
                                    <option value="<?= $m->id ?>"><?= $m->member_code ?> - <?= htmlspecialchars($m->first_name . ' ' . $m->last_name) ?> (<?= $m->phone ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Guarantee Amount</label>
                            <input type="number" name="guarantee_amount[]" class="form-control" step="0.01">
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-remove-guarantor">Remove</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <button type="button" id="addGuarantor" class="btn btn-sm btn-outline-primary">Add Guarantor</button>
            </div>

            <div class="form-group">
                <label>Purpose</label>
                <textarea name="purpose" class="form-control"><?= set_value('purpose', $application->purpose) ?></textarea>
            </div>

            <script>
                (function(){
                    var prod = document.getElementById('loan_product_id');
                    var tenure = document.getElementById('requested_tenure_months');
                    var help = document.getElementById('tenure_help');
                    function updateTenureLimits(){
                        var opt = prod.options[prod.selectedIndex];
                        var min = parseInt(opt.getAttribute('data-min')) || 1;
                        var max = parseInt(opt.getAttribute('data-max')) || 240;
                        tenure.min = min;
                        tenure.max = max;
                        if (parseInt(tenure.value) < min) tenure.value = min;
                        if (parseInt(tenure.value) > max) tenure.value = max;
                        help.textContent = 'Choose tenure between ' + min + ' and ' + max + ' months.';
                    }
                    prod.addEventListener('change', updateTenureLimits);
                    updateTenureLimits();

                    // Guarantor add/remove
                    document.getElementById('addGuarantor').addEventListener('click', function(){
                        var container = document.getElementById('guarantors_area');
                        var template = document.querySelector('.guarantor-row');
                        var clone = template.cloneNode(true);
                        clone.querySelector('select').value = '';
                        var amount = clone.querySelector('input[name="guarantee_amount[]"]');
                        if (amount) amount.value = '';
                        container.appendChild(clone);
                    });
                    document.getElementById('guarantors_area').addEventListener('click', function(e){
                        if (e.target && e.target.matches('.btn-remove-guarantor')) {
                            var row = e.target.closest('.guarantor-row');
                            if (row) row.remove();
                        }
                    });
                })();
            </script>
        </div>
        <div class="card-footer">
            <button class="btn btn-success">Save Changes & Resubmit</button>
            <a href="<?= site_url('member/loans/application/' . $application->id) ?>" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>