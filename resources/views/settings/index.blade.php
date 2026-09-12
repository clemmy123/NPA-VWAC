@extends('components.main-layout')
@section('title', 'Settings')

@php
    $cards = [
        ['route' => 'organizations.index', 'icon' => 'mdi-bank-outline', 'title' => 'Organizations', 'desc' => 'Banks and other reporting institutions (e.g. Bank A, Bank B).'],
        ['route' => 'organization-types.index', 'icon' => 'mdi-shape-outline', 'title' => 'Organization Types', 'desc' => 'Categories organizations belong to (bank, NGO, council, ...).'],
        ['route' => 'financial-years.index', 'icon' => 'mdi-calendar-range-outline', 'title' => 'Financial Years', 'desc' => 'Reporting years, e.g. 2026/27.'],
        ['route' => 'reporting-periods.index', 'icon' => 'mdi-calendar-week-outline', 'title' => 'Reporting Periods', 'desc' => 'Quarters/periods within a financial year.'],
        ['route' => 'measurement-types.index', 'icon' => 'mdi-ruler', 'title' => 'Measurement Types', 'desc' => 'Count, percentage, ratio, currency, etc.'],
        ['route' => 'units-of-measure.index', 'icon' => 'mdi-tape-measure', 'title' => 'Units of Measure', 'desc' => 'People, households, TZS, USD, etc.'],
        ['route' => 'dimensions.index', 'icon' => 'mdi-format-list-bulleted', 'title' => 'Dimensions', 'desc' => 'Disaggregation categories (Gender, Age Group, ...) and their options.'],
    ];
@endphp

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Settings</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Settings</li>
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
