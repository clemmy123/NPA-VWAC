<!-- Sidebar Start -->
<div class="left-side-menu">

    <!-- Brand (top of sidebar) -->
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
            <img class="brand-logo-sm" src="{{ asset('app-assets/logo.png') }}" alt="{{ config('app.name') }}">
            <span class="brand-text">{{ config('app.name') }}</span>
        </a>
    </div>

    <!-- Navigation -->
    <div class="slimscroll-menu">
        <div id="sidebar-menu">
            <ul class="metismenu" id="side-menu">

                <li class="menu-title">Navigation</li>

                {{-- Dashboard --}}
                <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <i class="bi bi-grid"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                {{-- Plan Builder: live, one-thematic-area-at-a-time plan construction --}}
                @can('thematic-area.view')
                <li class="{{ request()->routeIs('plan-builder.*') ? 'active' : '' }}">
                    <a href="{{ route('plan-builder.index') }}">
                        <i class="bi bi-pencil-square"></i>
                        <span>Plan Builder</span>
                    </a>
                </li>
                @endcan

                {{-- Plan hierarchy: Projects / Thematic Areas / Indicators / Interventions --}}
                @canany(['project.view', 'thematic-area.view', 'indicator.view', 'intervention.view'])
                @php
                    $planActive = request()->routeIs('projects.*')
                        || request()->routeIs('thematic-areas.*')
                        || request()->routeIs('indicators.*')
                        || request()->routeIs('interventions.*');
                @endphp
                <li class="{{ $planActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-diagram-3"></i>
                        <span>Plan Hierarchy</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <ul class="nav-second-level" aria-expanded="{{ $planActive ? 'true' : 'false' }}">
                        @can('project.view')
                        <li class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">
                            <a href="{{ route('projects.index') }}">Projects</a>
                        </li>
                        @endcan

                        @can('thematic-area.view')
                        <li class="{{ request()->routeIs('thematic-areas.*') ? 'active' : '' }}">
                            <a href="{{ route('thematic-areas.index') }}">Thematic Areas</a>
                        </li>
                        @endcan

                        @can('indicator.view')
                        <li class="{{ request()->routeIs('indicators.*') ? 'active' : '' }}">
                            <a href="{{ route('indicators.index') }}">Indicators</a>
                        </li>
                        @endcan

                        @can('intervention.view')
                        <li class="{{ request()->routeIs('interventions.*') ? 'active' : '' }}">
                            <a href="{{ route('interventions.index') }}">Interventions</a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcanany

                {{-- Baselines & targets --}}
                @can('indicator.view')
                @php
                    $targetsActive = request()->routeIs('indicator-baselines.*') || request()->routeIs('indicator-targets.*');
                @endphp
                <li class="{{ $targetsActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-bullseye"></i>
                        <span>Baselines &amp; Targets</span>
                        <span class="menu-arrow"></span>
                    </a>

                    <ul class="nav-second-level" aria-expanded="{{ $targetsActive ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('indicator-baselines.*') ? 'active' : '' }}">
                            <a href="{{ route('indicator-baselines.index') }}">Baselines</a>
                        </li>
                        <li class="{{ request()->routeIs('indicator-targets.*') ? 'active' : '' }}">
                            <a href="{{ route('indicator-targets.index') }}">Targets</a>
                        </li>
                    </ul>
                </li>
                @endcan

                {{-- Data collections --}}
                @can('indicator-data.view')
                <li class="{{ request()->routeIs('indicator-data-entries.*') ? 'active' : '' }}">
                    <a href="{{ route('indicator-data-entries.index') }}">
                        <i class="bi bi-clipboard-data"></i>
                        <span>Data Collections</span>
                    </a>
                </li>
                @endcan

                {{-- Indicator data assignments --}}
                @can('indicator.assign-user')
                <li class="{{ request()->routeIs('indicator-data-assignments.*') ? 'active' : '' }}">
                    <a href="{{ route('indicator-data-assignments.index') }}">
                        <i class="bi bi-person-check"></i>
                        <span>Data Assignments</span>
                    </a>
                </li>
                @endcan

                {{-- Reports --}}
                @can('report.view')
                @php($reportsActive = request()->routeIs('reports.*'))
                <li class="{{ $reportsActive ? 'active' : '' }}">
                    <a href="javascript: void(0);">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Reports</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul class="nav-second-level" aria-expanded="{{ $reportsActive ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('reports.general') ? 'active' : '' }}">
                            <a href="{{ route('reports.general') }}">General Report</a>
                        </li>
                        <li class="{{ request()->routeIs('reports.workstation') ? 'active' : '' }}">
                            <a href="{{ route('reports.workstation') }}">Workstation Reports</a>
                        </li>
                    </ul>
                </li>
                @endcan

                {{-- Users --}}
                @can('user.view')
                <li class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="bi bi-people"></i>
                        <span>Users</span>
                    </a>
                </li>
                @endcan

                {{-- Settings --}}
                @can('settings.manage')
                <li class="{{ request()->routeIs('settings.*', 'organizations.*', 'organization-types.*', 'financial-years.*', 'reporting-periods.*', 'measurement-types.*', 'units-of-measure.*', 'dimensions.*') ? 'active' : '' }}">
                    <a href="{{ route('settings.index') }}">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>
    </div>

</div>
<!-- Sidebar End -->
