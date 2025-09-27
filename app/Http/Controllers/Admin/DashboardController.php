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

class DashboardController extends Controller
{
   


    public function index(DrawProfilLossDataTable $dataTable, Request $request)
    {
        $today = Carbon::today('UTC');
        $today_1 = Carbon::today('Asia/Kolkata');

        $gameId = $request->input('game_id'); // 🔹 Get selected game
        $games = \App\Models\Game::all();
        $authUser = auth()->user();
        $ticketQuery = Ticket::whereDate('created_at', $today);
        if ($authUser->hasRole('shopkeeper')) {
            $ticketQuery->whereHas('user', function ($q) use ($authUser) {
                $q->where('created_by', $authUser->id);
            });
        }

        $drawDetailQuery = DrawDetail::where('date', $today_1);

        if ($gameId) {
            $ticketQuery->where('game_id', $gameId);
            $drawDetailQuery->where('game_id', $gameId);
        }

        $data = [
            'total_shopkeepers' => User::count(),
            'total_tickets' => $ticketQuery->count(),
            'total_claims' => (clone $drawDetailQuery)->sum('claim'),
            'total_cross_amt' => (clone $drawDetailQuery)->sum('total_cross_amt'),
            'total_cross_claim' => (clone $drawDetailQuery)->sum('claim_ab')
                + (clone $drawDetailQuery)->sum('claim_ac')
                + (clone $drawDetailQuery)->sum('claim_bc'),
            'claimed' => (clone $drawDetailQuery)->where('claim', '!=', 0)->count(),
        ];

        return $dataTable->render('admin.dashboard.index', compact('data', 'games', 'gameId'));
    }


    public function crossAbc(CrossAbDataTable $dataTable, CrossAcDataTable $crossAcDataTable, CrossBcDataTable $crossBcDataTable, Request $request)
    {
        $drawDetail = DrawDetail::findOrFail($request->get('draw_detail_id'));

        return $dataTable->render('admin.dashboard.cross-abc-details', [
            'drawDetail' => $drawDetail,
            'crossAcDataTable' => $crossAcDataTable->html(),
            'crossBcDataTable' => $crossBcDataTable->html(),
        ]);
    }

    public function getCrossAcList(CrossAcDataTable $crossAcDataTable, CrossBcDataTable $crossBcDataTable)
    {

        return $crossAcDataTable->render('admin.dashboard.cross-abc-details', compact('crossBcDataTable'));
    }

    public function getCrossBcList(CrossAcDataTable $crossAcDataTable, CrossBcDataTable $crossBcDataTable)
    {
        return $crossBcDataTable->render('admin.dashboard.cross-abc-details', compact('crossAcDataTable'));
    }

    public function totalQtyDetailList(DrawDetail $drawDetail)
{
    // get draws for the same game for today (ordered by start_time)
    $draw_list = DrawDetail::where('game_id', $drawDetail->game_id)
        ->whereDate('created_at', Carbon::today())
        ->orderBy('start_time')
        ->get();

    // If none found, prepare fallback start/end times and interval
    $start_time = null;
    $end_time = null;
    $interval_minutes = 15;

    if ($draw_list->isEmpty()) {
        try {
            $start_time = Carbon::parse($drawDetail->start_time)->subHour()->format('H:i');
            $end_time   = Carbon::parse($drawDetail->end_time)->addHour()->format('H:i');
        } catch (\Exception $e) {
            $start_time = '09:00';
            $end_time   = '10:15';
        }
    }

    return view('admin.dashboard.total-qty-details-table', compact(
        'drawDetail', 'draw_list', 'start_time', 'end_time', 'interval_minutes'
    ));
}
}
