<?php

namespace App\Traits\TicketForm;
use App\Models\DrawDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; 
use App\Models\TicketOption;  
use App\Models\Ticket;


trait OptonsOperation
{
    const PRICE = 11;
    const CACHE_KEY = 'options';
    const CACHE_TTL = 7200;

    public function move($focus, $row_property)
    {
        $this->calculateTotal($row_property);
        $this->dispatch($focus);
    }
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

    public function calculateTotal($row_property)
    {
        $qtyProp = $row_property . '_qty';
        $totalProp = 'total_' . $row_property;

        if (!empty($this->{$qtyProp})) {
            $this->{$totalProp} = self::PRICE * ($this->{$qtyProp} * str()->length($this->{$row_property}));
            return $this->{$totalProp};
        }

        return null;
    }

    public function enterKeyPressOnAbc()
    {
        $this->dispatch('focus-qty');
    }

    public function enterKeyPressOnQty()
    {
        $hasError = false;

        if ($this->abc === null || trim((string)$this->abc) === '') {
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

        $this->abc_qty = $this->abc = '';
        $this->dispatch('focus-abc');
        $this->setStoreOptions($this->selected_draw);
    }

    public function keyEnter($row_property, $focus)
    {
        $total = $this->calculateTotal($row_property);
        $val = $this->{$row_property} ?? null;
        $qty = $this->{$row_property . '_qty'} ?? null;

        if ($this->selected_draw && $val && $qty > 0) {
            $options = [$this->addOptions($val, ucfirst($row_property), $qty, $total)];
            $this->storeOptionsIntoCache($options);
            $this->dispatch($focus);

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

        foreach (['a', 'b', 'c'] as $option) {
            $qty = $ticketOption->{$option . '_qty'};
            if ($qty > 0) {
                $expanded->push([
                    'option' => strtoupper($option),
                    'number' => $ticketOption->number,
                    'qty'    => $qty,
                    'total'  => $qty * self::PRICE,
                ]);
            }
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

    public function storeOptionsIntoCache($data)
    {
        $existing = $this->getOptionsIntoCache()->values()->all();
        $incoming = is_array($data)
            ? $data
            : ($data instanceof Collection ? $data->values()->all() : (array)$data);

        $merged = array_values(array_merge($existing, $incoming));
        Cache::put(self::CACHE_KEY, $merged, self::CACHE_TTL);
        return true;
    }

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
        return collect(Cache::get(self::CACHE_KEY, []))
            ->sortByDesc('created_at')
            ->values();
    }

    public function clearAllOptionsIntoCache()
    {
        Cache::forget(self::CACHE_KEY);

        if (property_exists($this, 'stored_options')) {
            $this->stored_options = [];
        }

        foreach (['final_total_qty', 'cross_final_total_qty'] as $prop) {
            if (property_exists($this, $prop)) {
                $this->{$prop} = 0;
            }
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
    public function getActiveDrawIds(): array
    {
        $now = now('Asia/Kolkata')->format('H:i');

        return DrawDetail::where(function ($q) use ($now) {
            $q->where(function ($q1) use ($now) {
                $q1->whereRaw("STR_TO_DATE(start_time, '%H:%i') <= STR_TO_DATE(?, '%H:%i')", [$now])
                   ->whereRaw("STR_TO_DATE(end_time, '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$now]);
            })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$now]);
        })
        ->where('date', now('Asia/Kolkata')->toDateString())
        ->pluck('id')
        ->toArray();
    }

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
            return (float)$this->calculateFinalTotal();
        }

        return (float)($totalForCalc * max(1, $drawCount));
    }



  public function submitTicket()
{
    if (empty($this->selected_ticket_number)) {
        $this->selected_ticket_number = $this->generateNextTicketNumber();
    }

    if (!$this->game_id && !empty($this->selected_games)) {
        $this->game_id = (int)(is_array($this->selected_games) ? reset($this->selected_games) : $this->selected_games);
    }

    if (!$this->game_id && $this->current_ticket_id) {
        $this->game_id = Ticket::whereKey($this->current_ticket_id)->value('game_id');
    }

    if (!$this->game_id) {
        $this->addError('submit_error', 'Please select a Game (N1/N2) before submitting.');
        return true;
    }

    $openIds = $this->filterOpenDrawIds($this->selected_draw ?? []);
    if (empty($openIds)) {
        $this->addError('submit_error', 'Selected draw has closed. Please pick an upcoming draw.');
        return true;
    }

    $selected_draw_ids = array_map('intval', $openIds);
    $this->selected_draw = array_map('strval', $openIds);

    $gameIds = is_array($this->selected_games) && count($this->selected_games) > 0
        ? array_values(array_map('intval', $this->selected_games))
        : [(int)$this->game_id];

    if (empty($this->selected_ticket_number)) {
        $this->selected_ticket_number = $this->generateNextTicketNumber($this->auth_user->id);
    }

    $ticketNumber = $this->selected_ticket_number;
    $existingTicket = \App\Models\Ticket::withTrashed()
        ->where('ticket_number', $ticketNumber)
        ->first();

    if ($existingTicket) {
        if (empty($this->current_ticket_id) || ((int)$existingTicket->id !== (int)$this->current_ticket_id)) {
            $this->dispatch('notify', ['message' => "Ticket number {$ticketNumber} has already been used."]);
            return true;
        }
    }

    $result = DB::transaction(function () use ($selected_draw_ids, $gameIds) {
        try {
            $cachedOptions = $this->getOptionsIntoCache();
            $payloadStoredOptions = $cachedOptions->values()->all();
        } catch (\Throwable $e) {
            $payloadStoredOptions = [];
        }

        $cachedCross = $this->getCrossOptions();

        if (empty($payloadStoredOptions) && ($cachedCross instanceof Collection ? $cachedCross->isEmpty() : empty($cachedCross))) {
            $this->addError('submit_error', 'Please add at least one entry!');
            return true;
        } else {
            $this->resetError();
        }

        try {
            if (method_exists($this, 'buildIncomingSimpleCross')) {
                [$incomingSimple, $incomingCross] = $this->buildIncomingSimpleCross($payloadStoredOptions ?? [], $cachedCross ?? []);
            } else {
                $incomingSimple = $this->prepareIncomingSimple($payloadStoredOptions ?? []);
                $incomingCross = $this->prepareIncomingCross($cachedCross ?? []);
            }
        } catch (\Throwable $e) {
            $incomingSimple = is_array($incomingSimple ?? null) ? $incomingSimple : [];
            $incomingCross = is_array($incomingCross ?? null) ? $incomingCross : [];
        }

        $gameKey = 'N1';
        if (!empty($gameIds)) {
            $gid = (int)$gameIds[0];
            $maybeKey = DB::table('games')->where('id', $gid)->value('name');
            if ($maybeKey) {
                $gameKey = strtoupper(preg_replace('/\s+/', '_', (string)$maybeKey));
            }
        }

        $limitData = $this->buildLimitOwnerAndGroupForGame($selected_draw_ids, $gameKey);
        $validationErrors = $this->validateLimitsForGame($selected_draw_ids, $incomingSimple, $incomingCross, $limitData, $gameKey);

        if (!empty($validationErrors)) {
            $this->dispatch('swal', [
                'icon'  => 'error',
                'title' => 'Oops!',
                'text'  => implode("\n", $validationErrors),
            ]);
            return;
        }

        $chargeAmount = $this->computeChargeAmount();
        if (!empty($chargeAmount) && $chargeAmount > 0) {
            try {
                app(\App\Services\WalletService::class)
                    ->debit($this->auth_user->id, (float)$chargeAmount, $this->auth_user->id, $this->current_ticket_id ?? null, 'Ticket purchase (pre-reserve)');
            } catch (\Throwable $e) {
                $this->addError('submit_error', 'Wallet error: ' . $e->getMessage());
                throw $e;
            }
        }

       try {
    $existing = Ticket::where('ticket_number', $this->selected_ticket_number)->first();

    if ($existing) {
        // Ticket already exists — editing is disabled. Notify and abort submission.
        $this->addError('submit_error', 'This ticket has already been submitted and cannot be edited.');
        return true; // stop further processing
    }

    // Create new ticket record
    $this->current_ticket_id = Ticket::create([
        'ticket_number' => $this->selected_ticket_number,
        'status'  => 'COMPLETED',
        'user_id' => $this->auth_user->id,
        'game_id' => $this->game_id,
    ])->id;

} catch (\Illuminate\Database\QueryException $e) {
    // Handle duplicate-key (race) error gracefully
    if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
        $this->dispatch('notify', [
            'message' => "Ticket number {$this->selected_ticket_number} already exists (concurrent)."
        ]);
        return true;
    }
    throw $e;
}

        try {
            $ticket = Ticket::find($this->current_ticket_id);
            $firstDrawId = $selected_draw_ids[0] ?? null;
            $firstGameId = $gameIds[0] ?? null;

            if (empty($firstDrawId)) {
                $firstOpt = TicketOption::where('ticket_id', $this->current_ticket_id)->orderBy('id')->first();
                if ($firstOpt) {
                    $firstDrawId = (int)$firstOpt->draw_detail_id;
                }
            }

            if (empty($firstGameId) && !empty($firstDrawId)) {
                $draw = DrawDetail::find($firstDrawId);
                $firstGameId = $draw->game_id ?? TicketOption::where('ticket_id', $this->current_ticket_id)
                    ->where('draw_detail_id', $firstDrawId)
                    ->value('game_id');
            }

            $update = [];
            if ($ticket && empty($ticket->draw_detail_id) && $firstDrawId) {
                $update['draw_detail_id'] = $firstDrawId;
            }
            if ($ticket && empty($ticket->game_id) && $firstGameId) {
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
                            'draw_detail_id' => (int)$did,
                            'game_id'        => (int)$gid,
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
        }

        $currentTime = now('Asia/Kolkata')->format('H:i');
        $drawIds = $this->getActiveDrawIds();

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
                            ->whereRaw("STR_TO_DATE(end_time, '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                    })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                });
            })
            ->delete();

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
                            ->whereRaw("STR_TO_DATE(end_time, '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                    })->orWhereRaw("STR_TO_DATE(start_time, '%H:%i') > STR_TO_DATE(?, '%H:%i')", [$currentTime]);
                });
            })
            ->delete();

        $storedOptions = $payloadStoredOptions;
        $digitMatrix = $this->buildDigitMatrixFromStoredOptions($storedOptions);

        foreach ($gameIds as $gid) {
            foreach ($selected_draw_ids as $draw_detail_id) {
                foreach ($digitMatrix as $number => $opts) {
                    TicketOption::updateOrCreate(
                        [
                            'user_id'        => $this->auth_user->id,
                            'game_id'        => $gid,
                            'draw_detail_id' => $draw_detail_id,
                            'ticket_id'      => $this->current_ticket_id,
                            'number'         => $number,
                        ],
                        [
                            'a_qty' => $opts['A'] ?? 0,
                            'b_qty' => $opts['B'] ?? 0,
                            'c_qty' => $opts['C'] ?? 0,
                        ]
                    );
                }
            }
        }

        $this->saveCrossAbc();
        $this->saveCrossAbcDetail();

        $drawDetails = DrawDetail::whereIn('id', $selected_draw_ids)->get();
        foreach ($drawDetails as $detail) {
            $total_a_qty = (int)$detail->ticketOptions()->sum('a_qty');
            $total_b_qty = (int)$detail->ticketOptions()->sum('b_qty');
            $total_c_qty = (int)$detail->ticketOptions()->sum('c_qty');
            $total_cross_amt = (int)$detail->crossAbcDetail()->sum('amount');

            $detail->update([
                'total_qty'       => $total_a_qty + $total_b_qty + $total_c_qty,
                'total_cross_amt' => $total_cross_amt,
            ]);
        }

        $this->auth_user->drawDetails()->syncWithoutDetaching($selected_draw_ids);

        $selectedDrawModels = \App\Models\DrawDetail::whereIn('id', $selected_draw_ids)
            ->with(['draw', 'draw.game'])
            ->get();

        $drawsPayload = $selectedDrawModels->map(fn($d) => [
            'time' => method_exists($d, 'formatResultTime') ? $d->formatResultTime() : (string)($d->start_time ?? ''),
            'game' => $d->draw->game->name ?? '',
        ])->values()->all();

        $this->selectedDraws = $selectedDrawModels;

        try {
            $payloadStoredOptions = $this->getOptionsIntoCache()->values()->all();
        } catch (\Throwable $e) {
            $payloadStoredOptions = [];
        }

        try {
            $totalForCalc = collect($payloadStoredOptions)->sum('total');
            $tq = method_exists($this, 'calculateTq') ? $this->calculateTq() : ($totalForCalc > 0 ? (int)floor($totalForCalc / self::PRICE) : 0);
            $total = $totalForCalc;
            $drawCount = is_countable($this->selected_draw) ? count($this->selected_draw) : 1;
            $finalTotal = method_exists($this, 'calculateFinalTotal') ? $this->calculateFinalTotal() : $total * max(1, $drawCount);

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

    if ($result instanceof \Illuminate\Http\RedirectResponse) {
        return $result;
    }

    if (is_array($result) && !empty($result)) {
        $this->dispatch('ticketSubmitted', $result);
        $this->dispatch('ticket-submitted', payload: $result);
        $this->dispatch('refresh-window');
    }

    return true;
}


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

protected function buildIncomingSimpleCross(array $storedOptions, $cachedCross)
{
    $incomingSimple = [];
    $incomingCross = [];

    foreach ($storedOptions as $opt) {
        $rawNum = isset($opt['number']) ? trim((string)$opt['number']) : '';
        $optionRaw = isset($opt['option']) ? (string)$opt['option'] : '';

        $optionLetters = [];
        if ($optionRaw !== '') {
            preg_match_all('/[ABC]/i', $optionRaw, $m);
            if (!empty($m[0])) $optionLetters = array_map('strtoupper', $m[0]);
        }

        $hasExplicitA = isset($opt['a_qty']) && $opt['a_qty'] !== null && $opt['a_qty'] !== '';
        $hasExplicitB = isset($opt['b_qty']) && $opt['b_qty'] !== null && $opt['b_qty'] !== '';
        $hasExplicitC = isset($opt['c_qty']) && $opt['c_qty'] !== null && $opt['c_qty'] !== '';

        if ($rawNum !== '') {
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

    if ($cachedCross instanceof Collection) {
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
                    $numbers = is_array($raw) ? $raw : (is_string($raw) ? preg_split('/[,]/', trim($raw), -1, PREG_SPLIT_NO_EMPTY) : [(int)$raw]);
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

    return [$incomingSimple, $this->expandIncomingCross($incomingCross)];
}

protected function expandIncomingCross(array $incomingCross): array
{
    $expanded = [];
    foreach ($incomingCross as $key => $amt) {
        $amt = (int)$amt;
        if (strlen($key) < 3) {
            $expanded[$key] = ($expanded[$key] ?? 0) + $amt;
            continue;
        }
        $type = substr($key, 0, 2);
        $numPart = substr($key, 2);
        if (preg_match('/^\d{1,2}$/', $numPart)) {
            $pair = strlen($numPart) === 1 ? str_pad($numPart, 2, '0', STR_PAD_LEFT) : $numPart;
            $nk = strtolower($type) . $pair;
            $expanded[$nk] = ($expanded[$nk] ?? 0) + $amt;
            continue;
        }
        if (preg_match('/^\d+$/', $numPart)) {
            $digits = str_split($numPart);
            for ($i = 0; $i < count($digits) - 1; $i++) {
                for ($j = $i + 1; $j < count($digits); $j++) {
                    $pair = str_pad($digits[$i] . $digits[$j], 2, '0', STR_PAD_LEFT);
                    $nk = strtolower($type) . $pair;
                    $expanded[$nk] = ($expanded[$nk] ?? 0) + $amt;
                }
            }
            continue;
        }
        $expanded[$key] = ($expanded[$key] ?? 0) + $amt;
    }
    return $expanded;
}

protected function buildLimitOwnerAndGroupForGame(array $selected_draw_ids, string $gameKey = 'N1'): array
{
    $gameKey = strtoupper($gameKey ?: 'N1');
    $currentUser = auth()->user();
    $limitOwner = $currentUser;

    if (!empty($currentUser->created_by)) {
        $maybeShop = DB::table('users')->where('id', $currentUser->created_by)->first();
        if ($maybeShop) $limitOwner = $maybeShop;
    }

    $settingsMaxTqKey = 'maximum_tq_' . $gameKey;
    $settingsMaxCrossKey = 'maximum_cross_amount_' . $gameKey;
    $maxTqFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', $settingsMaxTqKey)->value('value') : null;
    $maxCrossFromSettings = Schema::hasTable('settings') ? DB::table('settings')->where('key', $settingsMaxCrossKey)->value('value') : null;
    $gid = DB::table('games')->where('name', $gameKey)->value('id');

    $userGameLimit = $ownerGameLimit = null;
    if (Schema::hasTable('user_game_limits') && $gid) {
        $userGameLimit = DB::table('user_game_limits')->where('user_id', $currentUser->id)->where('game_id', $gid)->first();
        $ownerGameLimit = DB::table('user_game_limits')->where('user_id', $limitOwner->id)->where('game_id', $gid)->first();
    }

    $defaultTq = 50;
    $defaultCross = 50;

    $maxTqColsUser = $currentUser->{"maximum_tq_" . strtolower($gameKey)} ?? $currentUser->maximum_tq ?? null;
    $maxTqColsOwner = $limitOwner->{"maximum_tq_" . strtolower($gameKey)} ?? $limitOwner->maximum_tq ?? null;
    $maxCrossColsUser = $currentUser->{"maximum_cross_amount_" . strtolower($gameKey)} ?? $currentUser->maximum_cross_amount ?? null;
    $maxCrossColsOwner = $limitOwner->{"maximum_cross_amount_" . strtolower($gameKey)} ?? $limitOwner->maximum_cross_amount ?? null;

    $maximum_tq = (int)($maxTqFromSettings
        ?? ($userGameLimit->maximum_tq ?? null)
        ?? $maxTqColsUser
        ?? ($ownerGameLimit->maximum_tq ?? null)
        ?? $maxTqColsOwner
        ?? $defaultTq);

    $maximum_cross_amt = (int)($maxCrossFromSettings
        ?? ($userGameLimit->maximum_cross_amount ?? null)
        ?? $maxCrossColsUser
        ?? ($ownerGameLimit->maximum_cross_amount ?? null)
        ?? $maxCrossColsOwner
        ?? $defaultCross);

    $groupUserIds = [$limitOwner->id];
    $childIds = DB::table('users')->where('created_by', $limitOwner->id)->pluck('id')->toArray();
    if (!empty($childIds)) $groupUserIds = array_unique(array_merge($groupUserIds, $childIds));

    return [
        'currentUser' => $currentUser,
        'limitOwner' => $limitOwner,
        'maximum_tq' => $maximum_tq,
        'maximum_cross_amt' => $maximum_cross_amt,
        'groupUserIds' => $groupUserIds,
        'sumUserIds' => [auth()->id()],
        'game_key' => $gameKey,
        'owner_game_limit_row' => $ownerGameLimit ? (array)$ownerGameLimit : null,
        'user_game_limit_row' => $userGameLimit ? (array)$userGameLimit : null,
    ];
}


   protected function prepareIncomingSimple(array $payloadStoredOptions): array
{
    $incomingSimple = [];

    try {
        $digitMatrix = $this->buildDigitMatrixFromStoredOptions($payloadStoredOptions);

        foreach ($digitMatrix as $number => $opts) {
            $num = (string)((int)$number);
            $incomingSimple['a' . $num] = isset($opts['A']) ? (int)$opts['A'] : 0;
            $incomingSimple['b' . $num] = isset($opts['B']) ? (int)$opts['B'] : 0;
            $incomingSimple['c' . $num] = isset($opts['C']) ? (int)$opts['C'] : 0;
        }

        for ($d = 0; $d <= 9; $d++) {
            foreach (['a', 'b', 'c'] as $prefix) {
                $k = $prefix . $d;
                if (!isset($incomingSimple[$k])) $incomingSimple[$k] = 0;
            }
        }
    } catch (\Throwable $e) {
        for ($d = 0; $d <= 9; $d++) {
            $incomingSimple['a' . $d] = 0;
            $incomingSimple['b' . $d] = 0;
            $incomingSimple['c' . $d] = 0;
        }
    }

    return $incomingSimple;
}

protected function prepareIncomingCross($cachedCross): array
{
    $incomingCross = [];
    $rows = $cachedCross instanceof \Illuminate\Support\Collection ? $cachedCross->toArray() : (array)$cachedCross;

    foreach ($rows as $r) {
        if (is_array($r)) {
            $type   = $r['type'] ?? '';
            $number = $r['number'] ?? null;
            $amount = $r['amount'] ?? 0;
        } elseif (is_object($r)) {
            $type   = $r->type ?? '';
            $number = $r->number ?? null;
            $amount = $r->amount ?? 0;
        } else {
            continue;
        }

        if ($type === '' || $number === null) continue;

        $type = strtolower((string)$type);
        $num2 = str_pad((int)$number, 2, '0', STR_PAD_LEFT);
        $key = $type . $num2;

        $incomingCross[$key] = (int)($incomingCross[$key] ?? 0) + (int)$amount;
    }

    return $incomingCross;
}
protected function validateLimitsForGame(array $selected_draw_ids, array $incomingSimple, array $incomingCross, array $limitData, string $gameKey = 'N1'): array
{
    $errors = [];
    $sumUserIds  = $limitData['sumUserIds']  ?? [auth()->id()];
    $limitOwner  = $limitData['limitOwner']  ?? auth()->user();

    if (empty($selected_draw_ids)) return $errors;

    $drawGameMap = DB::table('draw_details')
        ->whereIn('id', $selected_draw_ids)
        ->pluck('game_id', 'id')
        ->toArray();

    $missing = [];
    foreach ($selected_draw_ids as $did) {
        if (empty($drawGameMap[$did])) $missing[] = $did;
    }
    if (!empty($missing)) {
        $fallback = DB::table('draw_details as dd')
            ->leftJoin('draws as d', 'dd.draw_id', '=', 'd.id')
            ->whereIn('dd.id', $missing)
            ->pluck('d.game_id', 'dd.id')
            ->toArray();
        foreach ($fallback as $did => $g) {
            if (!empty($g)) $drawGameMap[$did] = $g;
        }
    }

    $gameNamesMap = DB::table('draw_details as dd')
        ->leftJoin('games as g', 'dd.game_id', '=', 'g.id')
        ->whereIn('dd.id', $selected_draw_ids)
        ->pluck('g.name', 'dd.id')
        ->toArray();

    $gameIds = array_values(array_filter(array_unique(array_values($drawGameMap))));
    $ownerGameLimits = [];

    if (!empty($gameIds) && Schema::hasTable('user_game_limits')) {
        $userIdsForLimits = array_unique(array_merge($sumUserIds, [$limitOwner->id]));
        $rows = DB::table('user_game_limits')
            ->whereIn('user_id', $userIdsForLimits)
            ->whereIn('game_id', $gameIds)
            ->get();

        foreach ($rows as $r) {
            $g = (int)$r->game_id;
            $u = (int)$r->user_id;
            if (!isset($ownerGameLimits[$g])) $ownerGameLimits[$g] = [];
            $ownerGameLimits[$g][$u] = (array)$r;
        }
    }

    $simpleRows = DB::table('ticket_options')
        ->whereIn('draw_detail_id', $selected_draw_ids)
        ->whereIn('user_id', $sumUserIds)
        ->select(
            'draw_detail_id',
            'number',
            DB::raw('SUM(COALESCE(a_qty,0)) as sum_a'),
            DB::raw('SUM(COALESCE(b_qty,0)) as sum_b'),
            DB::raw('SUM(COALESCE(c_qty,0)) as sum_c')
        )
        ->groupBy('draw_detail_id', 'number')
        ->get();

    $existingSimplePerDraw = [];
    foreach ($simpleRows as $r) {
        $drawId = (int)$r->draw_detail_id;
        $num    = (string)((int)$r->number);
        $existingSimplePerDraw[$drawId]['a' . $num] = (int)$r->sum_a;
        $existingSimplePerDraw[$drawId]['b' . $num] = (int)$r->sum_b;
        $existingSimplePerDraw[$drawId]['c' . $num] = (int)$r->sum_c;
    }

    $crossRows = DB::table('cross_abc_details')
        ->whereIn('draw_detail_id', $selected_draw_ids)
        ->whereIn('user_id', $sumUserIds)
        ->where('voided', 0)
        ->select('draw_detail_id', 'type', 'number', DB::raw('SUM(amount) as total_amt'))
        ->groupBy('draw_detail_id', 'type', 'number')
        ->get();

    $existingCrossPerDraw = [];
    foreach ($crossRows as $r) {
        $drawId = (int)$r->draw_detail_id;
        $type   = strtolower((string)$r->type);
        $num2   = str_pad((int)$r->number, 2, '0', STR_PAD_LEFT);
        $key    = $type . $num2;
        $existingCrossPerDraw[$drawId][$key] = (int)$r->total_amt;
    }

    foreach ($selected_draw_ids as $detailIdToCheck) {
        $existingSimple = $existingSimplePerDraw[$detailIdToCheck] ?? [];
        for ($d = 0; $d <= 9; $d++) {
            $k = 'a' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
            $k = 'b' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
            $k = 'c' . $d; if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
        }

        $existingCross = $existingCrossPerDraw[$detailIdToCheck] ?? [];

        $gameIdForDraw = $drawGameMap[$detailIdToCheck] ?? null;
        $gameNameForDraw = $gameNamesMap[$detailIdToCheck] ?? null;

        if (empty($gameNameForDraw) && $gameIdForDraw !== null) {
            $gameNameForDraw = DB::table('games')->where('id', $gameIdForDraw)->value('name');
        }

        $gameNameForDraw = $gameNameForDraw ?: 'Unknown';
        $defaultTq = 50;
        $defaultCross = 50;
        $maximum_tq = $defaultTq;
        $maximum_cross_amt = $defaultCross;

        if ($gameIdForDraw && isset($ownerGameLimits[(int)$gameIdForDraw])) {
            $rowsForGame = $ownerGameLimits[(int)$gameIdForDraw];
            $row = $rowsForGame[auth()->id()] ?? ($rowsForGame[$limitOwner->id] ?? null) ?? reset($rowsForGame);
            if ($row) {
                $maximum_tq = (int)($row['maximum_tq'] ?? $maximum_tq);
                $maximum_cross_amt = (int)($row['maximum_cross_amount'] ?? $maximum_cross_amt);
            }
        } else {
            $settingsMaxTq = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_tq_' . $gameKey)->value('value') : null;
            $settingsMaxCross = Schema::hasTable('settings') ? DB::table('settings')->where('key', 'maximum_cross_amount_' . $gameKey)->value('value') : null;

            if ($settingsMaxTq !== null) {
                $maximum_tq = (int)$settingsMaxTq;
            } else {
                $col = 'maximum_tq_' . strtolower($gameKey);
                $maximum_tq = (int)($limitOwner->{$col} ?? $limitOwner->maximum_tq ?? $maximum_tq);
            }

            if ($settingsMaxCross !== null) {
                $maximum_cross_amt = (int)$settingsMaxCross;
            } else {
                $col2 = 'maximum_cross_amount_' . strtolower($gameKey);
                $maximum_cross_amt = (int)($limitOwner->{$col2} ?? $limitOwner->maximum_cross_amount ?? $maximum_cross_amt);
            }
        }

        foreach ($incomingSimple as $key => $incomingQty) {
            $incomingQty = (int)$incomingQty;
            $existing = $existingSimple[$key] ?? 0;
            if ($existing + $incomingQty > $maximum_tq) {
                $allowed = max(0, $maximum_tq - $existing);
                $errors[] = strtoupper($key)
                    . " limit exceeded (Game {$gameNameForDraw}). Current: {$existing}, Incoming: {$incomingQty}, Max: {$maximum_tq}, Allowed add: {$allowed}";
            }
        }

        foreach ($incomingCross as $key => $incomingAmt) {
            $incomingAmt = (int)$incomingAmt;
            $existing = $existingCross[$key] ?? 0;
            if ($existing + $incomingAmt > $maximum_cross_amt) {
                $allowed = max(0, $maximum_cross_amt - $existing);
                $errors[] = strtoupper($key)
                    . " limit exceeded (Game {$gameNameForDraw}). Current: {$existing}, Incoming: {$incomingAmt}, Max: {$maximum_cross_amt}, Allowed add: {$allowed}";
            }
        }
    }

    return $errors;
}

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

    $maximum_tq = (int)($maxTqFromSettings ?? ($limitOwner->maximum_tq ?? 50));
    $maximum_cross_amt = (int)($maxCrossFromSettings ?? ($limitOwner->maximum_cross_amount ?? 50));

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


   protected function validateLimits(array $selected_draw_ids, array $incomingSimple, array $incomingCross, array $limitData): array
{
    $errors = [];

    $simpleRows = DB::table('ticket_options')
        ->whereIn('draw_detail_id', $selected_draw_ids)
        ->whereIn('user_id', $limitData['groupUserIds'])
        ->select(
            'draw_detail_id',
            'number',
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
        ->select('draw_detail_id', 'type', 'number', DB::raw('SUM(amount) as total_amt'))
        ->groupBy('draw_detail_id', 'type', 'number')
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

        for ($d = 0; $d <= 9; $d++) {
            $k = 'a' . $d;
            if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
            $k = 'b' . $d;
            if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
            $k = 'c' . $d;
            if (!isset($existingSimple[$k])) $existingSimple[$k] = 0;
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

protected function buildDigitMatrixFromStoredOptions(array $storedOptions): array
{
    $digitMatrix = [];
    foreach ($storedOptions as $opt) {
        $option = $opt['option'];
        $digits = str_split((string)$opt['number']);
        $qty = $opt['qty'];
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
