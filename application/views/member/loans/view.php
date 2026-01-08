<div class="card">
    <div class="card-header">
        <h3 class="card-title">Loan Details - <?= $loan->loan_number ?></h3>
    </div>
    <div class="card-body">
        <p><strong>Product:</strong> <?= $loan->product_name ?></p>
        <p><strong>Amount:</strong> ₹<?= number_format($loan->principal_amount, 2) ?></p>
        <p><strong>Outstanding:</strong> ₹<?= number_format($loan->outstanding_principal ?? 0, 2) ?></p>

        <h5>Installments</h5>
        <?php if (empty($installments)): ?>
            <p class="text-muted">No installments found.</p>
        <?php else: ?>
            <table class="table table-sm">
                <thead><tr><th>#</th><th>Due Date</th><th class="text-right">EMI</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($installments as $inst): ?>
                    <tr>
                        <td><?= $inst->installment_number ?></td>
                        <td><?= format_date($inst->due_date) ?></td>
                        <td class="text-right">₹<?= number_format($inst->emi_amount, 2) ?></td>
                        <td><?= ucfirst($inst->status) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Foreclosure Section -->
        <?php if ($loan->status === 'active' && ($loan->outstanding_principal ?? 0) > 0): ?>
            <div class="mt-4">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-hand-holding-usd"></i> Early Loan Closure (Foreclosure)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <p>If you wish to close this loan early, you can request foreclosure. The foreclosure amount will be calculated based on the outstanding principal, any applicable prepayment charges, and pending fines.</p>

                                <div class="alert alert-warning">
                                    <strong>Note:</strong> Foreclosure requests are subject to approval. Early closure may involve prepayment charges as per the loan agreement.
                                </div>

                                <button type="button" class="btn btn-danger" onclick="showForeclosureCalculator()">
                                    <i class="fas fa-calculator"></i> Calculate Foreclosure Amount
                                </button>
                                <button type="button" class="btn btn-outline-danger ml-2" onclick="requestForeclosure()">
                                    <i class="fas fa-paper-plane"></i> Request Foreclosure
                                </button>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6>Current Outstanding</h6>
                                        <h4 class="text-primary">₹<?= number_format($loan->outstanding_principal ?? 0, 2) ?></h4>
                                        <small class="text-muted">Principal Amount</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Foreclosure Calculator Modal -->
<div class="modal fade" id="foreclosureCalculatorModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Foreclosure Calculator</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="calculatorContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Calculating foreclosure amount...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="requestForeclosure()">Request Foreclosure</button>
            </div>
        </div>
    </div>
</div>

<!-- Foreclosure Request Modal -->
<div class="modal fade" id="foreclosureRequestModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Loan Foreclosure</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="foreclosureForm">
                <div class="modal-body">
                    <input type="hidden" name="loan_id" value="<?= $loan->id ?>">
                    <div class="alert alert-info">
                        <strong>Foreclosure Amount:</strong> <span id="foreclosureAmountDisplay">Calculating...</span>
                    </div>

                    <div class="form-group">
                        <label for="foreclosure_reason">Reason for Foreclosure Request</label>
                        <textarea class="form-control" id="foreclosure_reason" name="reason"
                                  rows="4" placeholder="Please explain why you are requesting early closure of this loan..."
                                  required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="settlement_date">Preferred Settlement Date</label>
                        <input type="date" class="form-control" id="settlement_date" name="settlement_date"
                               min="<?= date('Y-m-d') ?>" required>
                        <small class="form-text text-muted">Select the date you plan to make the settlement payment.</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox"
                                   id="foreclosure_acknowledgement" name="acknowledgement" required>
                            <label class="custom-control-label" for="foreclosure_acknowledgement">
                                I understand that foreclosure requests are subject to approval and may involve additional charges. I agree to pay the calculated foreclosure amount upon approval.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Foreclosure Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showForeclosureCalculator() {
    $('#foreclosureCalculatorModal').modal('show');

    $.ajax({
        url: '<?= site_url('member/loans/foreclosure-calculator/' . $loan->id) ?>',
        type: 'GET',
        success: function(response) {
            $('#calculatorContent').html(response);
        },
        error: function(xhr) {
            $('#calculatorContent').html('<div class="alert alert-danger">Error loading calculator. Please try again.</div>');
        }
    });
}

function requestForeclosure() {
    $('#foreclosureCalculatorModal').modal('hide');

    // Get the foreclosure amount from the calculator if available
    const foreclosureAmount = $('#calculatedAmount').text() || 'Calculating...';
    $('#foreclosureAmountDisplay').text(foreclosureAmount);

    $('#foreclosureRequestModal').modal('show');
}

$('#foreclosureForm').on('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: '<?= site_url('member/loans/request-foreclosure/' . $loan->id) ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#foreclosureRequestModal').modal('hide');
                toastr.success(response.message || 'Foreclosure request submitted successfully!');
                setTimeout(() => location.reload(), 2000);
            } else {
                toastr.error(response.message || 'Failed to submit foreclosure request');
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON || {};
            toastr.error(response.message || 'An error occurred while submitting the request');
        }
    });
});
</script>