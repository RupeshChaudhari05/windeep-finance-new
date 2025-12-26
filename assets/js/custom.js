/**
 * Windeep Finance - Custom JavaScript
 * Version: 1.0.0
 */

(function ($) {
    'use strict';

    // Global AJAX Setup
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    // ============================================
    // UTILITY FUNCTIONS
    // ============================================

    const WF = {
        // Base URL (set in layout)
        baseUrl: window.BASE_URL || '/',

        // Format currency
        formatCurrency: function (amount, symbol = '₹') {
            if (isNaN(amount)) return symbol + '0.00';
            return symbol + parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        // Format date
        formatDate: function (dateStr, format = 'DD-MM-YYYY') {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();

            switch (format) {
                case 'DD-MM-YYYY':
                    return `${day}-${month}-${year}`;
                case 'YYYY-MM-DD':
                    return `${year}-${month}-${day}`;
                case 'DD/MM/YYYY':
                    return `${day}/${month}/${year}`;
                default:
                    return `${day}-${month}-${year}`;
            }
        },

        // Show loading overlay
        showLoading: function (selector = 'body') {
            const overlay = '<div class="overlay-loading"><div class="spinner-custom"></div></div>';
            $(selector).css('position', 'relative').append(overlay);
        },

        // Hide loading overlay
        hideLoading: function (selector = 'body') {
            $(selector).find('.overlay-loading').remove();
        },

        // Toast notification
        toast: function (message, type = 'success', title = '') {
            const bgClass = {
                'success': 'bg-success',
                'error': 'bg-danger',
                'warning': 'bg-warning',
                'info': 'bg-info'
            };

            $(document).Toasts('create', {
                class: bgClass[type] || 'bg-info',
                title: title || type.charAt(0).toUpperCase() + type.slice(1),
                body: message,
                autohide: true,
                delay: 4000
            });
        },

        // Confirm dialog
        confirm: function (message, callback, title = 'Confirm Action') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed && typeof callback === 'function') {
                    callback();
                }
            });
        },

        // Delete confirmation
        confirmDelete: function (message, callback) {
            Swal.fire({
                title: 'Are you sure?',
                text: message || 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed && typeof callback === 'function') {
                    callback();
                }
            });
        },

        // AJAX request wrapper
        ajax: function (options) {
            const defaults = {
                url: '',
                method: 'POST',
                data: {},
                dataType: 'json',
                showLoading: true,
                loadingTarget: 'body',
                onSuccess: function () { },
                onError: function () { },
                onComplete: function () { }
            };

            const settings = $.extend({}, defaults, options);

            if (settings.showLoading) {
                WF.showLoading(settings.loadingTarget);
            }

            return $.ajax({
                url: settings.url,
                method: settings.method,
                data: settings.data,
                dataType: settings.dataType,
                success: function (response) {
                    if (response.status === 'success' || response.success === true) {
                        settings.onSuccess(response);
                    } else {
                        WF.toast(response.message || 'Operation failed', 'error');
                        settings.onError(response);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    WF.toast('An error occurred. Please try again.', 'error');
                    settings.onError({ message: error });
                },
                complete: function () {
                    if (settings.showLoading) {
                        WF.hideLoading(settings.loadingTarget);
                    }
                    settings.onComplete();
                }
            });
        },

        // Form validation helper
        validateForm: function (formSelector) {
            const form = $(formSelector);
            let isValid = true;

            // Clear previous errors
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();

            // Check required fields
            form.find('[required]').each(function () {
                const field = $(this);
                const value = field.val();

                if (!value || (Array.isArray(value) && value.length === 0)) {
                    isValid = false;
                    field.addClass('is-invalid');
                    const label = field.closest('.form-group').find('label').text().replace('*', '').trim();
                    field.after(`<div class="invalid-feedback">${label} is required</div>`);
                }
            });

            // Check email fields
            form.find('input[type="email"]').each(function () {
                const field = $(this);
                const value = field.val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (value && !emailRegex.test(value)) {
                    isValid = false;
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Please enter a valid email address</div>');
                }
            });

            // Check phone fields
            form.find('input[data-validate="phone"]').each(function () {
                const field = $(this);
                const value = field.val();
                const phoneRegex = /^[0-9]{10}$/;

                if (value && !phoneRegex.test(value)) {
                    isValid = false;
                    field.addClass('is-invalid');
                    field.after('<div class="invalid-feedback">Please enter a valid 10-digit phone number</div>');
                }
            });

            return isValid;
        },

        // Serialize form to object
        serializeFormToObject: function (formSelector) {
            const form = $(formSelector);
            const obj = {};
            const arr = form.serializeArray();

            $.each(arr, function () {
                if (obj[this.name]) {
                    if (!obj[this.name].push) {
                        obj[this.name] = [obj[this.name]];
                    }
                    obj[this.name].push(this.value || '');
                } else {
                    obj[this.name] = this.value || '';
                }
            });

            return obj;
        },

        // Print element
        printElement: function (selector, title = 'Print') {
            const content = $(selector).html();
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${title}</title>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
                    <style>
                        body { padding: 20px; }
                        @media print {
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    ${content}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onafterprint = function() { window.close(); };
                        };
                    </script>
                </body>
                </html>
            `);

            printWindow.document.close();
        },

        // Calculate EMI
        calculateEMI: function (principal, rate, tenure) {
            // Rate is annual, convert to monthly
            const monthlyRate = (rate / 12) / 100;
            const n = tenure;

            if (monthlyRate === 0) {
                return principal / n;
            }

            const emi = principal * monthlyRate * Math.pow(1 + monthlyRate, n) / (Math.pow(1 + monthlyRate, n) - 1);
            return Math.round(emi * 100) / 100;
        },

        // Generate amortization schedule
        generateAmortization: function (principal, rate, tenure, type = 'reducing') {
            const schedule = [];
            let balance = principal;
            const monthlyRate = (rate / 12) / 100;

            if (type === 'flat') {
                const totalInterest = principal * (rate / 100) * (tenure / 12);
                const emi = (principal + totalInterest) / tenure;
                const monthlyPrincipal = principal / tenure;
                const monthlyInterest = totalInterest / tenure;

                for (let i = 1; i <= tenure; i++) {
                    schedule.push({
                        installment: i,
                        emi: Math.round(emi * 100) / 100,
                        principal: Math.round(monthlyPrincipal * 100) / 100,
                        interest: Math.round(monthlyInterest * 100) / 100,
                        balance: Math.round((balance - monthlyPrincipal) * 100) / 100
                    });
                    balance -= monthlyPrincipal;
                }
            } else {
                const emi = WF.calculateEMI(principal, rate, tenure);

                for (let i = 1; i <= tenure; i++) {
                    const interest = balance * monthlyRate;
                    const principalPart = emi - interest;
                    balance -= principalPart;

                    schedule.push({
                        installment: i,
                        emi: Math.round(emi * 100) / 100,
                        principal: Math.round(principalPart * 100) / 100,
                        interest: Math.round(interest * 100) / 100,
                        balance: Math.max(0, Math.round(balance * 100) / 100)
                    });
                }
            }

            return schedule;
        }
    };

    // Expose to global scope
    window.WF = WF;

    // ============================================
    // DOCUMENT READY
    // ============================================

    $(document).ready(function () {

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Initialize popovers
        $('[data-toggle="popover"]').popover();

        // Initialize Select2
        if ($.fn.select2) {
            $('.select2').select2({
                theme: 'bootstrap4',
                allowClear: true,
                placeholder: function () {
                    return $(this).data('placeholder') || 'Select an option';
                }
            });
        }

        // Initialize DatePicker
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                format: 'dd-mm-yyyy',
                autoclose: true,
                todayHighlight: true,
                clearBtn: true
            });
        }

        // Initialize DateRangePicker
        if ($.fn.daterangepicker) {
            $('.daterange').daterangepicker({
                locale: {
                    format: 'DD-MM-YYYY'
                },
                autoApply: true
            });
        }

        // Initialize DataTables
        if ($.fn.DataTable) {
            $('.datatable').each(function () {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        responsive: true,
                        pageLength: 25,
                        language: {
                            search: '_INPUT_',
                            searchPlaceholder: 'Search...',
                            lengthMenu: 'Show _MENU_ entries',
                            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                            paginate: {
                                first: '<i class="fas fa-angle-double-left"></i>',
                                last: '<i class="fas fa-angle-double-right"></i>',
                                next: '<i class="fas fa-angle-right"></i>',
                                previous: '<i class="fas fa-angle-left"></i>'
                            }
                        },
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                            '<"row"<"col-sm-12"tr>>' +
                            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                    });
                }
            });
        }

        // Input masks
        if ($.fn.inputmask) {
            $('input[data-mask="phone"]').inputmask('9999999999');
            $('input[data-mask="aadhaar"]').inputmask('9999 9999 9999');
            $('input[data-mask="pan"]').inputmask('AAAAA9999A');
            $('input[data-mask="pincode"]').inputmask('999999');
            $('input[data-mask="ifsc"]').inputmask('AAAA0A*****');
            $('input[data-mask="currency"]').inputmask('numeric', {
                groupSeparator: ',',
                digits: 2,
                digitsOptional: false,
                prefix: '₹ ',
                placeholder: '0'
            });
        }

        // Auto-uppercase
        $(document).on('input', '.text-uppercase', function () {
            $(this).val($(this).val().toUpperCase());
        });

        // Number only input
        $(document).on('keypress', '.number-only', function (e) {
            if (e.which < 48 || e.which > 57) {
                e.preventDefault();
            }
        });

        // Decimal input
        $(document).on('keypress', '.decimal-only', function (e) {
            const charCode = e.which;
            const value = $(this).val();

            if (charCode === 46 && value.indexOf('.') === -1) {
                return true;
            }

            if (charCode < 48 || charCode > 57) {
                e.preventDefault();
            }
        });

        // Form submission with AJAX
        $(document).on('submit', 'form.ajax-form', function (e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('[type="submit"]');
            const originalBtnText = submitBtn.html();

            // Validate form
            if (!WF.validateForm(form)) {
                WF.toast('Please fill all required fields correctly', 'error');
                return false;
            }

            // Disable submit button
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            // Collect form data
            const formData = new FormData(form[0]);

            $.ajax({
                url: form.attr('action'),
                method: form.attr('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success' || response.success === true) {
                        WF.toast(response.message || 'Operation successful', 'success');

                        if (response.redirect) {
                            setTimeout(function () {
                                window.location.href = response.redirect;
                            }, 1000);
                        } else if (form.data('reload')) {
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        } else if (form.data('reset')) {
                            form[0].reset();
                            form.find('.select2').val('').trigger('change');
                        }

                        // Trigger custom event
                        form.trigger('ajax-success', [response]);
                    } else {
                        WF.toast(response.message || 'Operation failed', 'error');

                        // Show field errors
                        if (response.errors) {
                            $.each(response.errors, function (field, message) {
                                const input = form.find(`[name="${field}"]`);
                                input.addClass('is-invalid');
                                input.after(`<div class="invalid-feedback">${message}</div>`);
                            });
                        }

                        form.trigger('ajax-error', [response]);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Form submission error:', error);
                    WF.toast('An error occurred. Please try again.', 'error');
                    form.trigger('ajax-error', [{ message: error }]);
                },
                complete: function () {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                }
            });
        });

        // Delete action
        $(document).on('click', '.btn-delete', function (e) {
            e.preventDefault();

            const btn = $(this);
            const url = btn.data('url') || btn.attr('href');
            const message = btn.data('message') || 'Are you sure you want to delete this item?';

            WF.confirmDelete(message, function () {
                WF.ajax({
                    url: url,
                    method: 'POST',
                    data: { action: 'delete' },
                    onSuccess: function (response) {
                        WF.toast(response.message || 'Deleted successfully', 'success');

                        // Remove row if in table
                        const row = btn.closest('tr');
                        if (row.length) {
                            row.fadeOut(300, function () {
                                $(this).remove();
                            });
                        } else {
                            setTimeout(function () {
                                window.location.reload();
                            }, 1000);
                        }
                    }
                });
            });
        });

        // Status toggle
        $(document).on('click', '.btn-status-toggle', function (e) {
            e.preventDefault();

            const btn = $(this);
            const url = btn.data('url');
            const currentStatus = btn.data('status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            WF.confirm(`Change status to ${newStatus}?`, function () {
                WF.ajax({
                    url: url,
                    method: 'POST',
                    data: { status: newStatus },
                    onSuccess: function (response) {
                        WF.toast(response.message || 'Status updated', 'success');
                        btn.data('status', newStatus);

                        // Update badge
                        const badge = btn.find('.badge');
                        if (newStatus === 'active') {
                            badge.removeClass('badge-danger badge-secondary').addClass('badge-success').text('Active');
                        } else {
                            badge.removeClass('badge-success').addClass('badge-secondary').text('Inactive');
                        }
                    }
                });
            });
        });

        // EMI Calculator
        $(document).on('change', '.emi-calculator input', function () {
            const form = $(this).closest('.emi-calculator');
            const principal = parseFloat(form.find('[name="principal"]').val()) || 0;
            const rate = parseFloat(form.find('[name="rate"]').val()) || 0;
            const tenure = parseInt(form.find('[name="tenure"]').val()) || 0;
            const type = form.find('[name="interest_type"]').val() || 'reducing';

            if (principal > 0 && rate > 0 && tenure > 0) {
                let emi, totalInterest, totalPayable;

                if (type === 'flat') {
                    totalInterest = principal * (rate / 100) * (tenure / 12);
                    totalPayable = principal + totalInterest;
                    emi = totalPayable / tenure;
                } else {
                    emi = WF.calculateEMI(principal, rate, tenure);
                    totalPayable = emi * tenure;
                    totalInterest = totalPayable - principal;
                }

                form.find('.emi-result').text(WF.formatCurrency(emi));
                form.find('.total-interest-result').text(WF.formatCurrency(totalInterest));
                form.find('.total-payable-result').text(WF.formatCurrency(totalPayable));
            }
        });

        // File input preview
        $(document).on('change', '.custom-file-input', function () {
            const fileName = $(this).val().split('\\').pop();
            $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
        });

        // Image preview
        $(document).on('change', '.image-input', function () {
            const input = this;
            const preview = $($(this).data('preview'));

            if (input.files && input.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    preview.attr('src', e.target.result);
                };

                reader.readAsDataURL(input.files[0]);
            }
        });

        // Sidebar menu active state
        const currentUrl = window.location.href;
        $('.nav-sidebar .nav-link').each(function () {
            const href = $(this).attr('href');
            if (href && currentUrl.indexOf(href) !== -1) {
                $(this).addClass('active');
                $(this).closest('.nav-treeview').parent().addClass('menu-open');
                $(this).closest('.nav-treeview').show();
            }
        });

        // Card collapse toggle
        $(document).on('click', '.card-header .btn-tool[data-card-widget="collapse"]', function () {
            const icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });

    });

})(jQuery);
