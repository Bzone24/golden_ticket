<?php

namespace App\Traits\TicketForm;

use App\Models\DrawDetail;
use App\Models\Ticket;
use App\Models\TicketOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait OptonsOperation
{
    const PRICE = 11;
    const CACHE_KEY = 'options';
    const CACHE_TTL = 7200; // seconds

    public function move($focus, $row_property)
    {
        $this->calculateTotal($row_property);
        $this->dispatch($focus);
    }

    /**
     * Returns only draw ids that are still open (end_time >= now)
     */
    protected function filterOpenDrawIds(array $drawIds): array
    {
        if (empty($drawIds)) {
            return [];
        }

        $now = now('Asia/Kolkata')->format('H:i');

        return DrawDetail::query()
            ->whereIn('id', $drawIds)
            ->whereRaw("STR_TO_DATE(end_time, '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$now])
            ->pluck('id')
            ->all();
    }

    public function keyTab($row_property)
    {
        $this->calculateTotal($row_property);
    }

    /**
     * Calculate total for a row property (keeps original formula)
     */
    public function calculateTotal($row_property)
    {
        $qtyProp = $row_property . '_qty';
        $totalProp = 'total_' . $row_property;

        if (!empty($this->{$qtyProp})) {
            // preserve multibyte-aware length behaviour
            $this->{$totalProp} = self::PRICE * ($this->{$qtyProp} * str()->length($this->{$row_property}));
            return $this->{$totalProp};
        }

        return null;
    }

    public function enterKeyPressOnAbc()
    {
        $this->dispatch('focus-qty');
    }

    /**
     * Handle Enter on qty: validate, create 3 option rows (A,B,C) and cache them
     */
    public function enterKeyPressOnQty()
    {
        $hasError = false;

        if ($this->abc === null || trim((string) $this->abc) === '') {
            $this->addError('abc', 'Please Enter The Value');
            $hasError = true;
        }

        if (empty($this->abc_qty)) {
            $this->addError('abc_qty', 'Please Enter Qty');
            $hasError = true;
        } elseif ($this->abc_qty <= 0) {
            $this->addError('abc_qty', 'Qty must be greater than 0');
            $hasError = true;
        }

        if ($hasError) {
            return true;
        }

        $this->resetError();

        $total = $this->abc_qty * str()->length($this->abc) * self::PRICE;

        $options = [];
        foreach (['A', 'B', 'C'] as $option) {
            $options[] = $this->addOptions($this->abc, $option, $this->abc_qty, $total);
        }

        $this->storeOptionsIntoCache($options);

        // reset quickly
        $this->abc_qty = $this->abc = '';
        $this->dispatch('focus-abc');

        $this->setStoreOptions($this->selected_draw);
    }

    /**
     * Generic key enter for individual row properties
     */
    public function keyEnter($row_property, $focus)
    {
        $total = $this->calculateTotal($row_property);

        $val = $this->{$row_property} ?? null;
        $qty = $this->{$row_property . '_qty'} ?? null;

        if (
            $this->selected_draw &&
            $val !== null && $val !== '' &&
            $qty !== null && $qty !== '' &&
            $qty > 0
        ) {
            $options = [];
            $options[] = $this->addOptions($val, ucfirst($row_property), $qty, $total);
            $this->storeOptionsIntoCache($options);
            $this->dispatch($focus);

            // reset fields
            $this->{$row_property} = '';
            $this->{$row_property . '_qty'} = '';
            $this->{'total_' . $row_property} = 0;
            $this->resetError();
        }
        $this->setStoreOptions($this->selected_draw);
    }

    private function expandTicketOption($ticketOption)
{
    $expanded = collect();

    if ($ticketOption->a_qty > 0) {
        $expanded->push([
            'option' => 'A',
            'number' => $ticketOption->number,
            'qty'    => $ticketOption->a_qty,
            'total'  => $ticketOption->a_qty * 11,
        ]);
    }

    if ($ticketOption->b_qty > 0) {
        $expanded->push([
            'option' => 'B',
            'number' => $ticketOption->number,
            'qty'    => $ticketOption->b_qty,
            'total'  => $ticketOption->b_qty * 11,
        ]);
    }

    if ($ticketOption->c_qty > 0) {
        $expanded->push([
            'option' => 'C',
            'number' => $ticketOption->number,
            'qty'    => $ticketOption->c_qty,
            'total'  => $ticketOption->c_qty * 11,
        ]);
    }

    return $expanded;
}


    public function addOptions($number, $option, $qty, $total)
    {
        return [
            'number'           => $number,
            'option'           => $option,
            'qty'              => $qty,
            'total'            => $total,
            'status'           => 'RUNNING',
            'created_at'       => Carbon::now(),
            'draw_details_ids' => $this->selected_draw,
        ];
    }

    /**
     * Store provided options into cache. Preserves existing-first merge order.
     */
    public function storeOptionsIntoCache($data)
    {
        $existing = $this->getOptionsIntoCache()->values()->all();
        $incoming = is_array($data) ? $data : ($data instanceof Collection ? $data->values()->all() : (array) $data);

        $merged = array_values(array_merge($existing, $incoming));

        Cache::put(self::CACHE_KEY, $merged, self::CACHE_TTL);

        return true;
    }

    /**
     * Store options collection directly (keeps original shape)
     */
    public function optionStoreToCache(Collection $data)
    {
        Cache::put(self::CACHE_KEY, $data->values()->all(), self::CACHE_TTL);
    }

    public function deleteOption($index)
    {
        $data = collect($this->getOptionsIntoCache())->values();
        $data->forget($index);
        Cache::put(self::CACHE_KEY, $data->values()->all());
        $this->loadOptions(true);
    }

    public function getOptionsIntoCache()
    {
        return collect(Cache::get(self::CACHE_KEY, []))->sortByDesc('created_at')->values();
    }

    public function clearAllOptionsIntoCache()
    {
        Cache::forget(self::CACHE_KEY);

        if (property_exists($this, 'stored_options')) {
            $this->stored_options = [];
        }

        if (property_exists($this, 'final_total_qty')) {
            $this->final_total_qty = 0;
        }
        if (property_exists($this, 'cross_final_total_qty')) {
            $this->cross_final_total_qty = 0;
        }

        if (method_exists($this, 'loadOptions')) {
            $this->loadOptions(true);
        }

        if (method_exists($this, 'calculateFinalTotal')) {
            $this->calculateFinalTotal();
        }

        if (method_exists($this, 'dispatch')) {
            $this->dispatch('optionsCleared');
        }
    }

    public function clearDisplaySlip()
    {
        $this->clearAllOptionsIntoCache();
        Cache::forget('cross_abc_data');

        $this->loadOptions(true);
        $this->stored_cross_abc_data = [];

        $this->final_total_qty = 0;
        $this->cross_final_total_qty = 0;
    }

    public function resetError()
    {
        $this->resetErrorBag(['abc', 'abc_qty', 'submit_error']);
    }

    /**
     * Get active draw ids (open or upcoming) for today
     */
    public function getActiveDrawIds(): array
    {
        $now = now('Asia/Kolkata')->format('H:i');

        return DrawDetail::where(function ($q) use ($now) {
            $q->where(function ($q1) use ($now) {
                $q1->whereRaw("STR_TO_DATE(start_time, '%H:%i') <= STR_TO_DATE(?, '%H:%i')", [$now])
                    ->whereRaw("STR_TO_DATE(end_time,   '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$now]);
            })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$now]);
        })
            ->where('date', now('Asia/Kolkata')->toDateString())
            ->pluck('id')
            ->toArray();
    }

    /**
     * Compute how much to charge from cached options and selected draws/games.
     * Uses calculateFinalTotal() if present (preserve custom behaviour).
     */
    protected function computeChargeAmount(): float
    {
        try {
            $cachedOptions = $this->getOptionsIntoCache();
            $payloadStoredOptions = $cachedOptions->values()->all();
        } catch (\Throwable $e) {
            $payloadStoredOptions = [];
        }

        $totalForCalc = collect($payloadStoredOptions)->sum('total');
        $drawCount = is_countable($this->selected_draw) ? count($this->selected_draw) : 1;

        if (method_exists($this, 'calculateFinalTotal')) {
            return (float) $this->calculateFinalTotal();
        }

        return (float) ($totalForCalc * max(1, $drawCount));
    }

    /**
     * submitTicket - refactored for single wallet-debit and centralized validation/limit checking
     */
    public function submitTicket()
    {
        // backward-compat: ensure game_id is set from selected_games or current ticket
        if (!$this->game_id && !empty($this->selected_games)) {
            $this->game_id = (int) (is_array($this->selected_games) ? reset($this->selected_games) : $this->selected_games);
        }
        if (!$this->game_id && $this->current_ticket_id) {
            $this->game_id = Ticket::whereKey($this->current_ticket_id)->value('game_id');
        }
        if (!$this->game_id) {
            $this->addError('submit_error', 'Please select a Game (N1/N2) before submitting.');
            return true;
        }

        // Filter valid draws (still open)
        $openIds = $this->filterOpenDrawIds($this->selected_draw ?? []);
        if (empty($openIds)) {
            $this->addError('submit_error', 'Selected draw has closed. Please pick an upcoming draw.');
            return true;
        }

        // normalize
        $selected_draw_ids = array_map('intval', $openIds);
        $this->selected_draw = array_map('strval', $openIds);

        $gameIds = [];
        if (is_array($this->selected_games) && count($this->selected_games) > 0) {
            $gameIds = array_values(array_map('intval', $this->selected_games));
        } else {
            $gameIds = [(int) $this->game_id];
        }

        $result = DB::transaction(function () use ($selected_draw_ids, $gameIds) {

            // ---------- prepare cached options / cross ----------
            try {
                $cachedOptions = $this->getOptionsIntoCache();
                $payloadStoredOptions = $cachedOptions->values()->all();
            } catch (\Throwable $e) {
                $payloadStoredOptions = [];
            }
            $cachedCross = $this->getCrossOptions();

            // ensure there is at least one entry to submit
            if (empty($payloadStoredOptions) && ($cachedCross instanceof Collection ? $cachedCross->count() === 0 : empty($cachedCross))) {
                $this->addError('submit_error', 'Please add at least one entry!');
                return true;
            } else {
                $this->resetError();
            }

            // -------------------- LIMITS CHECK --------------------
            // Build incoming maps from stored options and cross cache
            [$incomingSimple, $incomingCross] = $this->buildIncomingSimpleCross($payloadStoredOptions, $cachedCross);

            // determine maximums & group users
            $limitData = $this->buildLimitOwnerAndGroup($selected_draw_ids);

            $validationErrors = $this->validateLimits($selected_draw_ids, $incomingSimple, $incomingCross, $limitData);

            if (!empty($validationErrors)) {
                $errMsg = implode("\n", $validationErrors);
                $this->dispatch('swal', [
                    'icon'  => 'error',
                    'title' => 'Oops!',
                    'text'  => $errMsg,
                ]);
                return;
            }

            // -------------------- WALLET DEBIT --------------------
            $chargeAmount = $this->computeChargeAmount();
            if (!empty($chargeAmount) && $chargeAmount > 0) {
                try {
                    app(\App\Services\WalletService::class)
                        ->debit($this->auth_user->id, (float)$chargeAmount, $this->auth_user->id, $this->current_ticket_id ?? null, 'Ticket purchase (pre-reserve)');
                } catch (\Throwable $e) {
                    $this->addError('submit_error', 'Wallet error: ' . $e->getMessage());
                    throw $e; // abort transaction to preserve behaviour
                }
            }

            // -------------------- create/update ticket --------------------
            $this->current_ticket_id = Ticket::updateOrCreate(
                ['ticket_number' => $this->selected_ticket_number],
                [
                    'status'  => 'COMPLETED',
                    'user_id' => $this->auth_user->id,
                    'game_id' => $this->game_id,
                ]
            )->id;

            // try to populate ticket's primary draw/game and optionally insert draw_detail_game relations
            try {
                $ticket = Ticket::find($this->current_ticket_id);
                $firstDrawId = !empty($selected_draw_ids) ? (int)$selected_draw_ids[0] : null;
                $firstGameId = !empty($gameIds) ? (int)$gameIds[0] : null;

                if (empty($firstDrawId)) {
                    $firstOpt = TicketOption::where('ticket_id', $this->current_ticket_id)->orderBy('id')->first();
                    if ($firstOpt) {
                        $firstDrawId = (int) $firstOpt->draw_detail_id;
                    }
                }

                if (empty($firstGameId) && !empty($firstDrawId)) {
                    $draw = DrawDetail::find($firstDrawId);
                    if ($draw && !empty($draw->game_id)) {
                        $firstGameId = (int) $draw->game_id;
                    } else {
                        $optGame = TicketOption::where('ticket_id', $this->current_ticket_id)
                            ->where('draw_detail_id', $firstDrawId)
                            ->value('game_id');
                        if ($optGame) $firstGameId = (int) $optGame;
                    }
                }

                $update = [];
                if ($ticket && empty($ticket->draw_detail_id) && !empty($firstDrawId)) {
                    $update['draw_detail_id'] = $firstDrawId;
                }
                if ($ticket && empty($ticket->game_id) && !empty($firstGameId)) {
                    $update['game_id'] = $firstGameId;
                }
                if (!empty($update)) {
                    $ticket->update($update);
                }

                if (!empty($selected_draw_ids) && !empty($gameIds) && Schema::hasTable('draw_detail_game')) {
                    $insertRows = [];
                    foreach ($gameIds as $gid) {
                        foreach ($selected_draw_ids as $did) {
                            $insertRows[] = [
                                'draw_detail_id' => (int) $did,
                                'game_id'        => (int) $gid,
                                'created_at'     => now(),
                                'updated_at'     => now(),
                            ];
                        }
                    }
                    if (!empty($insertRows)) {
                        DB::table('draw_detail_game')->insertOrIgnore($insertRows);
                    }
                }
            } catch (\Throwable $e) {
                // keep behaviour: swallow errors here
            }

            // -------------------- cleanup stale options/cross for open draws ----------------
            $currentTime = now('Asia/Kolkata')->format('H:i');
            $drawIds = $this->getActiveDrawIds();

            // Delete options for ticket where draw_details_ids contains an open draw id
            $this->auth_user->options()
                ->where('ticket_id', $this->current_ticket_id)
                ->where(function ($query) use ($drawIds) {
                    foreach ($drawIds as $id) {
                        $query->orWhereJsonContains('draw_details_ids', $id);
                    }
                })
                ->delete();

            // Clean user ticketOptions that have draw details currently active / upcoming
            $this->auth_user->ticketOptions()
                ->where('ticket_id', $this->current_ticket_id)
                ->whereHas('DrawDetail', function ($query) use ($currentTime) {
                    $query->where(function ($q) use ($currentTime) {
                        $q->where(function ($q1) use ($currentTime) {
                            $q1->whereRaw("STR_TO_DATE(start_time, '%H:%i') <= STR_TO_DATE(?, '%H:%i')", [$currentTime])
                                ->whereRaw("STR_TO_DATE(end_time,   '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                        })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                    });
                })
                ->delete();

            // Delete cross entries similarly
            $this->auth_user->crossAbc()
                ->where('ticket_id', $this->current_ticket_id)
                ->where(function ($query) use ($drawIds) {
                    foreach ($drawIds as $id) {
                        $query->orWhereJsonContains('draw_details_ids', $id);
                    }
                })
                ->delete();

            $this->auth_user->crossAbcDetail()
                ->where('ticket_id', $this->current_ticket_id)
                ->whereHas('drawDetail', function ($query) use ($currentTime) {
                    $query->where(function ($q) use ($currentTime) {
                        $q->where(function ($q1) use ($currentTime) {
                            $q1->whereRaw("STR_TO_DATE(start_time, '%H:%i') <= STR_TO_DATE(?, '%H:%i')", [$currentTime])
                                ->whereRaw("STR_TO_DATE(end_time,   '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                        })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                    });
                })
                ->delete();

            // -------------------- Save simple ticket options (digitMatrix) --------------------
            $storedOptions = $payloadStoredOptions;
            $digitMatrix = $this->buildDigitMatrixFromStoredOptions($storedOptions);

            foreach ($gameIds as $gid) {
                foreach ($selected_draw_ids as $draw_detail_id) {
                    foreach ($digitMatrix as $number => $opts) {
                        $a = $opts['A'] ?? 0;
                        $b = $opts['B'] ?? 0;
                        $c = $opts['C'] ?? 0;

                        TicketOption::updateOrCreate(
                            [
                                'user_id'        => $this->auth_user->id,
                                'game_id'        => $gid,
                                'draw_detail_id' => $draw_detail_id,
                                'ticket_id'      => $this->current_ticket_id,
                                'number'         => $number,
                            ],
                            [
                                'a_qty' => $a,
                                'b_qty' => $b,
                                'c_qty' => $c,
                            ]
                        );
                    }
                }
            }

            // Cross ABC persistence (kept as-is)
            $this->saveCrossAbc();
            $this->saveCrossAbcDetail();

            // Recalculate totals on the selected draw_details
            $drawDetails = DrawDetail::whereIn('id', $selected_draw_ids)->get();
            foreach ($drawDetails as $detail) {
                $total_a_qty = (int) $detail->ticketOptions()->sum('a_qty');
                $total_b_qty = (int) $detail->ticketOptions()->sum('b_qty');
                $total_c_qty = (int) $detail->ticketOptions()->sum('c_qty');

                $total_qty = $total_a_qty + $total_b_qty + $total_c_qty;
                $total_cross_amt = (int) $detail->crossAbcDetail()->sum('amount');

                $detail->update([
                    'total_qty'       => $total_qty,
                    'total_cross_amt' => $total_cross_amt,
                ]);
            }

            // attach user->drawDetails
            $this->auth_user->drawDetails()->syncWithoutDetaching($selected_draw_ids);

            // Build payload for frontend printing (do NOT emit here)
            $selectedDrawModels = \App\Models\DrawDetail::whereIn('id', $selected_draw_ids)
                ->with(['draw', 'draw.game'])
                ->get();

            // Build a simple array with time + game for the frontend
            $drawsPayload = $selectedDrawModels->map(function($d) {
                return [
                    'time' => method_exists($d, 'formatResultTime') ? $d->formatResultTime() : (string) ($d->start_time ?? ''),
                    'game' => $d->draw->game->name ?? '',
                ];
            })->values()->all();

            // (Optional) keep selectedDraws property so Blade header still works
            $this->selectedDraws = $selectedDrawModels;

            // Build the rest of payload
            try {
                $payloadStoredOptions = $this->getOptionsIntoCache()->values()->all();
            } catch (\Throwable $e) {
                $payloadStoredOptions = [];
            }

            try {
                $totalForCalc = collect($payloadStoredOptions)->sum('total');

                if (method_exists($this, 'calculateTq')) {
                    $tq = $this->calculateTq();
                } else {
                    $tq = $totalForCalc > 0 ? (int) floor($totalForCalc / self::PRICE) : 0;
                }

                $total = $totalForCalc;
                $drawCount = is_countable($this->selected_draw) ? count($this->selected_draw) : 1;

                if (method_exists($this, 'calculateFinalTotal')) {
                    $finalTotal = $this->calculateFinalTotal();
                } else {
                    $finalTotal = $total * max(1, $drawCount);
                }

                $labels = $this->selected_game_labels ?? $this->selected_games ?? [];
                $times = is_array($this->selected_times) ? $this->selected_times : ($this->selected_times ? [$this->selected_times] : []);

                $payload = [
                    'ticket_number'  => $this->selected_ticket_number ?? ($this->selected_ticket->ticket_number ?? null),
                    'stored_options' => $payloadStoredOptions,
                    'tq'             => $tq,
                    'total'          => $total,
                    'finalTotal'     => $finalTotal,
                    'draw_count'     => $drawCount,
                    'labels'         => $labels,
                    'times'          => $times,
                    'draws'          => $drawsPayload,
                ];
            } catch (\Throwable $e) {
                $payload = [];
            }

            if ($this->is_edit_mode) {
                return redirect()->route('dashboard');
            }

            return $payload;
        });

        // === AFTER TRANSACTION: only emit/dispatch when transaction returned a payload array ===
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        if (is_array($result) && !empty($result)) {
            $payload = $result;

            // emit ticketSubmitted event with payload (frontend handles printing)
            $this->dispatch('ticketSubmitted', $payload);
            // Fire DOM event for frontend print
            $this->dispatch('ticket-submitted', payload: $payload);

            $this->dispatch('refresh-window');

            return true;
        }

        return true;
    }

    /**
     * Write back options to cache, mark status completed and refresh UI
     */
    public function setStoreOptions(array $selected_draw_ids): void
    {
        $options = $this->getOptionsIntoCache()
            ->map(function ($store_option) use ($selected_draw_ids) {
                $store_option['draw_details_ids'] = $selected_draw_ids;
                $store_option['status'] = 'COMPLETED';
                return $store_option;
            })
            ->values()
            ->all();

        Cache::put(self::CACHE_KEY, $options, self::CACHE_TTL);
        $this->loadOptions(true);
        $this->calculateFinalTotal();
    }

    // -------------------- Helper / refactored private methods --------------------

    /**
     * Build incomingSimple and incomingCross from stored options and cached cross entries.
     * Preserves original aggregation logic but in one place.
     *
     * @return array [incomingSimpleAssoc, incomingCrossAssoc]
     */
    protected function buildIncomingSimpleCross(array $storedOptions, $cachedCross)
    {
        $incomingSimple = [];
        $incomingCross = [];

        // process stored options
        foreach ($storedOptions as $opt) {
            $rawNum = isset($opt['number']) ? trim((string)$opt['number']) : '';
            $optionRaw = isset($opt['option']) ? (string)$opt['option'] : '';

            // option letters A,B,C if present
            $optionLetters = [];
            if ($optionRaw !== '') {
                preg_match_all('/[ABC]/i', $optionRaw, $m);
                if (!empty($m[0])) {
                    $optionLetters = array_map('strtoupper', $m[0]);
                }
            }

            $hasExplicitA = isset($opt['a_qty']) && $opt['a_qty'] !== null && $opt['a_qty'] !== '';
            $hasExplicitB = isset($opt['b_qty']) && $opt['b_qty'] !== null && $opt['b_qty'] !== '';
            $hasExplicitC = isset($opt['c_qty']) && $opt['c_qty'] !== null && $opt['c_qty'] !== '';

            if ($rawNum !== '') {
                if (is_numeric($rawNum)) {
                    $digits = str_split($rawNum);
                    foreach ($digits as $digitChar) {
                        if (!ctype_digit($digitChar)) continue;
                        $digit = (string)(int)$digitChar;
                        if (!empty($opt['qty']) && !empty($optionLetters)) {
                            foreach ($optionLetters as $opChar) {
                                $key = strtolower($opChar) . $digit;
                                $incomingSimple[$key] = ($incomingSimple[$key] ?? 0) + (int)$opt['qty'];
                            }
                        }
                        if ($hasExplicitA) $incomingSimple['a' . $digit] = ($incomingSimple['a' . $digit] ?? 0) + (int)$opt['a_qty'];
                        if ($hasExplicitB) $incomingSimple['b' . $digit] = ($incomingSimple['b' . $digit] ?? 0) + (int)$opt['b_qty'];
                        if ($hasExplicitC) $incomingSimple['c' . $digit] = ($incomingSimple['c' . $digit] ?? 0) + (int)$opt['c_qty'];
                    }
                } else {
                    preg_match_all('/\d/', $rawNum, $digitsMatches);
                    if (!empty($digitsMatches[0])) {
                        foreach ($digitsMatches[0] as $digitChar) {
                            $digit = (string)(int)$digitChar;
                            if (!empty($opt['qty']) && !empty($optionLetters)) {
                                foreach ($optionLetters as $opChar) {
                                    $key = strtolower($opChar) . $digit;
                                    $incomingSimple[$key] = ($incomingSimple[$key] ?? 0) + (int)$opt['qty'];
                                }
                            }
                            if ($hasExplicitA) $incomingSimple['a' . $digit] = ($incomingSimple['a' . $digit] ?? 0) + (int)$opt['a_qty'];
                            if ($hasExplicitB) $incomingSimple['b' . $digit] = ($incomingSimple['b' . $digit] ?? 0) + (int)$opt['b_qty'];
                            if ($hasExplicitC) $incomingSimple['c' . $digit] = ($incomingSimple['c' . $digit] ?? 0) + (int)$opt['c_qty'];
                        }
                    }
                }
            }

            // cross entries inside options
            if (!empty($opt['cross']) && is_array($opt['cross'])) {
                foreach ($opt['cross'] as $cr) {
                    $type = strtolower($cr['type'] ?? ($cr['option'] ?? ''));
                    $num2 = str_pad((int)($cr['number'] ?? ($cr['num'] ?? 0)), 2, '0', STR_PAD_LEFT);
                    $amt = $cr['amount'] ?? ($cr['amt'] ?? null);
                    if ($type === '' || !is_numeric($amt)) continue;
                    $key = strtolower(substr($type, 0, 2)) . $num2;
                    $incomingCross[$key] = ($incomingCross[$key] ?? 0) + (int)$amt;
                }
            }
        }

        // include separately cached cross entries
        if (isset($cachedCross) && $cachedCross instanceof Collection) {
            foreach ($cachedCross->values()->all() as $cr) {
                $amt = $cr['amount'] ?? ($cr['amt'] ?? null);
                $numRaw = $cr['number'] ?? ($cr['num'] ?? null);
                $typeRaw = $cr['type'] ?? ($cr['abc'] ?? $cr['option'] ?? null);

                if ($numRaw !== null && $typeRaw !== null && $amt !== null && is_numeric($numRaw) && is_numeric($amt)) {
                    $num2 = str_pad((int)$numRaw, 2, '0', STR_PAD_LEFT);
                    $typeKey = strtolower(substr((string)$typeRaw, 0, 2));
                    $incomingCross[$typeKey . $num2] = ($incomingCross[$typeKey . $num2] ?? 0) + (int)$amt;
                    continue;
                }

                $amtCol = $cr['amt'] ?? ($cr['amount'] ?? null);
                if ($amtCol !== null && is_numeric($amtCol)) {
                    foreach (['ab', 'ac', 'bc'] as $col) {
                        if (empty($cr[$col])) continue;
                        $raw = $cr[$col];
                        $numbers = [];
                        if (is_array($raw)) {
                            $numbers = $raw;
                        } elseif (is_numeric($raw)) {
                            $numbers = [(int)$raw];
                        } elseif (is_string($raw)) {
                            $trimmed = trim($raw);
                            if (strpos($trimmed, '[') === 0) {
                                $decoded = json_decode($trimmed, true);
                                if (is_array($decoded)) $numbers = $decoded;
                            } elseif (strpos($trimmed, ',') !== false) {
                                $numbers = array_map('trim', explode(',', $trimmed));
                            } elseif ($trimmed !== '') {
                                $numbers = [$trimmed];
                            }
                        }
                        foreach ($numbers as $n) {
                            if (!is_numeric($n)) continue;
                            $num2 = str_pad((int)$n, 2, '0', STR_PAD_LEFT);
                            $incomingCross[$col . $num2] = ($incomingCross[$col . $num2] ?? 0) + (int)$amtCol;
                        }
                    }
                    continue;
                }

                foreach (['ab', 'ac', 'bc'] as $col) {
                    if (isset($cr[$col]) && is_numeric($cr[$col]) && isset($cr['number']) && is_numeric($cr['number'])) {
                        $num2 = str_pad((int)$cr['number'], 2, '0', STR_PAD_LEFT);
                        $incomingCross[$col . $num2] = ($incomingCross[$col . $num2] ?? 0) + (int)$cr[$col];
                    }
                }
            }
        }

        // expand multi-digit cross keys (ab123 -> ab12, ab13, ab23)
        $incomingCross = $this->expandIncomingCross($incomingCross);

        return [$incomingSimple, $incomingCross];
    }

    /**
     * Expand incomingCross keys like 'ab123' => 'ab12','ab13','ab23'
     */
    protected function expandIncomingCross(array $incomingCross): array
    {
        $expandedIncomingCross = [];
        foreach ($incomingCross as $key => $amt) {
            $amt = (int)$amt;
            if (strlen($key) < 3) {
                $expandedIncomingCross[$key] = ($expandedIncomingCross[$key] ?? 0) + $amt;
                continue;
            }
            $type = substr($key, 0, 2);
            $numPart = substr($key, 2);
            if (preg_match('/^\d{1,2}$/', $numPart)) {
                $pair = strlen($numPart) === 1 ? str_pad($numPart, 2, '0', STR_PAD_LEFT) : $numPart;
                $nk = strtolower($type) . $pair;
                $expandedIncomingCross[$nk] = ($expandedIncomingCross[$nk] ?? 0) + $amt;
                continue;
            }
            if (preg_match('/^\d+$/', $numPart)) {
                $digits = str_split($numPart);
                $n = count($digits);
                for ($i = 0; $i < $n - 1; $i++) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        $d1 = (string)(int)$digits[$i];
                        $d2 = (string)(int)$digits[$j];
                        $pairRaw = $d1 . $d2;
                        $pair = str_pad($pairRaw, 2, '0', STR_PAD_LEFT);
                        $nk = strtolower($type) . $pair;
                        $expandedIncomingCross[$nk] = ($expandedIncomingCross[$nk] ?? 0) + $amt;
                    }
                }
                continue;
            }
            $expandedIncomingCross[$key] = ($expandedIncomingCross[$key] ?? 0) + $amt;
        }
        return $expandedIncomingCross;
    }

    /**
     * Build limit owner, group user ids and maximums for later checking.
     */
    protected function buildLimitOwnerAndGroup(array $selected_draw_ids): array
    {
        $currentUser = auth()->user();
        $limitOwner = $currentUser;

        if (!empty($currentUser->created_by)) {
            $maybeShop = DB::table('users')->where('id', $currentUser->created_by)->first();
            if ($maybeShop) $limitOwner = $maybeShop;
        }

        $maxTqFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_tq')->value('value') : null;
        $maxCrossFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_cross_amount')->value('value') : null;

        $maximum_tq = (int) ($maxTqFromSettings ?? ($limitOwner->maximum_tq ?? 50));
        $maximum_cross_amt = (int) ($maxCrossFromSettings ?? ($limitOwner->maximum_cross_amount ?? 50));

        $maximum_source = [
            'maximum_tq' => $maxTqFromSettings ? 'settings' : ($limitOwner->id === $currentUser->id ? 'user' : 'shopkeeper'),
            'maximum_cross_amount' => $maxCrossFromSettings ? 'settings' : ($limitOwner->id === $currentUser->id ? 'user' : 'shopkeeper'),
        ];

        $groupUserIds = [$limitOwner->id];
        $childIds = DB::table('users')->where('created_by', $limitOwner->id)->pluck('id')->toArray();
        if (!empty($childIds)) {
            $groupUserIds = array_values(array_unique(array_merge($groupUserIds, $childIds)));
        }

        return [
            'currentUser' => $currentUser,
            'limitOwner' => $limitOwner,
            'maximum_tq' => $maximum_tq,
            'maximum_cross_amt' => $maximum_cross_amt,
            'maximum_source' => $maximum_source,
            'groupUserIds' => $groupUserIds,
        ];
    }

    /**
     * Validate limits across selected draws using incomingSimple and incomingCross
     * Returns an array of error messages (empty if none)
     */
    protected function validateLimits(array $selected_draw_ids, array $incomingSimple, array $incomingCross, array $limitData): array
    {
        $errors = [];

        // Fetch existing sums in bulk
        $simpleRows = DB::table('ticket_options')
            ->whereIn('draw_detail_id', $selected_draw_ids)
            ->whereIn('user_id', $limitData['groupUserIds'])
            ->select('draw_detail_id', 'number',
                DB::raw('SUM(COALESCE(a_qty,0)) as sum_a'),
                DB::raw('SUM(COALESCE(b_qty,0)) as sum_b'),
                DB::raw('SUM(COALESCE(c_qty,0)) as sum_c')
            )
            ->groupBy('draw_detail_id', 'number')
            ->get();

        $existingSimplePerDraw = [];
        foreach ($simpleRows as $r) {
            $drawId = (int)$r->draw_detail_id;
            $num = (string)((int)$r->number);
            $existingSimplePerDraw[$drawId]['a' . $num] = (int)$r->sum_a;
            $existingSimplePerDraw[$drawId]['b' . $num] = (int)$r->sum_b;
            $existingSimplePerDraw[$drawId]['c' . $num] = (int)$r->sum_c;
        }

        $crossRows = DB::table('cross_abc_details')
            ->whereIn('draw_detail_id', $selected_draw_ids)
            ->whereIn('user_id', $limitData['groupUserIds'])
            ->select('draw_detail_id','type','number', DB::raw('SUM(amount) as total_amt'))
            ->groupBy('draw_detail_id','type','number')
            ->get();

        $existingCrossPerDraw = [];
        foreach ($crossRows as $r) {
            $drawId = (int)$r->draw_detail_id;
            $type = strtolower((string)$r->type);
            $num2 = str_pad((int)$r->number, 2, '0', STR_PAD_LEFT);
            $key = $type . $num2;
            $existingCrossPerDraw[$drawId][$key] = (int)$r->total_amt;
        }

        foreach ($selected_draw_ids as $detailIdToCheck) {
            $existingSimple = $existingSimplePerDraw[$detailIdToCheck] ?? [];

            // ensure all simple digit keys exist for 0..9
            for ($d = 0; $d <= 9; $d++) {
                $k = 'a' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
                $k = 'b' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
                $k = 'c' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
            }

            $existingCross = $existingCrossPerDraw[$detailIdToCheck] ?? [];

            foreach ($incomingSimple as $key => $incomingQty) {
                $incomingQty = (int)$incomingQty;
                $existing = $existingSimple[$key] ?? 0;
                if ($existing + $incomingQty > $limitData['maximum_tq']) {
                    $allowed = max(0, $limitData['maximum_tq'] - $existing);
                    $errors[] = strtoupper($key) . " limit exceeded for draw_detail {$detailIdToCheck}. Current: {$existing}, Incoming: {$incomingQty}, Max: {$limitData['maximum_tq']}, Allowed add: {$allowed}";
                }
            }

            foreach ($incomingCross as $key => $incomingAmt) {
                $incomingAmt = (int)$incomingAmt;
                $existing = $existingCross[$key] ?? 0;
                if ($existing + $incomingAmt > $limitData['maximum_cross_amt']) {
                    $allowed = max(0, $limitData['maximum_cross_amt'] - $existing);
                    $errors[] = strtoupper($key) . " limit exceeded for draw_detail {$detailIdToCheck}. Current: {$existing}, Incoming: {$incomingAmt}, Max: {$limitData['maximum_cross_amt']}, Allowed add: {$allowed}";
                }
            }
        }

        return $errors;
    }

    /**
     * Build digit matrix (number => ['A' => qty, 'B' => qty, 'C' => qty]) from stored options
     */
    protected function buildDigitMatrixFromStoredOptions(array $storedOptions): array
    {
        $digitMatrix = [];
        foreach ($storedOptions as $opt) {
            $option = $opt['option'];
            $digits = str_split((string) $opt['number']);
            $qty    = $opt['qty'];
            foreach ($digits as $digit) {
                if (!isset($digitMatrix[$digit][$option])) {
                    $digitMatrix[$digit][$option] = 0;
                }
                $digitMatrix[$digit][$option] += $qty;
            }
        }
        ksort($digitMatrix);
        return $digitMatrix;
    }
}
