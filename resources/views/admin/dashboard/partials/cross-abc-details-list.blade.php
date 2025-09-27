@php
    $ab_claim = (int) $drawDetail->claim_ab ?? 0;
    $ac_claim = (int) $drawDetail->claim_ac ?? 0;
    $bc_claim = (int) $drawDetail->claim_bc ?? 0;

    $ab_pl = (int) $drawDetail->totalAbAmt() - $ab_claim * 100;
    $ac_pl = (int) $drawDetail->totalAcAmt() - $ac_claim * 100;
    $bc_pl = (int) $drawDetail->totalBcAmt() - $bc_claim * 100;
@endphp

<table class="table table-bordered mb-0 align-middle text-center custom-table">
    <thead>
        <tr>
            <th>Option</th>
            <th>Cross Amt</th>
            <th>Claim</th>
            <th>P&L <small>(Cross Amt – Claim × 100)</small></th>
            <th>Result</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="option-cell bg-ab">AB</td>
            <td><b>{{ $drawDetail->totalAbAmt() }}</b></td>
            <td class="claim-cell">{{ $ab_claim }}</td>
            <td class="{{ $ab_pl >= 0 ? 'pl-profit' : 'pl-loss' }}">{{ $ab_pl }}</td>
            <td><b>{{ $drawDetail->ab ?? 'N/A' }}</b></td>
        </tr>
        <tr>
            <td class="option-cell bg-bc">BC</td>
            <td><b>{{ $drawDetail->totalBcAmt() }}</b></td>
            <td class="claim-cell">{{ $bc_claim }}</td>
            <td class="{{ $bc_pl >= 0 ? 'pl-profit' : 'pl-loss' }}">{{ $bc_pl }}</td>
            <td><b>{{ $drawDetail->bc ?? 'N/A' }}</b></td>
        </tr>
        <tr>
            <td class="option-cell bg-ac">AC</td>
            <td><b>{{ $drawDetail->totalAcAmt() }}</b></td>
            <td class="claim-cell">{{ $ac_claim }}</td>
            <td class="{{ $ac_pl >= 0 ? 'pl-profit' : 'pl-loss' }}">{{ $ac_pl }}</td>
            <td><b>{{ $drawDetail->ac ?? 'N/A' }}</b></td>
        </tr>
        <tr class="total-row">
            <td>Total</td>
            <td>{{ $drawDetail->totalAbAmt() + $drawDetail->totalAcAmt() + $drawDetail->totalBcAmt() }}</td>
            <td class="claim-cell">{{ $ab_claim + $ac_claim + $bc_claim }}</td>
            <td>{{ $ab_pl + $ac_pl + $bc_pl }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

@push('custom-css')
<style>
    /* Table Header */
    .custom-table thead {
        background: linear-gradient(90deg,#6a11cb,#2575fc) !important;
        color: #fff;
        font-weight: bold;
        font-size: 1rem;
    }
    .custom-table thead th {
        background: linear-gradient(90deg, #022098, #022098) !important;
        color: #fff !important;
        font-weight: 700 !important;
        font-size: 1rem !important;
        text-transform: uppercase;
        text-align: center;
    }

    .custom-header {
        background: linear-gradient(90deg, #007bff, #6610f2) !important;
        border-radius: 12px 12px 0 0 !important;
    }

    /* Option Row Colors */
    .option-cell {
        font-weight: bold;
        color: #1b1b29 !important;
        text-align: center;
    }
    .bg-ab { background: #198754 !important; } /* Green */
    .bg-bc { background: #0d6efd !important; } /* Blue */
    .bg-ac { background: #ffc107 !important; color:#000 !important; } /* Yellow */

    /* Claim Cells */
    .claim-cell {
        font-weight: bold;
        color: red !important;
    }

    /* P&L Cells */
    .pl-profit {
        background: #28a745 !important;
        color: #fff !important;
        font-weight: bold;
    }
    .pl-loss {
        background: #dc3545 !important;
        color: #fff !important;
        font-weight: bold;
    }

    /* Total Row */
    .total-row {
        background: #f8f9fa !important;
        font-weight: bold;
    }

    /* Fix Hover Override */
    .custom-table tbody tr:hover td {
        background: lightcoral !important;
        color: inherit !important;
    }
</style>
@endpush
