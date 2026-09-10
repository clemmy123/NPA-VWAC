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
                        <i class="mdi mdi-view-dashboard-outline"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

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
                        <i class="mdi mdi-sitemap-outline"></i>
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
                        <i class="mdi mdi-bullseye-arrow"></i>
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

                {{-- Data entry --}}
                @can('indicator-data.view')
                <li class="{{ request()->routeIs('indicator-data-entries.*') ? 'active' : '' }}">
                    <a href="{{ route('indicator-data-entries.index') }}">
                        <i class="mdi mdi-clipboard-text-outline"></i>
                        <span>Data Entries</span>
                    </a>
                </li>
                @endcan

            </ul>
        </div>
    </div>

</div>
<!-- Sidebar End -->
