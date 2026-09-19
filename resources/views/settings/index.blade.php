@extends('components.main-layout')
        @section('title', __('Settings'))

@php
    $cards = [
        ['route' => 'organizations.index', 'icon' => 'mdi-bank-outline', 'title' => __('Organizations'), 'desc' => __('Banks and other reporting institutions (e.g. Bank A, Bank B).')],
        ['route' => 'organization-types.index', 'icon' => 'mdi-shape-outline', 'title' => __('Organization Types'), 'desc' => __('Categories organizations belong to (bank, NGO, council, ...).')],
        ['route' => 'financial-years.index', 'icon' => 'mdi-calendar-range-outline', 'title' => __('Financial Years'), 'desc' => __('Reporting years, e.g. 2026/27.')],
        ['route' => 'reporting-periods.index', 'icon' => 'mdi-calendar-week-outline', 'title' => __('Reporting Periods'), 'desc' => __('Quarters/periods within a financial year.')],
        ['route' => 'measurement-types.index', 'icon' => 'mdi-ruler', 'title' => __('Measurement Types'), 'desc' => __('Count, percentage, ratio, currency, etc.')],
        ['route' => 'units-of-measure.index', 'icon' => 'mdi-tape-measure', 'title' => __('Units of Measure'), 'desc' => __('People, households, TZS, USD, etc.')],
        ['route' => 'dimensions.index', 'icon' => 'mdi-format-list-bulleted', 'title' => __('Dimensions'), 'desc' => __('Disaggregation categories (Gender, Age Group, ...) and their options.')],
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
    @endforeach
</div>
@endsection
