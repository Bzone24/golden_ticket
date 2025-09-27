@extends('admin.layouts.base')
@section('title', 'Shopkeeper')
@section('contents')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.draw') }}">Draw List</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.draw.detail.list', ['draw_id' => $draw->id]) }}">Details
                        Of DN-{{ $draw->id }}</a>
                </li>
                <li class="breadcrumb-item active">Details of Number {{ $number }} of DN-{{ $draw->id }}</li>
            </ol>
        </nav>

        {{-- <h2></h2> --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white bg-primary d-flex justify-content-start">
                        <h4 class="text-white">Details of Number {{ $number }} of DN-{{ $draw->id }}</h4>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('custom-js')
        @include('admin.includes.datatable-js-plugins')
        {{ $dataTable->scripts() }}
    @endpush

@endsection
