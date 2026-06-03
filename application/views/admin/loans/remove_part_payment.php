<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Part Payment Removal
                    </h4>
                </div>
                
                <div class="card-body">
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>⚠️ Warning:</strong> This action will permanently remove the part payment record and restore the loan to its previous state.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Part Payment Details</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Member:</strong></td>
                                    <td><?php echo htmlspecialchars($part_payment->first_name . ' ' . $part_payment->last_name); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Member Code:</strong></td>
                                    <td><?php echo htmlspecialchars($part_payment->member_code); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Loan Number:</strong></td>
                                    <td><?php echo htmlspecialchars($part_payment->loan_number); ?></td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Payment Amount & Dates</h5>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Part Payment Amount:</strong></td>
                                    <td class="text-danger font-weight-bold">₹<?php echo number_format($part_payment->part_payment_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Date:</strong></td>
                                    <td><?php echo date('d M Y', strtotime($part_payment->payment_date)); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Entered By:</strong></td>
                                    <td><?php echo htmlspecialchars($part_payment->admin_name ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <h5>Outstanding Amount Changes</h5>
                            <div class="alert alert-info">
                                <table class="table table-sm mb-0 bg-light">
                                    <thead>
                                        <tr>
                                            <th>Amount Type</th>
                                            <th>Before Removal</th>
                                            <th>After Removal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Outstanding Principal</strong></td>
                                            <td>₹<?php echo number_format($part_payment->new_principal, 2); ?></td>
                                            <td class="text-danger">₹<?php echo number_format($part_payment->previous_principal, 2); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Change</strong></td>
                                            <td colspan="2">
                                                <span class="badge badge-danger">+ ₹<?php echo number_format($part_payment->part_payment_amount, 2); ?></span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h5>Confirmation</h5>
                            <p>This action will:</p>
                            <ul class="ml-3">
                                <li>Remove the part payment record from <code>loan_part_payments</code></li>
                                <li>Delete associated payment entry from <code>loan_payments</code></li>
                                <li>Restore the loan's outstanding principal by ₹<?php echo number_format($part_payment->part_payment_amount, 2); ?></li>
                                <li>Create an audit log entry for this reversal</li>
                                <li>Mark the part payment as reversed (soft-deleted for record keeping)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <form method="post" action="<?php echo site_url('admin/loans/remove_part_payment'); ?>" class="d-inline">
                                <?php echo form_hidden('part_payment_id', $part_payment->id); ?>
                                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                                
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="fas fa-trash-alt"></i> Confirm Removal
                                </button>
                                <a href="<?php echo site_url('admin/loans/view/' . $part_payment->loan_id); ?>" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        margin-top: 20px;
        margin-bottom: 20px;
    }
    
    code {
        background-color: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
</style>
