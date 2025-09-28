@extends('admin.layouts.base')
@section('title', 'Test Title')
@section('contents')

@push('custom-css')
<style>
    body {
        background-color: #f5f7fa !important;
        font-size: 1.05rem !important;
        font-weight: 500 !important;
        color: #222 !important;
    }

    h2 {
        font-weight: 400 !important;
        font-size: 1rem !important;
        color: #2c3e50 !important;
    }

    .card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
        margin-bottom: 20px !important;
    }

    .card-header {
        font-weight: 700 !important;
        font-size: 1.2rem !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 12px 15px !important;
    }

    .card-body {
        font-size: 1rem !important;
        font-weight: 500 !important;
        padding: 5px 5px !important;
    }

    /* DataTable Styling */
    table.dataTable {
        font-size: 1.05rem !important;
        font-weight: 600 !important;
        text-align: center !important;
        width: 100% !important;
    }

    table.dataTable thead th {
        background-color: #2c3e50 !important;
        color: #fff !important;
        text-transform: uppercase !important;
        font-size: 1.1rem !important;
        padding: 12px !important;
        font-weight: bold !important;
    }

    table.dataTable tbody td {
        padding: 10px !important;
        vertical-align: middle !important;
        font-weight: bold !important;
    }

    /* P&L Styling */
    .text-success, .bg-success {
        background-color: #198754 !important;
        color: #fff !important;
        font-weight: 700 !important;
        border-radius: 6px !important;
        padding: 5px 10px !important;
    }

    .text-danger, .bg-danger {
        background-color: #dc3545 !important;
        color: #fff !important;
        font-weight: 700 !important;
        border-radius: 6px !important;
        padding: 5px 10px !important;
    }

    /* Shade entire row based on P&L */
    tr.row-positive {
        background-color: #e6f4ea !important; /* light green */
    }
    tr.row-negative {
        background-color: #fdecea !important; /* light red */
    }

    /* Buttons */
    .btn {
        font-size: 1rem !important;
        font-weight: 400 !important;
        border-radius: 8px !important;
        padding: 6px 14px !important;
    }

    .btn-warning {
        background-color: #ffc107 !important;
        color: #222 !important;
        font-weight: 700 !important;
        border: none !important;
    }

    .btn-warning:hover {
        background-color: #e0a800 !important;
        color: #fff !important;
    }

    /* Hover Effect */
    table.dataTable tbody tr:hover {
        background-color: #eef3f7 !important;
        transition: 0.3s ease;
    }

    /* Scroll Body */
    .dataTables_scrollBody {
        max-height: 500px !important;
        overflow-y: auto !important;
    }

    /* Hide Pagination, Info, and Length dropdown */
    div.dataTables_wrapper div.dataTables_paginate,
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_length {
        display: none !important;
    }

    /* Sticky Header */
    .dataTables_scrollHead {
        position: sticky;
        top: 0;
        z-index: 100;
    }

    /* Flash highlights */
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

    /* Small responsive tweaks for Game column */
    th:nth-child(1), td:nth-child(1) {
        white-space: nowrap;
    }

     #totals-row  {
        /* Apply the sticky position to the entire footer */
        position: sticky;
        /* Stick to the bottom of the scrollable parent */
        bottom: 0;
        /* Add a background to prevent content from showing through */
        /* Add a shadow for better visual separation */
        box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    }
    #totals-row td {
        background: #2c3e50 !important; /* Uses Bootstrap's light color variable */
        color: white
    }
</style>
@endpush

@push('custom-js')
    @include('admin.includes.datatable-js-plugins')
    {{ $dataTable->scripts() }}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Make sure this matches your DataTable setTableId in the DataTable class (should be 'draw-details-table')
            let table = window.LaravelDataTables && window.LaravelDataTables["draw-details-table"]
                          ? window.LaravelDataTables["draw-details-table"]
                          : null;
                if (table) {
                // hide default controls (already done by CSS, but double-safe here)
                $(".dataTables_paginate, .dataTables_info, .dataTables_length").hide();

                // scroll body styling
                $('.dataTables_scrollBody').css({
                    'max-height': '70vh',
                    'overflow-y': 'auto'
                });

                $(".dataTables_scrollHead").css({
                    "position": "sticky",
                    "top": "0",
                    "z-index": "10",
                    "background": "#343a40"
                });

                // NOTE: Column indexes after adding Game column:
                // 0 -> Game
                // 1 -> Time (end_time)
                // 2 -> TQ (total_collection)
                // 3 -> T Amt (total_rewards)
                // 4 -> Claim
                // 5 -> Camt
                // 6 -> P&L

                // Shade rows by P&L on every draw
                table.on('draw', function () {
                    table.rows().every(function () {
                        let plCell = $(this.node()).find("td:eq(6)"); // P&L now at index 6
                        let plValue = parseFloat(plCell.text().replace(/,/g, ""));
                        // remove any previous classes first to prevent stacking
                        $(this.node()).removeClass('row-positive row-negative');
                        if (!isNaN(plValue)) {
                            if (plValue < 0) {
                                $(this.node()).addClass("row-negative");
                            } else if (plValue > 0) {
                                $(this.node()).addClass("row-positive");
                            }
                        }
                    });
                    addTotalsRow(); 
                });

      // --- Auto-refresh DataTable every 10 seconds and highlight only numeric changes ---
// setInterval(function() {
//     let oldData = {};

//     // Build snapshot keyed by stable ID (prefer data-draw-detail-id, fallback to Time text)
//     table.rows().every(function () {
//         let node = $(this.node());
//         // prefer stable id attribute if present on row or a button inside row
//         let id = node.data('draw-detail-id') || node.find('[data-draw-detail-id]').data('draw-detail-id') || node.find("td:eq(1)").text().trim();
//         if (!id) return;

//         // capture only the columns we want to compare numerically
//         // adjust indices if your columns change; these follow your comment mapping:
//         // 2 -> TQ, 3 -> T Amt, 4 -> Claim, 5 -> Camt, 6 -> P&L
//         const numericCols = [2,3,4,5,6];

//         oldData[id] = {};
//         numericCols.forEach(idx => {
//             let text = node.find("td").eq(idx).text().trim().replace(/,/g, "");
//             let num = parseFloat(text);
//             // store either a real number or null (so non-numeric won't be compared)
//             oldData[id][idx] = isNaN(num) ? null : num;
//         });
//     });

//     // Reload via DataTables' ajax reload and compare after reload finishes
//     table.ajax.reload(function () {
//         table.rows().every(function () {
//             let rowNode = $(this.node());
//             // skip totals/footers or artificially appended rows
//             if (rowNode.attr('id') === 'totals-row') return;

//             let id = rowNode.data('draw-detail-id') || rowNode.find('[data-draw-detail-id]').data('draw-detail-id') || rowNode.find("td:eq(1)").text().trim();
//             if (!id) return;

//             // numeric columns to check
//             const numericCols = [2,3,4,5,6];

//             // if we had previous snapshot for this id compare numeric columns only
//             let prev = oldData[id];
//             if (prev) {
//                 numericCols.forEach(idx => {
//                     let cell = rowNode.find("td").eq(idx);
//                     let newText = cell.text().trim().replace(/,/g, "");
//                     let newNum = parseFloat(newText);
//                     let oldNum = prev[idx];

//                     // only act when both oldNum and newNum are valid numbers and are different
//                     if (oldNum !== null && !isNaN(newNum)) {
//                         if (newNum > oldNum) {
//                             cell.addClass("flash-green");
//                             setTimeout(() => cell.removeClass("flash-green"), 2000);
//                         } else if (newNum < oldNum) {
//                             cell.addClass("flash-red");
//                             setTimeout(() => cell.removeClass("flash-red"), 2000);
//                         }
//                         // if equal, do nothing
//                     }
//                     // if previously non-numeric or new is non-numeric -> do nothing (no flash)
//                 });
//             } else {
//                 // New row (no previous snapshot) — if you want to highlight new rows, do it here.
//                 // For your request (no effect on every refresh) we will NOT flash new rows.
//                 // If you do want a subtle highlight for new rows, uncomment:
//                 // rowNode.addClass("flash-green"); setTimeout(()=>rowNode.removeClass("flash-green"),2000);
//             }
//         });

//         // update timestamp
//         let stampEl = document.getElementById("drawDetailsUpdatedAt");
//         if (stampEl) {
//             stampEl.innerText = new Date().toLocaleTimeString();
//         }
//     }, false);

// }, 10000);

//             }


 // 🔥 Refresh DataTable instantly when Livewire dispatches ticketSubmitted
                if (typeof Livewire !== 'undefined') {
                    Livewire.on("ticketSubmitted", () => {
                        table.ajax.reload(null, false);
                        updateTimestamp();
                    });
                }

                // 🔥 Also catch DOM event 'ticket-submitted'
                window.addEventListener("ticket-submitted", () => {
                    table.ajax.reload(null, false);
                    updateTimestamp();
                });

                function updateTimestamp() {
                    let stampEl = document.getElementById("drawDetailsUpdatedAt");
                    if (stampEl) {
                        stampEl.innerText = new Date().toLocaleTimeString();
                    }
                }
            }


            // OTP + Claim Button logic (unchanged)
            $(document).on("input", ".otp-input", function () {
                this.value = this.value.replace(/[^0-9]/g, "");
                if (this.value.length === 1) {
                    $(this).next(".otp-input").focus();
                }
            }).on("keydown", ".otp-input", function (e) {
                if (e.key === "Backspace" && this.value === "") {
                    $(this).prev(".otp-input").focus();
                }
            });

            // Claim button click -> dispatch Livewire event (expects data-draw-detail-id on the button)
            $(document).on("click", ".addClaim", function () {
                const claimId = $(this).data("draw-detail-id");
                if (typeof Livewire !== 'undefined' && Livewire.dispatch) {
                    Livewire.dispatch("claim-event", { "draw_details_id": claimId });
                } else {
                    console.warn('Livewire not available to dispatch claim-event');
                }
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
            });
    </script>
@endpush

<div class="container-fluid" style="font-size: larger; color: #222;">
    <h2>Dashboard</h2>
    <x-date-range-picker-filter />

    <div class="row mb-3">
        <div class="col-8">
            <div class="card">
                <div class="card-header text-center bg-info">
                    <h4 class="text-white">Draw's Details</h4>
                </div>
                <div class="card-body">
                    {{-- Renders the DataTable created by DashboardDrawDataTable --}}
                    {{ $dataTable->table() }}
                    <div class="text-muted small text-end mt-2">
                        Last updated: <span id="drawDetailsUpdatedAt">{{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-4">
            <livewire:dashboard-overview />
        </div>
    </div>

    @livewire('claim-add')
</div>
@endsection
