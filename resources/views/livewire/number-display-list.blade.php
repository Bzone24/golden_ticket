<div class="card mb-3 h-100 text-white" style="background:#5b1e63;">

    <!-- Timer Header -->
    @include('livewire.ticket-data-form')

    <div class="card-header text-white">
        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom w-100">
            @php
                use Illuminate\Support\Str;
                use Illuminate\Support\Carbon;

                // Helper: detect whether a time string contains seconds
                $formatFromString = function ($s) {
                    return is_string($s) && substr_count($s, ':') >= 2 ? 'g:i:s A' : 'g:i A';
                };

                $addOneMinute = function ($t) use ($formatFromString) {
                    try {
                        $dt = Carbon::parse($t)->addMinute();
                        $fmt = $formatFromString((string) $t);
                        return $dt->format($fmt);
                    } catch (\Throwable $e) {
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

                // Build labels (preserve original intent)
                $labels = !empty($selected_game_labels)
                    ? $selected_game_labels
                    : collect($games ?? [])
                        ->whereIn('id', $selected_games ?? [])
                        ->map(fn($g) => strtoupper($g->code ?? ($g->short_code ?? ($g->name ?? ''))))
                        ->values()
                        ->all();
            @endphp

            <h6 class="mb-0 w-100 text-center" id="printHeaderTitle">
                @foreach ($this->selectedDraws as $key => $draw)
                    <strong>Draw:</strong> {{ $draw->formatResultTime() }} ,
                    {{ $draw->draw->game->name }} |
                @endforeach
            </h6>
        </div>
    </div>

    <div class="card-body pb-0 py-0">
        {{-- Simple ABC Section --}}
        <div class="mb-0">
            <h5 class="fw-semibold">Simple ABC</h5>

            <!-- Fixed height + scroll only inside table area -->
            <div id="printSimpleArea" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-bordered table-striped table-hover mb-0 text-center fw-bold">
                    <thead class="table-light position-sticky top-0" style="z-index: 1;">
                        <tr>
                            {{-- <th>#</th> --}}
                            <th>Option</th>
                            <th>Number</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse (collect($stored_options)->sortBy('option') as $index => $option)
                            <tr class="text-center fw-bold">
                                {{-- <td>{{ $loop->index + 1 }}</td> --}}
                                <td>{{ $option['option'] }}</td>
                                <td>{{ $option['number'] }}</td>
                                <td>{{ $option['qty'] }}</td>
                                <td>{{ $option['total'] }}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger" wire:click="deleteOption({{ $index }})"
                                        title="Delete">🗑</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    @php
                        // totals (kept exact same calculations & constants)
                        $total = collect($stored_options)->sum('total');
                        $tq = $total > 0 ? floor($total / 11) : 0;

                        $drawCount = max(1, is_countable($selected_draw) ? count($selected_draw) : 1);

                        if (isset($selected_games) && is_countable($selected_games)) {
                            $gameCount = max(1, count($selected_games));
                        } elseif (!empty($selected_game_labels) && is_countable($selected_game_labels)) {
                            $gameCount = max(1, count($selected_game_labels));
                        } else {
                            $gameCount = 1;
                        }

                        $finalTotal = $total * $drawCount * $gameCount;
                    @endphp

                    <tfoot class="table-light position-sticky bottom-0" style="z-index: 2; background: #fff;">
                        <tr>
                            <td colspan="1" class="text-center">
                                <button class="btn btn-sm btn-danger" wire:click="clearAllOptionsIntoCache()">
                                    Clear All
                                </button>
                            </td>
                            <td colspan="6" class="text-end">
                                @error('draw_detail_simple')
                                    <div class="text-red-500">{{ $message }}</div>
                                @enderror
                                <button class="btn btn-sm btn-primary" wire:click="submitTicket">
                                    Submit Ticket
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="7" class="fw-bold text-danger text-start totals-row">
                                TQ: {{ $tq }}
                                &nbsp; | &nbsp; Total: {{ $total }}
                                &nbsp; | &nbsp; FT (× {{ $drawCount }} draws ): {{ $finalTotal }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Print + Shortcut Script --}}
@script
<script>
document.addEventListener('ticket-submitted', function(e) {
    try {
        const rawDetail = (e && e.detail && e.detail.payload) ? e.detail.payload : (e && e.detail ? e.detail : {});
        const ticketNo = rawDetail.ticket_number || '';
        const drawsArr = Array.isArray(rawDetail.draws) ? rawDetail.draws : [];

        let drawsStr = '';
        if (drawsArr.length) {
            drawsStr = drawsArr.map(d => {
                const time = d.time || d.t || '';
                const game = d.game || d.g || '';
                return (time ? time : '') + (game ? (time ? ' , ' : '') + '(' + game + ')' : '');
            }).join(' | ');
        } else if (Array.isArray(rawDetail.times) && rawDetail.times.length) {
            drawsStr = rawDetail.times.join(' | ');
        } else if (Array.isArray(rawDetail.labels) && rawDetail.labels.length) {
            drawsStr = rawDetail.labels.join(' | ');
        }

        const header = `
            <div style="text-align:center; margin-bottom:4px;">
                <strong>Ticket No:</strong> ${ticketNo || ''}<br>
                <strong>Draw:</strong> ${drawsStr}
            </div>
            <hr>
        `;

        function cleanClone(areaId, sectionTitle) {
            const orig = document.getElementById(areaId);
            if (!orig) return '';

            const clone = orig.cloneNode(true);

            // remove buttons/UI controls
            clone.querySelectorAll('button, .btn, [type="button"]').forEach(x => x.remove());

            // remove style attributes that limit height/overflow/positioning
            clone.querySelectorAll('[style]').forEach(el => {
                // keep essential inline styles (rare), but remove scrolling/fixed ones
                const s = el.getAttribute('style') || '';
                // if style contains max-height/overflow/position:sticky/remove them
                const newStyle = s
                    .replace(/max-height:[^;]+;?/ig, '')
                    .replace(/overflow[^;]+;?/ig, '')
                    .replace(/position:\s*sticky;?/ig, '')
                    .replace(/top:[^;]+;?/ig, '')
                    .replace(/bottom:[^;]+;?/ig, '')
                    .trim();
                if (newStyle) el.setAttribute('style', newStyle);
                else el.removeAttribute('style');
            });

            // remove position-sticky headers/footers (they can cause extra space in print)
            clone.querySelectorAll('.position-sticky').forEach(x => x.classList.remove('position-sticky'));

            // Remove "Action" column and its cells by header detection
            const table = clone.querySelector('table');
            if (table) {
                const ths = Array.from(table.querySelectorAll('th'));
                let actionIndex = -1;
                ths.forEach((th, i) => {
                    if ((th.innerText || '').toLowerCase().includes('action')) {
                        actionIndex = i;
                        th.remove();
                    }
                });
                if (actionIndex > -1) {
                    Array.from(table.querySelectorAll('tr')).forEach(tr => {
                        // keep totals-row intact
                        if (tr.classList.contains('totals-row')) return;
                        if (tr.cells[actionIndex]) tr.deleteCell(actionIndex);
                    });
                }
            }

            // Remove any scrollbars placeholders and allow natural height
            clone.querySelectorAll('*').forEach(el => {
                if (getComputedStyle(el).overflow === 'auto' || getComputedStyle(el).overflowY === 'auto') {
                    el.style.overflow = 'visible';
                }
            });

            return `<h4 style="margin:6px 0 4px 0; font-weight:bold;">${sectionTitle}</h4>` + clone.innerHTML;
        }

        const simpleHTML = cleanClone('printSimpleArea', 'Simple ABC');
        const crossHTML  = cleanClone('printCrossArea',  'Cross ABC');
        const content = header + simpleHTML + crossHTML;

        const win = window.open('', '', 'width=400,height=600');
        win.document.write(`
            <html>
            <head>
                <title>Print Ticket</title>
                <style>
                    /* Important: set page size to thermal roll width (72mm). Adjust to 58mm if you use 58mm paper. */
                    @page { size: 72mm auto; margin: 0; }
                    @media print {
                        html, body { margin: 0; padding: 2mm; width: 72mm; height: auto; }
                        /* Avoid page breaks inside tables/rows */
                        table { page-break-inside: avoid; border-collapse: collapse; width:100%; }
                        tr, td, th { page-break-inside: avoid; page-break-after: auto; }
                        thead { display: table-row-group; } /* keep header rules consistent */
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px; /* adjust to taste */
                        font-weight: 600;
                        margin: 0;
                        padding: 4px;
                        width: 72mm;
                    }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #000; }
                    hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
                    .totals-row { font-weight: 700; border-top: 1px solid #000; }
                </style>
            </head>
            <body>${content}
                
                  <script>
            window.onafterprint = function() {
                window.close();
            };
        <\/script>

                </body>
            </html>
        `);
        win.document.close();

 

// When printing finishes, close the popup
// win.onafterprint = () => {
//     try { win.close(); } catch (ignore) {}
//     try { window.close(); } catch (ignore) {}
// };

setTimeout(() => {
    try { win.focus(); } catch (ignore) {}
    try { win.print(); } catch (ignore) {}
}, 300); } catch (err) { } 

});
</script>

@endscript
