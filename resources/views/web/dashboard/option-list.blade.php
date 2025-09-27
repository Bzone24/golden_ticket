@extends('web.layouts.base')
@section('title', 'GameTicketHub')
@section('contents')
    @push('custom-css')
        @include('admin.includes.datatable-css-plugins')
    @endpush
    <div class="card">
        <div class="card-header">
            <a href="{{ route('dashboard') }}" class="btn btn-dark text-white">
                <i class="fa fa-arrow-circle-left"></i> Draw List
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-center">
                            <h5 class="ms-2">
                                Draw Details (Draw No:{{ $draw->id }})
                            </h5>

                        </div>

                    </div>
                    <div class="card-body">
                        {{ $dataTable->table() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    @push('custom-js')
        @include('admin.includes.datatable-js-plugins')
       <b text-center> {{ $dataTable->scripts() }}</b>
    @endpush

@endsection
