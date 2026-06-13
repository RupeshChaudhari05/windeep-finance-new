<!-- Current Month Overdues Modal -->
<div class="modal fade" id="monthOverduesModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl modal-fullscreen" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span id="modalTitle">Current Month Overdues</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <!-- Loading State -->
                <div id="loadingState" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading overdues data...</p>
                </div>

                <!-- Content State -->
                <div id="contentState" style="display: none;">
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pendingTab" data-toggle="tab" href="#pendingContent" role="tab" aria-selected="true">
                                <i class="fas fa-clock mr-1"></i>
                                Pending for Email (<span id="pendingCount">0</span>)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="paidTab" data-toggle="tab" href="#paidContent" role="tab" aria-selected="false">
                                <i class="fas fa-check-circle mr-1"></i>
                                Paid (<span id="paidCount">0</span>)
                            </a>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Pending Tab -->
                        <div class="tab-pane fade show active" id="pendingContent" role="tabpanel">
                            <div id="pendingList">
                                <!-- Pending items will be rendered here -->
                            </div>
                            <div id="noPendingMsg" class="alert alert-success text-center" style="display: none;">
                                <i class="fas fa-check-circle mr-2"></i>
                                No pending items for this month!
                            </div>
                        </div>

                        <!-- Paid Tab -->
                        <div class="tab-pane fade" id="paidContent" role="tabpanel">
                            <div id="paidList">
                                <!-- Paid items will be rendered here -->
                            </div>
                            <div id="noPaidMsg" class="alert alert-info text-center" style="display: none;">
                                <i class="fas fa-info-circle mr-2"></i>
                                No paid items for this month.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div id="errorState" class="alert alert-warning" style="display: none;">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="errorMessage">Failed to load data. Please try again.</span>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="refreshBtn">
                    <i class="fas fa-sync-alt mr-1"></i>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .overdue-item {
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: 0.8rem;
        margin-bottom: 0.75rem;
        background-color: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .overdue-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    }

    .overdue-item.paid {
        border-color: #28a745;
    }

    .overdue-item .card-body {
        padding: 1rem 1.25rem;
    }

    .overdue-header {
        font-size: 0.92rem;
        letter-spacing: 0.02em;
        color: #2c3e50;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 0.4rem;
    }

    .overdue-subtitle {
        font-size: 0.82rem;
        color: #6c757d;
        margin-bottom: 0.8rem;
    }

    .overdue-detail {
        font-size: 0.88rem;
        color: #495057;
        justify-content: space-between;
        margin-bottom: 0.4rem;
    }

    .overdue-detail .label {
        color: #868e96;
        font-weight: 600;
    }

    .status-pill {
        font-size: 0.75rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .status-paid {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .action-buttons .btn {
        min-width: 40px;
    }

    .modal-header {
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    .modal-fullscreen .modal-content {
        min-height: 100vh;
        border-radius: 0;
    }

    .modal-fullscreen .modal-body {
        padding: 1.5rem;
        overflow-y: auto;
        max-height: calc(100vh - 178px);
    }

    .modal-fullscreen .modal-footer {
        border-top: 1px solid rgba(0,0,0,0.06);
    }

    .nav-tabs .nav-link {
        font-weight: 600;
    }

    .nav-tabs .nav-link.active {
        background-color: #f7f7f7;
        border-color: #dee2e6 #dee2e6 #fff;
    }
</style>

<script>
    $(document).ready(function() {
        // Load data when modal is shown
        $('#monthOverduesModal').on('show.bs.modal', function() {
            loadMonthOverdues();
        });

        // Refresh button
        $('#refreshBtn').on('click', function() {
            loadMonthOverdues();
        });

        function loadMonthOverdues() {
            $('#loadingState').show();
            $('#contentState').hide();
            $('#errorState').hide();

            $.ajax({
                url: '<?= site_url('admin/dashboard/get_current_month_overdues') ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderOverdueData(response.data);
                    } else {
                        showError(response.message || 'Failed to load data');
                    }
                },
                error: function(xhr, status, error) {
                    showError('Error loading overdues: ' + error);
                }
            });
        }

        function renderOverdueData(data) {
            $('#loadingState').hide();
            $('#contentState').show();
            $('#errorState').hide();

            // Update counts
            $('#pendingCount').text(data.pending.length);
            $('#paidCount').text(data.paid.length);
            $('#modalTitle').text('Current Month Overdues - ' + data.month);

            // Render pending items
            if (data.pending.length > 0) {
                let pendingHtml = '';
                data.pending.forEach(function(item) {
                    pendingHtml += renderOverdueItem(item, false);
                });
                $('#pendingList').html(pendingHtml);
                $('#noPendingMsg').hide();
            } else {
                $('#pendingList').html('');
                $('#noPendingMsg').show();
            }

            // Render paid items
            if (data.paid.length > 0) {
                let paidHtml = '';
                data.paid.forEach(function(item) {
                    paidHtml += renderOverdueItem(item, true);
                });
                $('#paidList').html(paidHtml);
                $('#noPaidMsg').hide();
            } else {
                $('#paidList').html('');
                $('#noPaidMsg').show();
            }
        }

        function renderOverdueItem(item, isPaid) {
            const statusClass = isPaid ? 'status-paid' : 'status-pending';
            const statusText = isPaid ? 'PAID' : 'PENDING';
            const amount = parseFloat(item.emi_amount) - parseFloat(item.total_paid);
            const formattedAmount = '₹' + formatAmount(amount);

            return `
                <div class="overdue-item ${isPaid ? 'paid' : ''}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="mr-3 flex-grow-1">
                                <div class="overdue-header">
                                    <i class="fas fa-user-circle mr-2"></i>
                                    ${item.first_name} ${item.last_name}
                                    <span class="badge badge-info ml-2">${item.member_code}</span>
                                </div>
                                <div class="overdue-subtitle">
                                    Loan #: <strong>${item.loan_number}</strong> &nbsp;•&nbsp; EMI #: <strong>${item.installment_number}</strong>
                                </div>
                                <div class="overdue-detail">
                                    <span class="label">Due Date</span>
                                    <span>${formatDate(item.due_date)}</span>
                                </div>
                                <div class="overdue-detail">
                                    <span class="label">Amount Due</span>
                                    <span class="text-danger font-weight-bold">${formattedAmount}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <div class="status-pill ${statusClass}">${statusText}</div>
                                <div class="mt-3 action-buttons d-flex justify-content-end">
                                    <a href="<?= site_url('admin/members/view/') ?>${item.member_id}"
                                       class="btn btn-outline-info btn-sm mr-2" title="View Member">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= site_url('admin/loans/view/') ?>${item.loan_id}"
                                       class="btn btn-outline-primary btn-sm" title="View Loan">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function showError(message) {
            $('#loadingState').hide();
            $('#contentState').hide();
            $('#errorState').show();
            $('#errorMessage').text(message);
        }

        function formatAmount(amount) {
            return parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('en-IN', options);
        }
    });
</script>
