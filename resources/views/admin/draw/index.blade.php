@extends('admin.layouts.base')
@section('title','Shopkeeper')
@section('contents')
<div class="container-fluid">
    <h2>Details of Draw</h2>
    <div class="row">
        <div class="col-12">
            <div class="card">
               <div class="card-header d-flex justify-content-end">
                    <a href="{{route('admin.add.draw') }}" class="btn btn-primary">Add A New Draw <i class="ti ti-plus"></i></a>
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