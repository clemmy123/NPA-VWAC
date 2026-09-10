@extends('components.main-layout')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h4 class="page-title">Dashboard</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
</div>

<div class="chart-card">
    <p class="mb-0">Welcome, {{ auth()->user()->name }}.</p>
</div>
@endsection
