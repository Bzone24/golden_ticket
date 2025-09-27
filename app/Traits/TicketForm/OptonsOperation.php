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
use Illuminate\Support\Facades\Log;

trait OptonsOperation
{
    const PRICE = 11;

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
        // DB uses 24-hour format (HH:MM)
        $now = now('Asia/Kolkata')->format('H:i');

        if (empty($drawIds)) {
            return [];
        }

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
            // keep str()->length to preserve behaviour (multibyte aware in Laravel)
            $this->{$totalProp} = self::PRICE * ($this->{$qtyProp} * str()->length($this->{$row_property}));
            return $this->{$totalProp};
        }

        // Explicitly return null as before (original returned null if no qty)
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
     * Store provided options into cache. Preserves original merge order:
     * existing options first, then incoming appended.
     */
    public function storeOptionsIntoCache($data)
    {
        $existing = $this->getOptionsIntoCache()->values()->all();
        $incoming = is_array($data) ? $data : ($data instanceof Collection ? $data->values()->all() : (array) $data);

        // merge existing first so new entries appear after existing (same as original merge)
        $merged = array_values(array_merge($existing, $incoming));

        Cache::put('options', $merged, 7200);

        return true;
    }

    /**
     * Store options collection directly (keeps original shape)
     */
    public function optionStoreToCache(Collection $data)
    {
        Cache::put('options', $data->values()->all(), 7200);
    }

    public function deleteOption($index)
    {
        $data = collect($this->getOptionsIntoCache())->values();
        $data->forget($index);
        Cache::put('options', $data->values()->all());
        $this->loadOptions(true);
    }

    public function getOptionsIntoCache()
    {
        return collect(Cache::get('options', []))->sortByDesc('created_at')->values();
    }

    public function clearAllOptionsIntoCache()
    {
        Cache::forget('options');

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

        // dispatch browser event (safe in any Livewire version)
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
     * submitTicket - optimized internals, validation moved before persisting to avoid double-counting
     */
    public function submitTicket()
    {
        // Maintain backward compatibility: keep a single $this->game_id as the "primary" one
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

        // Normalize selected draws and game ids
        $selected_draw_ids = array_map('intval', $openIds);
        $this->selected_draw = array_map('strval', $openIds);

        $gameIds = [];
        if (is_array($this->selected_games) && count($this->selected_games) > 0) {
            $gameIds = array_values(array_map('intval', $this->selected_games));
        } else {
            $gameIds = [(int) $this->game_id];
        }

        $result = DB::transaction(function () use ($selected_draw_ids, $gameIds) {

            $digitMatrix = [];

            // --- WALLET: attempt to debit buyer's wallet for the ticket purchase (moved earlier) ---
            // Compute charge amount from cached options to ensure correct amount before creating ticket
            try {
                $cachedOptions = $this->getOptionsIntoCache();
                $payloadStoredOptions = $cachedOptions->values()->all();
            } catch (\Throwable $e) {
                $payloadStoredOptions = [];
            }
            $totalForCalc = collect($payloadStoredOptions)->sum('total');
            $drawCount = is_countable($this->selected_draw) ? count($this->selected_draw) : 1;
            if (method_exists($this, 'calculateFinalTotal')) {
                $chargeAmount = $this->calculateFinalTotal();
            } else {
                $chargeAmount = $totalForCalc * max(1, $drawCount);
            }

            if (!empty($chargeAmount) && $chargeAmount > 0) {
                try {
                    // Debit inside the DB transaction so failure will rollback ticket creation
                    app(\App\Services\WalletService::class)
                        ->debit($this->auth_user->id, (float)$chargeAmount, $this->auth_user->id, null, 'Ticket purchase (pre-reserve)');
                } catch (\Throwable $e) {
                    // Set submit error and rethrow to abort the transaction and prevent ticket creation
                    $this->addError('submit_error', 'Wallet error: ' . $e->getMessage());
                    throw $e;
                }
            }
            // --- end wallet debit (moved) ---


            // Create or update the ticket (kept as-is)
            $this->current_ticket_id = Ticket::updateOrCreate(
                ['ticket_number' => $this->selected_ticket_number],
                [
                    'status'  => 'COMPLETED',
                    'user_id' => $this->auth_user->id,
                    'game_id' => $this->game_id,
                ]
            )->id;

            // Populate ticket primary draw and primary game (non-destructive)
            try {
                $ticket = Ticket::find($this->current_ticket_id);

                $firstDrawId = !empty($selected_draw_ids) ? (int) $selected_draw_ids[0] : null;
                $firstGameId = !empty($gameIds) ? (int) $gameIds[0] : null;

                if (empty($firstDrawId)) {
                    $firstOpt = TicketOption::where('ticket_id', $this->current_ticket_id)
                        ->orderBy('id')
                        ->first();
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
                // swallowing to preserve existing behavior (no logging)
            }

            // Ensure there is at least one entry to submit
            $cachedOptions = $this->getOptionsIntoCache();
            $cachedCross   = $this->getCrossOptions();

            if ($cachedOptions->count() == 0 && $cachedCross->count() == 0) {
                $this->addError('submit_error', 'Please add at least one entry!');
                return true;
            } else {
                $this->resetError();
            }

            $currentTime = now('Asia/Kolkata')->format('H:i');

            // Cleanups (by ticket/draw; independent of game)
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

            // Save options for EACH game (keep create() to preserve Eloquent events)
            $storedOptions = $cachedOptions->values()->all();

            foreach ($gameIds as $gid) {
                foreach ($storedOptions as $option) {
                    $this->auth_user->options()->create([
                        'ticket_id'        => $this->current_ticket_id,
                        'game_id'          => $gid,
                        'draw_details_ids' => array_map('intval', array_values($option['draw_details_ids'] ?? [])),
                        'number'           => $option['number'],
                        'option'           => $option['option'],
                        'qty'              => $option['qty'],
                        'total'            => $option['total'],
                        'status'           => 'COMPLETED',
                    ]);
                }
            }

            // Build digit matrix once from cached options
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
            // ---------- PRE-PERSIST VALIDATION ----------
            $maxTqFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_tq')->value('value') : null;
            $maxCrossFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_cross_amount')->value('value') : null;

            $maximum_tq = (int) ($maxTqFromSettings ?? auth()->user()->maximum_tq ?? 50);
            $maximum_cross_amt = (int) ($maxCrossFromSettings ?? auth()->user()->maximum_cross_amount ?? 50);

            $maximum_source = [
                'maximum_tq' => $maxTqFromSettings ? 'settings' : 'user',
                'maximum_cross_amount' => $maxCrossFromSettings ? 'settings' : 'user',
            ];

            // build incomingSimple and incomingCross from storedOptions
            $incomingSimple = [];
            $incomingCross = [];

            foreach ($storedOptions as $opt) {
                $rawNum = isset($opt['number']) ? trim((string)$opt['number']) : '';
                $optionRaw = isset($opt['option']) ? (string)$opt['option'] : '';

                // option letters (A,B,C) if present
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
            if (!isset($cachedCross)) {
                $cachedCross = $this->getCrossOptions();
            }

            if (isset($cachedCross) && $cachedCross instanceof \Illuminate\Support\Collection) {
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
            $incomingCross = $expandedIncomingCross;

            // validate per draw
            $errors = [];
            $user = auth()->user();
            $maximum_cross_amt = $user->creator->maximum_cross_amount;
            $maximum_tq = $user->creator->maximum_tq;
            foreach ($selected_draw_ids as $detailIdToCheck) {
                $existingSimple = [];
                for ($d = 0; $d <= 9; $d++) {
                    $digit = (string)$d;
                    $existingSimple['a' . $digit] = (int) DB::table('ticket_options')->where('draw_detail_id', $detailIdToCheck)->where('number', $digit)->sum('a_qty');
                    $existingSimple['b' . $digit] = (int) DB::table('ticket_options')->where('draw_detail_id', $detailIdToCheck)->where('number', $digit)->sum('b_qty');
                    $existingSimple['c' . $digit] = (int) DB::table('ticket_options')->where('draw_detail_id', $detailIdToCheck)->where('number', $digit)->sum('c_qty');
                }

                $existingCross = [];
                $crossRows = DB::table('cross_abc_details')
                    ->where('draw_detail_id', $detailIdToCheck)
                    ->select('type', 'number', DB::raw('SUM(amount) as total_amt'))
                    ->groupBy('type', 'number')
                    ->get();
                foreach ($crossRows as $r) {
                    $existingCross[strtolower($r->type) . str_pad((int)$r->number, 2, '0', STR_PAD_LEFT)] = (int)$r->total_amt;
                }

                foreach ($incomingSimple as $key => $incomingQty) {
                    $incomingQty = (int)$incomingQty;
                    $existing = $existingSimple[$key] ?? 0;
                    if ($existing + $incomingQty > $maximum_tq) {
                        $allowed = max(0, $maximum_tq - $existing);
                        $errors[] =  " limit exceeded for draw_detail {$detailIdToCheck}. Current: {$existing}, Incoming: {$incomingQty}, Max: {$maximum_tq}, Allowed add: {$allowed}";
                    }
                }

                foreach ($incomingCross as $key => $incomingAmt) {
                    $incomingAmt = (int)$incomingAmt;
                    $existing = $existingCross[$key] ?? 0;
                    if ($existing + $incomingAmt > $maximum_cross_amt) {
                        $allowed = max(0, $maximum_cross_amt - $existing);
                        $errors[] =  " limit exceeded for draw_detail {$detailIdToCheck}. Current: {$existing}, Incoming: {$incomingAmt}, Max: {$maximum_cross_amt}, Allowed add: {$allowed}";
                    }
                }
            }

            Log::info('LIMIT_CHECK_PRE_PERSIST_DEBUG', [
                'selected_draw_ids' => $selected_draw_ids,
                'incomingSimple' => $incomingSimple,
                'incomingCross' => $incomingCross,
                'maximum_tq' => $maximum_tq,
                'maximum_cross_amt' => $maximum_cross_amt,
                'maximum_source' => $maximum_source,
                'errors' => $errors,
            ]);
            if (!empty($errors)) {
                // throw \Illuminate\Validation\ValidationException::withMessages($errors);
                $errMsg = '';
                foreach($errors as $key => $msgs) {
                    $errMsg .=  $msgs . "\n";
                }
                $this->dispatch('swal', [
                    'icon'  => 'error',
                    'title' => 'Oops!',
                    'text'  => $errMsg,
                ]);
                return;
            }
            // ---------- END PRE-PERSIST VALIDATION ----------





            // --- WALLET: attempt to debit buyer's wallet for the ticket purchase ---
            // Calculate total from cached options (same logic used later for payload)
            try {
                $cachedOptions = $this->getOptionsIntoCache();
                $payloadStoredOptions = $cachedOptions->values()->all();
            } catch (\Throwable $e) {
                $payloadStoredOptions = [];
            }
            $totalForCalc = collect($payloadStoredOptions)->sum('total');
            $drawCount = is_countable($this->selected_draw) ? count($this->selected_draw) : 1;
            // use calculateFinalTotal if implemented else multiply
            if (method_exists($this, 'calculateFinalTotal')) {
                $chargeAmount = $this->calculateFinalTotal();
            } else {
                $chargeAmount = $totalForCalc * max(1, $drawCount);
            }
            // Only attempt wallet debit when amount > 0 and payment method is wallet (if you have such flag)
            if (!empty($chargeAmount) && $chargeAmount > 0) {
                try {
                    // use service; will throw on insufficient funds
                    app(\App\Services\WalletService::class)
                        ->debit($this->auth_user->id, (float)$chargeAmount, $this->auth_user->id, $this->current_ticket_id ?? null, 'Ticket purchase');
                } catch (\Throwable $e) {
                    // Preserve original behavior: set submit error and abort persisting the ticket/options
                    $this->addError('submit_error', 'Wallet error: ' . $e->getMessage());
                    return true;
                }
            }
            // --- end wallet debit ---
            // Save TicketOption for EACH game and draw
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
            $drawDetails = \App\Models\DrawDetail::whereIn('id', $selected_draw_ids)->get();
            $drawDetails = DrawDetail::whereIn('id', $selected_draw_ids)->get();
            foreach ($drawDetails as $detail) {
                $total_a_qty = (int) $detail->ticketOptions()->sum('a_qty');
                $total_b_qty = (int) $detail->ticketOptions()->sum('b_qty');
                $total_c_qty = (int) $detail->ticketOptions()->sum('c_qty');

                $total_qty = $total_a_qty + $total_b_qty + $total_c_qty;
                $total_cross_amt = (int) $detail->crossAbcDetail()->sum('amount');

                // update totals (no limit checks here — those were done earlier)
                $detail->update([
                    'total_qty'       => $total_qty,
                    'total_cross_amt' => $total_cross_amt,
                ]);
            }

            // attach user->drawDetails
            $this->auth_user->drawDetails()->syncWithoutDetaching($selected_draw_ids);

            // Build payload for frontend printing (do NOT emit here)
            try {
                $payloadStoredOptions = $cachedOptions->values()->all();
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
                ];
            } catch (\Throwable $e) {
                $payload = [];
            }

            // If edit mode, redirect out (preserve existing behavior)
            if ($this->is_edit_mode) {
                return redirect()->route('dashboard');
            }

            // SUCCESS: return the payload so we can emit/dispatch after commit
            return $payload;
        });

        // === AFTER TRANSACTION: only emit/dispatch when transaction returned a payload array ===
        Log::info('SUBMIT_TICKET_AFTER_TX', ['transaction_result_type' => is_object($result) ? get_class($result) : gettype($result)]);

        // If edit-mode redirect was returned by the transaction, return it (preserve behavior)
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        // Only treat an array as a successful payload that should trigger printing
        if (is_array($result) && !empty($result)) {
            $payload = $result;

            Log::info('SUBMIT_TICKET_EMIT_PAYLOAD', [
                'ticket_number' => $payload['ticket_number'] ?? null,
                'stored_options_count' => is_array($payload['stored_options']) ? count($payload['stored_options']) : 0,
            ]);

            // try {
            //     $this->emit('ticketSubmitted', $payload);
            // } catch (\Throwable $e) {
            //     // keep original behavior: swallow emit errors
            // }

            // Only dispatch UI events after successful transaction
            $this->dispatch('ticketSubmitted');
            // $this->dispatch('refresh-window');

            return true;
        }

        // If transaction returned anything else (true/false/null), do NOT emit/dispatch.
        // If it was a validation failure it should have thrown ValidationException (or returned non-array).
        Log::info('SUBMIT_TICKET_NO_EMIT', ['transaction_result' => $result]);

        // Return true to keep original caller behavior (no print)
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

        Cache::put('options', $options, 7200);
        $this->loadOptions(true);
        $this->calculateFinalTotal();
    }
}
