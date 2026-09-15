<script>
(function () {
    var form = document.getElementById(@json($formId));
    if (!form || !window.jQuery) {
        return;
    }

    var emptyValue = '__none__';

    function enhance(select) {
        var $select = jQuery(select);

        Array.from(select.options).forEach(function (option) {
            if (option.value === '') {
                option.value = emptyValue;
            }
        });

        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            width: '100%',
            dropdownParent: $select.closest('.rf-field'),
            minimumResultsForSearch: 8,
            matcher: function (params, data) {
                if (data.element && data.element.disabled && (data.element.getAttribute('data-project-id') || data.element.getAttribute('data-thematic-area-id'))) {
                    return null;
                }

                if (jQuery.trim(params.term) === '') {
                    return data;
                }

                return data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1 ? data : null;
            },
        });

        $select.on('change', function () {
            markPlaceholder(select);
        });
        markPlaceholder(select);
    }

    function markPlaceholder(select) {
        jQuery(select).next('.select2-container').toggleClass(
            'is-placeholder',
            select.value === emptyValue || select.value === ''
        );
    }

    form.querySelectorAll('select.form-control').forEach(enhance);

    form.addEventListener('submit', function () {
        form.querySelectorAll('select.form-control').forEach(function (select) {
            if (select.value === emptyValue) {
                select.disabled = true;
            }
        });
    });

    var projectSelect = document.getElementById('project_id');
    var areaSelect = document.getElementById('thematic_area_id');
    var indicatorSelect = document.getElementById('indicator_id');
    if (!projectSelect || !areaSelect) {
        return;
    }

    function filterByAttribute(select, attribute, parentId) {
        var options = select.querySelectorAll('option[' + attribute + ']');
        var firstVisible = null;
        options.forEach(function (option) {
            var match = !parentId || option.getAttribute(attribute) === parentId;
            option.hidden = !match;
            option.disabled = !match;
            if (match && !firstVisible) {
                firstVisible = option;
            }
        });
        var selected = select.options[select.selectedIndex];
        if (selected && selected.hidden && firstVisible) {
            select.value = firstVisible.value;
        }
        jQuery(select).trigger('change.select2');
        markPlaceholder(select);
    }

    function filterAreas() {
        var projectId = projectSelect.value === emptyValue ? '' : projectSelect.value;
        filterByAttribute(areaSelect, 'data-project-id', projectId);
        if (indicatorSelect) {
            var areaId = areaSelect.value === emptyValue ? '' : areaSelect.value;
            filterByAttribute(indicatorSelect, 'data-thematic-area-id', areaId);
        }
    }

    jQuery(projectSelect).on('change', filterAreas);
    if (indicatorSelect) {
        jQuery(areaSelect).on('change', function () {
            var areaId = areaSelect.value === emptyValue ? '' : areaSelect.value;
            filterByAttribute(indicatorSelect, 'data-thematic-area-id', areaId);
        });
    }
    filterAreas();

    if (@json(! empty($autoSubmit))) {
        jQuery(form).on('select2:select', 'select.form-control', function () {
            form.submit();
        });
    }
})();
</script>
