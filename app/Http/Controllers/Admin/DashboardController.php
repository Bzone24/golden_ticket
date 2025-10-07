<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\CrossAbDataTable;
use App\DataTables\CrossAcDataTable;
use App\DataTables\CrossBcDataTable;
use App\DataTables\DrawProfilLossDataTable;
use App\Http\Controllers\Controller;
use App\Models\DrawDetail;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB; 

class DashboardController extends Controller
{
  


public function index(DrawProfilLossDataTable $dataTable, Request $request)
{
    $today = Carbon::today('UTC');
    // use a plain date string for whereDate() to avoid surprises
    $today_1 = Carbon::today('Asia/Kolkata')->toDateString();

    $gameId = $request->input('game_id'); // selected game (if any)
    $games = \App\Models\Game::all();
    $authUser = auth()->user();

    // Build ticketQuery (tickets created today, optionally filtered by game and shopkeeper)
    $ticketQuery = Ticket::whereDate('created_at', $today);
    if ($authUser->hasRole('shopkeeper')) {
        $ticketQuery->whereHas('user', function ($q) use ($authUser) {
            $q->where('created_by', $authUser->id);
        });
    }
    if ($gameId) {
        $ticketQuery->where('game_id', $gameId);
    }

    // Build drawDetailQuery (draw details for today, optionally filtered by game)
    $drawDetailQuery = DrawDetail::whereDate('date', $today_1);
    if ($gameId) {
        $drawDetailQuery->where('game_id', $gameId);
    }

    // Dashboard overview numbers (existing behavior)
    $data = [
        'total_shopkeepers'   => User::count(),
        'total_tickets'       => $ticketQuery->count(),
        'total_claims'        => (clone $drawDetailQuery)->sum('claim'),
        'total_cross_amt'     => (clone $drawDetailQuery)->sum('total_cross_amt'),
        'total_cross_claim'   => (clone $drawDetailQuery)->sum('claim_ab')
                                   + (clone $drawDetailQuery)->sum('claim_ac')
                                   + (clone $drawDetailQuery)->sum('claim_bc'),
        'claimed'             => (clone $drawDetailQuery)->where('claim', '!=', 0)->count(),
    ];

    // ---------------------------
    // SERVER TOTALS (authoritative for the "TOTALS" footer)
    // ---------------------------

    // total TQ (sum of a_qty+b_qty+c_qty across ticket_options joined with tickets & draw_details)
    $tqTotal = DB::table('ticket_options as topt')
        ->join('tickets as t', 'topt.ticket_id', '=', 't.id')
        ->join('draw_details as dd', 'topt.draw_detail_id', '=', 'dd.id')
        ->whereDate('dd.date', $today_1)
        ->where('topt.voided', 0)
        ->whereNull('t.deleted_at')
        ->selectRaw('COALESCE(SUM(topt.a_qty + topt.b_qty + topt.c_qty), 0) as tq_total')
        ->value('tq_total');

    // total cross amount (sum of c.amount)
    $crossTotal = DB::table('cross_abc_details as c')
        ->join('tickets as t', 'c.ticket_id', '=', 't.id')
        ->join('draw_details as dd', 'c.draw_detail_id', '=', 'dd.id')
        ->whereDate('dd.date', $today_1)
        ->where('c.voided', 0)
        ->whereNull('t.deleted_at')
        ->selectRaw('COALESCE(SUM(c.amount), 0) as cross_total')
        ->value('cross_total');

    // total claims from draw_details (claim field is likely in 'units' so you previously multiplied by 100 in P&L)
    $totalClaimUnits = (clone $drawDetailQuery)->sum('claim'); // units, multiply later if needed

    // cross claim units (sum of claim_ab, claim_ac, claim_bc)
    $crossClaimUnits = (clone $drawDetailQuery)->sum('claim_ab')
                    + (clone $drawDetailQuery)->sum('claim_ac')
                    + (clone $drawDetailQuery)->sum('claim_bc');

    // business multipliers used in your app (keep consistent with existing logic)
    $rewardMultiplier = 11;   // e.g. reward per qty
    $claimMultiplier  = 100;  // e.g. claim unit -> amount

    $totalRewards = ((int) $tqTotal) * $rewardMultiplier;            // total reward amount
    $totalClaimAmount = ((int) $totalClaimUnits) * $claimMultiplier;  // total claim amount
    $totalCrossClaimAmount = ((int) $crossClaimUnits) * $claimMultiplier;

    $pAndLTotal = $totalRewards - $totalClaimAmount + (float) $crossTotal - $totalCrossClaimAmount;

    $tableTotals = [
        'tq'         => (int) $tqTotal,
        't_amt'      => (int) $totalRewards,
        'claim'      => (int) $totalClaimUnits,
        'claim_amt'  => (int) $totalClaimAmount,
        'cross'      => (float) $crossTotal,
        'cross_claim_units' => (int) $crossClaimUnits,
        'cross_claim_amt'   => (int) $totalCrossClaimAmount,
        'p_and_l'    => (float) $pAndLTotal,
    ];

    // render DataTable and pass totals into view
    return $dataTable->render('admin.dashboard.index', compact('data', 'games', 'gameId', 'tableTotals'));
}

  public function crossAbc(
    CrossAbDataTable $dataTable,
    CrossAcDataTable $crossAcDataTable,
    CrossBcDataTable $crossBcDataTable,
    Request $request
) {
    $drawDetail = DrawDetail::findOrFail(
        $request->get('draw_detail_id') ?? $request->get('drawDetail')
    );

    $user = User::find($request->get('user_id')) ?? null;

    // Pass draw_detail_id into all three DataTables
    $abTable = $dataTable->with('draw_detail_id', $drawDetail->id);
    $acTable = $crossAcDataTable->with('draw_detail_id', $drawDetail->id);
    $bcTable = $crossBcDataTable->with('draw_detail_id', $drawDetail->id);

    if ($user) {
        $abTable->setUserId($user->id);
    }

    return $abTable->render('admin.dashboard.cross-abc-details', [
        'drawDetail'       => $drawDetail,
        'crossAcDataTable' => $acTable->html(),
        'crossBcDataTable' => $bcTable->html(),
        'user'             => $user,
    ]);
}



public function getCrossAbList(CrossAbDataTable $dataTable, Request $request)
{
    $drawId = $request->get('draw_detail_id') ?? $request->get('drawDetail');
    return $dataTable->with('draw_detail_id', $drawId)->ajax();
}
    

    public function getCrossAcList(CrossAcDataTable $crossAcDataTable, CrossBcDataTable $crossBcDataTable)
    {

        return $crossAcDataTable->render('admin.dashboard.cross-abc-details', compact('crossBcDataTable'));
    }

    public function getCrossBcList(CrossAcDataTable $crossAcDataTable, CrossBcDataTable $crossBcDataTable)
    {
        return $crossBcDataTable->render('admin.dashboard.cross-abc-details', compact('crossAcDataTable'));
    }

    public function totalQtyDetailList(DrawDetail $drawDetail, Request $request)
    {
        $user = $request->user_id ? User::findOrfail($request->user_id) : null;
        // if(!$user){
        //     return redirect()->back()->with('error','User not found');
        // }
        return view('admin.dashboard.total-qty-details-table', compact('drawDetail', 'user'));
    }
}
