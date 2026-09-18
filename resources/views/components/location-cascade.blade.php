@php
    $levelSelectId = $levelSelectId ?? 'location_level';
    $fieldName = $fieldName ?? 'location_id';
    $fieldId = $fieldId ?? 'location_id';
    $currentId = $currentId ?? null;
    $ancestorChain = $ancestorChain ?? [];
@endphp

<div
    class="location-cascade"
    data-level-select="{{ $levelSelectId }}"
    data-ancestor-chain='@json($ancestorChain)'
>
    <div class="location-cascade-selects"></div>

    <input
        type="hidden"
        name="{{ $fieldName }}"
        id="{{ $fieldId }}"
        value="{{ $currentId }}"
    >
</div>

@once
@push('scripts')
<script>
(function () {

    /*
    |--------------------------------------------------------------------------
    | LOCATION HIERARCHY
    |--------------------------------------------------------------------------
    |
    | Region
    |   └── District
    |        └── Council
    |             ├── Division
    |             └── Ward
    |                  └── Village/Mtaa
    |                       └── Kitongoji
    |
    */

    var PARENT_LEVEL = {
        region: null,
        district: 'region',
        council: 'district',
        division: 'council',
        township: 'division',

        // IMPORTANT:
        // Approval hierarchy currently uses Council -> Ward.
        ward: 'council',

        village_mtaa: 'ward',
        kitongoji: 'village_mtaa'
    };

    var LABELS = {
        region: 'Region',
        district: 'District',
        council: 'Council',
        division: 'Division',
        township: 'Township',
        ward: 'Ward',
        village_mtaa: 'Village/Mtaa',
        kitongoji: 'Kitongoji'
    };


    /*
    |--------------------------------------------------------------------------
    | BUILD PATH TO TARGET LEVEL
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | ward =>
    | region -> district -> council -> ward
    |
    */

    function pathToLevel(level) {
        var path = [];
        var current = level;

        while (current) {
            path.unshift(current);
            current = PARENT_LEVEL[current];
        }

        return path;
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD LOCATIONS FROM SERVER
    |--------------------------------------------------------------------------
    */

    function fetchOptions(level, parentId) {

        var params = new URLSearchParams();

        if (parentId) {
            params.set('parent_id', parentId);
        }

        var url = '/admin-locations/' + encodeURIComponent(level);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        console.log(
            '[Location Cascade] Loading:',
            level,
            'Parent:',
            parentId || 'NONE'
        );

        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    'Unable to load ' +
                    (LABELS[level] || level) +
                    '. HTTP ' +
                    response.status
                );
            }

            return response.json();
        })
        .then(function (response) {

            /*
             * Support:
             *
             * [
             *   { id: 1, name: 'Dodoma' }
             * ]
             *
             * OR
             *
             * {
             *   data: [...]
             * }
             *
             * OR
             *
             * {
             *   locations: [...]
             * }
             */

            if (Array.isArray(response)) {
                return response;
            }

            if (response && Array.isArray(response.data)) {
                return response.data;
            }

            if (response && Array.isArray(response.locations)) {
                return response.locations;
            }

            console.warn(
                '[Location Cascade] Unexpected response:',
                response
            );

            return [];
        });
    }


    /*
    |--------------------------------------------------------------------------
    | RENDER OPTIONS
    |--------------------------------------------------------------------------
    */

    function renderOptions(select, options, selectedId) {

        select.innerHTML = '';

        var placeholder = document.createElement('option');

        placeholder.value = '';
        placeholder.textContent =
            'Select ' +
            (LABELS[select.dataset.level] || 'location') +
            '…';

        select.appendChild(placeholder);


        options.forEach(function (item) {

            var option = document.createElement('option');

            option.value = String(item.id);

            option.textContent =
                item.name ||
                item.title ||
                item.label ||
                ('Location #' + item.id);

            if (
                selectedId !== null &&
                selectedId !== undefined &&
                selectedId !== '' &&
                String(item.id) === String(selectedId)
            ) {
                option.selected = true;
            }

            select.appendChild(option);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | DESTROY DYNAMIC FIELD
    |--------------------------------------------------------------------------
    */

    function destroyField(field) {

        if (!field) {
            return;
        }

        var select = field.querySelector('select');

        if (
            select &&
            window.jQuery &&
            jQuery.fn.select2 &&
            jQuery(select).hasClass('select2-hidden-accessible')
        ) {
            jQuery(select).select2('destroy');
        }

        field.remove();
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE SELECT2
    |--------------------------------------------------------------------------
    */

    function initializeSelect2(select, level) {

        if (window.initAppSelect2) {

            window.initAppSelect2(select, {
                placeholder:
                    'Select ' +
                    (LABELS[level] || level) +
                    '…'
            });

            return;
        }

        if (
            window.jQuery &&
            jQuery.fn &&
            jQuery.fn.select2
        ) {

            var $select = jQuery(select);

            if (!$select.hasClass('select2-hidden-accessible')) {

                $select.select2({
                    width: '100%',
                    placeholder:
                        'Select ' +
                        (LABELS[level] || level) +
                        '…'
                });
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | BIND CHANGE EVENT
    |--------------------------------------------------------------------------
    */

    function bindChange(select, handler) {

        if (
            window.jQuery &&
            jQuery.fn
        ) {

            jQuery(select)
                .off('change.locationCascade')
                .on('change.locationCascade', handler);

        } else {

            select.addEventListener('change', handler);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE ONE LOCATION CASCADE
    |--------------------------------------------------------------------------
    */

    function initCascade(root) {

        if (!root) {
            return;
        }

        /*
         * Prevent initialization twice.
         */

        if (root.dataset.cascadeReady === '1') {
            return;
        }


        /*
         * Find form fields.
         */

        var form = root.closest('form');

        var levelSelectId =
            root.dataset.levelSelect || 'location_level';

        var levelSelect = form
            ? form.querySelector('#' + CSS.escape(levelSelectId))
            : document.getElementById(levelSelectId);

        var hiddenInput =
            root.querySelector('input[type="hidden"]');

        var host =
            root.querySelector('.location-cascade-selects');


        if (!levelSelect) {

            console.error(
                '[Location Cascade] Level select not found:',
                levelSelectId
            );

            return;
        }


        if (!hiddenInput) {

            console.error(
                '[Location Cascade] Hidden location input not found.'
            );

            return;
        }


        if (!host) {

            console.error(
                '[Location Cascade] Container not found.'
            );

            return;
        }


        /*
         * Read existing ancestor chain.
         */

        var ancestorChain = {};

        try {

            ancestorChain = JSON.parse(
                root.dataset.ancestorChain || '{}'
            );

        } catch (error) {

            console.error(
                '[Location Cascade] Invalid ancestor chain:',
                error
            );

            ancestorChain = {};
        }


        var selects = [];

        root.dataset.cascadeReady = '1';


        /*
        |--------------------------------------------------------------------------
        | CLEAR CHILD SELECTS
        |--------------------------------------------------------------------------
        */

        function clearSelectsFrom(index) {

            for (
                var i = selects.length - 1;
                i >= index;
                i--
            ) {

                if (!selects[i]) {
                    continue;
                }

                var field =
                    selects[i].closest(
                        '.location-cascade-field'
                    );

                if (field) {
                    destroyField(field);
                }
            }

            selects.length = index;
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE SELECT
        |--------------------------------------------------------------------------
        */

        function buildLevel(level, index, path) {

            var wrapper =
                document.createElement('div');

            wrapper.className =
                'mb-3 location-cascade-field';


            var label =
                document.createElement('label');

            label.className =
                'form-label small text-muted mb-1';

            label.textContent =
                LABELS[level] || level;


            var select =
                document.createElement('select');

            select.className =
                'form-control form-control-sm';

            select.dataset.level = level;
            select.dataset.index = String(index);


            var loading =
                document.createElement('option');

            loading.value = '';
            loading.textContent =
                'Loading ' +
                (LABELS[level] || level) +
                '…';

            select.appendChild(loading);

            select.disabled = true;


            wrapper.appendChild(label);
            wrapper.appendChild(select);

            host.appendChild(wrapper);

            selects[index] = select;


            return select;
        }


        /*
        |--------------------------------------------------------------------------
        | SELECT CHANGE
        |--------------------------------------------------------------------------
        */

        function onSelectChange(
            select,
            index,
            path
        ) {

            return function () {

                /*
                 * Remove all children.
                 */

                clearSelectsFrom(index + 1);


                /*
                 * Clear final location.
                 */

                hiddenInput.value = '';


                /*
                 * Nothing selected.
                 */

                if (!select.value) {
                    return;
                }


                /*
                 * Final level reached.
                 */

                if (index === path.length - 1) {

                    hiddenInput.value =
                        select.value;

                    console.log(
                        '[Location Cascade] Final location:',
                        select.dataset.level,
                        select.value
                    );

                    return;
                }


                /*
                 * Load next level.
                 */

                buildAndPopulate(
                    index + 1,
                    path,
                    select.value,
                    false
                );
            };
        }


        /*
        |--------------------------------------------------------------------------
        | LOAD + POPULATE LEVEL
        |--------------------------------------------------------------------------
        */

        function buildAndPopulate(
            index,
            path,
            parentId,
            usePreset
        ) {

            if (index >= path.length) {
                return Promise.resolve();
            }


            var level = path[index];

            var select =
                buildLevel(
                    level,
                    index,
                    path
                );


            var preset =
                usePreset &&
                ancestorChain &&
                ancestorChain[level]
                    ? ancestorChain[level]
                    : null;


            return fetchOptions(
                level,
                parentId
            )
            .then(function (options) {

                renderOptions(
                    select,
                    options,
                    preset
                        ? preset.id
                        : null
                );


                select.disabled = false;


                /*
                 * Initialize Select2 AFTER
                 * options have been added.
                 */

                initializeSelect2(
                    select,
                    level
                );


                /*
                 * Bind after Select2 initialization.
                 */

                bindChange(
                    select,
                    onSelectChange(
                        select,
                        index,
                        path
                    )
                );


                /*
                 * Restore preset.
                 */

                if (preset) {

                    select.value =
                        String(preset.id);

                    if (
                        window.jQuery &&
                        jQuery.fn &&
                        jQuery.fn.select2
                    ) {

                        jQuery(select)
                            .trigger(
                                'change.select2'
                            );
                    }
                }


                /*
                 * Final level.
                 */

                if (
                    index ===
                    path.length - 1
                ) {

                    hiddenInput.value =
                        preset
                            ? preset.id
                            : (
                                select.value ||
                                ''
                            );

                    return;
                }


                /*
                 * Editing existing record:
                 * automatically continue
                 * through ancestor chain.
                 */

                if (preset) {

                    return buildAndPopulate(
                        index + 1,
                        path,
                        preset.id,
                        true
                    );
                }
            })
            .catch(function (error) {

                console.error(
                    '[Location Cascade]',
                    error
                );


                select.disabled = false;

                select.innerHTML = '';


                var option =
                    document.createElement(
                        'option'
                    );

                option.value = '';

                option.textContent =
                    'Unable to load ' +
                    (LABELS[level] || level);

                select.appendChild(option);
            });
        }


        /*
        |--------------------------------------------------------------------------
        | REBUILD CASCADE
        |--------------------------------------------------------------------------
        */

        function rebuild(usePreset) {

            /*
             * Properly destroy existing Select2s.
             */

            Array.from(
                host.querySelectorAll(
                    '.location-cascade-field'
                )
            ).forEach(function (field) {

                destroyField(field);
            });


            selects = [];

            hiddenInput.value = '';


            var targetLevel =
                levelSelect.value;


            console.log(
                '[Location Cascade] Target level:',
                targetLevel
            );


            if (!targetLevel) {
                return;
            }


            var path =
                pathToLevel(targetLevel);


            if (!path.length) {

                console.error(
                    '[Location Cascade] Invalid target level:',
                    targetLevel
                );

                return;
            }


            console.log(
                '[Location Cascade] Path:',
                path
            );


            buildAndPopulate(
                0,
                path,
                null,
                usePreset
            );
        }


        /*
        |--------------------------------------------------------------------------
        | APPROVAL LEVEL CHANGE
        |--------------------------------------------------------------------------
        */

        bindChange(
            levelSelect,
            function () {

                /*
                 * Once user manually changes
                 * approval level, don't restore
                 * old location.
                 */

                ancestorChain = {};

                rebuild(false);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | FORM SUBMISSION
        |--------------------------------------------------------------------------
        */

        if (
            form &&
            form.dataset.locationCascadeSubmit !== '1'
        ) {

            form.dataset.locationCascadeSubmit = '1';

            form.addEventListener(
                'submit',
                function () {

                    var last =
                        selects.length
                            ? selects[
                                selects.length - 1
                              ]
                            : null;


                    if (
                        levelSelect.value &&
                        last &&
                        last.value
                    ) {

                        hiddenInput.value =
                            last.value;
                    }
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | EXTERNAL SET EVENT
        |--------------------------------------------------------------------------
        |
        | Allows other forms to tell the cascade
        | to load a saved location.
        |
        */

        root.addEventListener(
            'location-cascade:set',
            function (event) {

                var detail =
                    event.detail || {};


                ancestorChain =
                    detail.ancestorChain || {};


                /*
                 * If targetLevel is provided,
                 * update the actual level select.
                 */

                if (detail.targetLevel !== undefined) {

                    levelSelect.value =
                        detail.targetLevel || '';

                    if (
                        window.jQuery &&
                        jQuery.fn &&
                        jQuery.fn.select2
                    ) {

                        jQuery(levelSelect)
                            .trigger(
                                'change.select2'
                            );
                    }
                }


                rebuild(true);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | INITIAL LOAD
        |--------------------------------------------------------------------------
        */

        if (levelSelect.value) {

            rebuild(true);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GLOBAL INITIALIZER
    |--------------------------------------------------------------------------
    */

    window.initLocationCascades =
        function (container) {

            var scope =
                container || document;


            /*
             * Container itself may be
             * a location cascade.
             */

            if (
                scope.classList &&
                scope.classList.contains(
                    'location-cascade'
                )
            ) {

                initCascade(scope);
            }


            /*
             * Or contain cascades.
             */

            if (scope.querySelectorAll) {

                scope
                    .querySelectorAll(
                        '.location-cascade'
                    )
                    .forEach(
                        initCascade
                    );
            }
        };


    /*
    |--------------------------------------------------------------------------
    | INITIAL PAGE LOAD
    |--------------------------------------------------------------------------
    */

    function initializePage() {

        window.initLocationCascades(
            document
        );
    }


    if (
        document.readyState ===
        'loading'
    ) {

        document.addEventListener(
            'DOMContentLoaded',
            initializePage
        );

    } else {

        initializePage();
    }

})();
</script>
@endpush
@endonce