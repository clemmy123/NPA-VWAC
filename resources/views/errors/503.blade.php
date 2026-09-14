@extends('errors.layout', ['variant' => 'warning'])

@section('title', '503 - Under Maintenance')
@section('icon', 'mdi-tools')
@section('code', '503')
@section('title-text', 'Under maintenance')
@section('message', config('app.name').' is temporarily unavailable while we perform some maintenance. Please check back shortly.')

@section('actions')
@endsection
