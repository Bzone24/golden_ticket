<div class="card mb-3 h-100">

    <!-- Timer Header -->
    @include('livewire.ticket-data-form')

    <div class="card-header text-black">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom w-100">

            {{-- @php
      // TIMES: prefer $selected_times; fallback to active_draw->formatEndTime()
      $times = !empty($selected_times)
          ? $selected_times
          : (isset($active_draw) ? [ $active_draw->formatTime() ] : []);
          

      // GAMES: prefer $selected_game_labels; fallback from games list
      $labels = !empty($selected_game_labels)
          ? $selected_game_labels
          : collect($games ?? [])
                ->whereIn('id', $selected_games ?? [])
                ->map(fn($g) => strtoupper($g->code ?? $g->short_code ?? $g->name ?? ''))
                ->values()
                ->all();
    @endphp --}}

            @php
                use Illuminate\Support\Str;

                // Decide format: if original has seconds -> show seconds, else no seconds.
                $formatFromString = function ($s) {
                    return is_string($s) && substr_count($s, ':') >= 2 ? 'g:i:s A' : 'g:i A';
                };

                $addOneMinute = function ($t) use ($formatFromString) {
                    try {
                        $dt = \Illuminate\Support\Carbon::parse($t)->addMinute();
                        $fmt = $formatFromString((string) $t);
                        return $dt->format($fmt); // 12-hour with AM/PM, e.g. 3:00 PM
                    } catch (\Throwable $e) {
                        // parsing failed — return original value as safe fallback
                        return $t;
                    }
                };

                if (!empty($selected_times)) {
                    $selected_times_arr = is_array($selected_times) ? $selected_times : [$selected_times];
                    $times = array_map($addOneMinute, $selected_times_arr);
                } elseif (isset($active_draw)) {
                    $raw = $active_draw->formatEndTime();
                    $times = [$addOneMinute($raw)];
                } else {
                    $times = [];
                }

                // GAMES: prefer $selected_game_labels; fallback from games list
                $labels = !empty($selected_game_labels)
                    ? $selected_game_labels
                    : collect($games ?? [])
                        ->whereIn('id', $selected_games ?? [])
                        ->map(fn($g) => strtoupper($g->code ?? ($g->short_code ?? ($g->name ?? ''))))
                        ->values()
                        ->all();
            @endphp

            <h6 class="mb-0 w-100 text-center">
                @foreach ($this->selectedDraws as $key => $draw)
                    <strong>Draw:</strong> {{ $draw->formatResultTime() }} ,
                    {{ $draw->draw->game->name }} |
                @endforeach
            </h6>

        </div>
    </div>



    <div class="card-body pb-2">
        {{-- Cross ABC Section --}}
        <div class="mb-2">
            <h5 class="fw-semibold">Cross ABC</h5>

            <!-- ✅ Fixed height + scroll only inside table area -->
            <div id="printCrossArea" style="max-height: 300px; overflow-y: auto;">

                <table class="table table-bordered table-striped table-hover mb-0 text-center fw-bold">
                    <thead class="table-light position-sticky top-0" style="z-index: 1;">
                        <tr>
                            {{-- <th>#</th> --}}
                            <th>Option</th>
                            <th>Number</th>
                            <th>Amt</th>
                            <th>Comb</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse (collect($stored_cross_abc_data)->sortBy('option') as $index => $d)
                            <tr text-center fw-bold>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $d['option'] }}</td>
                                <td>{{ $d['number'] }}</td>
                                <td>{{ $d['amt'] }}</td>
                                <td>{{ $d['combination'] }}</td>
                                <td>{{ $d['combination'] * $d['amt'] }}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger"
                                        wire:click="deleteCrossAbc({{ $index }})" title="Delete">
                                        🗑
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    @php
                        // base total
                        $totalAmt = collect($stored_cross_abc_data)->sum(
                            fn($d) => ($d['amt'] ?? 0) * ($d['combination'] ?? 0),
                        );

                        // Tolerant count for draws
                        $drawCount = 1;
                        if (isset($selected_draw)) {
                            $drawCount = is_countable($selected_draw) ? max(1, count($selected_draw)) : 1;
                        }

                        // Determine selected games count (try the common variables your template uses)
                        if (isset($selected_games) && is_countable($selected_games)) {
                            $gameCount = max(1, count($selected_games));
                        } elseif (!empty($selected_game_labels) && is_countable($selected_game_labels)) {
                            $gameCount = max(1, count($selected_game_labels));
                        } else {
                            $gameCount = 1;
                        }

                        // final total multiplies base amount by draws AND games
                        $finalTotal = $totalAmt * $drawCount * $gameCount;
                    @endphp


                    <tfoot class="table-light position-sticky bottom-0" style="z-index: 2; background: #fff;">
                        <tr>
                            <td colspan="1" class="text-center">
                                {{-- <div class="d-flex flex-column gap-2"> --}}
                                <button class="btn btn-sm btn-danger" wire:click="clearAllCrossAbcIntoCache">
                                    Clear All
                                </button>
                            </td>
                            <td colspan="6" class="fw-bold text-danger ">
                                <div class="d-flex justify-content-between align-items-center">

                                    <span> Total {{ $totalAmt }}
                                        FT (× {{ $drawCount }} draws ):
                                        {{ $finalTotal }} </span>
                                    <span>
                                        @error('draw_detail_cross')
                                            <div class="text-red-500">{{ $message }}</div>
                                        @enderror
                                        <span>
                                            <button class="btn btn-sm btn-primary" wire:click="submitTicket">
                                                Submit Ticket
                                            </button>

                            </td>

                        </tr>
                    </tfoot>

                </table>
            </div>
        </div>
    </div>

</div>


{{-- Print & Shortcut Script --}}
@script
    <script>
        let _activeIntervals = [];

        function _setManagedInterval(fn, ms) {
            let id = setInterval(fn, ms);
            _activeIntervals.push({
                id,
                fn,
                ms
            });
            return id;
        }

        function _clearManagedIntervals() {
            _activeIntervals.forEach(obj => clearInterval(obj.id));
            _activeIntervals = [];
        }

        function _restartManagedIntervals() {
            _activeIntervals.forEach(obj => clearInterval(obj.id));
            const existing = [..._activeIntervals];
            _activeIntervals = [];
            existing.forEach(obj => {
                let id = setInterval(obj.fn, obj.ms);
                _activeIntervals.push({
                    id,
                    fn: obj.fn,
                    ms: obj.ms
                });
            });
        }
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                _clearManagedIntervals();
            } else {
                _restartManagedIntervals();
            }
        });


        document.addEventListener('keydown', function(e) {
            // Detect Ctrl + F12
            if (e.ctrlKey && e.key === "F12") {
                e.preventDefault();
                @this.call('submitTicket');
            }
        });

        // After save -> print cross ticket
        Livewire.on('ticketSubmitted', () => {
            // skip partial printing if combined printer exists
            if (window.COMBINED_PRINTER) return;

            // keep old fallback for debugging / dev if no combined printer present:
            printSimpleTicket?.();
            printCrossTicket?.();
        });


        function printCrossTicket() {
            let content = document.getElementById("printCrossArea")?.innerHTML ?? '';
            let printWindow = window.open("", "", "width=300,height=600");
            printWindow.document.write(`
        <html>
            <head>
                <title>Print Cross Ticket</title>
                <style>
                    body { font-family: monospace; font-size: 12px; margin: 0; padding: 2px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 2px 0; }
                    th { font-weight: bold; }
                    hr { border: none; border-top: 1px dashed #000; margin: 4px 0; }
                </style>
            </head>
            <body>
                <div style="text-align:center; margin-bottom:4px;">
                    <strong>Ticket No:</strong> {{ $selected_ticket_number ?? '' }}<br>
                    <strong>Time:</strong> {{ implode(', ', (array) $selected_times) }}
                </div>
                <hr>
                ${content}
            </body>
        </html>
    `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.close();
        }
    </script>
@endscript
