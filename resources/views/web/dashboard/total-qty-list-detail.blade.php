@extends('web.layouts.base')
@section('title', 'GameTicketHub')
@section('contents')
@push('custom-css')
<style>
    /* Make table text larger and clearer */
    .table {
        font-size: 1.15rem !important;   /* 🔹 Bigger text */
        font-weight: bold !important;    /* 🔹 Bold text */
        text-align: center !important;   /* 🔹 Center everything */
    }

    /* Table header */
    .table thead th {
        background-color: #343a40 !important; /* Dark header */
        color: #fff !important;
        font-size: 1.2rem !important;         /* 🔹 Larger header */
        text-transform: uppercase;
        text-align: center !important;
        padding: 12px !important;
    }

    /* Table cells */
    .table tbody td {
        vertical-align: middle !important;
        padding: 10px !important;
    }

    /* Highlight total row */
    .table tbody tr:last-child td {
        background-color: #f8f9fa !important;
        font-size: 1.2rem !important;
        font-weight: 900 !important;    /* 🔹 Extra bold */
        color: #000 !important;
    }

    /* Option column (A, B, C, Total) */
    .table tbody td:first-child {
        font-size: 1.2rem !important;
        font-weight: 900 !important;
        text-align: center !important;
    }

    /* Positive P&L */
    .table td.bg-success,
    .table .text-success {
        background-color: #198754 !important;
        color: #fff !important;
        font-weight: bold !important;
    }

    /* Negative P&L */
    .table td.bg-danger,
    .table .text-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
        font-weight: bold !important;
    }

    /* Warning cells */
    .table .text-warning {
        color: #ffc107 !important;
        font-weight: bold !important;
    }

    /* Row hover effect */
    .table tbody tr:hover {
        background-color: #ececec !important;
        transition: 0.3s ease;
    }

    /* Highlight TOTAL Claim Q cell in red */
.table tbody tr:last-child td:nth-child(13) {
    background-color: #dc3545 !important;  /* 🔴 Red */
    color: #fff !important;               /* White text */
    font-weight: bold !important;
    font-size: 1.2rem !important;
    text-align: center !important;
}

</style>
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
                        <div class="card-header bg-primary d-flex justify-content-between">
                            <h4 class="text-white">Details Of Total Qty (Time: {{ $drawDetail->formatEndTime() }})
                            </h4>
                            <h4 class="text-white">
                                Game:{{ $drawDetail->draw->game->name }}
                            </h4>
                        </div>
                        <div class="card-body">
                            @php
                                $indices = range(0, 9); // 0,1,2,...,9
                            @endphp
                            @php
                                $tq_a = $tq_b = $tq_c = 0;
                                $a_amounts = $b_amounts = $c_amounts = [];
                                foreach ($indices as $i) {
                                    $tq_a += $drawDetail->totalAqty($i,auth()->user()->id);
                                    $a_amounts[] = $drawDetail->totalAqty($i,auth()->user()->id);
                                }
                                foreach ($indices as $i) {
                                    $tq_b += $drawDetail->totalBqty($i,auth()->user()->id);
                                    $b_amounts[] = $drawDetail->totalBqty($i,auth()->user()->id);
                                }
                                foreach ($indices as $i) {
                                    $tq_c += $drawDetail->totalCqty($i,auth()->user()->id);
                                    $c_amounts[] = $drawDetail->totalCqty($i,auth()->user()->id);
                                }

                                $claim_a_amt = $drawDetail->claim_a ? $a_amounts[$drawDetail->claim_a] : 0;
                                $claim_b_amt = $drawDetail->claim_b ? $a_amounts[$drawDetail->claim_b] : 0;
                                $claim_c_amt = $drawDetail->claim_c ? $c_amounts[$drawDetail->claim_c] : 0;

                                $a_pl = $tq_a * 11 - $claim_a_amt * 100;
                                $b_pl = $tq_b * 11 - $claim_b_amt * 100;
                                $c_pl = $tq_c * 11 - $claim_c_amt * 100;

                            @endphp
                            <table class="table table-bordered ">
                                <thead class="">
                                    <tr>
                                        <th>Option</th>
                                        <th>0</th>
                                        <th>1</th>
                                        <th>2</th>
                                        <th>3</th>
                                        <th>4</th>
                                        <th>5</th>
                                        <th>6</th>
                                        <th>7</th>
                                        <th>8</th>
                                        <th>9</th>
                                        <th>TQ</th>
                                        <th>Claim Q</th>
                                        <th>P & L</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="bg-success text-white text-center">A</td>

                                        @foreach ($indices as $i)
                                            <td @class([
                                                'bg-danger text-white' =>
                                                    $drawDetail->claim_a == $i &&
                                                    $drawDetail->totalAqty($i,auth()->user()->id) == $claim_a_amt &&
                                                    $drawDetail->totalAqty($i,auth()->user()->id) != 0,
                                            ])>{{ $drawDetail->totalAqty($i,auth()->user()->id) }}</td>
                                        @endforeach
                                        <td>{{ $tq_a }}</td>
                                        <td>{{ $claim_a_amt }}</td>
                                        <td @class([
                                            'bg-danger text-white' => $a_pl < 0,
                                            'bg-success text-white' => $a_pl > 0,
                                        ])>{{ $a_pl }}</td>
                                        <td>{{ $drawDetail->claim_a ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="bg-warning text-white text-center">B</td>
                                        @foreach ($indices as $i)
                                            <td @class([
                                                'bg-danger text-white' =>
                                                    $drawDetail->claim_b == $i &&
                                                    $drawDetail->totalBqty($i,auth()->user()->id) == $claim_b_amt &&
                                                    $drawDetail->totalBqty($i,auth()->user()->id) != 0,
                                            ])>{{ $drawDetail->totalBqty($i,auth()->user()->id) }}</td>
                                        @endforeach
                                        <td>{{ $tq_b }}</td>
                                        <td>{{ $claim_b_amt }}</td>
                                        <td @class([
                                            'bg-danger text-white' => $b_pl < 0,
                                            'bg-success text-white' => $b_pl > 0,
                                        ])>{{ $b_pl }}</td>
                                        <td>{{ $drawDetail->claim_b ?? 'N/A' }}</td>

                                    </tr>
                                    <tr>
                                        <td class="bg-info text-white text-center">C</td>
                                        @foreach ($indices as $i)
                                            <td @class([
                                                'bg-danger text-white' =>
                                                    $drawDetail->claim_c == $i &&
                                                    $drawDetail->totalCqty($i,auth()->user()->id) == $claim_c_amt &&
                                                    $drawDetail->totalCqty($i,auth()->user()->id) != 0,
                                            ])>{{ $drawDetail->totalCqty($i,auth()->user()->id) }}</td>
                                        @endforeach
                                        <td>{{ $tq_c }}</td>
                                        <td>{{ $claim_c_amt }}</td>
                                        <td @class([
                                            'bg-danger text-white' => $c_pl < 0,
                                            'bg-success text-white' => $c_pl > 0,
                                        ])>{{ $c_pl }}</td>
                                        <td>{{ $drawDetail->claim_c ?? 'N/A' }}</td>

                                    </tr>
                                    <tr>
                                        <td colspan="11"><b>Total</b></td>
                                        <td class="text-success"><b>{{ $tq_a + $tq_b + $tq_c }}</b></td>
                                        <td style="background-color:#dc3545 !important; color:#fff !important; font-weight:bold;"><b>{{ $claim_a_amt + $claim_b_amt + $claim_c_amt }}</b>
                                        </td>
                                        <td><b>{{ $a_pl + $b_pl + $c_pl }}</b></td>

                                    </tr>
                                    <!-- Add more rows as needed -->
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @push('custom-js')
    @endpush

@endsection
