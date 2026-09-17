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
        var output = root.querySelector('input[type="hidden"]');
        var target = root.dataset.targetLevel || '';
        var path = pathTo(target);
        var preset = root.dataset.ancestorChain ? JSON.parse(atob(root.dataset.ancestorChain)) : {};
        var generation = 0;

        function removeAfter(index) {
            Array.from(host.querySelectorAll('.location-cascade-field')).forEach(function (field) {
                if (Number(field.dataset.index) > index) destroyField(field);
            });
        }

        function appendLevel(index, parentId, usePreset, buildGeneration) {
            if (index >= path.length || buildGeneration !== generation) return Promise.resolve();
            var level = path[index];
            var field = document.createElement('div');
            field.className = 'location-cascade-field';
            field.dataset.index = index;
            var label = document.createElement('label');
            label.className = 'form-label small text-muted mb-1';
            label.textContent = LABELS[level];
            var select = document.createElement('select');
            select.className = 'form-control';
            select.dataset.level = level;
            select.dataset.index = index;
            select.innerHTML = '<option value="">Loading ' + LABELS[level] + '…</option>';
            field.appendChild(label);
            field.appendChild(select);
            host.appendChild(field);

            return loadOptions(level, parentId, index ? path[index - 1] : null).then(function (options) {
                if (buildGeneration !== generation || !field.isConnected) return;
                var selectedId = usePreset && preset[level] ? String(preset[level].id) : '';
                select.replaceChildren(new Option('Select ' + LABELS[level] + '…', ''));
                options.forEach(function (option) {
                    select.appendChild(new Option(option.name, String(option.id), false, String(option.id) === selectedId));
                });
                if (window.jQuery && jQuery.fn.select2) {
                    jQuery(select).select2({
                        width: '100%', minimumResultsForSearch: 8,
                        placeholder: 'Select ' + LABELS[level] + '…', dropdownParent: jQuery(field)
                    });
                }
                if (index === path.length - 1) output.value = selectedId;
                if (selectedId && index < path.length - 1) return appendLevel(index + 1, selectedId, true, buildGeneration);
            }).catch(function (error) {
                select.replaceChildren(new Option(error.message, ''));
                select.classList.add('is-invalid');
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
            appendLevel(index + 1, select.value, false, generation);
        }

        if (window.jQuery) {
            jQuery(root).on('change.locationCascade', 'select[data-level]', function () { selectionChanged(this); });
        } else {
            root.addEventListener('change', function (event) {
                if (event.target.matches('select[data-level]')) selectionChanged(event.target);
            });
        }

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

    document.querySelectorAll('.location-cascade').forEach(init);
})();
</script>
@endpush
@endonce
