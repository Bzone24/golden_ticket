<?php

namespace App\Traits\TicketForm;
use Illuminate\Support\Facades\DB;
use App\Models\DrawDetail;
use App\Models\Ticket;
use Carbon\Carbon;
use Livewire\Attributes\On;

trait TicketFormAction
{
    use OptonsOperation;

    public array $selected_draw_ids = [];
   
    public $submit_error = '';

    public string $selected_ticket_number = '';

    protected $table = 'ticket_options';
    
    public $auto_select_count;

    public bool $is_view_only = false;
public array $view_only_aggregated_options = [];
public array $view_only_cross_abc = [];

    /**
     * Parse a time string that may be "h:i a", "H:i", or "H:i:s" into a Carbon instance
     * in Asia/Kolkata timezone. Handles mixed historical data safely.
     */
    private function parseTimeToCarbon(string $time): Carbon
    {
        $time = trim($time);
        $tz   = 'Asia/Kolkata';

        foreach (['h:i a', 'h:i A', 'H:i', 'H:i:s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $time, $tz);
            } catch (\Throwable $e) {
                // try next
            }
        }

        // Last resort
        return \Carbon\Carbon::parse($time, $tz);
    }
    /**
     * Keep only selected draws that are still open at the current moment.
     */
    private function sanitizeSelectedDrawDB(): void
    {
        $now = now('Asia/Kolkata')->format('H:i:s');

        $this->selected_draw = DrawDetail::query()
            ->whereIn('id', $this->selected_draw ?? [])
            ->whereRaw("STR_TO_DATE(end_time, '%h:%i %p') >= STR_TO_DATE(?, '%H:%i:%s')", [$now])
            ->pluck('id')
            ->all();
    }

  protected function addTicket()
{
    // make sure we pick the real last ticket for this user, include soft-deleted ones
    $lastTicket = $this->auth_user
        ->tickets()               // use relation (query builder), not the loaded collection
        ->withTrashed()           // include soft-deleted so numbers are reserved
        ->orderBy('id', 'desc')   // newest first
        ->first();

    // compute next ticket number from lastTicket (or from user's series)
    if ($lastTicket && !empty($lastTicket->ticket_number)) {
        $parts = explode('-', $lastTicket->ticket_number);
        $prefix = $parts[0] ?? '';
        $num = isset($parts[1]) ? (int) $parts[1] : 0;
        $nextTicketNumber = $prefix . '-' . ($num + 1);
    } else {
        // user's base series may be like "A32-100"
        $series = explode('-', $this->auth_user->ticket_series ?? 'A1-0');
        $prefix = $series[0] ?? 'A1';
        $num = isset($series[1]) ? (int) $series[1] : 0;
        $nextTicketNumber = $prefix . '-' . ($num + 1);
    }

    // only set selected_ticket_number if not already set (avoid stomping mount/generated value)
    if (empty($this->selected_ticket_number)) {
        $this->selected_ticket_number = $nextTicketNumber;
    }

    // Draw setup
    $this->active_draw = DrawDetail::runningDraw()->first();
    if ($this->active_draw) {
        $this->draw_detail_id = $this->active_draw->id;

        $now = Carbon::now('Asia/Kolkata');
        $end = $this->parseTimeToCarbon($this->active_draw->end_time)
            ->setDate($now->year, $now->month, $now->day)
            ->setSecond(59);

        $this->end_time  = $end->format('h:i a');               // display
        $this->duration  = max(0, $now->diffInSeconds($end));   // seconds left

        $this->selected_ticket        = $this->user_running_ticket;

        $this->loadOptions(true);
        $this->loadTickets(true);

        $this->selected_draw[]  = (string) $this->draw_detail_id;
        $this->selected_draw_id = $this->draw_detail_id;
        $this->sanitizeSelectedDrawDB(); // keep only open draws
    }
}


    #[On('draw-selected')]
   public function handleDrawSelected($draw_detail_id, $isChecked)
{
    if ($isChecked) {
        // ✅ Add if not already in the array
        if (!in_array((string) $draw_detail_id, $this->selected_draw, true)) {
            $this->selected_draw[] = (string) $draw_detail_id;
        }

        // Only fetch/store options for this new draw (not all again)
        $options = $this->getOptionsIntoCache();
        $newOptions = $options->filter(fn($opt) => in_array($draw_detail_id, $opt['draw_details_ids']));
        if ($newOptions->isNotEmpty()) {
            $this->optionStoreToCache($newOptions);
        }
    } else {
        // ✅ Remove from selected draws
        $this->selected_draw = array_values(array_filter(
            $this->selected_draw,
            fn($id) => (string) $id !== (string) $draw_detail_id
        ));

        // Remove cached options only for this draw
        $options = $this->getOptionsIntoCache();
        if ($options) {
            $remainingOptions = $options->reject(fn($opt) =>
                in_array($draw_detail_id, $opt['draw_details_ids'])
            );
            $this->optionStoreToCache($remainingOptions);
        }
    }

    // ✅ Count for UI update
    $total_selected_draws = count($this->selected_draw);

    $this->dispatch(
        'check-selected-draw',
        total_selected_draw: $total_selected_draws,
        draw_details_id: $draw_detail_id
    );

    // ✅ Lighter calls (don’t reload everything unnecessarily)
    $this->sanitizeSelectedDrawDB();

    if ($isChecked) {
    $this->getTimes();
    // 🔥 Only update cache for this single draw
    $this->setStoreOptions([$draw_detail_id], false);
} else {
    // 🔥 Rebuild with remaining draws only
    $this->setStoreOptions($this->selected_draw, true);
}
}
 
    // select ticket number
public function handleTicketSelect($ticket_number)
{
    // 🔹 Reset temporary caches & errors
    $this->clearAllOptionsIntoCache();
    $this->clearAllCrossAbcIntoCache();
    $this->resetError();

    // 🔹 Load the selected ticket (include soft-deleted if needed)
    $this->selected_ticket = $this->auth_user->tickets()
        ->withTrashed()
        ->where('ticket_number', $ticket_number)
        ->first();

    // 🔹 Determine if this is a submitted (view-only) ticket
    $this->is_view_only = $this->selected_ticket ? true : false;
    $this->selected_ticket_number = $ticket_number;

    $selected_draw_ids = collect();

    if ($this->selected_ticket) {
        $drawIds = $this->getActiveDrawIds();

        // -----------------------------------------------------------
        // 🔹 Simple ABC (aggregate across draws)
        // -----------------------------------------------------------
        $ticketOptions = $this->auth_user->ticketOptions()
            ->where('ticket_id', $this->selected_ticket->id)
            ->get();

        if ($ticketOptions->isNotEmpty()) {
            $aggregated = [];

            foreach ($ticketOptions as $opt) {
                foreach (['a_qty' => 'A', 'b_qty' => 'B', 'c_qty' => 'C'] as $field => $label) {
                    if ($opt->$field > 0) {
                        $key = "{$label}|{$opt->number}";
                        if (!isset($aggregated[$key])) {
                            $aggregated[$key] = [
                                'option' => $label,
                                'number' => $opt->number,
                                'qty' => 0,
                                'total' => 0,
                            ];
                        }
                        $aggregated[$key]['qty'] += $opt->$field;
                        $aggregated[$key]['total'] += $opt->$field * \App\Models\Draw::PRICE;
                    }
                }
            }

            // 🔹 Store aggregated options: use view-only array or cache
            if (!empty($aggregated)) {
                if ($this->is_view_only) {
                    $this->view_only_aggregated_options = collect($aggregated)->values()->all();
                } else {
                    $this->optionStoreToCache(collect($aggregated)->values());
                }
            }

            // collect all draws used by these options
            $optDraws = $ticketOptions->pluck('draw_detail_id')
                ->map(fn($id) => (string) $id)
                ->unique();

            $selected_draw_ids = $selected_draw_ids->merge($optDraws);
        }

        // -----------------------------------------------------------
        // 🔹 Cross ABC (grouped per draw)
        // -----------------------------------------------------------
        $crossAbc = $this->auth_user->crossAbc()
            ->where('ticket_id', $this->selected_ticket->id)
            ->where(function ($query) use ($drawIds) {
                foreach ($drawIds as $id) {
                    $query->orWhereJsonContains('draw_details_ids', $id);
                }
            })
            ->get();

        if ($crossAbc->isNotEmpty()) {
            if ($this->is_view_only) {
                $this->view_only_cross_abc = $crossAbc->map(fn($c) => $c->toArray())->all();
            } else {
                $this->storeCrossAbcIntoCache($crossAbc);
            }

            $crossDraws = $crossAbc
                ->pluck('draw_details_ids')
                ->flatten()
                ->filter()
                ->map(fn($id) => (string) $id)
                ->unique();

            $selected_draw_ids = $selected_draw_ids->merge($crossDraws);
        }

        // -----------------------------------------------------------
        // 🔹 Draw labels for header display (used in Blade)
        // -----------------------------------------------------------
       $this->view_only_draw_labels = \App\Models\DrawDetail::with('draw.game')
    ->whereIn('id', $selected_draw_ids)
    ->get()
    ->map(function($d) {
        $labelTime = '';

        try {
            // use your helper that tries multiple formats
            if (method_exists($this, 'parseTimeToCarbon') && !empty($d->start_time)) {
                $start = $this->parseTimeToCarbon($d->start_time);
                $labelTime = $start->format('h:i a');
            } elseif (!empty($d->start_time)) {
                // defensive fallback
                $labelTime = \Carbon\Carbon::parse($d->start_time)->format('h:i a');
            }
        } catch (\Throwable $e) {
            // if parse fails, use raw string (trimmed) to avoid exceptions
            $labelTime = trim((string) ($d->start_time ?? ''));
        }

        $gameName = $d->game->name ?? '';
        return trim($labelTime . ' | ' . $gameName);
    })
    ->values()
    ->toArray();

    }

    // -----------------------------------------------------------
    // 🔹 Finalize selected draws
    // -----------------------------------------------------------
    $this->selected_draw = $selected_draw_ids->isNotEmpty()
        ? $selected_draw_ids->unique()->map(fn($id) => (string) $id)->values()->toArray()
        : (array) $this->draw_detail_ids;

    $this->selectedDraws = \App\Models\DrawDetail::with('draw.game')
        ->whereIn('id', $this->selected_draw)
        ->get();

    // -----------------------------------------------------------
    // 🔹 Handle editable vs view-only modes
    // -----------------------------------------------------------
    if ($this->is_view_only) {
        // only compute times/totals — no mutation
        $this->getTimes();
        $this->calculateFinalTotal();
        $this->calculateCrossFinalTotal();
        $this->dispatch('checked-draws', drawIds: $this->selected_draw);
    } else {
        // editable flow — reload caches and UI data
        $this->setStoreOptions($this->selected_draw);
        $this->getTimes();
        $this->loadOptions(true);
        $this->loadAbcData(true);
        $this->dispatch('checked-draws', drawIds: $this->selected_draw);
    }
}



public function deleteTicketDraws(array $drawDetailIds)
{
    // ensure we have a selected ticket
    if (empty($this->selected_ticket) || !($this->selected_ticket instanceof \App\Models\Ticket)) {
        $this->submit_error = 'No ticket selected.';
        return;
    }

    // normalize to strings/ints
    $requested = array_map(fn($v) => (int)$v, $drawDetailIds);

    // filter only currently open/active draw ids using your existing helper
    $allowed = $this->filterOpenDrawIds($requested);

    if (empty($allowed)) {
        $this->submit_error = 'Selected draw(s) are expired or not deletable.';
        return;
    }

    $ticketId = (int)$this->selected_ticket->id;
    $userId = auth()->id();

    DB::transaction(function () use ($ticketId, $allowed, $userId) {
        // fetch rows we will remove (for audit)
        $oldOptions = \App\Models\TicketOption::where('ticket_id', $ticketId)
            ->whereIn('draw_detail_id', $allowed)
            ->get()
            ->map->toArray();

        $oldCross = \App\Models\CrossAbcDetail::where('ticket_id', $ticketId)
            ->whereIn('draw_detail_id', $allowed)
            ->get()
            ->map->toArray();

        // delete the rows (safe: removal only for allowed draws)
        \App\Models\TicketOption::where('ticket_id', $ticketId)
            ->whereIn('draw_detail_id', $allowed)
            ->delete();

        \App\Models\CrossAbcDetail::where('ticket_id', $ticketId)
            ->whereIn('draw_detail_id', $allowed)
            ->delete();

        // write audit event
        DB::table('ticket_events')->insert([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'event_type' => 'DELETE',
            'draw_detail_ids' => json_encode(array_values($allowed)),
            'details' => json_encode(['options' => $oldOptions, 'cross' => $oldCross]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    // Refresh UI cache & recalc totals using existing helpers to keep behavior unchanged
    $this->clearAllOptionsIntoCache();
    $this->clearAllCrossAbcIntoCache();
    // reload options & cross from DB for this ticket (so cache is consistent)
    $this->setStoreOptions($this->selected_draw ?? []);
    $this->loadOptions(true);
    $this->loadAbcData(true);
    if (method_exists($this, 'calculateFinalTotal')) $this->calculateFinalTotal();
    if (method_exists($this, 'calculateCrossFinalTotal')) $this->calculateCrossFinalTotal();

    // dispatch an event that front-end can listen to (keep pattern)
    $this->dispatch('ticket-draws-deleted', drawIds: $allowed, ticketId: $ticketId);

    $this->submit_error = '';
    // optionally display success message (you can adapt to your UI)
    $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Selected draws removed from ticket.']);
}

    public function getTimes()
    {
        $this->sanitizeSelectedDrawDB();
        if (empty($this->selected_draw)) {
            $this->selected_times = [];
            $this->calculateFinalTotal();
            $this->calculateCrossFinalTotal();
            return;
        }

        $this->selected_times = DrawDetail::whereIn('id', $this->selected_draw)
            ->get()
            ->map(function ($draw) {
                $end = $this->parseTimeToCarbon($draw->end_time);
                return $end->copy()->addMinute()->format('h:i a');
            })
            ->toArray();

        $this->calculateFinalTotal();
        $this->calculateCrossFinalTotal();
    }

    public function calculateFinalTotal()
    {
        $total_stored_options = collect($this->stored_options)->sum('total');
        $total_selected_times = $this->selected_draw ? count($this->selected_draw) : 0;
        $this->final_total_qty = $total_stored_options * $total_selected_times;
    }

    public function calculateCrossFinalTotal()
    {
        $total_stored_cross = collect($this->stored_cross_abc_data)
            ->sum(fn($item) => $item['combination'] * $item['amt']);
        $total_selected_times = $this->selected_draw ? count($this->selected_draw) : 0;
        $this->cross_final_total_qty = $total_stored_cross * $total_selected_times;
    }

    public function loadActiveDraw()
    {
        if (count($this->draw_list) > 0) {
            $this->active_draw = $this->draw_list[0];
            $this->end_time    = $this->active_draw->end_time;

            $now = Carbon::now('Asia/Kolkata');
            $end = $this->parseTimeToCarbon($this->end_time)
                ->setDate($now->year, $now->month, $now->day)
                ->setSecond(59);

            $this->duration = max(0, $now->diffInSeconds($end));
        }
    }
    

    public function selectNextDraws()
{
    if (!$this->auto_select_count || $this->auto_select_count < 1) {
        return;
    }

    // Get current time in your timezone
    $now = now('Asia/Kolkata');

    // Fetch next N draws starting from current
   $nextDraws = \App\Models\DrawDetail::runningDraw()
    ->orderBy('start_time')
    ->limit($this->auto_select_count)
    ->pluck('id')
    ->toArray();

    // Update selected draws
    $this->selected_draw_ids = $nextDraws;

    // Refresh labels if you’re using pre-formatted header
    $this->selected_draw_labels = \App\Models\DrawDetail::whereIn('id', $this->selected_draw_ids)
    ->orderBy('start_time')
    ->get()
    ->map(fn($d) => optional($d->start_time)->format('h:i A') . ' , ' . ($d->game->name ?? ''))
    ->toArray();
}

public function clearSelectedDraws()
{
    $this->selected_draw = [];   // reset the array
    $this->setStoreOptions([]);  // also reset store if you’re syncing
    $this->refreshSelectedTimes();
}

    
}
