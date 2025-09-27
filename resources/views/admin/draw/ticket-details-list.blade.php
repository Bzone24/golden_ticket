@extends('admin.layouts.base')
@section('title', 'Ticket Details')
@section('contents')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.draw') }}">Draw List</a></li>
                <li class="breadcrumb-item"><a
                        href="{{ route('admin.draw.detail.list', $drawDetail->id) }}">{{ $drawDetail->formatEndTime() }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('admin.draw.details.shopkeeper', ['drawDetail' => $drawDetail->id, 'user' => $user->id]) }}">Draw
                        of
                        {{ $user->name }}</a></li>
                <li class="breadcrumb-item active">Details of Ticket Number: {{ $ticket->ticket_number }}</li>
            </ol>
        </nav>

        {{-- <h2></h2> --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white bg-primary d-flex justify-content-start">
                        <h4 class="text-white me-auto">Details of Ticket Number: {{ $ticket->ticket_number }}</h4>
                    </div>
                    <div class="card-body">
                        {{ $dataTable->table(['id'=>'shopkeepers-table']) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header text-white bg-primary d-flex justify-content-start">
                        <h4 class="text-white me-auto">Details of Ticket Cross: {{ $ticket->ticket_number }}</h4>
                    </div>
                    <div class="card-body">
                        {{-- {!! $crossTable->table(['id'=>'CrossTicketDetailsDataTable']) !!} --}}
                        <table id="CrossTicketDetailsDataTable" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Option</th>
                                    <th>Number</th>
                                    <th>Combination</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('custom-js')
        @include('admin.includes.datatable-js-plugins')
        {{ $dataTable->scripts() }}
        <script>
            $(function () {
               
                // Orders DataTable with param
                $('#CrossTicketDetailsDataTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('admin.draw.cross.ticket.details.list', ['drawDetail' => $drawDetail->id,'ticket'=>$ticket->id,'user'=>$user->id]) }}',
                        data: function (d) {
                            // extra params if required
                            d.status = 'paid';
                        }
                    },
                    columns: [
                        { data: 'option', name: 'Option' },
                        { data: 'number', name: 'Number' },
                        { data: 'combination', name: 'Combination' },
                        { data: 'amt', name: 'Amount' }
                    ]
                });
            });
        </script>
        
    @endpush

@endsection
