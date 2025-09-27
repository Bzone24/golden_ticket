@extends('admin.layouts.base')
@section('title', 'User Details')
@section('contents')
    <div class="container-fluid">
        <h2>
            @if($user->hasRole('admin'))
                Shopkeepers of {{$user->name}}
            @elseif($user->hasRole('shopkeeper'))
                Users of {{$user->name}}
            @endif
        </h2>
        <div class="row">
            <div class="col-12">
                <div class="card">
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
