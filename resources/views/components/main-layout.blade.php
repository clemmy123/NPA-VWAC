<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>{{ config('app.name', 'NPA VWAC') }} | @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="NPA VWAC II" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('app-assets/images/logo-sm.png') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="{{ asset('app-assets/libs/bootstrap-tagsinput/bootstrap-tagsinput.css') }}" rel="stylesheet" />
    <link href="{{ asset('app-assets/libs/switchery/switchery.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/libs/multiselect/multi-select.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/libs/bootstrap-select/bootstrap-select.min.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('app-assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('app-assets/libs/custombox/custombox.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/libs/rwd-table/rwd-table.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="{{ asset('app-assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"
        id="bootstrap-stylesheet" />
    <link href="{{ asset('app-assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('app-assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>
    <div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="true"></div>

    {{-- Shared "quick add / quick edit" modal shell. A page defines one or more
         <template data-quick-add-template="..."> blocks holding a normal form
         (same partials as the resource's own create/edit page); a trigger with
         data-quick-add="<template id>" clones that template's content into this
         single modal instead of navigating away. Only one clone is ever live in
         the DOM at a time, so field ids never collide even when a page defines
         several templates (e.g. "Add Indicator" + "Add Intervention"). --}}
    <div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body"></div>
            </div>
        </div>
    </div>

    <div id="wrapper">

        @include('components.top-bar')
        @include('components.side-bar')
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <div class="content-page">
            <div class="content">

                @yield('content')
            </div>
            @include('components.footer')
        </div>

    </div>

    <!-- Vendor JS -->
    <script src="{{ asset('app-assets/js/vendor.min.js') }}"></script>
    <script src="{{ asset('app-assets/bootstrap-5.0.2/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('app-assets/js/app.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/jquery-steps/jquery.steps.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/jquery-validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/switchery/switchery.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/bootstrap-tagsinput/bootstrap-tagsinput.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/multiselect/jquery.multi-select.js') }}"></script>
    <script src="{{ asset('app-assets/libs/jquery-quicksearch/jquery.quicksearch.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/autocomplete/jquery.autocomplete.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/bootstrap-select/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/bootstrap-maxlength/bootstrap-maxlength.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/bootstrap-filestyle2/bootstrap-filestyle.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/custombox/custombox.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/rwd-table/rwd-table.min.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('app-assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>

    <script src="{{ asset('app-assets/js/pages/sweetalerts.init.js') }}"></script>
    <script src="{{ asset('app-assets/select2/js/select2Init.js') }}"></script>

    @php
        $flashToasts = [];

        if (session('success')) {
            $flashToasts[] = ['type' => 'success', 'message' => session('success')];
        }

        if (session('warning')) {
            $flashToasts[] = ['type' => 'warning', 'message' => session('warning')];
        }

        foreach ($errors->all() as $error) {
            $flashToasts[] = ['type' => 'danger', 'message' => $error];
        }
    @endphp
    <script>
        window.showToast = function (type, message, duration) {
            duration = duration || 10000;
            var icons = { success: 'mdi-check-circle', warning: 'mdi-alert', danger: 'mdi-alert-circle' };
            var container = document.getElementById('toast-container');
            if (!container) return;

            var toast = document.createElement('div');
            toast.className = 'toast-item toast-' + type;
            toast.innerHTML =
                '<i class="mdi ' + (icons[type] || 'mdi-information') + ' toast-icon"></i>' +
                '<span class="toast-message"></span>' +
                '<button type="button" class="toast-close" aria-label="Dismiss">&times;</button>';
            toast.querySelector('.toast-message').textContent = message;
            container.appendChild(toast);

            var timer = setTimeout(function () { dismiss(); }, duration);

            function dismiss() {
                clearTimeout(timer);
                toast.classList.add('toast-leaving');
                toast.addEventListener('animationend', function () { toast.remove(); }, { once: true });
            }

            toast.querySelector('.toast-close').addEventListener('click', dismiss);
        };

        @foreach ($flashToasts as $toast)
        window.showToast(@json($toast['type']), @json($toast['message']));
        @endforeach
    </script>

    <script>
        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-quick-add]');
            if (!trigger) return;

            var template = document.getElementById(trigger.getAttribute('data-quick-add'));
            var modalEl = document.getElementById('quickAddModal');
            if (!template || !modalEl) return;

            var body = modalEl.querySelector('.modal-body');
            body.innerHTML = '';
            body.appendChild(template.content.cloneNode(true));
            modalEl.querySelector('.modal-title').textContent = trigger.getAttribute('data-quick-add-title') || 'Add';

            // Select2 multi-selects (e.g. Intervention's Linked Indicators) only
            // exist in the DOM from this point on, since they were inert inside
            // a <template> until just now — init here rather than relying on
            // the form partial's own @push('scripts'), which already fired
            // (and no-opped) at initial page load.
            if (window.jQuery) {
                jQuery(body).find('.select2-multi').select2({
                    placeholder: 'Select indicators…',
                    width: '100%',
                    dropdownParent: jQuery(modalEl),
                });
            }

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    </script>
    @stack('scripts')

    <script>
        $(document).ready(function () {

        // Bootstrap 5 tooltips on truncated cells
        // We wrap content in a <span> so tooltip anchors to the text width, not the full td width
        try {
            // Every plain-text table cell that isn't already opted in with
            // class="td-ellipsis" data-tip="...": auto-detect truncation instead of
            // requiring every view to hand-annotate every column. Cells holding
            // interactive/rich markup (buttons, links, badges, selects) are left
            // alone so we never break their behaviour or double-wrap their content.
            document.querySelectorAll('.table-card .table tbody td').forEach(function(td) {
                if (td.classList.contains('td-ellipsis')) return;
                if (td === td.parentElement.lastElementChild) return; // actions column
                if (td.children.length > 0) return; // interactive/rich content, skip
                if (td.scrollWidth <= td.clientWidth) return; // not actually truncated
                var text = td.textContent.trim();
                if (!text) return;
                td.setAttribute('data-tip', text);
                td.classList.add('td-ellipsis');
            });

            document.querySelectorAll('.td-ellipsis[data-tip]').forEach(function(el) {
                var tip = el.getAttribute('data-tip');
                if (!el.querySelector('.tip-anchor')) {
                    el.innerHTML = '<span class="tip-anchor" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100%;">' + el.innerHTML + '</span>';
                }
                var anchor = el.querySelector('.tip-anchor');
                anchor.setAttribute('title', tip);
                new bootstrap.Tooltip(anchor, {
                    placement: 'top',
                    trigger: 'hover',
                    container: 'body',
                    customClass: 'td-tooltip'
                });
            });
        } catch(e) { console.warn('Tooltip init failed:', e); }

        function isMobile() { return window.innerWidth < 768; }

        // Hamburger toggle
        $('.button-menu-mobile').on('click', function (e) {
            e.stopImmediatePropagation();
            if (isMobile()) {
                $('body').toggleClass('sidebar-open');
            } else {
                $('body').toggleClass('sidebar-collapsed');
            }
        });

        // Close sidebar when clicking backdrop (mobile)
        $('#sidebarBackdrop').on('click', function () {
            $('body').removeClass('sidebar-open');
        });

        // On resize: clean up stale classes
        $(window).on('resize', function () {
            if (!isMobile()) {
                $('body').removeClass('sidebar-open');
            } else {
                $('body').removeClass('sidebar-collapsed');
            }
        });

        // Auto-collapse on mobile on page load
        if (isMobile()) {
            $('body').removeClass('sidebar-collapsed');
        }

        // Tablet hover: temporarily expand icon-only sidebar
        function isTablet() { return window.innerWidth >= 768 && window.innerWidth <= 991; }

        $('.left-side-menu').on('mouseenter', function () {
            if (isTablet() && !$('body').hasClass('sidebar-collapsed')) {
                $(this).addClass('sidebar-hovered');
            }
        }).on('mouseleave', function () {
            $(this).removeClass('sidebar-hovered');
        });
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('#sidebar-menu .nav-second-level li > a').forEach(function (el) {
            if (el.scrollWidth > el.clientWidth) {
                el.title = el.textContent.trim();
            }
        });

        // Ensure MetisMenu picks up the active submenu from aria-expanded="true" set server-side.
        document.querySelectorAll('#sidebar-menu .nav-second-level[aria-expanded="true"]').forEach(function (ul) {
            ul.classList.add('mm-show', 'in');
            var parentLi = ul.closest('li');
            if (parentLi) {
                parentLi.classList.add('mm-active');
            }
        });

        // Init MetisMenu accordion if the plugin is loaded; otherwise use a manual toggle
        // so parent items with href="javascript: void(0);" actually expand/collapse their submenu.
        if (window.jQuery && typeof jQuery.fn.metisMenu === 'function') {
            jQuery('#side-menu').metisMenu();
        } else {
            document.querySelectorAll('#sidebar-menu > ul > li > a[href="javascript: void(0);"], #sidebar-menu .metismenu > li > a[href="javascript: void(0);"]').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    var submenu = link.nextElementSibling;
                    if (!submenu || !submenu.classList.contains('nav-second-level')) return;

                    var isOpen = submenu.style.display === 'block';
                    submenu.style.display = isOpen ? 'none' : 'block';
                    link.closest('li').classList.toggle('mm-active', !isOpen);
                });
            });
        }
    });
    </script>
</body>

</html>
