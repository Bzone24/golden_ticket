@extends('web.layouts.base')
@section('title', 'GameTicketHub')
@section('contents')
  @push('custom-css')
    @include('admin.includes.datatable-css-plugins')

    <style>
        /* ===== Custom Table Styling ===== */
        table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            background-color: #2b2828 !important; /* dark background */
            color: #efe1e1 !important; /* light text */
            font-size: 1.1rem !important; /* 🔹 larger default font */
        }

        table.dataTable th,
        table.dataTable td {
            text-align: center !important;   
            font-weight: bold !important;    
            vertical-align: middle !important;
            padding: 14px 10px !important;   /* 🔹 more padding */
            font-size: 1.15rem !important;   /* 🔹 larger font size */
        }

        table.dataTable thead {
            background-color: #444 !important;
            color: #fff !important;
            font-size: 1.2rem !important;    /* 🔹 larger header font */
            text-transform: uppercase;
        }

        table.dataTable tbody tr:nth-child(odd) {
            background-color: #3a3a3a !important;
        }

        table.dataTable tbody tr:nth-child(even) {
            background-color: #2f2f2f !important;
        }

        table.dataTable tbody tr:hover {
            background-color: #555 !important;
            transition: 0.3s ease;
        }

        /* P&L column green/red styling */
        td.pl-positive {
            background-color: #155724 !important;
            color: #d4edda !important;
            font-weight: bold;
            font-size: 1.2rem !important;   /* 🔹 bigger font for P&L */
            border-radius: 6px;
        }

        td.pl-negative {
            background-color: #721c24 !important;
            color: #f8d7da !important;
            font-weight: bold;
            font-size: 1.2rem !important;   /* 🔹 bigger font for P&L */
            border-radius: 6px;
        }

        /* Pagination, search, filters */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            background: #444 !important;
            color: #fff !important;
            border-radius: 5px;
            margin: 0 2px;
            padding: 6px 12px !important;   /* 🔹 larger buttons */
            font-size: 1.1rem !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #e3ea16 !important;
            color: #000 !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            background-color: #cdb7b7 !important;
            border: 1px solid #777 !important;
            color: #fff !important;
            padding: 8px 12px !important;   /* 🔹 bigger input */
            border-radius: 5px;
            font-size: 1rem !important;
        }

        #totals-row  {
            /* Apply the sticky position to the entire footer */
            position: sticky;
            /* Stick to the bottom of the scrollable parent */
            bottom: 0;
            /* Add a background to prevent content from showing through */
            background-color: var(--bs-light); /* Uses Bootstrap's light color variable */
            /* Add a shadow for better visual separation */
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
        }

    </style>
@endpush

    <div class="card" style="color:#efe1e1; background-color: #2b2828;">
        <div class="card-header" style="background-color:#b52020;">
            <h4>Dashboard</h4>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header" style="background-color: #e3ea16;">
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
            // Extra: Apply green/red classes for P&L dynamically
            document.addEventListener("DOMContentLoaded", function () {
                setTimeout(() => {
                    document.querySelectorAll("td:nth-child(6)").forEach(td => {
                        let val = parseFloat(td.innerText);
                        if (!isNaN(val)) {
                            td.classList.add(val >= 0 ? "pl-positive" : "pl-negative");
                        }
                    });
                }, 500);

                 let table = window.LaravelDataTables && window.LaravelDataTables["draw-details-table"]
                          ? window.LaravelDataTables["draw-details-table"]
                          : null;
                if (table) {
                    table.on('draw', function () {  
                        addTotalsRow(); 
                    });

                    function calculateTotals() {
                        let totals = {
                            tq: 0,        // Total Collection (index 2)
                            tAmt: 0,      // Total Rewards (index 3)  
                            claim: 0,     // Claim (index 4)
                            camt: 0,      // Camt (index 5)
                            pl: 0         // P&L (index 6)
                        };

                        // Calculate totals from visible rows
                        table.rows().every(function () {
                            let rowData = $(this.node()).find("td");
                            
                            // Parse and sum numeric values (remove commas first)
                            totals.tq += parseFloat(rowData.eq(2).text().replace(/,/g, "")) || 0;
                            totals.tAmt += parseFloat(rowData.eq(3).text().replace(/,/g, "")) || 0;
                            totals.claim += parseFloat(rowData.eq(4).text().replace(/,/g, "")) || 0;
                            totals.camt += parseFloat(rowData.eq(5).text().replace(/,/g, "")) || 0;
                            totals.pl += parseFloat(rowData.eq(6).text().replace(/,/g, "")) || 0;
                        });

                        return totals;
                    }

                    function addTotalsRow() {
                        // Remove existing totals row if any
                        $("#totals-row").remove();
                        
                        let totals = calculateTotals();
                        
                        // Create totals row HTML
                        let totalsRowHtml = `
                            <tr id="totals-row" class="totals-row bg-info text-white font-weight-bold">
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
                        
                        // Add to table body
                        $(table.table().body()).append(totalsRowHtml);
                    }

                    function updateFooterTotals() {
                        let totals = calculateTotals();
                        
                        // Update footer cells (assumes footer exists in your DataTable)
                        $(table.table().footer()).find('th').each(function(index) {
                            switch(index) {
                                case 0: $(this).text('TOTALS'); break;
                                case 1: $(this).text('-'); break;
                                case 2: $(this).text(totals.tq.toLocaleString()); break;
                                case 3: $(this).text(totals.tAmt.toLocaleString()); break;
                                case 4: $(this).text(totals.claim.toLocaleString()); break;
                                case 5: $(this).text(totals.camt.toLocaleString()); break;
                                case 6: 
                                    $(this).text(totals.pl.toLocaleString())
                                        .removeClass('text-success text-danger')
                                        .addClass(totals.pl >= 0 ? 'text-success' : 'text-danger');
                                    break;
                            }
                        });
                    }
                }
            });
        </script>
    @endpush
@endsection
