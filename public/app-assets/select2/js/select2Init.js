(function () {
    function parentFor(element) {
        var $parent = jQuery(element).closest(
            '.rf-field, .org-loc-field, .location-cascade-field, .mb-3, .form-group, td, .col-auto, .plan-project-filter'
        );

        return $parent.length ? $parent : jQuery(document.body);
    }

    window.initAppSelect2 = function (element, extra) {
        if (!window.jQuery || !jQuery.fn.select2 || !element) {
            return;
        }

        var $element = jQuery(element);
        if ($element.hasClass('select2-hidden-accessible') || $element.prop('multiple')) {
            return;
        }

        var options = jQuery.extend({
            width: '100%',
            minimumResultsForSearch: 8,
            dropdownParent: parentFor(element),
        }, extra || {});

        $element.select2(options);
    };

    window.initAppSelect2In = function (root, extra) {
        if (!root) {
            return;
        }

        root.querySelectorAll('select.form-control, select.pb-input').forEach(function (select) {
            if (select.multiple || select.classList.contains('org-loc-select')) {
                return;
            }
            window.initAppSelect2(select, extra);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initAppSelect2In(document);
    });
})();
