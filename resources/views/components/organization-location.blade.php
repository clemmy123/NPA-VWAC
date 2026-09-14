@php
    $ancestorChain = $ancestorChain ?? [];
    $currentLevel = $currentLevel ?? null;
    $currentId = $currentId ?? null;
@endphp

<div class="org-location"
     data-ancestor-chain="{{ json_encode($ancestorChain) }}"
     data-options-url="{{ url('/admin-locations') }}">
    <div class="row">
        <div class="col-md-6 mb-3 org-loc-field" data-level="region">
            <label for="org_location_region" class="form-label">Region</label>
            <select id="org_location_region" class="form-control org-loc-select" data-level="region" data-placeholder="Select Region…">
                <option value="">Select Region…</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 org-loc-field" data-level="district" hidden>
            <label for="org_location_district" class="form-label">District</label>
            <select id="org_location_district" class="form-control org-loc-select" data-level="district" data-parent="region" data-placeholder="Select District…">
                <option value="">Select District…</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 org-loc-field" data-level="council" hidden>
            <label for="org_location_council" class="form-label">Council</label>
            <select id="org_location_council" class="form-control org-loc-select" data-level="council" data-parent="district" data-placeholder="Select Council…">
                <option value="">Select Council…</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 org-loc-field" data-level="ward" hidden>
            <label for="org_location_ward" class="form-label">Ward</label>
            <select id="org_location_ward" class="form-control org-loc-select" data-level="ward" data-parent="council" data-parent-level="council" data-placeholder="Select Ward…">
                <option value="">Select Ward…</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 org-loc-field" data-level="village_mtaa" hidden>
            <label for="org_location_village" class="form-label">Street / Village</label>
            <select id="org_location_village" class="form-control org-loc-select" data-level="village_mtaa" data-parent="ward" data-placeholder="Select Street / Village…">
                <option value="">Select Street / Village…</option>
            </select>
        </div>
    </div>
    <input type="hidden" name="location_level" id="location_level" value="{{ $currentLevel }}">
    <input type="hidden" name="location_id" id="location_id" value="{{ $currentId }}">
    @error('location_level')<div class="text-danger small">{{ $message }}</div>@enderror
    @error('location_id')<div class="text-danger small">{{ $message }}</div>@enderror
</div>

@once
@push('scripts')
<script>
(function () {
    var LEVEL_ORDER = ['region', 'district', 'council', 'ward', 'village_mtaa'];

    function fetchOptions(baseUrl, level, parentId, parentLevel) {
        var url = baseUrl + '/' + level;
        var params = [];
        if (parentId) {
            params.push('parent_id=' + encodeURIComponent(parentId));
        }
        if (parentLevel) {
            params.push('parent_level=' + encodeURIComponent(parentLevel));
        }
        if (params.length) {
            url += '?' + params.join('&');
        }
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (response) {
            return response.json();
        });
    }

    function fieldWrap(select) {
        return select.closest('.org-loc-field');
    }

    function placeholderOf(select) {
        return select.dataset.placeholder || 'Select…';
    }

    function destroySelect2(select) {
        if (! window.jQuery) {
            return;
        }
        var $select = jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
    }

    function fillSelect(select, options, selectedId) {
        var placeholder = placeholderOf(select);
        var html = '<option value="">' + placeholder + '</option>';
        options.forEach(function (option) {
            var selected = selectedId != null && String(option.id) === String(selectedId);
            html += '<option value="' + option.id + '"' + (selected ? ' selected' : '') + '>' + option.name + '</option>';
        });
        destroySelect2(select);
        select.innerHTML = html;
        if (window.jQuery) {
            jQuery(select).select2({
                width: '100%',
                placeholder: placeholder,
                dropdownParent: jQuery(fieldWrap(select)),
                minimumResultsForSearch: 8
            });
        }
    }

    function hideFrom(selects, index) {
        for (var i = index; i < selects.length; i++) {
            destroySelect2(selects[i]);
            selects[i].innerHTML = '<option value="">' + placeholderOf(selects[i]) + '</option>';
            selects[i].value = '';
            fieldWrap(selects[i]).hidden = true;
        }
    }

    function syncHidden(root, selects) {
        var levelInput = root.querySelector('#location_level');
        var idInput = root.querySelector('#location_id');
        var level = '';
        var id = '';
        selects.forEach(function (select) {
            if (select.value) {
                level = select.dataset.level;
                id = select.value;
            }
        });
        levelInput.value = level;
        idInput.value = id;
    }

    function init(root) {
        var baseUrl = root.dataset.optionsUrl;
        var ancestorChain = JSON.parse(root.dataset.ancestorChain || '{}');
        var selects = LEVEL_ORDER.map(function (level) {
            return root.querySelector('.org-loc-select[data-level="' + level + '"]');
        });

        function loadLevel(index, parentId, usePreset) {
            var select = selects[index];
            if (! select) {
                return Promise.resolve();
            }

            if (index > 0 && ! parentId) {
                hideFrom(selects, index);
                return Promise.resolve();
            }

            fieldWrap(select).hidden = false;

            return fetchOptions(baseUrl, select.dataset.level, parentId, select.dataset.parentLevel || null).then(function (options) {
                var preset = usePreset && ancestorChain[select.dataset.level] ? ancestorChain[select.dataset.level].id : null;
                fillSelect(select, options, preset);
                if (preset && index + 1 < selects.length) {
                    return loadLevel(index + 1, preset, true);
                }
            });
        }

        selects.forEach(function (select, index) {
            var onChange = function () {
                hideFrom(selects, index + 1);
                if (select.value && index + 1 < selects.length) {
                    loadLevel(index + 1, select.value, false).then(function () {
                        syncHidden(root, selects);
                    });
                } else {
                    syncHidden(root, selects);
                }
            };
            if (window.jQuery) {
                jQuery(select).on('change.orgLoc', onChange);
            } else {
                select.addEventListener('change', onChange);
            }
        });

        loadLevel(0, null, true).then(function () {
            syncHidden(root, selects);
        });
    }

    document.querySelectorAll('.org-location').forEach(init);
})();
</script>
@endpush
@endonce
