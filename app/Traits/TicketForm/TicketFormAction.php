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
            if (!in_array($draw_detail_id, $this->selected_draw)) {
                $this->selected_draw[] = (string) $draw_detail_id;
            }
        } elseif (!$isChecked && count($this->selected_draw) != 0) {
            $options = $this->getOptionsIntoCache();
            if ($options && count($this->selected_draw) > 1) {
                $filteredOptions = $this->getOptionsIntoCache()->filter(function ($option) use ($draw_detail_id) {
                    return in_array($draw_detail_id, $option['draw_details_ids']);
                });
                $this->optionStoreToCache($filteredOptions);
            }

            $this->selected_draw = array_filter(
                $this->selected_draw,
                fn($id) => $id != $draw_detail_id
            );
        }

        $total_selected_draws = count($this->selected_draw);

        $this->dispatch(
            'check-selected-draw',
            total_selected_draw: $total_selected_draws,
            draw_details_id: $draw_detail_id
        );

        $this->sanitizeSelectedDrawDB();
        $this->getTimes();
        $this->setStoreOptions($this->selected_draw);
    }

    // select ticket number
    public function handleTicketSelect($ticket_number)
    {
        $this->clearAllOptionsIntoCache();
        $this->clearAllCrossAbcIntoCache();
        $this->resetError();

        $this->selected_ticket = $this->auth_user->tickets()->where('ticket_number', $ticket_number)->first() ?? null;

        $selected_draw_ids = collect();

        if ($this->selected_ticket) {
            $this->selected_ticket_number = $ticket_number;

            $drawIds = $this->getActiveDrawIds();

            // get options
            $option_query = $this->auth_user->options()
                ->where('ticket_id', $this->selected_ticket->id)
                ->where(function ($query) use ($drawIds) {
                    foreach ($drawIds as $id) {
                        $query->orWhereJsonContains('draw_details_ids', $id);
                    }
                });

            $cross_abc_query = $this->auth_user->crossAbc()
                ->where('ticket_id', $this->selected_ticket->id)
                ->where(function ($query) use ($drawIds) {
                    foreach ($drawIds as $id) {
                        $query->orWhereJsonContains('draw_details_ids', $id);
                    }
                });

            $options = $option_query->get();
            $cross_abc = $cross_abc_query->get();

            if ($options->isNotEmpty()) {
                // store Options into cache exactly as before
                $this->optionStoreToCache($options);

                // collect draw ids referenced by options and intersect with open draws
                $optDraws = $options
                    ->pluck('draw_details_ids')   // arrays
                    ->flatten()
                    ->unique()
                    ->intersect($drawIds)
                    ->values();

                $selected_draw_ids = $selected_draw_ids->merge($optDraws);
            }

            if ($cross_abc->isNotEmpty()) {
                // store cross abc to cache
                $this->storeCrossAbcIntoCache($cross_abc);

                // collect draw ids from cross_abc rows (they also have draw_details_ids)
                $crossDraws = $cross_abc
                    ->pluck('draw_details_ids')
                    ->flatten()
                    ->unique()
                    ->intersect($drawIds)
                    ->values();

                $selected_draw_ids = $selected_draw_ids->merge($crossDraws);
            }
        }

        // final selected_draw fallback — use the draw_detail_id as before if none detected
        $this->selected_draw = $selected_draw_ids->isNotEmpty()
            ? $selected_draw_ids->unique()->values()->toArray()
            : [$this->draw_detail_id];

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
}
