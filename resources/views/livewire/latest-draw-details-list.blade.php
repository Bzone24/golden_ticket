<div class="card shadow-lg border-0 rounded-3 bg-dark text-light">
    <!-- Header -->
    <div class="card-header bg-gradient bg-primary text-white rounded-top">
        <h4 class="mb-0"><i class="bi bi-clock-history me-2"></i> Draw Details</h4>
    </div>

    <!-- Body -->
    <div class="card-body p-3">
        <div class="table-responsive latest-draw-list bg-secondary bg-opacity-10 rounded p-2"
             style="max-height: 150px; overflow-y: auto;">
            <table class="table table-dark table-bordered table-striped table-hover align-middle mb-0">
                <thead class="bg-secondary text-white position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th>Time</th>
                        <th>TQ</th>
                        <th>TC</th>
                        <th>C.Amt.</th>
                        <th>CC</th>
                        <th>PL</th>
                    </tr>
                </thead>
    <tbody>
@forelse (collect($latest_draw_list)->sortByDesc('end_time') as $draw_detail)
    @php
        $userId = auth()->user()->id;

        // TQ = total qty from ticketOptions
        $tq = $draw_detail->ticketOptions()
            ->where('user_id', $userId)
            ->sum(\DB::raw('a_qty + b_qty + c_qty'));

        // Ticket Claim (TC) → from draw_details.claim column (not ticket_options)
        $tc = $draw_detail->claim ?? 0;

        // Cross Amount (C.Amt) → sum of crossAbcDetail.amount
        $crossAmt = $draw_detail->crossAbcDetail()
            ->where('user_id', $userId)
            ->sum('amount');

        // Cross Claim (CC) → from draw_details.claim_ab + claim_bc + claim_ac
        $cc = ($draw_detail->claim_ab ?? 0)
            + ($draw_detail->claim_ac ?? 0)
            + ($draw_detail->claim_bc ?? 0);

        // Profit & Loss (PL) same as dashboard logic
        $pl = ($tc + $cc) - ($tq * \App\Models\DrawDetail::PRICE);
    @endphp

    <tr>
        <td>{{ $draw_detail->formatResultTime() }}</td>
        <td>{{ $tq }}</td>
        <td>{{ $tc }}</td>
        <td>{{ $crossAmt }}</td>
        <td>{{ $cc }}</td>
        <td class="{{ $pl >= 0 ? 'text-success' : 'text-danger' }}">
            {{ $pl }}
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="text-center text-muted">No records found.</td>
    </tr>
@endforelse
</tbody>


            </table>
        </div>
    </div>
</div>
