@extends('components.main-layout')
@section('title', __('Units of Measure'))

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">{{ __('Units of Measure') }}</h4>
        <nav aria-label="{{ __('breadcrumb') }}">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('settings.index') }}">{{ __('Settings') }}</a></li>
                <li class="breadcrumb-item active">{{ __('Units of Measure') }}</li>
            </ol>
        </nav>
    </div>
    <a href="{{ route('units-of-measure.create') }}" class="btn btn-dark btn-sm"><i class="mdi mdi-plus"></i> {{ __('New Unit') }}</a>
</div>

<div class="table-card d-none d-md-block">
    <table class="table mb-0">
        <thead>
            <tr><th>#</th><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Symbol') }}</th><th>{{ __('Status') }}</th><th>{{ __('Actions') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($unitsOfMeasure as $unitOfMeasure)
            <tr>
                <td>{{ ($unitsOfMeasure->firstItem() ?? 1) + $loop->index }}</td>
                <td><span class="ref-pill">{{ $unitOfMeasure->code }}</span></td>
                <td>{{ $unitOfMeasure->name }}</td>
                <td>{{ $unitOfMeasure->symbol ?? '—' }}</td>
                <td><span class="s-badge {{ $unitOfMeasure->is_active ? 's-active' : 's-inactive' }}">{{ $unitOfMeasure->is_active ? __('Active') : __('Inactive') }}</span></td>
                <td>
                    <a href="{{ route('units-of-measure.edit', $unitOfMeasure) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
                    <form action="{{ route('units-of-measure.destroy', $unitOfMeasure) }}" method="POST" class="d-inline"
                          data-confirm="{{ __('Delete this unit of measure?') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6">
                    <div class="tbl-empty">
                        <i class="mdi mdi-tape-measure"></i>
                        <p>{{ __('No units of measure found.') }}</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if ($unitsOfMeasure->hasPages())
    <div class="px-3 py-2 border-top">{{ $unitsOfMeasure->links() }}</div>
    @endif
</div>

<div class="d-md-none">
    @forelse ($unitsOfMeasure as $unitOfMeasure)
    <div class="mob-card">
        <div class="mob-card-top">
            <span class="fw-600">{{ $unitOfMeasure->name }}</span>
            <span class="s-badge {{ $unitOfMeasure->is_active ? 's-active' : 's-inactive' }}">{{ $unitOfMeasure->is_active ? __('Active') : __('Inactive') }}</span>
        </div>
        <div class="mob-card-meta">
            <span><i class="mdi mdi-pound"></i> {{ $unitOfMeasure->code }}</span>
            @if ($unitOfMeasure->symbol)<span>{{ $unitOfMeasure->symbol }}</span>@endif
        </div>
        <div class="mob-card-footer">
            <a href="{{ route('units-of-measure.edit', $unitOfMeasure) }}" class="btn-icon" title="{{ __('Edit') }}"><i class="mdi mdi-pencil-outline"></i></a>
            <form action="{{ route('units-of-measure.destroy', $unitOfMeasure) }}" method="POST" class="d-inline"
                  data-confirm="{{ __('Delete this unit of measure?') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-icon danger" title="{{ __('Delete') }}"><i class="mdi mdi-trash-can-outline"></i></button>
            </form>
        </div>
    </div>
    @empty
    <div class="tbl-empty">
        <i class="mdi mdi-tape-measure"></i>
        <p>{{ __('No units of measure found.') }}</p>
    </div>
    @endforelse
    @if ($unitsOfMeasure->hasPages())
    <div class="mt-3">{{ $unitsOfMeasure->links() }}</div>
    @endif
</div>
@endsection
