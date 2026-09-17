@php
    $levelSelectId = $levelSelectId ?? 'location_level';
    $fieldName = $fieldName ?? 'location_id';
    $fieldId = $fieldId ?? 'location_id';
    $currentId = $currentId ?? null;
    $ancestorChain = $ancestorChain ?? [];
@endphp

<div class="location-cascade"
     data-level-select="{{ $levelSelectId }}"
     data-ancestor-chain="{{ json_encode($ancestorChain) }}">
    <div class="location-cascade-selects"></div>
    <input type="hidden" name="{{ $fieldName }}" id="{{ $fieldId }}" value="{{ $currentId }}">
</div>

@once
@push('scripts')
<script>
(function () {
    var PARENT_LEVEL = {
        region: null, district: 'region', council: 'district', division: 'council',
        township: 'division', ward: 'division', village_mtaa: 'ward', kitongoji: 'village_mtaa',
    };
    var LABELS = {
        region: 'Region', district: 'District', council: 'Council', division: 'Division',
        township: 'Township', ward: 'Ward', village_mtaa: 'Village/Mtaa', kitongoji: 'Kitongoji',
    };

    function pathToLevel(level) {
        var path = [];
        var current = level;
        while (current) {
            path.unshift(current);
            current = PARENT_LEVEL[current];
        }
        return path;
    }

    function fetchOptions(level, parentId) {
        var url = '/admin-locations/' + level + (parentId ? ('?parent_id=' + parentId) : '');
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); });
    }

    function renderOptions(select, options, selectedId) {
        var html = '<option value="">Select ' + LABELS[select.dataset.level] + '…</option>';
        options.forEach(function (opt) {
            var isSelected = selectedId != null && String(opt.id) === String(selectedId);
            html += '<option value="' + opt.id + '"' + (isSelected ? ' selected' : '') + '>' + opt.name + '</option>';
        });
        select.innerHTML = html;
    }

    function initCascade(root) {
        if (root.dataset.cascadeReady === '1') {
            return;
        }

        var levelSelect = root.closest('form')
            ? root.closest('form').querySelector('#' + root.dataset.levelSelect)
            : document.getElementById(root.dataset.levelSelect);
        var hiddenInput = root.querySelector('input[type="hidden"]');
        var host = root.querySelector('.location-cascade-selects');
        var ancestorChain = JSON.parse(root.dataset.ancestorChain || '{}');
        var selects = [];

        if (!levelSelect || !hiddenInput || !host) {
            return;
        }

        root.dataset.cascadeReady = '1';

        function bindChange(element, handler) {
            if (window.jQuery) {
                jQuery(element).off('change.locationCascade').on('change.locationCascade', handler);
            } else {
                element.addEventListener('change', handler);
            }
        }

        function syncHiddenFromLastSelect() {
            var last = selects.length ? selects[selects.length - 1] : null;
            hiddenInput.value = last && last.value ? last.value : '';
            if (! levelSelect.value) {
                hiddenInput.value = '';
            }
        }

        function clearSelectsFrom(index) {
            for (var i = selects.length - 1; i >= index; i--) {
                if (selects[i]) {
                    selects[i].closest('.location-cascade-field').remove();
                }
            }
            selects.length = index;
        }

        function onSelectChange(select, index, path) {
            return function () {
                clearSelectsFrom(index + 1);

                if (index === path.length - 1) {
                    hiddenInput.value = select.value;
                    return;
                }

                hiddenInput.value = '';

                if (select.value) {
                    buildAndPopulate(index + 1, path, select.value, false);
                }
            };
        }

        function buildLevel(level, index, path) {
            var wrapper = document.createElement('div');
            wrapper.className = 'mb-2 location-cascade-field';
            var label = document.createElement('label');
            label.className = 'form-label small text-muted mb-1';
            label.textContent = LABELS[level];
            var select = document.createElement('select');
            select.className = 'form-control form-control-sm select2';
            select.dataset.level = level;
            wrapper.appendChild(label);
            wrapper.appendChild(select);
            host.appendChild(wrapper);
            selects[index] = select;
            bindChange(select, onSelectChange(select, index, path));

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

                bindChange(select, onSelectChange(select, index, path));

                if (index === path.length - 1) {
                    hiddenInput.value = preset ? preset.id : (select.value || '');
                }

                if (preset && index + 1 < path.length) {
                    return buildAndPopulate(index + 1, path, preset.id, true);
                }
            });
        }

        function rebuild(usePreset) {
            host.innerHTML = '';
            selects = [];
            hiddenInput.value = '';

            var targetLevel = levelSelect.value;
            if (! targetLevel) {
                return;
            }

            buildAndPopulate(0, pathToLevel(targetLevel), null, usePreset);
        }

        bindChange(levelSelect, function () { rebuild(false); });

        var form = root.closest('form');
        if (form && form.dataset.locationCascadeSubmit !== '1') {
            form.dataset.locationCascadeSubmit = '1';
            form.addEventListener('submit', syncHiddenFromLastSelect);
        }

        root.addEventListener('location-cascade:set', function (event) {
            ancestorChain = event.detail && event.detail.ancestorChain ? event.detail.ancestorChain : {};
            rebuild(true);
        });

        if (levelSelect.value) {
            rebuild(true);
        }
    }

    window.initLocationCascades = function (root) {
        (root || document).querySelectorAll('.location-cascade').forEach(initCascade);
    };

    window.initLocationCascades(document);
})();
</script>
@endpush
@endonce
