@extends('admin.layouts.base')
@section('title', 'Shopkeeper')
@section('contents')
    <div class="container-fluid">
        <h2>Dashboard</h2>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-end">
                        <a href="{{ route('admin.shopkeeper_form') }}" class="btn btn-primary">
                                @hasrole(['admin'])Add New Shopkeeper @elserole('shopkeeper')Add New User @elserole('master') Add New Admin @endrole
                                <i class="ti ti-plus"></i></a>
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
        <script>
            $(document).ready(function() {
                $(document).on("click", ".show-password-button", function() {
                    const $parent = $(this).parent();

                    if ($(this).hasClass('fa-eye')) {
                        $parent.find('.star-password').addClass('d-none');
                        $parent.find('.show-password').removeClass('d-none');
                        $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        $parent.find('.star-password').removeClass('d-none');
                        $parent.find('.show-password').addClass('d-none');
                        $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            });
        </script>
    @endpush

@endsection
