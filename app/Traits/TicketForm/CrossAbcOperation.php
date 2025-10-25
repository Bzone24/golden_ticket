<?php

namespace App\Traits\TicketForm;

use App\Models\CrossAbc;
use App\Models\CrossAbcDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

trait CrossAbcOperation
{
    public $cross_abc_input;

    public $cross_abc_amt;

    public $cross_combination ;

    public $activeTab = 'simple_abc'; // default tab

    public $cross_ab;

    public $cross_ab_amt;

    public $cross_ac;

    public $cross_ac_amt;

    public $cross_bc;

    public $cross_bc_amt;

    public $cross_a;

    public $cross_b;

    public $cross_c;

    public $cross_single_amount;

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public $cross_attributes = [
        'cross_abc_input' => 'ABC',
        'cross_abc_amt' => 'Qty',
        'cross_combination' => 'Combination',
    ];

    public function resetCrossError()
    {
        $this->resetErrorBag(['cross_abc_input', 'cross_abc_amt', 'cross_combination']);
    }

    public function resetAbError()
    {
        $this->resetErrorBag(['cross_ab_amt', 'cross_ab']);
    }

  public function makeCombination(): array
{
    // normalize + clear old errors
    $cross_abc = trim((string) $this->cross_abc_input);
    $input_combination = (int) $this->cross_combination;
    $this->resetErrorBag(['cross_abc_input', 'cross_combination']);

    // Basic validation
    if ($cross_abc === '') {
        $msg = 'Please enter ABC digits (2 or 3 digits).';
        $this->addError('cross_abc_input', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    if (!preg_match('/^\d{2,3}$/', $cross_abc)) {
        $msg = 'ABC must be 2 or 3 digits (numbers only).';
        $this->addError('cross_abc_input', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    // allow 3, 6, 27
    if (!in_array($input_combination, [3, 6, 27], true)) {
        $msg = 'Combination must be 3, 6, or 27.';
        $this->addError('cross_combination', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    $chars = str_split($cross_abc);
    $count = count($chars);

    // Validate lengths per combination
    if ($input_combination === 27 && $count !== 3) {
        $msg = '27-combination requires exactly 3 digits (ABC).';
        $this->addError('cross_abc_input', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    if ($input_combination === 6 && !in_array($count, [2, 3], true)) {
        $msg = '6-combination requires 2 or 3 digits.';
        $this->addError('cross_abc_input', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    if ($input_combination === 3 && !in_array($count, [2, 3], true)) {
        // This shouldn't happen because we validated regex above, but keep check
        $msg = '3-combination requires 2 or 3 digits.';
        $this->addError('cross_abc_input', $msg);
        $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
        return [[], [], []];
    }

    // prepare results (always arrays)
    $ab = $ac = $bc = [];

    // 27: full combinations with repetition (3-digit required)
    if ($input_combination === 27) {
        foreach ($chars as $first) {
            foreach ($chars as $second) {
                $ab[] = (int) ($first . $second);
            }
        }
        foreach ($chars as $first) {
            foreach ($chars as $second) {
                $ac[] = (int) ($first . $second);
            }
        }
        foreach ($chars as $first) {
            foreach ($chars as $second) {
                $bc[] = (int) ($first . $second);
            }
        }
        return [$ab, $ac, $bc];
    }

    // 6: for 2-digit -> return both permutations per key; for 3-digit -> forward+reverse per pair
    if ($input_combination === 6) {
        if ($count === 2) {
            $n = $chars[0] . $chars[1];
            $r = $chars[1] . $chars[0];
            $ab = [(int)$n, (int)$r];
            $ac = [(int)$n, (int)$r];
            $bc = [(int)$n, (int)$r];
            return [$ab, $ac, $bc];
        }

        // count === 3
        $ab[] = (int) ($chars[0] . $chars[1]);
        $ab[] = (int) ($chars[1] . $chars[0]);

        $ac[] = (int) ($chars[0] . $chars[2]);
        $ac[] = (int) ($chars[2] . $chars[0]);

        $bc[] = (int) ($chars[1] . $chars[2]);
        $bc[] = (int) ($chars[2] . $chars[1]);

        return [$ab, $ac, $bc];
    }

    // 3: single forward pair for each (works for both 2 and 3 digits)
    if ($count === 2) {
        $val = (int) ($chars[0] . $chars[1]);
        $ab = [$val];
        $ac = [$val];
        $bc = [$val];
        return [$ab, $ac, $bc];
    }

    // count === 3
    $ab[] = (int) ($chars[0] . $chars[1]);
    $ac[] = (int) ($chars[0] . $chars[2]);
    $bc[] = (int) ($chars[1] . $chars[2]);

    return [$ab, $ac, $bc];
}


    // Live validation hooks
    public function updatedCrossAbcInput($value)
    {
        // reset errors and validate ABC quickly on every change
        $this->resetErrorBag('cross_abc_input');

        if ($value === '') return;

        if (!preg_match('/^\d{1,3}$/', $value)) {
            $this->addError('cross_abc_input', 'Only digits allowed (max 3).');
            // also notify popup
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Only digits allowed (max 3).']);
            return;
        }

        // if full 3 digits, proactively call makeCombination to surface any combination errors
        if (strlen($value) === 3) {
            $this->makeCombination();
        }
    }

public function updatedCrossCombination($value)
{
    $this->resetErrorBag('cross_combination');
    if ($value === '') return;

    if (!in_array((int)$value, [3,6,27], true)) {
        $this->addError('cross_combination', 'Combination must be 3, 6 or 27.');
        $this->dispatch('notify', ['type' => 'error', 'message' => 'Combination must be 3, 6 or 27.']);
        return;
    }

    $len = strlen((string)$this->cross_abc_input);
    $shouldRun = false;
    if ((int)$value === 27 && $len === 3) $shouldRun = true;
    if ((int)$value === 6 && in_array($len, [2,3], true)) $shouldRun = true;
    if ((int)$value === 3 && in_array($len, [2,3], true)) $shouldRun = true;

    if ($shouldRun) $this->makeCombination();
}



    public function addCrossOptions($amt, $comb, $number = null, $ab = null, $ac = null, $bc = null, $option = null)
    {
        return [
            'number' => $number,
            'ab' => $ab,
            'ac' => $ac,
            'bc' => $bc,
            'amt' => $amt,
            'combination' => $comb,
            'option' => $option,
            // 'total' => $total,
            'created_at' => Carbon::now(),
            'draw_details_ids' => $this->selected_draw,
        ];
    }

    public function storeCrossAbcIntoCache($data)
    {
        $options = collect($this->getCrossOptions());
        if ($options) {
            $data = $options->merge($data);
        }

        Cache::put('cross_abc', $data->values()->all(), 7200);
        $this->loadAbcData(true);
    }

    public function getCrossOptions()
    {
        return collect(Cache::get('cross_abc'))->sortByDesc('created_at');
    }

    public function deleteCrossAbc($index)
    {
        $data = collect($this->getCrossOptions())
            ->values();
        $data->forget($index);

        Cache::put('cross_abc', $data->values()->all());
        $this->loadAbcData(true);
    }

    public function clearAllCrossAbcIntoCache()
    {
        Cache::forget('cross_abc');
        $this->loadAbcData(true);
    }

    // Enter Abc of cross
    public function enterKeyPressOnCrossAbc($focus, $value)
    {
      $rules_and_attributes = [
    ['cross_abc_input' => [
        'required',
        // allow 2 or 3 digits only (digits unique constraint handled client-side)
        'regex:/^[0-9]{1,3}$/',
    ]],

    ['cross_abc_amt' => [
        'required',
        'integer',
        'multiple_of:5',
    ]],

    // combination rules:
    // - if ABC length == 3 => allow 3, 6, 27
    // - if ABC length == 2 => allow 3, 6
    ['cross_combination' => (strlen((string)$this->cross_abc_input) == 3) ? [
        'required',
        'in:3,6,27',
    ] : [
        'required',
        'in:3,6',
    ]],
];

        $attributes = [
            ['cross_abc_input' => 'ABC'],
            ['cross_abc_amt' => 'Amount'],
            ['cross_combination' => 'Combination'],
        ];

        switch ($value) {
            case 'cross_abc_input':
                try {
                    $this->validate($rules_and_attributes[0], [], $attributes[0]);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $first = collect($e->validator->errors()->all())->first() ?? 'Invalid input';
                    $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                    throw $e;
                }
                $this->dispatch($focus);
                break;
            case 'cross_abc_amt':
                try {
                    $this->validate($rules_and_attributes[0], [], $attributes[0]);
                    $this->validate($rules_and_attributes[1], [], $attributes[1]);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $first = collect($e->validator->errors()->all())->first() ?? 'Invalid input';
                    $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                    throw $e;
                }
                $this->dispatch($focus);
                break;
            case 'cross_combination':
                try {
                    $this->validate($rules_and_attributes[0], [], $attributes[0]);
                    $this->validate($rules_and_attributes[1], [], $attributes[1]);
                    $this->validate($rules_and_attributes[2], [], $attributes[2]);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $first = collect($e->validator->errors()->all())->first() ?? 'Invalid input';
                    $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                    throw $e;
                }

                [$ab, $ac, $bc] = $this->makeCombination();
                // If validation inside makeCombination failed you'll get empty arrays
                if (empty($ab) && empty($ac) && empty($bc)) {
                    // do not proceed further, errors already added inside makeCombination()
                    return;
                }

                $data[] = $this->addCrossOptions(number: $this->cross_abc_input,
                    ab: $ab, ac: $ac, bc: $bc,
                    amt: $this->cross_abc_amt, comb: $this->cross_combination, option: 'ABC');

                $this->storeCrossAbcIntoCache($data);

                // reset fields; keep cross_combination default to 3
                $this->cross_abc_input = $this->cross_abc_amt = '';
                // $this->cross_combination = 3;

                $this->resetCrossError();
                $this->AddTicketAndDrawDetailsIntoCrossAbc($this->selected_draw);
                $this->dispatch($focus);

                break;
        }
    }

    // Enter AB
    public function enterKeyPressOnCrossAb($focus, $value)
    {
        if ($value == 'cross_ab') {
            try {
                $this->validate([
                    'cross_ab' => [
                        'required',
                        // 'regex:/^(?!.*(.).*\\1)[0-9]{2}$/',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_ab' => 'AB']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid AB';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }
            $this->dispatch($focus);
        } elseif ($value == 'cross_ab_amt') {
            try {
                $this->validate([
                    'cross_ab' => [
                        'required',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_ab' => 'AB']);

                $this->validate([ 'cross_ab_amt' => [
                    'required',
                    'min:1', 'integer',
                ]], [], ['cross_ab_amt' => 'Amount']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid AB/amount';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }

            $data[] = $this->addCrossOptions(
                ab: $this->cross_ab,
                amt: $this->cross_ab_amt,
                comb: 1,
                number: $this->cross_ab,
                option: 'AB'
            );
            $this->storeCrossAbcIntoCache($data);
            $this->cross_ab = $this->cross_ab_amt = '';
            $this->AddTicketAndDrawDetailsIntoCrossAbc($this->selected_draw);
            $this->dispatch($focus);
        }
    }

    // Enter BC
    public function enterKeyPressOnCrossBc($focus, $value)
    {
        if ($value == 'cross_bc') {
            try {
                $this->validate([
                    'cross_bc' => [
                        'required',
                        // 'regex:/^(?!.*(.).*\\1)[0-9]{2}$/',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_bc' => 'BC']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid BC';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }
            $this->dispatch($focus);
        } elseif ($value == 'cross_bc_amt') {
            try {
                $this->validate([
                    'cross_bc' => [
                        'required',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_bc' => 'BC']);

                $this->validate([ 'cross_bc_amt' => [
                    'required',
                    'min:1', 'integer',
                ]], [], ['cross_bc_amt' => 'Amount']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid BC/amount';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }

            $data[] = $this->addCrossOptions(
                bc: $this->cross_bc,
                amt: $this->cross_bc_amt,
                comb: 1,
                number: $this->cross_bc,
                option: 'BC'
            );
            $this->storeCrossAbcIntoCache($data);
            $this->cross_bc = $this->cross_bc_amt = '';
            $this->AddTicketAndDrawDetailsIntoCrossAbc($this->selected_draw);
            $this->dispatch($focus);
        }
    }

    // Entry AC
    public function enterKeyPressOnCrossAc($focus, $value)
    {
        if ($value == 'cross_ac') {
            try {
                $this->validate([
                    'cross_ac' => [
                        'required',
                        // 'regex:/^(?!.*(.).*\\1)[0-9]{2}$/',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_ac' => 'AC']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid AC';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }
            $this->dispatch($focus);
        } elseif ($value == 'cross_ac_amt') {
            try {
                $this->validate([
                    'cross_ac' => [
                        'required',
                        'regex:/^[0-9]{2}$/',
                    ],
                ], [], ['cross_ac' => 'AC']);

                $this->validate([ 'cross_ac_amt' => [
                    'required',
                    'min:1', 'integer',
                ]], [], ['cross_ac_amt' => 'Amount']);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $first = collect($e->validator->errors()->all())->first() ?? 'Invalid AC/amount';
                $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                throw $e;
            }

            $data[] = $this->addCrossOptions(
                ac: $this->cross_ac,
                amt: $this->cross_ac_amt,
                comb: 1,
                number: $this->cross_ac,
                option: 'AC'
            );
            $this->storeCrossAbcIntoCache($data);
            $this->cross_ac = $this->cross_ac_amt = '';
            $this->AddTicketAndDrawDetailsIntoCrossAbc($this->selected_draw);

            $this->dispatch($focus);
        }
    }

    public function generateRegularCombinations()
    {
        $a = $this->cross_a;
        $b = $this->cross_b;
        $c = $this->cross_c;

        // Convert each into array of digits only if not null/empty
        $aDigits = ($a !== null && $a !== '') ? str_split((string) $a) : [];
        $bDigits = ($b !== null && $b !== '') ? str_split((string) $b) : [];
        $cDigits = ($c !== null && $c !== '') ? str_split((string) $c) : [];

        // Helper closure to generate combinations
        $makePairs = function ($x, $y) {
            $pairs = [];
            foreach ($x as $dx) {
                foreach ($y as $dy) {
                    $pairs[] = (int) ($dx.$dy);
                }
            }

            return $pairs;
        };

        // Generate only if both inputs exist
        $ab = (! empty($aDigits) && ! empty($bDigits)) ? $makePairs($aDigits, $bDigits) : [];
        $ac = (! empty($aDigits) && ! empty($cDigits)) ? $makePairs($aDigits, $cDigits) : [];
        $bc = (! empty($bDigits) && ! empty($cDigits)) ? $makePairs($bDigits, $cDigits) : [];

        // Total count
        $total = count($ab) + count($ac) + count($bc);

        return [
            $ab,
            $ac,
            $bc,
            $total,
        ];
    }

    // Enter A B and C
    public function enterKeyPressOnCrossA($focus, $value)
    {
        switch ($value) {
            case 'cross_a':
                $this->dispatch($focus);
                break;
            case 'cross_b':
                $this->dispatch($focus);
                break;
            case 'cross_c':
                $this->dispatch($focus);
                break;
            case 'cross_single_amount':
                try {
                    $this->validate(['cross_single_amount' => [
                        'required', 'integer', 'min:5', 'multiple_of:5',
                    ]], [], ['cross_single_amount' => 'Amount']);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $first = collect($e->validator->errors()->all())->first() ?? 'Invalid amount';
                    $this->dispatch('notify', ['type' => 'error', 'message' => $first]);
                    throw $e;
                }

                [$ab,$ac,$bc,$comb] = $this->generateRegularCombinations();
                $data[] = $this->addCrossOptions(
                    ab: $ab,
                    ac: $ac,
                    bc: $bc,
                    amt: $this->cross_single_amount,
                    comb: $comb,
                    number: $this->cross_a.'-'.$this->cross_b.'-'.$this->cross_c,
                    option: 'A-B-C'
                );
                $this->storeCrossAbcIntoCache($data);
                $this->cross_a = $this->cross_b = $this->cross_c = $this->cross_single_amount = '';
                $this->AddTicketAndDrawDetailsIntoCrossAbc($this->selected_draw);
                $this->dispatch($focus);
                break;
        }
    }

    public function AddTicketAndDrawDetailsIntoCrossAbc(array $selected_draw_ids): void
    {
        $selected_ticket_id = $this->current_ticket_id;

        $crossAbc = $this->getCrossOptions()
            ->map(function ($crossData) use ($selected_draw_ids, $selected_ticket_id) {
                $this->selected_draw = array_values(array_unique(
                    array_map('strval', $selected_draw_ids ?? [])
                ));
                $crossData['ticket_id'] = $selected_ticket_id;
                return $crossData;
            })
            ->values()
            ->all();

        Cache::put('cross_abc', $crossAbc, 7200);

        $this->selected_draw = array_values(array_unique(
            array_map('strval', $selected_draw_ids ?? [])
        ));
        $this->loadAbcData(true);
        $this->calculateCrossFinalTotal();
    }

    public function saveCrossAbc()
    {
        // build list of game IDs (from selected_games or fallback)
        $gameIds = [];
        if (is_array($this->selected_games) && count($this->selected_games) > 0) {
            $gameIds = array_values(array_map('intval', $this->selected_games));
        } elseif ($this->game_id) {
            $gameIds = [(int) $this->game_id];
        } elseif ($this->current_ticket_id) {
            $gameIds = [
                (int) \App\Models\Ticket::whereKey($this->current_ticket_id)->value('game_id')
            ];
        }

        $cross_data = $this->getCrossOptions();

        foreach ($cross_data as $data) {
            foreach ($gameIds as $gid) {
                $cross_input_data = [
                    'number'           => $data['number'],
                    'combination'      => $data['combination'],
                    'option'           => $data['option'],
                    'draw_details_ids' => array_map('intval', array_values($data['draw_details_ids'])),
                    'ticket_id'        => $this->current_ticket_id,
                    'ab'               => $data['ab'],
                    'ac'               => $data['ac'],
                    'bc'               => $data['bc'],
                    'amt'              => $data['amt'],
                    'user_id'          => $this->auth_user->id,
                    'game_id'          => $gid,
                ];

                \App\Models\CrossAbc::create($cross_input_data);
            }
        }
    }

    public function saveCrossAbcDetail()
    {
        $now = now('Asia/Kolkata')->format('H:i');

        // re-validate draws
        $this->selected_draw = \App\Models\DrawDetail::query()
            ->whereIn('id', $this->selected_draw ?? [])
            ->whereRaw("STR_TO_DATE(end_time, '%H:%i') >= STR_TO_DATE(?, '%H:%i')", [$now])
            ->pluck('id')
            ->all();

        if (empty($this->selected_draw)) {
            $msg = 'Selected draw has closed. Please pick an upcoming draw.';
            $this->addError('selected_draw', $msg);
            $this->dispatch('notify', ['type' => 'error', 'message' => $msg]);
            return;
        }

        // build list of game IDs
        $gameIds = [];
        if (is_array($this->selected_games) && count($this->selected_games) > 0) {
            $gameIds = array_values(array_map('intval', $this->selected_games));
        } elseif ($this->game_id) {
            $gameIds = [(int) $this->game_id];
        } elseif ($this->current_ticket_id) {
            $gameIds = [
                (int) \App\Models\Ticket::whereKey($this->current_ticket_id)->value('game_id')
            ];
        }

        $cross_data = $this->getCrossOptions();

        $cross_abc = collect(['ab', 'ac', 'bc'])->mapWithKeys(function ($key) use ($cross_data, $gameIds) {
            $items = $cross_data->flatMap(function ($row) use ($key, $gameIds) {
                return collect($row[$key])->flatMap(function ($number) use ($row, $gameIds) {
                    return collect($gameIds)->map(function ($gid) use ($row, $number) {
                        return [
                            'number'       => $number,
                            'amount'       => $row['amt'],
                            'combination'  => $row['combination'],
                            'option'       => $row['option'],
                            'ticket_id'    => $this->current_ticket_id,
                            'user_id'      => $this->auth_user->id,
                            'game_id'      => $gid,
                        ];
                    });
                });
            })->values();

            return [$key => $items];
        })->toArray();

        $bulkData = [];

        $ab_data = $cross_abc['ab'];
        $ac_data = $cross_abc['ac'];
        $bc_data = $cross_abc['bc'];

        foreach ($this->selected_draw as $draw_id) {
            foreach ($ab_data as $d) {
                $bulkData[] = array_merge($d, [
                    'draw_detail_id' => $draw_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'type'           => 'AB',
                ]);
            }
            foreach ($ac_data as $d) {
                $bulkData[] = array_merge($d, [
                    'draw_detail_id' => $draw_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'type'           => 'AC',
                ]);
            }
            foreach ($bc_data as $d) {
                $bulkData[] = array_merge($d, [
                    'draw_detail_id' => $draw_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                    'type'           => 'BC',
                ]);
            }
        }

        collect($bulkData)->chunk(500)->each(function ($chunk) {
            \App\Models\CrossAbcDetail::insert($chunk->toArray());
        });
    }
}
