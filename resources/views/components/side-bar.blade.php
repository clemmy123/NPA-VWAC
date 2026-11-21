<!-- Sidebar Start -->
<div class="left-side-menu">

    <!-- Brand (top of sidebar) -->
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
            <span class="brand-logo-wrap">
                <img class="brand-logo-sm" src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            </span>
            <span class="brand-text">{{ config('app.name') }}</span>
        </a>
        <a href="{{ route('dashboard') }}" class="sidebar-home">{{ __('Home') }}</a>
    </div>

    <!-- Navigation -->
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">

                {{-- Dashboard --}}
                <li class="nav-tone-blue {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="bi bi-grid"></i>
                        <span>{{ __('Dashboard') }}</span>
                    </a>
                </li>

                {{-- Plan Builder: live, one-thematic-area-at-a-time plan construction --}}
                @can('thematic-area.view')
                <li class="nav-tone-purple {{ request()->routeIs('plan-builder.*') ? 'active' : '' }}">
                    <a href="{{ route('plan-builder.index') }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>{{ __('Plan Builder') }}</span>
                    </a>
                </li>
                @endcan

                {{-- Super Admin reaches Projects / Thematic Areas / Indicators / Baselines / Targets
                     from the Settings hub; everyone else (who cannot open Settings) keeps the group here. --}}
                @cannot('settings.manage')
                {{-- Plan hierarchy: Projects / Thematic Areas / Indicators / Interventions.
                     Deliberately gated on 'indicator.view-all' rather than plain
                     'indicator.view'/'intervention.view' — a Data Entry User has
                     those (needed for their own scoped indicator dropdown) but
                     shouldn't see the full plan-management browsing UI. --}}
                @canany(['project.view', 'thematic-area.view', 'indicator.view-all'])
                @php
                    $planActive = request()->routeIs('projects.*')
                        || request()->routeIs('thematic-areas.*')
                        || request()->routeIs('indicators.*')
                        ;
                @endphp
                <li class="nav-tone-teal {{ $planActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-diagram-3"></i>
                        <span>{{ __('Plan Hierarchy') }}</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <ul class="nav-second-level" aria-expanded="{{ $planActive ? 'true' : 'false' }}">
                        @can('project.view')
                        <li class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                            <a href="{{ route('projects.index') }}">{{ __('Projects') }}</a>
                        </li>
                        @endcan

                        @can('thematic-area.view')
                        <li class="{{ request()->routeIs('thematic-areas.*') ? 'active' : '' }}">
                            <a href="{{ route('thematic-areas.index') }}">{{ __('Thematic Areas') }}</a>
                        </li>
                        @endcan

                        @can('indicator.view')
                        <li class="{{ request()->routeIs('indicators.*') ? 'active' : '' }}">
                            <a href="{{ route('indicators.index') }}">{{ __('Indicators') }}</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Baselines & targets: management-only, not a Data Entry User concern --}}
                @can('indicator.view-all')
                @php
                    $targetsActive = request()->routeIs('indicator-baselines.*') || request()->routeIs('indicator-targets.*');
                @endphp
                <li class="nav-tone-amber {{ $targetsActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-bullseye"></i>
                        <span>{{ __('Baselines & Targets') }}</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <ul class="nav-second-level" aria-expanded="{{ $targetsActive ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('indicator-baselines.*') ? 'active' : '' }}">
                            <a href="{{ route('indicator-baselines.index') }}">{{ __('Baselines') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('indicator-targets.*') ? 'active' : '' }}">
                            <a href="{{ route('indicator-targets.index') }}">{{ __('Targets') }}</a>
                        </li>
                    </ul>
                </li>
                @endcan

                @endcannot

                @can('institution.confirm')
                <li class="nav-tone-pink {{ request()->routeIs('institution-inbox.*') ? 'active' : '' }}">
                    <a href="{{ route('institution-inbox.index') }}"><i class="bi bi-building-check"></i><span>{{ __('Institution Ownership Inbox') }}</span></a>
                </li>
                @endcan

                {{-- Data collections --}}
                @can('indicator-data.view')
                <li class="nav-tone-green {{ request()->routeIs('collection-sheet.*', 'indicator-data-entries.*') ? 'active' : '' }}">
                    <a href="{{ route('collection-sheet.index') }}">
                        <i class="bi bi-clipboard-data"></i>
                        <span>{{ __('Data Collections') }}</span>
                    </a>
                </li>
                @endcan

                {{-- Reports --}}
                @can('report.view')
                @php($reportsActive = request()->routeIs('reports.*'))
                <li class="nav-tone-sky {{ $reportsActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>{{ __('Reports') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('reports.general') ? 'active' : '' }}">
                            <a href="{{ route('reports.general') }}">{{ __('General Report') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('reports.workstation') ? 'active' : '' }}">
                            <a href="{{ route('reports.workstation') }}">{{ __('Workstation Reports') }}</a>
                        </li>
                        <li class="{{ request()->routeIs('reports.late-data-entries') ? 'active' : '' }}">
                            <a href="{{ route('reports.late-data-entries') }}">{{ __('Late Data Entries') }}</a>
                        </li>
                    </ul>
                </li>
                @endcan

                {{-- Settings (also the home of Projects / Thematic Areas / Indicators) --}}
                @can('settings.manage')
                <li class="nav-tone-slate {{ request()->routeIs('settings.*', 'organizations.*', 'organization-types.*', 'financial-years.*', 'reporting-periods.*', 'measurement-types.*', 'units-of-measure.*', 'dimensions.*', 'projects.*', 'thematic-areas.*', 'indicators.*', 'indicator-baselines.*', 'indicator-targets.*', 'users.*') ? 'active' : '' }}">
                    <a href="{{ route('settings.index') }}">
                        <i class="bi bi-gear"></i>
                        <span>{{ __('Settings') }}</span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>
    </div>

</div>
<!-- Sidebar End -->
