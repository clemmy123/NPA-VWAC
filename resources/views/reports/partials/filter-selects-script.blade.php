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
                if (data.element && data.element.disabled && data.element.getAttribute('data-project-id')) {
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
    if (!projectSelect || !areaSelect) {
        return;
    }

    function filterAreas() {
        var projectId = projectSelect.value === emptyValue ? '' : projectSelect.value;
        var options = areaSelect.querySelectorAll('option[data-project-id]');
        var firstVisible = null;
        options.forEach(function (option) {
            var match = !projectId || option.getAttribute('data-project-id') === projectId;
            option.hidden = !match;
            option.disabled = !match;
            if (match && !firstVisible) {
                firstVisible = option;
            }
        });
        var selected = areaSelect.options[areaSelect.selectedIndex];
        if (selected && selected.hidden && firstVisible) {
            areaSelect.value = firstVisible.value;
        }
        jQuery(areaSelect).trigger('change.select2');
        markPlaceholder(areaSelect);
    }

    jQuery(projectSelect).on('change', filterAreas);
    filterAreas();
})();
</script>
