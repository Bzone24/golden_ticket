<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        return view('web.ticket.ticket-list');
    }

    public function addTicket(Request $request)
    {
        $ticket = null;
        $number = $request->number ?: null;

        if ($request->ticket_id) {
            $ticket = Ticket::where('id', $request->ticket_id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();
        }

        // 🔹 Pass games list to the view for dropdown
        $games = \App\Models\Game::all();

        return view('web.ticket.add-ticket', compact('ticket', 'number', 'games'));
    }
}
