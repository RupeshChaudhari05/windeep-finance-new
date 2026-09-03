/**
 * Live validation for the Add / Edit Member forms.
 *
 * Rules mirror the server (admin/Members::store and ::update) so the form never
 * accepts something the controller will reject. Fields are checked as they are
 * typed, and a running summary shows exactly what is still outstanding instead
 * of leaving the user to guess after a failed save.
 *
 * Usage:  <script src=".../member-form-validation.js"></script>
 *         MemberFormValidation.init('#memberForm');
 */
(function (window, $) {
    'use strict';

    var RULES = {
        first_name:      { required: true,  max: 100, label: 'First Name' },
        last_name:       { required: true,  max: 100, label: 'Last Name' },
        middle_name:     { max: 50,  label: 'Middle Name' },
        phone:           { required: true,  digits: true, min: 10, max: 15, label: 'Phone' },
        alternate_phone: { digits: true, min: 10, max: 15, label: 'Alternate Phone' },
        email:           { email: true, label: 'Email' },
        date_of_birth:   { required: true, minAge: 18, notFuture: true, label: 'Date of Birth' },
        gender:          { oneOf: ['male', 'female', 'other'], label: 'Gender' },
        pincode:         { digits: true, exact: 6, label: 'Pincode' },
        aadhaar_number:  { digits: true, exact: 12, label: 'Aadhaar Number' },
        pan_number:      { regex: /^[A-Z]{5}[0-9]{4}[A-Z]$/, upper: true, label: 'PAN Number',
                           hint: 'Format: AAAAA9999A' },
        monthly_income:  { number: true, minValue: 0, label: 'Monthly Income' },
        nominee_phone:   { digits: true, min: 10, max: 15, label: 'Nominee Phone' },
        bank_ifsc:       { regex: /^[A-Z]{4}0[A-Z0-9]{6}$/, upper: true, label: 'IFSC Code',
                           hint: 'Format: ABCD0123456' }
    };

    function ageFrom(dateString) {
        var dob = new Date(dateString);
        if (isNaN(dob.getTime())) { return null; }
        var t = new Date();
        var age = t.getFullYear() - dob.getFullYear();
        var m = t.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && t.getDate() < dob.getDate())) { age--; }
        return age;
    }

    /** @return {string|null} an error message, or null when the value is acceptable */
    function checkField($el, rule) {
        var raw = ($el.val() || '');
        var value = typeof raw === 'string' ? raw.trim() : raw;
        var label = rule.label || $el.attr('name');
        var isRequired = rule.required || $el.prop('required');

        if (value === '') {
            return isRequired ? label + ' is required' : null;
        }

        if (rule.digits && !/^\d+$/.test(value)) {
            return label + ' must contain digits only';
        }
        if (rule.number && isNaN(Number(value))) {
            return label + ' must be a number';
        }
        if (rule.minValue !== undefined && Number(value) < rule.minValue) {
            return label + ' cannot be negative';
        }
        if (rule.exact && value.length !== rule.exact) {
            return label + ' must be exactly ' + rule.exact + ' digits (' + value.length + ' entered)';
        }
        if (rule.min && value.length < rule.min) {
            return label + ' must be at least ' + rule.min + ' digits (' + value.length + ' entered)';
        }
        if (rule.max && value.length > rule.max) {
            return label + ' cannot exceed ' + rule.max + ' characters';
        }
        if (rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            return 'Enter a valid email address';
        }
        if (rule.regex && !rule.regex.test(value.toUpperCase())) {
            return (rule.hint ? label + ' is not valid. ' + rule.hint : label + ' is not valid');
        }
        if (rule.oneOf && rule.oneOf.indexOf(value) === -1) {
            return 'Choose a valid ' + label;
        }
        if (rule.notFuture && new Date(value) > new Date()) {
            return label + ' cannot be in the future';
        }
        if (rule.minAge) {
            var age = ageFrom(value);
            if (age === null) { return 'Enter a valid ' + label; }
            if (age < rule.minAge) {
                return 'Member must be at least ' + rule.minAge + ' years old (currently ' + age + ')';
            }
        }
        return null;
    }

    function fieldsOf($form) {
        var list = [];
        $form.find('input[name], select[name], textarea[name]').each(function () {
            var $el = $(this);
            var name = $el.attr('name');
            if (!name || $el.is(':hidden, [type=hidden], [type=file], [type=submit]')) { return; }
            var rule = RULES[name] || {};
            if (rule.required || $el.prop('required') || Object.keys(rule).length) {
                list.push({ el: $el, name: name, rule: rule });
            }
        });
        return list;
    }

    function paint($el, message, touched) {
        var $group = $el.closest('.form-group');
        $group.find('.live-feedback').remove();

        if (message) {
            $el.addClass('is-invalid').removeClass('is-valid');
            $group.append('<div class="live-feedback invalid-feedback d-block"><i class="fas fa-exclamation-circle mr-1"></i>' + message + '</div>');
        } else if (touched && ($el.val() || '').toString().trim() !== '') {
            $el.removeClass('is-invalid').addClass('is-valid');
        } else {
            $el.removeClass('is-invalid is-valid');
        }
    }

    function init(formSelector) {
        var $form = $(formSelector);
        if (!$form.length) { return; }

        var fields = fieldsOf($form);
        var touched = {};

        // Live summary panel
        var $summary = $('<div class="alert alert-info live-summary" style="position:sticky;bottom:0;z-index:5;">')
            .html('<span class="summary-text"></span>');
        $form.find('button[type=submit]').first().closest('div').before($summary);

        function refresh() {
            var missing = [], invalid = [];

            fields.forEach(function (f) {
                var msg = checkField(f.el, f.rule);
                paint(f.el, touched[f.name] ? msg : null, touched[f.name]);
                if (!msg) { return; }
                var required = f.rule.required || f.el.prop('required');
                var empty = (f.el.val() || '').toString().trim() === '';
                (required && empty ? missing : invalid).push({ name: f.name, msg: msg, el: f.el });
            });

            var $s = $summary.find('.summary-text');
            var total = missing.length + invalid.length;

            if (total === 0) {
                $summary.removeClass('alert-info alert-warning alert-danger').addClass('alert-success');
                $s.html('<i class="fas fa-check-circle mr-1"></i><strong>All set.</strong> Everything required is filled in correctly.');
            } else {
                $summary.removeClass('alert-info alert-success alert-danger').addClass('alert-warning');
                var parts = [];
                if (missing.length) {
                    parts.push('<strong>' + missing.length + '</strong> still to fill: ' +
                        missing.slice(0, 6).map(function (m) { return '<a href="#" class="goto-field alert-link" data-f="' + m.name + '">' + (RULES[m.name] ? RULES[m.name].label : m.name) + '</a>'; }).join(', ') +
                        (missing.length > 6 ? ' +' + (missing.length - 6) + ' more' : ''));
                }
                if (invalid.length) {
                    parts.push('<strong>' + invalid.length + '</strong> to correct: ' +
                        invalid.slice(0, 4).map(function (m) { return '<a href="#" class="goto-field alert-link" data-f="' + m.name + '">' + (RULES[m.name] ? RULES[m.name].label : m.name) + '</a>'; }).join(', '));
                }
                $s.html('<i class="fas fa-info-circle mr-1"></i>' + parts.join(' &nbsp;|&nbsp; '));
            }
            return { missing: missing, invalid: invalid };
        }

        fields.forEach(function (f) {
            f.el.on('input change', function () {
                if (f.rule.upper) {
                    var pos = this.selectionStart;
                    $(this).val(($(this).val() || '').toUpperCase());
                    if (pos !== null && this.setSelectionRange) { try { this.setSelectionRange(pos, pos); } catch (e) {} }
                }
                touched[f.name] = true;
                refresh();
            });
            f.el.on('blur', function () { touched[f.name] = true; refresh(); });
        });

        $summary.on('click', '.goto-field', function (e) {
            e.preventDefault();
            var $t = $form.find('[name="' + $(this).data('f') + '"]').first();
            if ($t.length) {
                $('html, body').animate({ scrollTop: $t.offset().top - 140 }, 250);
                $t.focus();
            }
        });

        $form.on('submit', function (e) {
            fields.forEach(function (f) { touched[f.name] = true; });
            var state = refresh();
            if (state.missing.length || state.invalid.length) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var first = (state.missing[0] || state.invalid[0]).el;
                $('html, body').animate({ scrollTop: first.offset().top - 140 }, 250);
                first.focus();
                if (window.toastr) {
                    toastr.error('Please complete the highlighted fields before saving.');
                }
                return false;
            }
        });

        refresh();
    }

    window.MemberFormValidation = { init: init, rules: RULES };
})(window, jQuery);
