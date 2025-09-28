<?php

namespace App\Livewire;

use App\Models\DrawDetail;
use App\Models\User;
use App\Traits\TicketForm\CrossAbcOperation;
use App\Traits\TicketForm\TicketFormAction;
use App\Traits\TicketForm\TicketFormPagination;
use Illuminate\Http\Request;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class AddTicketForm extends Component
{
    use CrossAbcOperation, TicketFormAction, TicketFormPagination, WithPagination;

    protected $paginationTheme = 'bootstrap'; // For Bootstrap 5

    public $auth_user;

    public string $gameFilter = 'both';

    // Slip display state
    public array $selected_game_labels = [];
    public array $selected_games = [];  // bound to N1/N2 checkboxes
    public array $selected_times = [];

    // Draw & game state
    public $active_draw_number;
    public $games = [];

    // Ticket inputs
    public $a;
    public $b;
    public $c;

    public $a_qty = '';
    public $b_qty = '';
    public $c_qty = '';

    public $total_a = 0;
    public $total_b = 0;
    public $total_c = 0;

    public $end_time;
    public int $duration = 0;

    // Authoritative end time in milliseconds since epoch (Asia/Kolkata)
    public int $endAtMs = 0;

    public $active_draw;
    public $user_running_ticket;

    public $search = '';
    public $filterOption = '';
    public $ticketNumber = '';

    public $current_ticket_id = '';
    public $draw_detail_id = '';

    

    public $is_edit_mode = false;

    public $abc;
    public $abc_qty;

    public int $final_total_qty = 0;
    public int $cross_final_total_qty = 0;

    public array $stored_options = [];

    public array $drawSchedule = [];
public string $serverNowIso = '';

public $drawCount;


    /** -------------------------
     *  Game selection (N1/N2)
     *  -------------------------
     *  Keep these ONLY on the component (traits must NOT redeclare them)
     */
    public ?int $game_id = null; // active game

    /* -------------------------
     *  Utility helpers
     * ------------------------- 
     */

public function applyGameFilter(string $filter): void
{
    $allowed = ['both', 'N1', 'N2'];
    $this->gameFilter = in_array($filter, $allowed) ? $filter : 'both';
    $this->refreshDraw(); // will call loadDraws() again with the filter applied
}

    private function refreshSelectedTimes(): void
    {
        $ids = $this->selected_draw ?? [];
        $this->selected_times = \App\Models\DrawDetail::whereIn('id', $ids)
            ->get()
            ->map(fn($d) => $d->formatEndTime())
            ->toArray();
    }

    private function refreshSelectedGameLabels(): void
    {
        $this->selected_game_labels = collect($this->games ?? [])
            ->whereIn('id', $this->selected_games ?? [])
            ->map(fn($g) => strtoupper($g->code ?? $g->short_code ?? $g->name ?? ''))
            ->values()
            ->toArray();
    }

    private function parseTimeToCarbon(string $time): Carbon
    {
        $time = trim((string) $time);
        $tz   = 'Asia/Kolkata';

        foreach ([
            'h:i a', 'h:i A',
            'g:i a', 'g:i A',
            'h:i:s a', 'h:i:s A',
            'H:i', 'H:i:s',
        ] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $time, $tz);
            } catch (\Throwable $e) {
                // try next format
            }
        }

        return Carbon::parse($time, $tz);
    }

    private function ensureDefaultGameSelected(): void
    {
        $n1 = collect($this->games ?? [])->first(function ($g) {
            $code = strtoupper($g->code ?? $g->short_code ?? $g->name ?? '');
            return $code === 'N1';
        });

        $fallback = collect($this->games ?? [])->first();

        if (empty($this->selected_games)) {
            if ($n1) {
                $this->selected_games = [(int) $n1->id];
            } elseif ($fallback) {
                $this->selected_games = [(int) $fallback->id];
            }
        }
    }


  
  
  

private function ensureCurrentDrawSelected(): void
{
    if (empty($this->selected_draw) && !empty($this->draw_list)) {
        // allowed game IDs
        $allowedGameIds = $this->auth_user
            ? $this->auth_user->games->pluck('id')->map(fn($v) => (int)$v)->toArray()
            : [];

        // Pick first draw allowed for this user
        $current = collect($this->draw_list)->first(function ($d) use ($allowedGameIds) {
            $gameId = $d->draw->game->id ?? $d->game->id ?? null;
            return $gameId && (empty($allowedGameIds) || in_array((int)$gameId, $allowedGameIds));
        });

        // fallback
        if (!$current) {
            $current = $this->draw_list[0];
        }

        if ($current) {
            $id = (string) ($current->id ?? ($current->draw->id ?? null));
            if ($id) {
                $this->selected_draw = [$id];
                if (method_exists($this, 'setStoreOptions')) {
                    $this->setStoreOptions($this->selected_draw);
                }
                $this->refreshSelectedTimes(); // use same as applyDrawCount
            }
        }
    }
}


    public function mount(Request $request, $ticket = null)
    {
        $this->clearAllOptionsIntoCache();
        $this->clearAllCrossAbcIntoCache();

        $this->auth_user = User::find($request->user()->id);

        $this->games = \App\Models\Game::all();
        $this->ensureDefaultGameSelected();

        if ($ticket) {
            $this->game_id             = $ticket->game_id ?? null;
            $this->draw_detail_id      = $ticket->drawDetail->id;
            $this->current_ticket_id   = $ticket->id;
            $this->user_running_ticket = $ticket;
            $this->is_edit_mode        = true;

            $this->selected_draw[] = (string) $this->draw_detail_id;
            $this->handleTicketSelect($ticket->id);
        } else {
            $this->game_id = null;
            $this->addTicket();
        }

        $this->getTimes();
        $this->loadDraws();
        $this->loadTickets();
        $this->loadLatestDraws();
        $this->refreshDraw();

        // Ensure slip is initialized
        $this->refreshSelectedTimes();
        $this->refreshSelectedGameLabels();

        // Ensure at least one draw selected
        if (empty($this->selected_draw) && count($this->draw_list) > 0) {
            $this->selected_draw = [(string) $this->draw_list[0]->id];
            $this->setStoreOptions($this->selected_draw);
        }

        if (count($this->draw_list) == 0) {
            return redirect()->route('dashboard')->with([
                'message' => 'Draws not available at this time. Please try after some time.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.add-ticket-form');
    }

    /* -------------------------
     *  Events / Listeners
     * ------------------------- 
     */
    #[On('countdown-tick')]
    public function handleTime($timeLeft)
    {
        if (count($this->draw_list) == 0) {
            return redirect()->route('dashboard')->with([
                'message' => 'Draws not available at this time. Please try after some time.',
            ]);
        }
    }

   public function refreshDraw()
{
    // 1) Reload the draw list
    $this->loadDraws();

    // 2) Ensure we have a current/next draw selected if empty
    $this->ensureCurrentDrawSelected();

    // 3) Remove expired draws
    $this->selected_draw = $this->filterOpenDrawIds($this->selected_draw ?? []);

     // 4) If nothing left, pick first available from allowed games
    if (empty($this->selected_draw) && !empty($this->draw_list)) {
        $allowedGameIds = $this->auth_user
            ? $this->auth_user->games->pluck('id')->toArray()
            : [];

        $firstAllowed = collect($this->draw_list)
            ->first(fn($d) => in_array($d->draw->game->id, $allowedGameIds));

        if ($firstAllowed) {
            $this->selected_draw = [(string) $firstAllowed->id];
            $this->setStoreOptions($this->selected_draw);
        }
    }

     // 5) Timer — compute active draw for allowed games only
    $allowedGameIds = $this->auth_user
        ? $this->auth_user->games->pluck('id')->toArray()
        : [];

    $this->active_draw = collect($this->draw_list)
        ->first(fn($d) => in_array($d->draw->game->id, $allowedGameIds));

    if (!$this->active_draw) {
        $this->duration = 0;
        $this->endAtMs = 0;
    } else {
        $now    = \Carbon\Carbon::now('Asia/Kolkata');
        $endStr = (string) ($this->active_draw->end_time ?? '');
        try {
            $end = $this->parseTimeToCarbon($endStr)
                ->setDate($now->year, $now->month, $now->day)
                ->setSecond(0);
            $this->duration = max(0, $now->diffInSeconds($end));
            $this->endAtMs  = $end->getTimestamp() * 1000;
        } catch (\Throwable $e) {
            $this->duration = 0;
            $this->endAtMs = 0;
        }
    }

    // 6) Refresh slip ONCE (source of truth)
    $this->refreshSelectedTimes();
    $this->refreshSelectedGameLabels();

    // 7) Build drawSchedule (full day schedule with ISO datetimes) and serverNowIso
    try {
        $now = \Carbon\Carbon::now('Asia/Kolkata');

        $this->drawSchedule = collect($this->draw_list ?? [])->map(function ($d) use ($now) {
            try {
                $start = $this->parseTimeToCarbon((string)$d->start_time)
                    ->setDate($now->year, $now->month, $now->day)
                    ->setSecond(0);

                $end = $this->parseTimeToCarbon((string)$d->end_time)
                    ->setDate($now->year, $now->month, $now->day)
                    ->setSecond(59);

                return [
                    'id'        => $d->id,
                    'start_iso' => $start->toIso8601String(),
                    'end_iso'   => $end->toIso8601String(),
                    'label'     => $start->format('h:i a') . ' → ' . $end->format('h:i a'),
                ];
            } catch (\Throwable $ex) {
                return null;
            }
        })->filter()->values()->toArray();

        $this->serverNowIso = \Carbon\Carbon::now('Asia/Kolkata')->toIso8601String();
    } catch (\Throwable $e) {
        // safe fallback
        $this->drawSchedule = [];
        $this->serverNowIso = \Carbon\Carbon::now('Asia/Kolkata')->toIso8601String();
    }
}

#[On('countdownReached')]
public function handleCountdownReached($payload = null)
{
    // Client reported countdown reached; re-validate server-side and act if necessary.
    // Keep this minimal and authoritative: refresh draw state and resend authoritative endAt if needed.
    $this->refreshDraw();

    if ($this->duration > 0) {
        // Client says reached but server still has time -> send authoritative timestamp
        $this->dispatch('durationSynced', ['endAt' => $this->endAtMs]);
        return;
    }

    // If server duration is 0 (draw ended), let client know we refreshed schedule.
    // emit a server->browser event to inform clients they should re-sync their schedule
    // Note: This will trigger a browser event with name 'drawsRefreshed' (kebab-cased on DOM).
    $this->dispatch('drawsRefreshed', ['schedule' => $this->drawSchedule ?? [], 'serverNow' => $this->serverNowIso ?? '']);
}


    public function updatedSelectedDraw()
    {
        $this->selected_draw = $this->filterOpenDrawIds($this->selected_draw ?? []);

        if (empty($this->selected_draw)) {
            $current = $this->draw_list[0] ?? null;
            if (!$current) {
                $current = \App\Models\DrawDetail::runningDraw()->first();
            }

            if ($current) {
                $this->selected_draw = [(string) $current->id];
            } else {
                $this->selected_times = [];
                return;
            }
        }

        $this->refreshSelectedTimes();
    }

    public function updatedSelectedGames(): void
    {
        if (empty($this->selected_games)) {
            $n1 = collect($this->games ?? [])->first(function ($g) {
                $code = strtoupper($g->code ?? $g->short_code ?? $g->name ?? '');
                return $code === 'N1';
            });

            $fallback = collect($this->games ?? [])->first();

            if ($n1) {
                $this->selected_games = [(int) $n1->id];
            } elseif ($fallback) {
                $this->selected_games = [(int) $fallback->id];
            } else {
                $this->game_id = null;
                $this->selected_game_labels = [];
                return;
            }
        }

        $this->refreshSelectedGameLabels();

        $first = is_array($this->selected_games) ? reset($this->selected_games) : $this->selected_games;
        $this->game_id = $first !== false ? (int) $first : null;
    }

 public function applyDrawCount()
{
    // guard
    $count = (int) ($this->drawCount ?? 0);
    if ($count <= 0) {
        return;
    }

    $now = \Carbon\Carbon::now('Asia/Kolkata');

    // allowed game IDs for this user (as ints)
    $allowedGameIds = $this->auth_user
        ? $this->auth_user->games->pluck('id')->map(fn($v) => (int)$v)->toArray()
        : [];

    $draws = collect($this->draw_list ?? []);

    // helper to get game id from draw item (defensive)
    $getGameId = function ($d) {
        if (isset($d->draw) && isset($d->draw->game->id)) return (int)$d->draw->game->id;
        if (isset($d->game) && isset($d->game->id)) return (int)$d->game->id;
        return null;
    };

    // 1) find index of the "current" draw (now between start and end)
    $startIndex = null;
    foreach ($draws as $i => $d) {
        $gId = $getGameId($d);
        if (!empty($allowedGameIds) && $gId !== null && !in_array($gId, $allowedGameIds)) {
            continue; // skip draws user is not allowed to see
        }

        try {
            if (empty($d->start_time) || empty($d->end_time)) {
                continue;
            }

            $start = $this->parseTimeToCarbon((string)$d->start_time)
                ->setDate($now->year, $now->month, $now->day)
                ->setSecond(0);

            $end = $this->parseTimeToCarbon((string)$d->end_time)
                ->setDate($now->year, $now->month, $now->day)
                ->setSecond(59);

            if ($now->between($start, $end)) {
                $startIndex = $i;
                break;
            }
        } catch (\Throwable $e) {
            // ignore malformed time and continue
            continue;
        }
    }

    // 2) if no current active draw found, find next upcoming allowed draw
    if ($startIndex === null) {
        foreach ($draws as $i => $d) {
            $gId = $getGameId($d);
            if (!empty($allowedGameIds) && $gId !== null && !in_array($gId, $allowedGameIds)) {
                continue;
            }

            try {
                if (empty($d->start_time)) continue;

                $start = $this->parseTimeToCarbon((string)$d->start_time)
                    ->setDate($now->year, $now->month, $now->day)
                    ->setSecond(0);

                if ($start->greaterThanOrEqualTo($now)) {
                    $startIndex = $i;
                    break;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    // 3) fallback to first allowed draw if still not found
    if ($startIndex === null) {
        foreach ($draws as $i => $d) {
            $gId = $getGameId($d);
            if (!empty($allowedGameIds) && $gId !== null && !in_array($gId, $allowedGameIds)) {
                continue;
            }
            $startIndex = $i;
            break;
        }
    }

    // 4) if nothing is available, clear selection and return
    if ($startIndex === null) {
        $this->selected_draw = [];
        $this->refreshSelectedTimes();
        return;
    }

    // 5) collect N draws starting from startIndex (respect allowed games)
    $selected = [];
    for ($i = $startIndex; $i < $draws->count() && count($selected) < $count; $i++) {
        $d = $draws[$i];
        $gId = $getGameId($d);
        if (!empty($allowedGameIds) && $gId !== null && !in_array($gId, $allowedGameIds)) {
            continue;
        }
        // normalize to string ids like the rest of your component
        $selected[] = (string) ($d->id ?? ($d->draw->id ?? null));
    }

    // 6) apply selection and refresh local slip state
    $this->selected_draw = array_values(array_filter($selected)); // keep order, remove empty
    // persist to store or update options if you need (you call this elsewhere)
    if (method_exists($this, 'setStoreOptions')) {
        $this->setStoreOptions($this->selected_draw);
    }
    $this->refreshSelectedTimes();
}


    public function getSelectedDrawsProperty()
    {
        return DrawDetail::whereIn('id', $this->selected_draw)->get();
    }


}
