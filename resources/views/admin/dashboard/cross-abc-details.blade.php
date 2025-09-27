@extends('admin.layouts.base')
@section('title', 'Cross ABC Details')
@section('contents')
    <div class="container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Details of {{ $drawDetail->formatEndTime() }}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-gradient" style="background: linear-gradient(90deg,#007bff,#6610f2);">
                        <h4 class="text-white fw-bold mb-0 text-center">
                            <i class="bi bi-graph-up-arrow me-2"></i> 
                            Details Of (Time: {{ $drawDetail->formatEndTime() }})
                        </h4>
                    </div>

                    <div class="card-body">
                        <div class="row mb-4">
                            @include('admin.dashboard.cross-abc-details-list')
                        </div>

                        {{-- ✅ Partial for Cross ABC Tables --}}
                        @include('admin.dashboard.partials.cross-abc-details-table')

                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('custom-css')
    <style>
        /* Bold, larger table */
        .custom-dt th, 
        .custom-dt td {
            font-size: 1.05rem !important;
            font-weight: 600 !important;
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Header color */
        .custom-dt thead th {
            background: linear-gradient(90deg,#17a2b8,#20c997) !important;
            color: #fff !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            text-transform: uppercase;
        }

        /* Row hover effect */
        .custom-dt tbody tr:hover {
            background-color: #f1f5f9 !important;
            transition: 0.3s ease;
        }

        /* Remove pagination/info */
        div.dataTables_wrapper div.dataTables_paginate,
        div.dataTables_wrapper div.dataTables_info,
        div.dataTables_wrapper div.dataTables_length {
            display: none !important;
        }

        /* Flash highlight */
        .flash-green {
            background-color: #28a745 !important;
            color: #fff !important;
            animation: flashFadeGreen 2s ease forwards;
        }
        .flash-red {
            background-color: #dc3545 !important;
            color: #fff !important;
            animation: flashFadeRed 2s ease forwards;
        }

        @keyframes flashFadeGreen {
            0% { background-color: #28a745; color: #fff; }
            100% { background-color: inherit; color: inherit; }
        }
        @keyframes flashFadeRed {
            0% { background-color: #dc3545; color: #fff; }
            100% { background-color: inherit; color: inherit; }
        }

        /* Hide DataTables processing/loading dots */
        div.dataTables_processing {
            display: none !important;
        }
    </style>
    @endpush

    @push('custom-js')
    @include('admin.includes.datatable-js-plugins')

    {{-- Init DataTables --}}
    {{ $dataTable->scripts() }}
    {!! $crossAcDataTable->scripts() !!}
    {!! $crossBcDataTable->scripts() !!}

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        setInterval(function () {
            if (window.LaravelDataTables) {
                Object.keys(window.LaravelDataTables).forEach(function (key) {
                    let dt = window.LaravelDataTables[key];
                    if (dt && typeof dt.ajax !== 'undefined') {
                        // Save old data before reload
                        let oldData = dt.rows().data().toArray();

                        dt.ajax.reload(function () {
                            let newData = dt.rows().data().toArray();

                            // Compare rows
                            dt.rows().every(function (rowIdx) {
                                let rowNode = $(this.node());
                                let oldRow = oldData[rowIdx];
                                let newRow = newData[rowIdx];

                                if (!oldRow || !newRow) return;

                                newRow.forEach((cell, colIdx) => {
                                    if (oldRow[colIdx] != cell) {
                                        let td = rowNode.find("td").eq(colIdx);

                                        let newNum = parseFloat(cell.toString().replace(/,/g, ""));
                                        let oldNum = parseFloat(oldRow[colIdx]?.toString().replace(/,/g, ""));

                                        if (!isNaN(newNum) && !isNaN(oldNum)) {
                                            if (newNum > oldNum) {
                                                td.addClass("flash-green");
                                                setTimeout(() => td.removeClass("flash-green"), 2000);
                                            } else if (newNum < oldNum) {
                                                td.addClass("flash-red");
                                                setTimeout(() => td.removeClass("flash-red"), 2000);
                                            }
                                        } else {
                                            // fallback for text values
                                            td.addClass("flash-green");
                                            setTimeout(() => td.removeClass("flash-green"), 2000);
                                        }
                                    }
                                });
                            });
                        }, false);
                    }
                });
            }
        }, 10000); // refresh every 10s
    });
    </script>
    @endpush
@endsection
