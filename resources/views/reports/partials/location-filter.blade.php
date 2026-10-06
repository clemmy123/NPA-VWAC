@php
    $locationFields = [
        'region' => ['label' => __('Region'), 'all' => __('All Regions')],
        'district' => ['label' => __('District'), 'all' => __('All Districts')],
        'council' => ['label' => __('Council'), 'all' => __('All Councils')],
        'ward' => ['label' => __('Ward'), 'all' => __('All Wards')],
        'village_mtaa' => ['label' => __('Street / Village'), 'all' => __('All Streets / Villages')],
    ];
@endphp

<div class="report-location-filter"
     data-options-url="{{ url('/admin-locations') }}"
     data-ancestor-chain="{{ json_encode($locationChain ?? []) }}">
    @foreach ($locationFields as $level => $field)
    <div class="rf-field" data-location-field="{{ $level }}" @if ($level !== 'region') hidden @endif>
        <label for="location_filter_{{ $level }}">{{ $field['label'] }}</label>
        <select id="location_filter_{{ $level }}" class="form-control" data-location-select="{{ $level }}" data-placeholder="{{ $field['all'] }}">
            <option value="">{{ $field['all'] }}</option>
        </select>
    </div>
    @endforeach
    <input type="hidden" name="location_level" value="{{ $locationLevel }}" data-location-level-input>
    <input type="hidden" name="location_id" value="{{ $locationId }}" data-location-id-input>
</div>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('.report-location-filter');
    if (!root) {
        return;
    }

    var LEVELS = ['region', 'district', 'council', 'ward', 'village_mtaa'];
    var baseUrl = root.dataset.optionsUrl;
    var chain = JSON.parse(root.dataset.ancestorChain || '{}');
    var levelInput = root.querySelector('[data-location-level-input]');
    var idInput = root.querySelector('[data-location-id-input]');
    var selects = LEVELS.map(function (level) {
        return root.querySelector('[data-location-select="' + level + '"]');
    });

    function fieldOf(select) {
        return select.closest('[data-location-field]');
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value;
        return div.innerHTML;
    }

    function enhance(select) {
        if (!window.jQuery) {
            return;
        }
        var $select = jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }
        $select.select2({
            width: '100%',
            dropdownParent: jQuery(fieldOf(select)),
            minimumResultsForSearch: 8
        });
        $select.next('.select2-container').toggleClass('is-placeholder', !select.value);
    }

    function fill(select, options, selectedId) {
        var html = '<option value="">' + escapeHtml(select.dataset.placeholder) + '</option>';
        options.forEach(function (option) {
            var selected = selectedId != null && String(option.id) === String(selectedId) ? ' selected' : '';
            html += '<option value="' + option.id + '"' + selected + '>' + escapeHtml(option.name) + '</option>';
        });
        select.innerHTML = html;
        enhance(select);
    }

    function resetFrom(index) {
        for (var i = index; i < selects.length; i++) {
            fill(selects[i], [], null);
            fieldOf(selects[i]).hidden = true;
        }
    }

    function sync() {
        var level = '';
        var id = '';
        selects.forEach(function (select) {
            if (select.value) {
                level = select.dataset.locationSelect;
                id = select.value;
            }
        });
        levelInput.value = level;
        idInput.value = id;
    }

    function load(index, parentId, usePreset) {
        var select = selects[index];
        if (!select) {
            return Promise.resolve();
        }

        var params = [];
        if (parentId) {
            params.push('parent_id=' + encodeURIComponent(parentId));
        }
        if (select.dataset.locationSelect === 'ward') {
            params.push('parent_level=council');
        }

        fieldOf(select).hidden = false;

        return fetch(baseUrl + '/' + select.dataset.locationSelect + (params.length ? '?' + params.join('&') : ''), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            return response.ok ? response.json() : [];
        }).then(function (options) {
            var preset = usePreset && chain[select.dataset.locationSelect] ? chain[select.dataset.locationSelect].id : null;
            fill(select, options, preset);
            if (preset && index + 1 < selects.length) {
                return load(index + 1, preset, true);
            }
        });
    }

    selects.forEach(function (select, index) {
        var onChange = function () {
            resetFrom(index + 1);
            jQuery(select).next('.select2-container').toggleClass('is-placeholder', !select.value);
            if (select.value && index + 1 < selects.length) {
                load(index + 1, select.value, false).then(sync);
            } else {
                sync();
            }
        };
        if (window.jQuery) {
            jQuery(select).on('change.locationFilter', onChange);
        } else {
            select.addEventListener('change', onChange);
        }
    });

    enhance(selects[0]);
    load(0, null, true).then(sync);
})();
</script>
@endpush
