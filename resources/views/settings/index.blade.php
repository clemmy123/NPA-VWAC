@extends('components.main-layout')
        @section('title', __('Settings'))

@php
    $cards = [
        ['route' => 'users.index', 'can' => 'user.view', 'icon' => 'mdi-account-multiple-outline', 'title' => __('Users'), 'desc' => __('Accounts, roles and who can do what.')],
        ['route' => 'projects.index', 'can' => 'project.view', 'icon' => 'mdi-folder-outline', 'title' => __('Projects'), 'desc' => __('The projects the plan is organised under.')],
        ['route' => 'thematic-areas.index', 'can' => 'thematic-area.view', 'icon' => 'mdi-sitemap', 'title' => __('Thematic Areas'), 'desc' => __('Browse thematic areas and assign their coordinators.')],
        ['route' => 'indicators.index', 'can' => 'indicator.view-all', 'icon' => 'mdi-chart-bar', 'title' => __('Indicators'), 'desc' => __('Every indicator across the plan, with its configuration.')],
        ['route' => 'indicator-baselines.index', 'can' => 'indicator.view-all', 'icon' => 'mdi-chart-line', 'title' => __('Baselines'), 'desc' => __('Baselines recorded for indicators (filled in when a plan is approved).')],
        ['route' => 'indicator-targets.index', 'can' => 'indicator.view-all', 'icon' => 'mdi-bullseye-arrow', 'title' => __('Targets'), 'desc' => __('Targets recorded for indicators (filled in when a plan is approved).')],
        ['route' => 'organizations.index', 'can' => 'settings.manage', 'icon' => 'mdi-bank-outline', 'title' => __('Organizations'), 'desc' => __('Banks and other reporting institutions (e.g. Bank A, Bank B).')],
        ['route' => 'organization-types.index', 'can' => 'settings.manage', 'icon' => 'mdi-shape-outline', 'title' => __('Organization Types'), 'desc' => __('Categories organizations belong to (bank, NGO, council, ...).')],
        ['route' => 'financial-years.index', 'can' => 'settings.manage', 'icon' => 'mdi-calendar-range-outline', 'title' => __('Financial Years'), 'desc' => __('Reporting years, e.g. 2026/27.')],
        ['route' => 'reporting-periods.index', 'can' => 'settings.manage', 'icon' => 'mdi-calendar-week', 'title' => __('Reporting Periods'), 'desc' => __('Quarters/periods within a financial year.')],
        ['route' => 'measurement-types.index', 'can' => 'settings.manage', 'icon' => 'mdi-ruler', 'title' => __('Measurement Types'), 'desc' => __('Count, percentage, ratio, currency, etc.')],
        ['route' => 'units-of-measure.index', 'can' => 'settings.manage', 'icon' => 'mdi-tape-measure', 'title' => __('Units of Measure'), 'desc' => __('People, households, TZS, USD, etc.')],
        ['route' => 'dimensions.index', 'can' => 'settings.manage', 'icon' => 'mdi-format-list-bulleted', 'title' => __('Dimensions'), 'desc' => __('Disaggregation categories (Gender, Age Group, ...) and their options.')],
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Settings') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Settings') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    @foreach ($cards as $card)
    @can($card['can'])
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="{{ route($card['route']) }}" class="chart-card d-block text-decoration-none h-100">
            <div class="d-flex align-items-start" style="gap:12px;">
                <div style="width:40px;height:40px;border-radius:10px;background:var(--accent-bg);color:var(--accent-text);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="mdi {{ $card['icon'] }}" style="font-size:1.2rem;"></i>
                </div>
                <div>
                    <div class="fw-600" style="color:var(--heading-dark);">{{ $card['title'] }}</div>
                    <div class="text-muted" style="font-size:0.8rem;">{{ $card['desc'] }}</div>
                </div>
            </div>
        </a>
    </div>
    @endcan
    @endforeach
</div>
@endsection
