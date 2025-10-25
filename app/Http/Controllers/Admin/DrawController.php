<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\DrawDataTable;
use App\DataTables\Admin\DrawDetailsDataTable;
use App\DataTables\Admin\NumberTicketListDataTable;
use App\DataTables\Admin\ShopKeeperDrawDetailsDataTable;
use App\DataTables\Admin\TicketDetailsDataTable;
use App\DataTables\Admin\CrossTicketDataTable;
use App\Http\Controllers\Controller;
use App\Models\CrossAbc;
use App\Models\Draw;
use App\Models\DrawDetail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\TicketOption;
use App\Models\CrossAbcDetail;
use DataTables;

class DrawController extends Controller
{
    


    public function index(DrawDataTable $dataTable, Request $request)
    {
        $gameId = $request->input('game_id');
        $games = \App\Models\Game::all();

        if ($gameId) {
            $dataTable->with('game_id', $gameId);
        }

        return $dataTable->render('admin.draw.index', compact('games', 'gameId'));
    }


    public function drawDetails(DrawDetailsDataTable $dataTable, DrawDetail $drawDetail)
    {

        return $dataTable->render('admin.draw.draw-details-table', compact('drawDetail'));
        // return view('admin.draw.draw-details-table');
    }

    public function ticketModal(DrawDetail $drawDetail, Ticket $ticket, User $user)
{
   
    return view('admin.draw.partials.ticket-modal', compact('drawDetail', 'ticket', 'user'));
}

    public function shopKeeperDrawDetails(ShopKeeperDrawDetailsDataTable $dataTable, DrawDetail $drawDetail, User $user)
    {

        return $dataTable->render('admin.draw.shopkeeper-draw-details', compact('drawDetail', 'user'));
        // return view('admin.draw.shopkeeper-draw-details');
    }

    public function claimDetails(Request $request, $drawDetailId)
    {
        try {
            $type = $request->get('type', 'claim');
            $clickedValue = $request->get('value', null); // unused for cross_claim but kept for compatibility

            $drawDetail = \App\Models\DrawDetail::find($drawDetailId);
            if (!$drawDetail) {
                return response()->json(['data' => [], 'error' => 'Draw detail not found'], 404);
            }

            // Common: collect ticket ids referenced by ticket_options or cross_abc_details for this draw_detail
            $ticketOptionIds = TicketOption::query()
                ->where('draw_detail_id', $drawDetailId)
                ->where('voided', 0)
                ->pluck('ticket_id');

            $crossIds = CrossAbcDetail::query()
                ->where('draw_detail_id', $drawDetailId)
                ->where('voided', 0)
                ->pluck('ticket_id');

            $ids = $ticketOptionIds->merge($crossIds)->unique()->filter()->values()->all();
            if (empty($ids)) {
                return response()->json(['data' => []]);
            }

            // Load tickets we might show
            $tickets = Ticket::whereIn('id', $ids)
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc')
                ->get();

            $aClaim = $drawDetail->claim_a;
            $bClaim = $drawDetail->claim_b;
            $cClaim = $drawDetail->claim_c;

            $abWin = $drawDetail->ab ?? null;
            $acWin = $drawDetail->ac ?? null;
            $bcWin = $drawDetail->bc ?? null;

            $results = $tickets->map(function ($t) use ($drawDetailId, $aClaim, $bClaim, $cClaim, $abWin, $acWin, $bcWin) {
                $ticketId = $t->id;

                // --- TQ: sum of a_qty+b_qty+c_qty for this ticket & draw_detail ---
                $tq = (int) TicketOption::where('ticket_id', $ticketId)
                    ->where('draw_detail_id', $drawDetailId)
                    ->where('voided', 0)
                    ->select(DB::raw('COALESCE(SUM(CAST(a_qty AS SIGNED) + CAST(b_qty AS SIGNED) + CAST(c_qty AS SIGNED)),0) as total'))
                    ->value('total');

                // --- cross_amt: total cross money for ticket & draw_detail (all types/numbers) ---
                $crossAmt = (float) CrossAbcDetail::where('ticket_id', $ticketId)
                    ->where('draw_detail_id', $drawDetailId)
                    ->where('voided', 0)
                    ->select(DB::raw('COALESCE(SUM(CAST(amount AS DECIMAL(14,2))),0) as total'))
                    ->value('total');

                // --- For claim total per ticket (units) we replicate getClaim() logic but scoped to this ticket ---
                $totals = TicketOption::query()
                    ->where('ticket_id', $ticketId)
                    ->where('draw_detail_id', $drawDetailId)
                    ->selectRaw("
                        SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                        SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                        SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                    ", [$aClaim, $bClaim, $cClaim])
                    ->first();

                $a_qty = (int) ($totals->a_total ?? 0);
                $b_qty = (int) ($totals->b_total ?? 0);
                $c_qty = (int) ($totals->c_total ?? 0);
                $claimTotal = $a_qty + $b_qty + $c_qty; // units

                // --- crossClaimAmount: sum(amount) for matching winning AB/AC/BC combos for this ticket (money) ---
                $crossClaimAmount = (float) CrossAbcDetail::where('ticket_id', $ticketId)
                    ->where('draw_detail_id', $drawDetailId)
                    ->where('voided', 0)
                    ->where(function ($q) use ($abWin, $acWin, $bcWin) {
                        if ($abWin !== null && $abWin !== '') {
                            $q->orWhere(function ($q2) use ($abWin) { $q2->where('number', $abWin)->where('type', 'AB'); });
                        }
                        if ($acWin !== null && $acWin !== '') {
                            $q->orWhere(function ($q2) use ($acWin) { $q2->where('number', $acWin)->where('type', 'AC'); });
                        }
                        if ($bcWin !== null && $bcWin !== '') {
                            $q->orWhere(function ($q2) use ($bcWin) { $q2->where('number', $bcWin)->where('type', 'BC'); });
                        }
                    })
                    ->select(DB::raw('COALESCE(SUM(CAST(amount AS DECIMAL(14,2))),0) as total'))
                    ->value('total');

                // --- crossClaimUnits: compute units (prefer explicit units column, otherwise infer from money/100) ---
                $crossClaimUnits = 0.0;
                if (\Schema::hasColumn('cross_abc_details', 'units')) {
                    $crossClaimUnits = (float) CrossAbcDetail::where('ticket_id', $ticketId)
                        ->where('draw_detail_id', $drawDetailId)
                        ->where('voided', 0)
                        ->where(function ($q) use ($abWin, $acWin, $bcWin) {
                            if ($abWin !== null && $abWin !== '') {
                                $q->orWhere(function ($q2) use ($abWin) { $q2->where('number', $abWin)->where('type','AB'); });
                            }
                            if ($acWin !== null && $acWin !== '') {
                                $q->orWhere(function ($q2) use ($acWin) { $q2->where('number', $acWin)->where('type','AC'); });
                            }
                            if ($bcWin !== null && $bcWin !== '') {
                                $q->orWhere(function ($q2) use ($bcWin) { $q2->where('number', $bcWin)->where('type','BC'); });
                            }
                        })
                        ->sum('units');
                } else {
                    // fallback: infer units from money (1 unit = 100 money)
                    $crossClaimUnits = $crossClaimAmount;
                }

                // --- compute P&L using the same helper as DataTable with integer unit counts ---
                $t_amt = $tq * 11;
                $claimUnitsInt = (int) round($claimTotal);
                $crossClaimUnitsInt = (int) round($crossClaimUnits);

                if (method_exists($this, 'calculateProfitAndLoss')) {
                    $p_and_l = $this->calculateProfitAndLoss($t_amt, $crossAmt, $claimUnitsInt, $crossClaimUnitsInt);
                } else {
                    $tmp = $t_amt - ($claimUnitsInt * 100) + $crossAmt - ($crossClaimUnitsInt * 70);
                    $p_and_l = is_float($tmp) && floor($tmp) != $tmp ? round($tmp, 2) : (int) $tmp;
                }

                // format/display values (same logic as DataTable)
                $p_and_l_output = $p_and_l == 0 ? 0 : $p_and_l;
                $p_and_l_display = $p_and_l_output == 0 ? null
                    : (is_float($p_and_l_output) && floor($p_and_l_output) != $p_and_l_output ? number_format($p_and_l_output, 2) : number_format((int) $p_and_l_output));
                $bgClass = $p_and_l_output < 0 ? 'bg-danger text-white' : ($p_and_l_output > 0 ? 'bg-success text-white' : 'text-dark');

                // username fallback via relation (users.full_name / username / login_id)
                $username = '';
                try {
                    if (method_exists($t, 'user')) {
                        $u = $t->user()->first();
                        $username = $u ? ($u->full_name ?? $u->username ?? $u->login_id ?? '') : '';
                    }
                } catch (\Throwable $e) { $username = ''; }

               return [
    'id' => $ticketId,
    'ticket_id' => $ticketId, // required by front-end
    'user_id' => $t->user_id ?? ($t->user ? $t->user->id ?? null : null),
    'draw_detail_id' => $drawDetailId,
    'username' => $username,
    'ticket_number' => data_get($t, 'ticket_number') ?? data_get($t, 'full_ticket_no') ?? '',
    'tq' => $tq,
    'claim' => $claimTotal,
    'cross_amt' => $crossAmt,
    'cross_claim' => $crossClaimAmount,
    'p_and_l' => $p_and_l_output,
    'p_and_l_display' => $p_and_l_display,
    'p_and_l_class' => $bgClass,
];
            });


            // Filtering per popup type
            if ($type === 'claim') {
                // only tickets that have claim units > 0 (these contributed to "claim" total)
                $filtered = $results->filter(fn($r) => $r['claim'] > 0)->values();
                return response()->json(['data' => $filtered]);
            }

            if ($type === 'cross_claim') {
                // only tickets that had crossClaimAmount > 0 or cross_amt > 0 (contributed cross money)
                $filtered = $results->filter(fn($r) => ($r['cross_claim'] > 0) || ($r['cross_amt'] > 0))->values();
                return response()->json(['data' => $filtered]);
            }

            // Fallback
            return response()->json(['data' => []]);
        } catch (\Throwable $e) {
            Log::error('claimDetails error for drawDetailId='.$drawDetailId.' : '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['data' => [], 'error' => 'Server error while fetching claim details'], 500);
        }
    }

    public function numberList(NumberTicketListDataTable $dataTable, Request $request)
    {
        $draw = $this->findDraw($request->draw_id);
        $number = $request->number;

        return $dataTable->render('admin.draw.number-list', compact('draw', 'number'));

        // return view('admin.draw.number-list', compact('draw', 'number'));
    }

    public function ticketDetailsList(TicketDetailsDataTable $dataTable, DrawDetail $drawDetail, Ticket $ticket, User $user)
    {
        return $dataTable->render('admin.draw.ticket-details-list',[
            'drawDetail'=>$drawDetail,
            'ticket'=>$ticket,
            'user'=>$user,
        ]);
        // return view('admin.draw.ticket-details-list');
    }

public function getCrossDataTable(Request $request, DrawDetail $drawDetail, Ticket $ticket, User $user)
{
    $ticket_id    = $ticket->id;
    $userId       = $user->id;
    $drawDetailId = $drawDetail->id;

    // Grouped query
    $query = CrossAbcDetail::query()
        ->select([
            'option',
            'type',
            DB::raw('GROUP_CONCAT(DISTINCT number ORDER BY number ASC SEPARATOR ", ") as numbers'),
            DB::raw('MAX(combination) as combination'),
            DB::raw('MAX(amount) as amount')
        ])
        ->where('user_id', $userId)
        ->where('ticket_id', $ticket_id)
        ->where('draw_detail_id', $drawDetailId)
        ->where('voided', 0)
        ->groupBy('option', 'type');

    return DataTables::of($query)
        ->addColumn('option', fn($r) => strtoupper($r->option ?? ''))
        ->addColumn('type', fn($r) => strtoupper($r->type ?? ''))
        ->addColumn('numbers', fn($r) => $r->numbers)
        ->addColumn('combination', fn($r) => $r->combination)
        ->addColumn('amount', fn($r) => $r->amount)
        ->make(true);
}




    private function findDraw($draw_id)
    {
        return Draw::findOrFail($draw_id);
    }

    public function addDraw(Request $request)
    {
        $draw = null;
        if ($request->draw_id) {
            $draw = $this->findDraw($request->draw_id);
        }

        return view('admin.draw.add-draw', compact('draw'));
    }

    
}
