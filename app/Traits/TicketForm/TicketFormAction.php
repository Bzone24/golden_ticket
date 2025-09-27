<?php

namespace App\Traits\TicketForm;

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
        if ($this->auth_user->tickets->last() && $this->auth_user->tickets->last()->ticket_number) {
            $last_ticket_number = explode('-', $this->auth_user->tickets->last()->ticket_number);
            $ticketNumber = $last_ticket_number[0] . '-' . ((int) $last_ticket_number[1] + 1);
        } else {
            $series = explode('-', $this->auth_user->ticket_series);
            $ticketNumber = $series[0] . '-' . ((int) $series[1] + 1);
        }

        $this->active_draw = DrawDetail::runningDraw()->first();
        if ($this->active_draw) {
            $this->draw_detail_id = $this->active_draw->id;

            $now = Carbon::now('Asia/Kolkata');

            // Parse DB value (supports "01:30 pm" and "13:30")
            $end = $this->parseTimeToCarbon($this->active_draw->end_time)
                ->setDate($now->year, $now->month, $now->day)
                ->setSecond(59);

            $this->end_time  = $end->format('h:i a');               // display
            $this->duration  = max(0, $now->diffInSeconds($end));   // seconds left
            $this->selected_ticket_number = $ticketNumber;
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
    $this->clearAllOptionsIntoCache();
    $this->clearAllCrossAbcIntoCache();
    $this->resetError();

    $this->selected_ticket = $this->auth_user->tickets()
        ->where('ticket_number', $ticket_number)
        ->first() ?? null;

    $selected_draw_ids = collect();

    if ($this->selected_ticket) {
        $this->selected_ticket_number = $ticket_number;

        $drawIds = $this->getActiveDrawIds();

        // 🔹 Simple ABC (aggregate across draws)
        $ticketOptions = $this->auth_user->ticketOptions()
            ->where('ticket_id', $this->selected_ticket->id)
            ->get();

        if ($ticketOptions->isNotEmpty()) {
            $aggregated = [];

            foreach ($ticketOptions as $opt) {
                // A qty
                if ($opt->a_qty > 0) {
                    $key = 'A|' . $opt->number;
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = ['option' => 'A', 'number' => $opt->number, 'qty' => 0, 'total' => 0];
                    }
                    $aggregated[$key]['qty']   += $opt->a_qty;
                    $aggregated[$key]['total'] += $opt->a_qty * \App\Models\Draw::PRICE;
                }

                // B qty
                if ($opt->b_qty > 0) {
                    $key = 'B|' . $opt->number;
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = ['option' => 'B', 'number' => $opt->number, 'qty' => 0, 'total' => 0];
                    }
                    $aggregated[$key]['qty']   += $opt->b_qty;
                    $aggregated[$key]['total'] += $opt->b_qty * \App\Models\Draw::PRICE;
                }

                // C qty
                if ($opt->c_qty > 0) {
                    $key = 'C|' . $opt->number;
                    if (!isset($aggregated[$key])) {
                        $aggregated[$key] = ['option' => 'C', 'number' => $opt->number, 'qty' => 0, 'total' => 0];
                    }
                    $aggregated[$key]['qty']   += $opt->c_qty;
                    $aggregated[$key]['total'] += $opt->c_qty * \App\Models\Draw::PRICE;
                }
            }

            if (!empty($aggregated)) {
                $this->optionStoreToCache(collect($aggregated)->values());
            }

            $optDraws = $ticketOptions->pluck('draw_detail_id')
                ->map(fn($id) => (string) $id)
                ->unique();

            $selected_draw_ids = $selected_draw_ids->merge($optDraws);
        }

        // 🔹 Cross ABC (unchanged)
        $cross_abc = $this->auth_user->crossAbc()
            ->where('ticket_id', $this->selected_ticket->id)
            ->where(function ($query) use ($drawIds) {
                foreach ($drawIds as $id) {
                    $query->orWhereJsonContains('draw_details_ids', $id);
                }
            })
            ->get();

        if ($cross_abc->isNotEmpty()) {
            $this->storeCrossAbcIntoCache($cross_abc);

            $crossDraws = $cross_abc
                ->pluck('draw_details_ids')
                ->flatten()
                ->filter()
                ->map(fn($id) => (string) $id)
                ->unique();

            $selected_draw_ids = $selected_draw_ids->merge($crossDraws);
        }
    }

   $this->selected_draw = $selected_draw_ids->isNotEmpty()
    ? $selected_draw_ids->unique()->map(fn($id) => (string) $id)->values()->toArray()
    : [(string) $this->draw_detail_ids];

        // 🔹 Load full draw details for header display
$this->selectedDraws = \App\Models\DrawDetail::with('draw.game')
    ->whereIn('id', $this->selected_draw)
    ->get();

    $this->setStoreOptions($this->selected_draw);

    $this->getTimes();
    $this->loadOptions(true);
    $this->loadAbcData(true);
    $this->dispatch('checked-draws', drawIds: $this->selected_draw);
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
