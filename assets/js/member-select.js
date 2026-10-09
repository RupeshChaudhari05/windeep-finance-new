/**
 * Searchable member dropdowns — applied application-wide.
 *
 * Any <select> that lists members (name/id containing "member" or "guarantor",
 * or class .member-select / .guarantor-select) is turned into a Select2
 * searchable dropdown, including selects inside modals and ones added later
 * by JavaScript. Search matches anywhere in the option text (code, name, phone).
 *
 * Opt out with data-no-search="1". Selects already using Select2 with AJAX
 * (they have no static options) are left alone.
 */
(function ($) {
    if (!$ || !$.fn || !$.fn.select2) return;

    var SELECTOR = [
        'select[name*="member"]', 'select[id*="member"]',
        'select[name*="guarantor"]', 'select[id*="guarantor"]',
        'select.member-select', 'select.guarantor-select'
    ].join(',');

    // Member attributes, not member lists
    var EXCLUDE = /membership_type|member_level|member_type|member_status/i;

    function matcher(params, data) {
        var term = $.trim(params.term || '').toLowerCase();
        if (term === '') return data;
        if (!data.text) return null;
        var text = data.text.toLowerCase();
        // every word typed must appear somewhere ("ram 98" finds Ram Patil (98...))
        var words = term.split(/\s+/);
        for (var i = 0; i < words.length; i++) {
            if (text.indexOf(words[i]) === -1) return null;
        }
        return data;
    }

    function enhance(el) {
        var $s = $(el);
        if ($s.hasClass('select2-hidden-accessible')) return;   // already Select2
        if ($s.data('no-search') || $s.data('noSearch')) return;
        if (EXCLUDE.test((el.name || '') + ' ' + (el.id || ''))) return;
        if (el.options.length < 2) return;                       // AJAX-driven or empty

        var placeholder = $s.data('placeholder')
            || ($s.find('option[value=""]').first().text() || 'Search member...');
        var $modal = $s.closest('.modal');

        $s.select2({
            theme: 'bootstrap',
            width: '100%',
            placeholder: placeholder,
            allowClear: !el.multiple && !el.required,
            matcher: matcher,
            dropdownParent: $modal.length ? $modal : $(document.body)
        });
    }

    function scan(root) {
        $(root || document).find(SELECTOR).each(function () { enhance(this); });
    }

    $(function () {
        // Run after page scripts so their own Select2 setups take precedence
        setTimeout(scan, 0);

        $(document).on('shown.bs.modal', function (e) { scan(e.target); });

        // Selects added dynamically (e.g. "Add guarantor" rows)
        if (window.MutationObserver) {
            var pending = false;
            new MutationObserver(function () {
                if (pending) return;
                pending = true;
                setTimeout(function () { pending = false; scan(); }, 50);
            }).observe(document.body, { childList: true, subtree: true });
        }

        // Keep Select2 display in sync when code resets a form
        $(document).on('reset', 'form', function () {
            var $f = $(this);
            setTimeout(function () {
                $f.find('select.select2-hidden-accessible').trigger('change.select2');
            }, 0);
        });
    });
})(window.jQuery);
