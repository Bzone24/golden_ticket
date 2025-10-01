@extends('web.layouts.base')
@section('title', 'GameTicketHub')
@section('contents')
  @push('custom-css')
    @include('admin.includes.datatable-css-plugins')

    <style>
        /* ===== Custom Table Styling for User ===== */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            background: linear-gradient(145deg, #0a192f, #172a45) !important; /* 🔹 deep navy gradient */
            color: #e6f1ff !important;
            font-size: 1.05rem !important;
        }

        table.dataTable th,
        table.dataTable td {
            text-align: center !important;
            font-weight: 500 !important;
            vertical-align: middle !important;
            padding: 12px 10px !important;
            font-size: 1.05rem !important;
        }

        table.dataTable thead {
            background: #0e76a8 !important; /* 🔹 cyan-blue header */
            color: #fff !important;
            font-size: 1.15rem !important;
            text-transform: uppercase;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: rgba(255, 255, 255, 0.04) !important;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: rgba(255, 255, 255, 0.07) !important;
        }

        table.dataTable tbody tr:hover {
            background-color: #1f4068 !important;
            box-shadow: inset 0 0 8px rgba(0, 255, 255, 0.4);
            transition: 0.3s ease;
        }

        /* 🔹 P&L column green/red */
        /* td.pl-positive {
            background-color: #0f5132 !important;
            color: #080c0a !important;
            font-weight: bold;
            font-size: 1.15rem !important;
            border-radius: 6px;
        } */

        /* td.pl-negative {
            background-color: #842029 !important;
            color: #f8d7da !important;
            font-weight: bold;
            font-size: 1.15rem !important;
            border-radius: 6px;
        } */

        /* Pagination & Search */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #0e76a8 !important;
            color: #fff !important;
            border-radius: 6px;
            margin: 0 2px;
            padding: 6px 12px !important;
            font-size: 1rem !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #39ff14 !important; /* neon green */
            color: #000 !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            background-color: #1f2937 !important;
            border: 1px solid #0e76a8 !important;
            color: #e6f1ff !important;
            padding: 8px 12px !important;
            border-radius: 6px;
            font-size: 1rem !important;
        }

        /* Sticky Totals Row */
        #totals-row {
            position: sticky;
            bottom: 0;
            background: #0e76a8 !important;
            color: #fff !important;
            font-weight: bold;
            box-shadow: 0 -2px 6px rgba(0,0,0,0.3);
        }
    </style>
@endpush

    <div class="card" style="background:linear-gradient(145deg, #0a192f, #172a45); color:#e6f1ff;">
        <div class="card-header" style="background-color:#0e76a8;">
            <h4>User Dashboard</h4>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="card" style="background: #112240; color:#e6f1ff;">
                        <div class="card-header" style="background-color: #39ff14; color:#000;">
                            <div class="col-12 mb-3">
                                <div class="d-flex justify-content-start">
                                    <h5 class="me-auto">Draw List</h5>
                                    @if ($total_available_draws > 0)
                                        <a href="{{ route('ticket.add') }}" class="btn btn-primary">
                                            Add A New Ticket <i class="fa fa-ticket"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <x-date-range-picker-filter />
                            {{ $dataTable->table() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('custom-js')
        @include('admin.includes.datatable-js-plugins')
        {{ $dataTable->scripts() }}

        <script>
            // Apply green/red to P&L
            function applyPLColors() {
                document.querySelectorAll("td:nth-child(6)").forEach(td => {
                    let val = parseFloat(td.innerText);
                    if (!isNaN(val)) {
                        td.classList.add(val >= 0 ? "pl-positive" : "pl-negative");
                    }
                });
            }

            document.addEventListener("DOMContentLoaded", function () {
                setTimeout(applyPLColors, 500);

                let table = window.LaravelDataTables && window.LaravelDataTables["draw-details-table"]
                          ? window.LaravelDataTables["draw-details-table"]
                          : null;

                if (table) {
                    table.on('draw', function () {
                        applyPLColors();
                        addTotalsRow();
                    });

                    function calculateTotals() {
                        let totals = { tq: 0, tAmt: 0, claim: 0, camt: 0, pl: 0 };
                        table.rows().every(function () {
                            let rowData = $(this.node()).find("td");
                            totals.tq += parseFloat(rowData.eq(2).text().replace(/,/g, "")) || 0;
                            totals.tAmt += parseFloat(rowData.eq(3).text().replace(/,/g, "")) || 0;
                            totals.claim += parseFloat(rowData.eq(4).text().replace(/,/g, "")) || 0;
                            totals.camt += parseFloat(rowData.eq(5).text().replace(/,/g, "")) || 0;
                            totals.pl += parseFloat(rowData.eq(6).text().replace(/,/g, "")) || 0;
                        });
                        return totals;
                    }

                    function addTotalsRow() {
                        $("#totals-row").remove();
                        let totals = calculateTotals();
                        let totalsRowHtml = `
                            <tr id="totals-row">
                                <td>TOTALS</td>
                                <td>-</td>
                                <td>${totals.tq.toLocaleString()}</td>
                                <td>${totals.tAmt.toLocaleString()}</td>
                                <td>${totals.claim.toLocaleString()}</td>
                                <td>${totals.camt.toLocaleString()}</td>
                                <td class="${totals.pl >= 0 ? 'text-success' : 'text-danger'}">${totals.pl.toLocaleString()}</td>
                                <td>--</td>
                            </tr>
                        `;
                        $(table.table().body()).append(totalsRowHtml);
                    }
                }
            });
        </script>
    @endpush
@endsection

