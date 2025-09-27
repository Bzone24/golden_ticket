<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\DrawDataTable;
use App\DataTables\Admin\DrawDetailsDataTable;
use App\DataTables\Admin\NumberTicketListDataTable;
use App\DataTables\Admin\ShopKeeperDrawDetailsDataTable;
use App\DataTables\Admin\TicketDetailsDataTable;
// use App\DataTables\Admin\CrossTicketDetailsDataTable;
use App\DataTables\Admin\CrossTicketDataTable;
// use App\DataTables\DrawProfilLossDataTable;
use App\Http\Controllers\Controller;
use App\Models\CrossAbc;
use App\Models\Draw;
use App\Models\DrawDetail;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;

class DrawController extends Controller
{
    // public function index(DrawDataTable $dataTable)
    // {
    //     return $dataTable->render('admin.draw.index');
    //     // return view('admin.draw.index');
    // }


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

    public function shopKeeperDrawDetails(ShopKeeperDrawDetailsDataTable $dataTable, DrawDetail $drawDetail, User $user)
    {

        return $dataTable->render('admin.draw.shopkeeper-draw-details', compact('drawDetail', 'user'));
        // return view('admin.draw.shopkeeper-draw-details');
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
        $ticket_id = $ticket->id;
        $userId = $user->id;
        $drawDetailId = $drawDetail->id;

        $query = CrossAbc::where('user_id', $userId) 
            ->where('ticket_id', $ticket_id)
            ->whereJsonContains('draw_details_ids', $drawDetailId)
            ->where('user_id', $userId);

        return (new EloquentDataTable($query))
            ->toJson();
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

    // public function userDrawList(DrawProfilLossDataTable $dataTable, Request $request)
    // {
    //     $today = Carbon::today('Asia/Kolkata');

    //     $drawQuery = DrawDetail::whereDate('date', $today)
    //         ->when($request->input('game_id'), function ($q) use ($request) {
    //             $q->where('game_id', $request->input('game_id'));
    //         });

    //     $total_available_draws = $drawQuery->count();

    //     $games = \App\Models\Game::all();
    //     $currentGame = $request->input('game_id');

    //     // ✅ Pass $dataTable properly
    //     return $dataTable->render('web.dashboard.index', compact(
    //         'total_available_draws',
    //         'games',
    //         'currentGame'
    //     ));
    // }
}
