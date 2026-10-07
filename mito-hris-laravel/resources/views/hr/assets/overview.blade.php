@extends('layouts.portal')

@section('title', 'Assets Overview - MITO HRIS')
@section('page-title', 'Assets Overview')
@section('page-subtitle', 'Ringkasan dan kategori aset perusahaan')

@section('content')
    @include('hr.assets.partials.overview')
@endsection
