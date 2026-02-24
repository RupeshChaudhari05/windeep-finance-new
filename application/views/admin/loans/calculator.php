<!-- EMI Calculator -->
<div class="row">
    <div class="col-md-5">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calculator mr-1"></i> EMI Calculator</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Loan Product <small class="text-muted">(Optional - auto-fills rate)</small></label>
                    <select id="loan_product" class="form-control">
                        <option value="">-- Custom Calculation --</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?= $product->id ?>" 
                                data-rate="<?= $product->interest_rate ?>" 
                                data-type="<?= $product->interest_type ?? 'reducing' ?>"
                                data-min="<?= $product->min_amount ?>"
                                data-max="<?= $product->max_amount ?>"
                                data-min-tenure="<?= $product->min_tenure_months ?>"
                                data-max-tenure="<?= $product->max_tenure_months ?>">
                            <?= $product->name ?> (<?= $product->interest_rate ?>%)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Loan Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">₹</span></div>
                        <input type="number" id="principal" class="form-control" value="100000" min="1000" step="1000">
                    </div>
                    <small class="text-muted" id="amountRange"></small>
                </div>
                
                <div class="form-group">
                    <label>Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" id="rate" class="form-control" value="12" step="0.01" min="0" max="100">
                        <div class="input-group-append"><span class="input-group-text">%</span></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tenure (Months) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" id="tenure" class="form-control" value="12" min="1" max="360">
                        <div class="input-group-append"><span class="input-group-text">months</span></div>
                    </div>
                    <small class="text-muted" id="tenureRange"></small>
                </div>
                
                <div class="form-group">
                    <label>Interest Calculation Type</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" name="calc_type" id="type_reducing" class="custom-control-input" value="reducing" checked>
                        <label class="custom-control-label" for="type_reducing">Reducing Balance Method</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" name="calc_type" id="type_flat" class="custom-control-input" value="flat">
                        <label class="custom-control-label" for="type_flat">Flat Rate Method</label>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" id="calculateBtn" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-calculator mr-1"></i> Calculate EMI
                </button>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <!-- Results -->
        <div class="card" id="resultCard" style="display: none;">
            <div class="card-header bg-success text-white">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> EMI Calculation Results</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 text-center border-right">
                        <h2 class="text-primary mb-0" id="emiAmount">₹0</h2>
                        <p class="text-muted mb-0">Monthly EMI</p>
                    </div>
                    <div class="col-md-6 text-center">
                        <h2 class="text-danger mb-0" id="totalPayable">₹0</h2>
                        <p class="text-muted mb-0">Total Payable</p>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h5 class="mb-0" id="totalPrincipal">₹0</h5>
                        <small class="text-muted">Principal Amount</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <h5 class="text-success mb-0" id="totalInterest">₹0</h5>
                        <small class="text-muted">Total Interest</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <h5 class="mb-0" id="interestPercent">0%</h5>
                        <small class="text-muted">Interest %</small>
                    </div>
                </div>
                
                <hr>
                
                <!-- Chart Container -->
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="emiChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Payment Breakdown</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-primary" id="principalBar" style="width: 0%"></div>
                            <div class="progress-bar bg-success" id="interestBar" style="width: 0%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small><span class="badge badge-primary">●</span> Principal</small>
                            <small><span class="badge badge-success">●</span> Interest</small>
                        </div>
                        
                        <div class="mt-4">
                            <table class="table table-sm">
                                <tr>
                                    <td>Number of EMIs:</td>
                                    <td class="text-right" id="numEmis">-</td>
                                </tr>
                                <tr>
                                    <td>Calculation Method:</td>
                                    <td class="text-right" id="calcMethod">-</td>
                                </tr>
                                <tr>
                                    <td>Effective Rate:</td>
                                    <td class="text-right" id="effectiveRate">-</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- EMI Schedule -->
        <div class="card" id="scheduleCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table mr-1"></i> EMI Schedule</h3>
                <div class="card-tools">
                    <button class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-striped table-sm mb-0">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th>EMI #</th>
                            <th class="text-right">EMI Amount</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Interest</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleBody">
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Tips -->
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="fas fa-lightbulb text-warning"></i> Understanding EMI</h6>
                <ul class="list-unstyled mb-0">
                    <li><small><strong>Reducing Balance:</strong> Interest is calculated on the outstanding principal, resulting in lower total interest. Most banks use this method.</small></li>
                    <li class="mt-2"><small><strong>Flat Rate:</strong> Interest is calculated on the original principal for the entire tenure, resulting in higher total interest.</small></li>
                    <li class="mt-2"><small><strong>Tip:</strong> A higher down payment or shorter tenure reduces total interest significantly.</small></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var emiChart = null;

$(document).ready(function() {
    // Product selection
    $('#loan_product').change(function() {
        var selected = $(this).find(':selected');
        if (selected.val()) {
            $('#rate').val(selected.data('rate'));
            $('input[name="calc_type"][value="' + selected.data('type') + '"]').prop('checked', true);
            $('#amountRange').text('Range: ₹' + selected.data('min').toLocaleString('en-IN') + ' - ₹' + selected.data('max').toLocaleString('en-IN'));
            $('#tenureRange').text('Range: ' + selected.data('min-tenure') + ' - ' + selected.data('max-tenure') + ' months');
        } else {
            $('#amountRange').text('');
            $('#tenureRange').text('');
        }
    });
    
    // Calculate EMI
    function calculateEMI() {
        var principal = parseFloat($('#principal').val()) || 0;
        var rate = parseFloat($('#rate').val()) || 0;
        var tenure = parseInt($('#tenure').val()) || 0;
        var type = $('input[name="calc_type"]:checked').val();
        
        if (principal <= 0 || rate <= 0 || tenure <= 0) {
            toastr.warning('Please enter valid values');
            return;
        }
        
        $.post('<?= site_url('admin/loans/calculate_emi') ?>', {
            principal: principal,
            rate: rate,
            tenure: tenure,
            type: type
        }, function(data) {
            // Display results
            $('#resultCard, #scheduleCard').show();
            
            $('#emiAmount').text('₹' + data.emi.toLocaleString('en-IN', {maximumFractionDigits: 0}));
            $('#totalPayable').text('₹' + data.total_amount.toLocaleString('en-IN', {maximumFractionDigits: 0}));
            $('#totalPrincipal').text('₹' + data.total_principal.toLocaleString('en-IN', {maximumFractionDigits: 0}));
            $('#totalInterest').text('₹' + data.total_interest.toLocaleString('en-IN', {maximumFractionDigits: 0}));
            
            var interestPercent = ((data.total_interest / data.total_principal) * 100).toFixed(1);
            $('#interestPercent').text(interestPercent + '%');
            
            var principalPercent = (data.total_principal / data.total_amount * 100).toFixed(1);
            var interestBarPercent = (100 - principalPercent).toFixed(1);
            $('#principalBar').css('width', principalPercent + '%');
            $('#interestBar').css('width', interestBarPercent + '%');
            
            $('#numEmis').text(tenure);
            $('#calcMethod').text(type == 'reducing' ? 'Reducing Balance' : 'Flat Rate');
            $('#effectiveRate').text(rate + '% p.a.');
            
            // Create/update chart
            if (emiChart) {
                emiChart.destroy();
            }
            
            var ctx = document.getElementById('emiChart').getContext('2d');
            emiChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Principal', 'Interest'],
                    datasets: [{
                        data: [data.total_principal, data.total_interest],
                        backgroundColor: ['#007bff', '#28a745'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
            
            // Generate schedule
            generateSchedule(principal, rate, tenure, type, data.emi);
            
        }, 'json');
    }
    
    function generateSchedule(principal, rate, tenure, type, emi) {
        var html = '';
        var balance = principal;
        var monthlyRate = rate / 12 / 100;
        
        for (var i = 1; i <= tenure; i++) {
            var interest, principalPart;
            
            if (type == 'reducing') {
                interest = balance * monthlyRate;
                principalPart = emi - interest;
            } else {
                interest = (principal * rate / 100) / 12;
                principalPart = principal / tenure;
            }
            
            balance -= principalPart;
            if (balance < 0) balance = 0;
            
            html += '<tr>';
            html += '<td>' + i + '</td>';
            html += '<td class="text-right">₹' + emi.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</td>';
            html += '<td class="text-right">₹' + principalPart.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</td>';
            html += '<td class="text-right">₹' + interest.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</td>';
            html += '<td class="text-right">₹' + balance.toLocaleString('en-IN', {maximumFractionDigits: 0}) + '</td>';
            html += '</tr>';
        }
        
        $('#scheduleBody').html(html);
    }
    
    $('#calculateBtn').click(calculateEMI);
    
    // Auto-calculate on Enter
    $('#principal, #rate, #tenure').keypress(function(e) {
        if (e.which == 13) {
            calculateEMI();
        }
    });
    
    // Initial calculation
    calculateEMI();
});
</script>
