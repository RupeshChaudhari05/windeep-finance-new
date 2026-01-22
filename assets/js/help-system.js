/**
 * Windeep Finance - Help System
 * 
 * Provides contextual help, tooltips, and onboarding guidance
 * throughout the application.
 */

(function ($) {
    'use strict';

    // Help text configurations
    const helpTexts = {
        // Member Management
        'member_code': 'Unique identifier for the member. Leave empty to auto-generate.',
        'member_name': 'Enter the full legal name as it appears on ID documents.',
        'member_email': 'Email is required for notifications and password recovery.',
        'member_phone': 'Primary contact number. Include country code if international.',
        'member_address': 'Complete postal address for correspondence.',
        'guarantor_required': 'Members who will vouch for loan repayment.',
        'nominee_name': 'Person to receive benefits in case of member\'s demise.',

        // Loan Management
        'loan_amount': 'Principal amount to be disbursed. Must be within product limits.',
        'loan_product': 'Select the appropriate loan product. Interest rate and terms will be applied automatically.',
        'loan_tenure': 'Number of months/weeks for repayment. Check product limits.',
        'processing_fee': 'One-time fee deducted from disbursement or paid separately.',
        'interest_rate': 'Annual interest rate. Will be calculated based on product settings.',
        'emi_amount': 'Equated Monthly Installment - calculated automatically.',
        'disbursement_date': 'Date when loan amount will be released.',
        'first_emi_date': 'Date of first EMI payment. Usually one period after disbursement.',
        'guarantor_consent': 'All guarantors must provide consent before loan approval.',

        // Savings Management
        'savings_scheme': 'Choose the savings product. Interest rate and schedule will apply.',
        'monthly_contribution': 'Fixed amount to be deposited each month.',
        'due_day': 'Day of month when payment is due (1-28).',
        'maturity_months': 'Total duration of the savings plan.',
        'interest_rate_savings': 'Annual interest rate credited on maturity.',

        // Payments
        'payment_amount': 'Amount being paid. Can be partial or full installment.',
        'payment_mode': 'Method of payment - Cash, Bank Transfer, UPI, etc.',
        'reference_number': 'Transaction ID for bank transfers, UPI, or cheques.',
        'payment_date': 'Date of actual payment receipt.',
        'receipt_number': 'System-generated receipt. Print for member\'s records.',

        // Reports
        'date_range': 'Select the period for report generation.',
        'report_format': 'Choose PDF for printing or Excel for further analysis.',
        'grouping': 'How to organize the data in the report.',

        // Bank Reconciliation
        'bank_statement': 'Upload CSV file from your bank. Match format in sample.',
        'auto_match': 'System will attempt to match transactions automatically.',
        'manual_match': 'Click to manually match a bank transaction with a payment.',

        // Settings
        'company_name': 'Your organization name. Appears on all documents.',
        'company_logo': 'Upload logo (PNG/JPG, max 500KB). Used in reports and receipts.',
        'email_settings': 'Configure SMTP for sending notifications.',
        'sms_settings': 'Set up SMS gateway for payment reminders.',
        'whatsapp_settings': 'Enable WhatsApp Business API for notifications.',
        'fine_settings': 'Configure late payment penalty rules.',
        'npa_settings': 'Define Non-Performing Asset classification rules.',

        // Fines
        'fine_type': 'Type of fine - Late Payment, Bounced Cheque, etc.',
        'fine_amount': 'Fixed penalty amount or percentage of overdue.',
        'grace_period': 'Days after due date before fine is applied.',
        'max_fine': 'Maximum fine amount regardless of calculation.',

        // Dashboard
        'active_loans': 'Currently running loans with outstanding balance.',
        'npa_loans': 'Loans classified as Non-Performing Assets.',
        'overdue_amount': 'Total pending payments past their due dates.',
        'collection_rate': 'Percentage of expected payments actually received.'
    };

    // Initialize help system
    function init() {
        initTooltips();
        initHelpIcons();
        initOnboarding();
        initContextualHelp();
        initFormValidationHelp();
    }

    // Initialize Bootstrap tooltips with custom styling
    function initTooltips() {
        $('[data-toggle="tooltip"]').tooltip({
            trigger: 'hover focus',
            placement: 'auto',
            html: true,
            template: '<div class="tooltip help-tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
        });
    }

    // Add help icons to form fields
    function initHelpIcons() {
        // Auto-add help icons to labeled form groups
        $('[data-help]').each(function () {
            const $element = $(this);
            const helpKey = $element.data('help');
            const helpText = helpTexts[helpKey] || $element.data('help-text');

            if (helpText) {
                const $helpIcon = $('<i class="fas fa-question-circle help-icon ml-1" data-toggle="tooltip" title="' + helpText + '"></i>');

                // Find the label for this input
                const $label = $('label[for="' + $element.attr('id') + '"]');
                if ($label.length) {
                    $label.append($helpIcon);
                } else {
                    $element.closest('.form-group').find('label').first().append($helpIcon);
                }
            }
        });

        // Re-initialize tooltips for newly added elements
        initTooltips();
    }

    // First-time user onboarding
    function initOnboarding() {
        const onboardingShown = localStorage.getItem('windeep_onboarding_complete');

        if (!onboardingShown && window.location.pathname.includes('/admin/dashboard')) {
            showOnboardingTour();
        }
    }

    // Show onboarding tour
    function showOnboardingTour() {
        const steps = [
            {
                element: '.main-sidebar',
                title: 'Navigation Menu',
                content: 'Access all modules from here: Members, Loans, Savings, Reports, and Settings.',
                position: 'right'
            },
            {
                element: '.content-wrapper',
                title: 'Main Content Area',
                content: 'This is where you\'ll perform all operations. Forms, tables, and reports appear here.',
                position: 'left'
            },
            {
                element: '.navbar-nav.ml-auto',
                title: 'Quick Actions',
                content: 'Access notifications, your profile, and logout from here.',
                position: 'bottom'
            }
        ];

        let currentStep = 0;

        function showStep(stepIndex) {
            if (stepIndex >= steps.length) {
                completeOnboarding();
                return;
            }

            const step = steps[stepIndex];
            const $element = $(step.element);

            if (!$element.length) {
                showStep(stepIndex + 1);
                return;
            }

            // Create overlay
            $('body').append('<div class="onboarding-overlay"></div>');

            // Highlight element
            $element.addClass('onboarding-highlight');

            // Create tooltip
            const $tooltip = $(`
                <div class="onboarding-tooltip" style="position: fixed; z-index: 10000;">
                    <h5>${step.title}</h5>
                    <p>${step.content}</p>
                    <div class="mt-3">
                        <button class="btn btn-sm btn-secondary onboarding-skip">Skip Tour</button>
                        <button class="btn btn-sm btn-primary onboarding-next">
                            ${stepIndex < steps.length - 1 ? 'Next' : 'Finish'}
                        </button>
                    </div>
                    <div class="onboarding-progress mt-2">
                        ${steps.map((_, i) => `<span class="dot ${i <= stepIndex ? 'active' : ''}"></span>`).join('')}
                    </div>
                </div>
            `);

            $('body').append($tooltip);
            positionTooltip($tooltip, $element, step.position);

            // Event handlers
            $tooltip.find('.onboarding-next').on('click', function () {
                cleanupStep();
                showStep(stepIndex + 1);
            });

            $tooltip.find('.onboarding-skip').on('click', function () {
                cleanupStep();
                completeOnboarding();
            });
        }

        function cleanupStep() {
            $('.onboarding-overlay').remove();
            $('.onboarding-tooltip').remove();
            $('.onboarding-highlight').removeClass('onboarding-highlight');
        }

        function completeOnboarding() {
            localStorage.setItem('windeep_onboarding_complete', 'true');
            cleanupStep();
        }

        function positionTooltip($tooltip, $element, position) {
            const offset = $element.offset();
            const width = $element.outerWidth();
            const height = $element.outerHeight();
            const tooltipWidth = 300;

            let left, top;

            switch (position) {
                case 'right':
                    left = offset.left + width + 20;
                    top = offset.top + height / 2 - 50;
                    break;
                case 'left':
                    left = offset.left - tooltipWidth - 20;
                    top = offset.top + height / 2 - 50;
                    break;
                case 'bottom':
                    left = offset.left + width / 2 - tooltipWidth / 2;
                    top = offset.top + height + 20;
                    break;
                default:
                    left = offset.left + width / 2 - tooltipWidth / 2;
                    top = offset.top - 120;
            }

            $tooltip.css({ left: left, top: top, width: tooltipWidth });
        }

        // Start tour
        showStep(0);
    }

    // Context-sensitive help panel
    function initContextualHelp() {
        // Add help button to page headers
        $('.content-header h1').each(function () {
            const $header = $(this);
            const pageName = $header.text().trim();

            if (!$header.find('.context-help-btn').length) {
                const $helpBtn = $('<button class="btn btn-link context-help-btn ml-2" title="Need help?"><i class="fas fa-question-circle"></i></button>');
                $helpBtn.on('click', function () {
                    showContextualHelp(pageName);
                });
                $header.append($helpBtn);
            }
        });
    }

    // Show contextual help modal
    function showContextualHelp(pageName) {
        const helpContent = getPageHelp(pageName);

        const $modal = $(`
            <div class="modal fade" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title"><i class="fas fa-question-circle mr-2"></i>${pageName} - Help</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${helpContent}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `);

        $('body').append($modal);
        $modal.modal('show');
        $modal.on('hidden.bs.modal', function () {
            $modal.remove();
        });
    }

    // Get page-specific help content
    function getPageHelp(pageName) {
        const pageHelp = {
            'Dashboard': `
                <h6>Dashboard Overview</h6>
                <p>The dashboard provides a quick overview of your organization's financial status.</p>
                <ul>
                    <li><strong>Statistics Cards:</strong> Show key metrics at a glance</li>
                    <li><strong>Charts:</strong> Visual representation of collections and loans</li>
                    <li><strong>Recent Activity:</strong> Latest transactions and actions</li>
                </ul>
                <h6>Quick Actions</h6>
                <p>Use the quick action buttons to rapidly access common tasks like adding a new member or recording a payment.</p>
            `,
            'Members': `
                <h6>Member Management</h6>
                <p>This section allows you to manage all member information.</p>
                <ul>
                    <li><strong>Add Member:</strong> Register new members with personal and KYC details</li>
                    <li><strong>View/Edit:</strong> Update member information and view their history</li>
                    <li><strong>Status:</strong> Members can be Active, Inactive, or Suspended</li>
                </ul>
                <h6>Member Documents</h6>
                <p>Upload ID proofs, photos, and other required documents for each member.</p>
            `,
            'Loans': `
                <h6>Loan Management</h6>
                <p>Process and track all loan applications and disbursements.</p>
                <ul>
                    <li><strong>New Loan:</strong> Create loan application with product selection</li>
                    <li><strong>Guarantors:</strong> Add and manage guarantor consents</li>
                    <li><strong>Approval:</strong> Review and approve/reject loan applications</li>
                    <li><strong>Disbursement:</strong> Process loan amount release</li>
                </ul>
                <h6>EMI Schedule</h6>
                <p>View and manage the complete repayment schedule for each loan.</p>
            `,
            'default': `
                <h6>General Help</h6>
                <p>Welcome to Windeep Finance. This system helps you manage:</p>
                <ul>
                    <li>Member registrations and profiles</li>
                    <li>Loan applications, approvals, and repayments</li>
                    <li>Savings schemes and contributions</li>
                    <li>Financial reports and analytics</li>
                </ul>
                <p>Use the sidebar navigation to access different modules. Each page has contextual help available.</p>
            `
        };

        return pageHelp[pageName] || pageHelp['default'];
    }

    // Form validation help messages
    function initFormValidationHelp() {
        // Custom validation messages
        $('form').on('submit', function () {
            $(this).find(':invalid').each(function () {
                const $field = $(this);
                let message = $field.data('validation-message');

                if (!message) {
                    if ($field.prop('required') && !$field.val()) {
                        message = 'This field is required';
                    } else if ($field.attr('type') === 'email') {
                        message = 'Please enter a valid email address';
                    } else if ($field.attr('type') === 'tel') {
                        message = 'Please enter a valid phone number';
                    } else if ($field.attr('min') || $field.attr('max')) {
                        message = `Value must be between ${$field.attr('min') || 0} and ${$field.attr('max') || '∞'}`;
                    }
                }

                $field[0].setCustomValidity(message || 'Invalid value');
            });
        });

        // Clear custom validity on input
        $('input, select, textarea').on('input change', function () {
            this.setCustomValidity('');
        });
    }

    // Utility: Show inline help
    window.showInlineHelp = function (element, message, type = 'info') {
        const $element = $(element);
        const $help = $(`<small class="form-text text-${type} inline-help"><i class="fas fa-info-circle mr-1"></i>${message}</small>`);

        $element.closest('.form-group').find('.inline-help').remove();
        $element.after($help);

        setTimeout(function () {
            $help.fadeOut(function () {
                $help.remove();
            });
        }, 5000);
    };

    // Utility: Show step indicator
    window.showStepIndicator = function (container, currentStep, totalSteps, steps) {
        const $container = $(container);
        $container.empty();

        const $stepper = $('<div class="stepper d-flex justify-content-between"></div>');

        steps.forEach((step, index) => {
            const status = index < currentStep ? 'completed' : (index === currentStep ? 'active' : '');
            $stepper.append(`
                <div class="step ${status}">
                    <div class="step-number">${index + 1}</div>
                    <div class="step-label">${step}</div>
                </div>
            `);
        });

        $container.append($stepper);
    };

    // Add CSS for help system
    $('head').append(`
        <style>
            .help-icon {
                color: #17a2b8;
                cursor: help;
                font-size: 0.875rem;
            }
            .help-tooltip .tooltip-inner {
                max-width: 300px;
                text-align: left;
                padding: 10px 15px;
            }
            .onboarding-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9998;
            }
            .onboarding-highlight {
                position: relative;
                z-index: 9999;
                box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);
            }
            .onboarding-tooltip {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            }
            .onboarding-tooltip h5 {
                margin-bottom: 10px;
                color: #333;
            }
            .onboarding-progress {
                text-align: center;
            }
            .onboarding-progress .dot {
                display: inline-block;
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #ddd;
                margin: 0 3px;
            }
            .onboarding-progress .dot.active {
                background: #007bff;
            }
            .context-help-btn {
                font-size: 1rem;
                padding: 0;
                color: #17a2b8;
            }
            .stepper {
                padding: 20px 0;
            }
            .stepper .step {
                text-align: center;
                flex: 1;
                position: relative;
            }
            .stepper .step-number {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                background: #ddd;
                color: #666;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 5px;
                font-weight: bold;
            }
            .stepper .step.active .step-number {
                background: #007bff;
                color: white;
            }
            .stepper .step.completed .step-number {
                background: #28a745;
                color: white;
            }
            .stepper .step-label {
                font-size: 12px;
                color: #666;
            }
            .stepper .step.active .step-label {
                color: #007bff;
                font-weight: bold;
            }
        </style>
    `);

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
