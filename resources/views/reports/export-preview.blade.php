@extends('components.main-layout')
@section('title', $title)

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ $title }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item">{{ __('Reports') }}</li>
                <li class="breadcrumb-item active">{{ __('Export Preview') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ $downloadRoute }}" class="btn btn-dark btn-sm">
        <i class="mdi mdi-download-outline"></i>
        {{ __('Download :format', ['format' => strtoupper($format)]) }}
    </a>
</div>

<div class="table-card p-3 mb-3">
    <h5 class="mb-2">{{ __('Executive Summary') }}</h5>
    <ol class="mb-0 ps-3">
        @foreach ($executiveSummary as $sentence)
        <li>{{ $sentence }}</li>
        @endforeach
    </ol>
</div>

<div class="table-card p-3 mb-3">
    <h5 class="mb-2">{{ __('Report Context') }}</h5>
    @foreach ($context as $line)
    <div>{{ $line }}</div>
    @endforeach
</div>

<div class="table-card p-3 mb-3">
    @foreach ($sectionIntro as $paragraph)
    <p class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">{{ $paragraph }}</p>
    @endforeach
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>{{ __('Indicator') }}</th>
                <th>{{ __('Code') }}</th>
                <th>{{ __('Thematic Area') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="text-end">{{ __('Target') }}</th>
                <th class="text-end">{{ __('Actual') }}</th>
                <th class="text-end">{{ __('Achievement %') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableRows as $row)
            <tr>
                @foreach ($row as $cell)
                <td class="{{ $loop->index >= 4 ? 'text-end' : '' }}">{{ $cell ?? '—' }}</td>
                @endforeach
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No report rows available.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
