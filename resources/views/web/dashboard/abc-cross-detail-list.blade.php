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
            <div class="row mb-3">
                <div class="col-12">

                    <div class="card" >
                        <div class="card-header bg-secondary text-white d-flex justify-content-between">
                            <h6 class="text-white">Details Of Cross ABC (Time: {{ $drawDetail->formatEndTime() }})</h6>
                            <h6 class="text-white">Game:{{$drawDetail->draw->game->name}}</h6>
                        </div>
                        <div class="card-body" >
                            <table class="table table-bordered">
                                <thead class="bg-dark">
                                    <tr>
                                        <th style="width: 50px; background-color: #d72222; text-align: center; color: white;">Option</th>
                                        <th style="width: 200px; background-color: #d72222; text-align: center; color: white;">Cross Amt</th>
                                        <th style="width: 200px; background-color: #d72222; text-align: center; color: white;">Claim</th>
                                        <th class="w-25" style="width: 200px; background-color: #d72222; text-align: center; color: white;">P&L <small>(Cross Amt. - Claim &times; 100)</small>
                                        </th>
                                        <th style="width: 50px; text-align: center;">Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="bg-success text-white text-center"><b>AB</b></td>
                                        <td class="bg-white text-center"><b>{{ $totalAbCrossAmt }}</b></td>
                                        <td class="bg-white text-dark text-center">
                                            @if ($totalAbCrossClaimAmt != 0)
                                                <a href="{{ route('dashboard.draw.details.list', ['draw_detail_id' => $drawDetail->id, 'claim' => 1]) }}"
                                                    class="text-primary"><b>{{ $totalAbCrossClaimAmt }}</b></a>
                                            @else
                                                {{ $totalAbCrossClaimAmt }}
                                            @endif
                                        </td>
                                        <td @class([
                                            'text-white bg-danger text-center' => $ab_pl < 0,
                                            'text-white bg-success text-center' => $ab_pl > 0,
                                        ])><b>{{ $ab_pl }}</b></td>
                                        <td class="bg-success text-white text-center"><b>{{ $drawDetail->ab ?? 'N/A' }}</b></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-info text-white text-center"><b>BC</b></td>
                                        <td class="bg-white text-center"><b>{{ $totalBcCrossAmt }}</b></td>

                                        <td class="bg-white text-dark text-center">
                                            @if ($totalBcCrossClaimAmt != 0)
                                                <a href="{{ route('dashboard.draw.details.list', ['draw_detail_id' => $drawDetail->id, 'claim' => 1]) }}"
                                                    class="text-primary"><b>{{ $totalBcCrossClaimAmt }}</b></a>
                                            @else
                                                {{ $totalBcCrossClaimAmt }}
                                            @endif
                                        </td>

                                        <td @class([
                                            'text-white bg-danger text-center' => $bc_pl < 0,
                                            'text-white bg-success text-center' => $bc_pl > 0,
                                        ])><b>{{ $bc_pl }}</b></td>
                                        <td class="bg-info text-white text-center"><b>{{ $drawDetail->bc ?? 'N/A' }}</b></td>
                                    </tr>
                                    <tr>
                                        <td class="bg-warning text-white text-center"><b>AC</b></td>
                                        <td class="bg-white text-center"><b>{{ $totalAcCrossAmt }}</b></td>

                                        <td class="bg-white text-dark text-center">
                                            @if ($totalAcCrossClaimAmt != 0)
                                                <a href="{{ route('dashboard.draw.details.list', ['draw_detail_id' => $drawDetail->id, 'claim' => 1]) }}"
                                                    class="text-primary text-center"><b>{{ $totalAcCrossClaimAmt }}</b></a>
                                            @else
                                                {{ $totalAcCrossClaimAmt }}
                                            @endif
                                        </td>

                                        <td @class([
                                            'text-white bg-danger text-center' => $ac_pl < 0,
                                            'text-white bg-success text-center' => $ac_pl > 0,
                                        ])><b>{{ $ac_pl }}</b></td>
                                        <td class="bg-warning text-white text-center"><b>{{ $drawDetail->ac ?? 'N/A' }}</b></td>
                                    </tr>
                                    
                                    <tr>
                                        <td style="width: 50px; background-color: #3f3f45; text-align: center; color: white;"><b>Total</b></td>
                                        <td style="width: 50px; background-color: #3f3f45; text-align: center; color: white;"><b>{{ $totalAbCrossAmt + $totalBcCrossAmt + $totalAcCrossAmt }}</b>
                                        </td>
                                        <td style="width: 50px; background-color: #3f3f45; text-align: center; color: white;"><b>{{ $totalAbCrossClaimAmt + $totalBcCrossClaimAmt + $totalAcCrossClaimAmt }}
                                            </b></td>
                                        <td style="width: 50px; background-color: #3f3f45; text-align: center; color: white;"><b>{{ $ab_pl + $ac_pl + $bc_pl }}
                                            </b></td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
            <hr>
            <div class="row">
                <div class="col-4">
                    <div class="card">
                        <div class="card-header bg-success justify-content-between">
                            {{-- <h6 class="text-white">Details Of AB (Time: {{ $drawDetail->formatEndTime() }})
                            </h6> --}}
                            <h6 class="text-white text-center mb-0">
                                Details Of AB (Time: {{ $drawDetail->formatEndTime() }})
                            </h6>
                            <h6 class="text-white text-center mb-0">
                                Game:{{$drawDetail->draw->game->name}}
                            </h6>
                        </div>
                        <div class="card-body" style="background-color: #3f3f45; color:white; text-align: center;">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <b > {!! $dataTable->table() !!}</b>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="card">
                        <div class="card-header bg-info  justify-content-between">
                            <h6 class="text-dark text-center">Details Of BC (Time: {{ $drawDetail->formatEndTime() }})</h6>
                             <h6 class="text-white text-center mb-0">
                                Game:{{$drawDetail->draw->game->name}}
                            </h6>
                        </div>
                       <div class="card-body" style="background-color: #3f3f45; color:white; text-align: center;">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <b>{!! $crossBcDataTable->table() !!}</b>
                        </div>
                        </div>
                    </div>
                </div>

                <div class="col-4">
                    <div class="card">
                        <div class="card-header bg-warning justify-content-between">
                            <h6 class="text-dark text-center">Details Of AC (Time: {{ $drawDetail->formatEndTime() }})</h6>
                             <h6 class="text-white text-center mb-0">
                                Game:{{$drawDetail->draw->game->name}}
                            </h6>
                        </div>
                        <div class="card-body" style="background-color: #3f3f45; color:white; text-align: center;">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                           <b> {!! $crossAcDataTable->table() !!}</b>
                        </div>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
    </div>
  @push('custom-js')
    @include('admin.includes.datatable-js-plugins')
    {!! $dataTable->scripts() !!}
    {!! $crossAcDataTable->scripts() !!}
    {!! $crossBcDataTable->scripts() !!}

    
    <script>
    (function () {
        // safe DOM ready
        function onReady(fn) {
            if (document.readyState !== 'loading') fn();
            else document.addEventListener('DOMContentLoaded', fn);
        }

        onReady(function () {
            // pick the draw id from the blade (server-rendered)
            var drawId = '{{ $drawDetail->id ?? request("draw_detail_id") ?? request("drawDetail") }}';

            // build frontend URLs (adjust if your route URIs differ)
            var abUrl = '/dashboard/cross-ab-list' + (drawId ? '?draw_detail_id=' + drawId : '');
            var acUrl = '/dashboard/cross-ac-list' + (drawId ? '?draw_detail_id=' + drawId : '');
            var bcUrl = '/dashboard/cross-bc-list' + (drawId ? '?draw_detail_id=' + drawId : '');

            // Helper: set ajax url & reload if DataTable already exists
            function ensureTable(tableId, url) {
                try {
                    var tableEl = jQuery('#' + tableId);
                    if (!tableEl.length) return;
                    // if DataTable has been initialised by yajra scripts, get API
                    if ( $.fn.dataTable.isDataTable(tableEl) ) {
                        var dt = tableEl.DataTable();
                        // set ajax to the frontend url and reload
                        if (typeof dt.ajax === 'object' && typeof dt.ajax.url === 'function') {
                            dt.ajax.url(url).load();
                        } else if (dt.settings && dt.settings()[0] && dt.settings()[0].oAjaxData) {
                            // fallback: force a reload
                            dt.ajax && dt.ajax.reload && dt.ajax.reload();
                        }
                    } else {
                        // not initialised yet — attempt to initialise with only ajax URL
                        tableEl.DataTable({
                            ajax: url,
                            paging: false,
                            info: false,
                            searching: true,
                            scrollY: '250px',
                            scrollCollapse: true
                        });
                    }
                } catch (e) {
                    console.error('Error ensuring DataTable', tableId, e);
                }
            }

            // ensure all three tables use the correct frontend endpoints
            ensureTable('cross-ab-table', abUrl);
            ensureTable('cross-ac-table', acUrl);
            ensureTable('cross-bc-table', bcUrl);
        });
    })();
    </script>
@endpush

@endsection

@push('scripts')
{{-- {!! $dataTable->scripts() !!} --}}
<script>
$(document).ready(function() {
    $('#myDataTable').DataTable({
        paging: false,
        searching: true,
        info: false,
        scrollY: '250px',
        scrollCollapse: true
    });
});
</script>
@endpush

