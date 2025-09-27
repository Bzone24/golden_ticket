@extends('admin.layouts.base')
@section('title', 'Shopkeeper')
@section('contents')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.shopkeepers') }}">Shopkeeper List</a></li>
                <li class="breadcrumb-item active">{{ $user->name }}</li>
            </ol>
        </nav>
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h5 class="text-white">Details of {{ $user->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 text-end">
                                <a class="btn btn-sm btn-warning text-white"
                                    href="{{ route('admin.shopkeeper_form', ['user_id' => $user->id]) }}"> <i
                                        class="fa fa-pencil"></i> Edit</a>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Name:</div>
                            <div class="col-sm-8">{{ $user->name }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Mobile Number:</div>
                            <div class="col-sm-8">{{ $user->mobile_number }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Email:</div>
                            <div class="col-sm-8">{{ $user->email }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Total Draw:</div>
                            <div class="col-sm-8">{{ $user->drawDetails->count() }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-4 fw-bold">Total Tickets:</div>
                            <div class="col-sm-8">{{ $user->tickets->count() }}</div>
                        </div>

                    </div>
                </div>
            </div>
            {{-- <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white bg-primary d-flex justify-content-start">
                        <h4 class="text-white me-auto">Details of Draw</h4>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table() }}
                    </div>
                </div>
            </div>
        </div> --}}
        </div>
        @push('custom-js')
            {{-- @include('admin.includes.datatable-js-plugins')
        {{ $dataTable->scripts() }} --}}
        @endpush

    @endsection
