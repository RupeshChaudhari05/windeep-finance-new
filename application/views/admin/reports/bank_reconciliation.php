<!-- Bank Reconciliation Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-balance-scale mr-1"></i> Bank Reconciliation Report</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i> Bank reconciliation functionality is under development. 
            This feature will compare bank statements with system transactions to identify discrepancies.
        </div>
        
        <?php if (empty($report)): ?>
        <div class="text-center py-5">
            <i class="fas fa-balance-scale fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No reconciliation data available</h5>
            <p class="text-muted">Upload bank statements to perform reconciliation.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="reconciliationTable">
                <thead class="thead-dark">
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>System Amount</th>
                        <th>Bank Amount</th>
                        <th>Difference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Placeholder for actual data -->
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#reconciliationTable').DataTable({
        "pageLength": 25
    });
});
</script>