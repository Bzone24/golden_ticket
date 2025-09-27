@extends('admin.layouts.base')
@section('title', 'Draw Details')
@section('contents')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.draw') }}">Draw List</a></li>
                <li class="breadcrumb-item"><a
                        href="{{ route('admin.draw.detail.list', $drawDetail->id) }}">{{ $drawDetail->formatEndTime() }}</a>
                </li>

                <li class="breadcrumb-item active">Details of {{ $drawDetail->formatEndTime() }} of {{ $user->name }}</li>
            </ol>
        </nav>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary d-flex justify-content-start">
                        <h4 class="text-white">Time: {{ $drawDetail->formatEndTime() }} of {{ $user->name }}</h4>
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
