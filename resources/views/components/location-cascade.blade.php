@php
    $fieldName = $fieldName ?? 'location_id';
    $fieldId = $fieldId ?? 'location_id';
    $currentId = $currentId ?? null;
    $ancestorChain = $ancestorChain ?? [];
    $targetLevel = $targetLevel ?? null;
@endphp

<div class="location-cascade" data-target-level="{{ $targetLevel }}" data-ancestor-chain="{{ base64_encode(json_encode($ancestorChain)) }}">
    <div class="location-cascade-selects"></div>
    <input type="hidden" name="{{ $fieldName }}" id="{{ $fieldId }}" value="{{ $currentId }}">
</div>

@once
@push('scripts')
<script>
(function () {
    var PARENT_LEVEL = {
        region: null, district: 'region', council: 'district', division: 'council',
        township: 'division', ward: 'council', village_mtaa: 'ward', kitongoji: 'village_mtaa'
    };
    var LABELS = {
        region: 'Region', district: 'District', council: 'Council', division: 'Division',
        township: 'Township', ward: 'Ward', village_mtaa: 'Village/Mtaa', kitongoji: 'Kitongoji'
    };

    function pathTo(level) {
        var path = [];
        while (level) {
            path.unshift(level);
            level = PARENT_LEVEL[level];
        }
        return path;
    }

    function loadOptions(level, parentId, parentLevel) {
        var params = new URLSearchParams();
        if (parentId) params.set('parent_id', parentId);
        if (parentLevel) params.set('parent_level', parentLevel);
        return fetch('/admin-locations/' + level + (params.toString() ? '?' + params.toString() : ''), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (response) {
            if (!response.ok) throw new Error('Unable to load ' + LABELS[level]);
            return response.json();
        });
    }

    function destroyField(field) {
        var select = field.querySelector('select');
        if (select && window.jQuery && jQuery.fn.select2 && jQuery(select).hasClass('select2-hidden-accessible')) {
            jQuery(select).select2('destroy');
        }
        field.remove();
    }

    function init(root) {
        var host = root.querySelector('.location-cascade-selects');
        var ancestorChain = JSON.parse(root.dataset.ancestorChain || '{}');
        var selects = [];

        function clearSelectsFrom(index) {
            for (var i = selects.length - 1; i >= index; i--) {
                if (selects[i]) {
                    selects[i].closest('.location-cascade-field').remove();
                }
            }
            selects.length = index;
        }

        function buildLevel(level, index, path) {
            var wrapper = document.createElement('div');
            wrapper.className = 'mb-2 location-cascade-field';
            var label = document.createElement('label');
            label.className = 'form-label small text-muted mb-1';
            label.textContent = LABELS[level];
            var select = document.createElement('select');
            select.className = 'form-control';
            select.dataset.level = level;
            wrapper.appendChild(label);
            wrapper.appendChild(select);
            host.appendChild(wrapper);
            selects[index] = select;

            select.addEventListener('change', function () {
                clearSelectsFrom(index + 1);

                if (index === path.length - 1) {
                    hiddenInput.value = select.value;
                    return;
                }

                hiddenInput.value = '';

                if (select.value) {
                    buildAndPopulate(index + 1, path, select.value, false);
                }
            });

            return select;
        }

        function buildAndPopulate(index, path, parentId, usePreset) {
            var level = path[index];
            var select = buildLevel(level, index, path);
            var preset = usePreset ? ancestorChain[level] : null;

            return fetchOptions(level, parentId).then(function (options) {
                renderOptions(select, options, preset ? preset.id : null);

                // Some levels (ward, village/mtaa) can run into the thousands,
                // so every cascade level gets a searchable select2 rather than
                // a long native dropdown — init here since these selects are
                // built well after the page's own DOMContentLoaded already fired.
                if (window.initAppSelect2) {
                    window.initAppSelect2(select, { placeholder: 'Select ' + LABELS[level] + '…' });
                } else if (window.jQuery) {
                    jQuery(select).select2({ width: '100%', placeholder: 'Select ' + LABELS[level] + '…' });
                }

                if (index === path.length - 1) {
                    hiddenInput.value = preset ? preset.id : (select.value || '');
                }

                if (preset && index + 1 < path.length) {
                    return buildAndPopulate(index + 1, path, preset.id, true);
                }
            });
        }

        function selectionChanged(select) {
            var index = Number(select.dataset.index);
            removeAfter(index);
            output.value = '';
            if (!select.value) return;
            if (index === path.length - 1) {
                output.value = select.value;
                return;
            }

            buildAndPopulate(0, pathToLevel(targetLevel), null, usePreset);
        }

        levelSelect.addEventListener('change', function () { rebuild(false); });
        root.addEventListener('location-cascade:set', function (event) {
            generation++;
            target = event.detail && event.detail.targetLevel ? event.detail.targetLevel : target;
            path = pathTo(target);
            preset = event.detail && event.detail.ancestorChain ? event.detail.ancestorChain : {};
            root.dataset.targetLevel = target;
            Array.from(host.querySelectorAll('.location-cascade-field')).forEach(destroyField);
            output.value = '';
            if (target) appendLevel(0, null, true, generation);
        });

        if (target) appendLevel(0, null, true, generation);
    }

    document.querySelectorAll('.location-cascade').forEach(initCascade);
})();
</script>
@endpush
@endonce
