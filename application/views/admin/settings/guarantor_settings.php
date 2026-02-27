<!-- Guarantor Settings -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Guarantor Configuration</h3>
            </div>
            <form method="post" action="<?= site_url('admin/settings/update') ?>">
                <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
                <div class="card-body">
                    <h5 class="text-primary mb-3"><i class="fas fa-cog mr-1"></i> Basic Settings</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Guarantors Required</label>
                                <input type="number" name="settings[min_guarantors]" class="form-control" 
                                       value="<?= $settings['min_guarantors'] ?? 1 ?>" min="0" max="10">
                                <small class="text-muted">Minimum number of guarantors needed per loan</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Maximum Guarantors Allowed</label>
                                <input type="number" name="settings[max_guarantors]" class="form-control" 
                                       value="<?= $settings['max_guarantors'] ?? 5 ?>" min="1" max="10">
                                <small class="text-muted">Maximum guarantors allowed per loan</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Guarantor Coverage Required (%)</label>
                                <input type="number" name="settings[guarantor_coverage_percent]" class="form-control" 
                                       value="<?= $settings['guarantor_coverage_percent'] ?? 100 ?>" min="0" max="200">
                                <small class="text-muted">Total guarantee amount as % of loan (100% = full coverage)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Loans as Guarantor</label>
                                <input type="number" name="settings[max_loans_as_guarantor]" class="form-control" 
                                       value="<?= $settings['max_loans_as_guarantor'] ?? 3 ?>" min="1" max="20">
                                <small class="text-muted">How many active loans can a member guarantee</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="text-primary mb-3"><i class="fas fa-check-circle mr-1"></i> Eligibility Rules</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Membership Duration (Months)</label>
                                <input type="number" name="settings[guarantor_min_membership_months]" class="form-control" 
                                       value="<?= $settings['guarantor_min_membership_months'] ?? 6 ?>" min="0">
                                <small class="text-muted">Member must be a member for at least this many months</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Min Savings Balance (<?= get_currency_symbol() ?>)</label>
                                <input type="number" name="settings[guarantor_min_savings]" class="form-control" 
                                       value="<?= $settings['guarantor_min_savings'] ?? 5000 ?>" min="0">
                                <small class="text-muted">Minimum savings balance required to be a guarantor</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="guarantor_must_have_loan" 
                                           name="settings[guarantor_must_have_loan]" value="1"
                                           <?= ($settings['guarantor_must_have_loan'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="guarantor_must_have_loan">
                                        Guarantor must have taken loan before
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="guarantor_no_default" 
                                           name="settings[guarantor_no_default]" value="1"
                                           <?= ($settings['guarantor_no_default'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="guarantor_no_default">
                                        Guarantor must have no default history
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="self_guarantee_allowed" 
                                           name="settings[self_guarantee_allowed]" value="1"
                                           <?= ($settings['self_guarantee_allowed'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="self_guarantee_allowed">
                                        Allow member to guarantee own loan (with savings)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="family_guarantee_allowed" 
                                           name="settings[family_guarantee_allowed]" value="1"
                                           <?= ($settings['family_guarantee_allowed'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="family_guarantee_allowed">
                                        Allow family members as guarantors
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <h5 class="text-primary mb-3"><i class="fas fa-exclamation-triangle mr-1"></i> Liability Settings</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Guarantor Liability Type</label>
                                <select name="settings[guarantor_liability_type]" class="form-control">
                                    <option value="proportional" <?= ($settings['guarantor_liability_type'] ?? '') == 'proportional' ? 'selected' : '' ?>>
                                        Proportional (based on guarantee amount)
                                    </option>
                                    <option value="joint" <?= ($settings['guarantor_liability_type'] ?? '') == 'joint' ? 'selected' : '' ?>>
                                        Joint & Several (full liability each)
                                    </option>
                                    <option value="sequential" <?= ($settings['guarantor_liability_type'] ?? '') == 'sequential' ? 'selected' : '' ?>>
                                        Sequential (primary guarantor first)
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Days before Guarantor Notification</label>
                                <input type="number" name="settings[guarantor_notify_after_days]" class="form-control" 
                                       value="<?= $settings['guarantor_notify_after_days'] ?? 30 ?>" min="1">
                                <small class="text-muted">Days after default to notify guarantors</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="guarantor_auto_debit" 
                                           name="settings[guarantor_auto_debit]" value="1"
                                           <?= ($settings['guarantor_auto_debit'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="guarantor_auto_debit">
                                        Auto-debit guarantor savings on default
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Auto-debit after (days)</label>
                                <input type="number" name="settings[guarantor_auto_debit_days]" class="form-control" 
                                       value="<?= $settings['guarantor_auto_debit_days'] ?? 90 ?>" min="1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Stats -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-1"></i> Guarantor Statistics</h3>
            </div>
            <div class="card-body p-0">
                <?php
                $CI =& get_instance();
                $total_guarantors = $CI->db->count_all_results('loan_guarantors');
                $active_guarantees = $CI->db->where('consent_status', 'approved')
                                            ->join('loans', 'loans.id = loan_guarantors.loan_id', 'left')
                                            ->where('loans.status', 'active')
                                            ->count_all_results('loan_guarantors');
                $pending_consents = $CI->db->where('consent_status', 'pending')->count_all_results('loan_guarantors');
                ?>
                <table class="table mb-0">
                    <tr>
                        <td>Total Guarantee Records</td>
                        <td class="text-right"><strong><?= number_format($total_guarantors) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Active Guarantees</td>
                        <td class="text-right"><strong class="text-success"><?= number_format($active_guarantees) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Pending Consents</td>
                        <td class="text-right"><strong class="text-warning"><?= number_format($pending_consents) ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Help</h3>
            </div>
            <div class="card-body">
                <p class="small"><strong>Coverage %:</strong> 100% means total guarantee amount must equal loan amount. 150% requires 1.5x coverage.</p>
                <p class="small"><strong>Liability Types:</strong></p>
                <ul class="small">
                    <li><strong>Proportional:</strong> Each guarantor liable for their guaranteed amount</li>
                    <li><strong>Joint:</strong> Any guarantor can be held liable for full amount</li>
                    <li><strong>Sequential:</strong> Primary guarantor pays first, then secondary</li>
                </ul>
            </div>
        </div>
    </div>
</div>
