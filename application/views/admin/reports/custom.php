<!-- Custom Report Builder -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cogs mr-1"></i> Custom Report Builder</h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i> Custom report builder allows you to create personalized reports 
            by selecting specific data fields and applying filters.
        </div>
        
        <form id="customReportForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select class="form-control" name="report_type" id="reportType">
                            <option value="">Select Report Type</option>
                            <option value="members">Members</option>
                            <option value="loans">Loans</option>
                            <option value="savings">Savings</option>
                            <option value="transactions">Transactions</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control" id="dateRange" name="date_range" placeholder="Select date range">
                    </div>
                </div>
            </div>
            
            <div id="fieldSelector" style="display: none;">
                <h5>Select Fields to Include:</h5>
                <div class="row" id="fieldsContainer">
                    <!-- Fields will be loaded dynamically -->
                </div>
            </div>
            
            <div class="mt-3">
                <button type="button" class="btn btn-primary" id="generateReport">
                    <i class="fas fa-play mr-1"></i> Generate Report
                </button>
                <button type="button" class="btn btn-secondary" id="resetForm">
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
            </div>
        </form>
        
        <div id="reportResults" style="display: none;" class="mt-4">
            <h5>Report Results:</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="customReportTable">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#dateRange').daterangepicker({
        locale: { format: 'YYYY-MM-DD' },
        opens: 'right'
    });
    
    $('#reportType').change(function() {
        var type = $(this).val();
        if (type) {
            loadFields(type);
            $('#fieldSelector').show();
        } else {
            $('#fieldSelector').hide();
            $('#fieldsContainer').empty();
        }
    });
    
    function loadFields(type) {
        var fields = {
            members: ['member_code', 'first_name', 'last_name', 'phone', 'email', 'joining_date', 'status'],
            loans: ['loan_number', 'member_code', 'principal_amount', 'outstanding_principal', 'status', 'disbursement_date'],
            savings: ['account_number', 'member_code', 'current_balance', 'created_at'],
            transactions: ['date', 'type', 'amount', 'description']
        };
        
        var html = '';
        fields[type].forEach(function(field) {
            html += '<div class="col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="fields[]" value="' + field + '" id="' + field + '"><label class="form-check-label" for="' + field + '">' + field.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) + '</label></div></div>';
        });
        
        $('#fieldsContainer').html(html);
    }
    
    $('#generateReport').click(function() {
        // Placeholder for report generation
        alert('Custom report generation is under development. This feature will be available in future updates.');
    });
    
    $('#resetForm').click(function() {
        $('#customReportForm')[0].reset();
        $('#fieldSelector').hide();
        $('#reportResults').hide();
    });
});
</script>