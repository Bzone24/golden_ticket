@extends('admin.layouts.base')
@section('title', 'Cross ABC Details')
@section('contents')

@push('custom-css')
<style>
    body {
        background: #f8fafc !important;
        font-size: 1.05rem !important;
        font-weight: 500 !important;
        color: #15171a !important;
    }

    h4 {
        font-weight: 700;
        font-size: 1.3rem;
    }

    .card {
        border-radius: 14px !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
        overflow: hidden;
    }

    .card-header {
        font-weight: bold;
        font-size: 1.2rem;
        padding: 15px;
        border-radius: 14px 14px 0 0 !important;
    }

    /* Table design */
    table.table {
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }

    table thead th {
        background: #121315;
        color: #e70606 !important;
        text-transform: uppercase;
        font-size: 0.95rem;
        padding: 12px;
        text-align: center;
    }

    table tbody td {
        text-align: center;
        font-weight: 600;
        vertical-align: middle;
        padding: 10px;
    }

   /* Force custom background for thead row */
   .table thead tr {
       background-color: #1e3a8a !important;
   }

   .table thead th {
       background-color: #1e3a8a !important;
       color: #ffffff !important;
       font-weight: 700 !important;
       text-transform: uppercase;
       text-align: center;
   }

    /* Row Highlighting */
    tbody tr:hover {
        background: #f1f5f9 !important;
        transition: 0.3s;
    }

    /* Option Labels */
    td.bg-success,
    td.bg-warning,
    td.bg-info {
        font-weight: bold;
        font-size: 1.1rem;
    }

    td.option-a {
        background: #166534 !important;
        color: #fff !important;
        font-weight: bold !important;
    }
    td.option-b {
        background: #b45309 !important;
        color: #fff !important;
        font-weight: bold !important;
    }
    td.option-c {
        background: #1e40af !important;
        color: #fff !important;
        font-weight: bold !important;
    }

    /* Claim Cell */
    td.bg-danger {
        font-weight: bold;
        font-size: 1rem;
    }

    td.bg-success.text-white {
        background-color: #28a745 !important;
        font-weight: 700;
        border-radius: 6px;
    }

    td.bg-danger.text-white {
        background-color: #dc3545 !important;
        font-weight: 700;
        border-radius: 6px;
    }

    tbody tr:last-child {
        background: #f0f4f8;
        font-size: 1.1rem;
        font-weight: bold;
    }
    tbody tr:last-child td {
        padding: 12px;
    }

    /* ✅ Flash effect */
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

</style>
@endpush

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">
                Details of Total Qty: {{ $drawDetail->total_qty }} {{ $drawDetail->formatEndTime() }}
            </li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h4 class="text-white mb-0 d-flex justify-content-between w-100">
                        {{$user->name ?? ''}}
                    <h4>
                    <h4 class="text-white mb-0 d-flex justify-content-between w-100">
                        <span>Details Of Total Qty (Time: {{ $drawDetail->formatEndTime() }})</span>
                        <span>Game: {{$drawDetail->draw->game->name}}</span>
                    </h4>
                </div> 

                {{-- ⬇️ Table container (auto-refreshes) --}}
                <div class="card-body" id="crossAbcTableContainer">
                    @include('admin.dashboard.partials.total-qty-details-table', ['drawDetail' => $drawDetail,'userId'=>$userId ?? null])
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Auto Refresh + highlight changes --}}
{{-- Auto Refresh + highlight changes --}}
@push('custom-js')
<script>
    setInterval(function () {
        // grab old values before reload
        let oldValues = [];
        $("#crossAbcTableContainer table tbody tr").each(function () {
            let rowVals = [];
            $(this).find("td").each(function () {
                rowVals.push($(this).text().trim());
            });
            oldValues.push(rowVals);
        });

        // reload
        $("#crossAbcTableContainer").load(window.location.href + " #crossAbcTableContainer>*", function () {
            // after reload, compare new values to old ones
            $("#crossAbcTableContainer table tbody tr").each(function (rowIndex) {
                $(this).find("td").each(function (colIndex) {
                    let newVal = $(this).text().trim();
                    let oldVal = (oldValues[rowIndex] ?? [])[colIndex];

                    if (newVal !== oldVal && oldVal !== undefined && newVal !== "") {
                        let newNum = parseFloat(newVal.replace(/,/g, ""));
                        let oldNum = parseFloat(oldVal.replace(/,/g, ""));

                        if (!isNaN(newNum) && !isNaN(oldNum)) {
                            if (newNum > oldNum) {
                                $(this).addClass("flash-green");
                                setTimeout(() => $(this).removeClass("flash-green"), 2000);
                            } else if (newNum < oldNum) {
                                $(this).addClass("flash-red");
                                setTimeout(() => $(this).removeClass("flash-red"), 2000);
                            }
                        } else {
                            // fallback for text changes (non-numeric)
                            $(this).addClass("flash-green");
                            setTimeout(() => $(this).removeClass("flash-green"), 2000);
                        }
                    }
                });
            });
        });
    }, 10000); // refresh every 10s
</script>
@endpush


@endsection
