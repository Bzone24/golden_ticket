<div class="card mb-3 h-100">

    <!-- Timer Header -->
    @include('livewire.ticket-data-form')

    <div class="card-header text-black">
        <div class="d-flex justify-content-between align-items-center px-2 py-1 border-bottom w-100">
            @php
                use Illuminate\Support\Str;

                // Decide time format based on presence of seconds
                $formatFromString = function ($s) {
                    return is_string($s) && substr_count($s, ':') >= 2 ? 'g:i:s A' : 'g:i A';
                };

                $addOneMinute = function ($t) use ($formatFromString) {
                    try {
                        $dt = \Illuminate\Support\Carbon::parse($t)->addMinute();
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
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $option['option'] }}</td>
                                <td>{{ $option['number'] }}</td>
                                <td>{{ $option['qty'] }}</td>
                                <td>{{ $option['total'] }}</td>
                                <td>
                                    <button class="btn btn-sm btn-danger" wire:click="deleteOption({{ $index }})"
                                        title="Delete">
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
                            <td colspan="6" class="fw-bold text-danger ">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        TQ: {{ $tq }}
                                        Total: {{ $total }}
                                        FT (× {{ $drawCount }} draws ):
                                        {{ $finalTotal }}
                                    </span>
                                    <span>
                                        @error('draw_detail_simple')
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

{{-- Print + Shortcut Script --}}
@script
    <script>
        // === minimal, non-invasive improvements to printing ===

        // keep this global flag name to stay compatible
        window.COMBINED_PRINTER = true;

        // short-lived guard to avoid duplicate prints (2 seconds)
        window._printingRecently = false;
        window._lastPrintArgs = null;

        // Utility: prevent duplicate triggers (by ticketNo + drawTime)
        function _shouldProceedWithPrint(ticketNo, drawTime) {
            const key = `${ticketNo}::${drawTime}`;
            if (window._printingRecently && window._lastPrintArgs === key) return false;
            window._printingRecently = true;
            window._lastPrintArgs = key;
            // reset after 2000ms
            setTimeout(() => {
                window._printingRecently = false;
                window._lastPrintArgs = null;
            }, 2000);
            return true;
        }

        // Faster cleanPanelHtml:
        // - minimize querySelectorAll calls
        // - remove only known action elements
        function cleanPanelHtml(elem, title) {
            if (!elem) return '';
            // if elem is container (like #printSimpleArea) we want to use its inner table only
            const table = elem.querySelector ? elem.querySelector('table') : null;
            const wrapper = document.createElement('div');
            wrapper.className = 'panel-wrap';

            if (title) {
                const h = document.createElement('div');
                h.textContent = title;
                h.style.fontWeight = '700';
                h.style.marginBottom = '6px';
                wrapper.appendChild(h);
            }

            // operate on a shallow clone of table or the element (avoid deep walking of entire document)
            const target = table ? table.cloneNode(true) : elem.cloneNode(true);

            // Remove interactive elements (single pass)
            const interactive = target.querySelectorAll('button, input, textarea, select, .no-print, .btn, a');
            for (let i = 0; i < interactive.length; i++) interactive[i].remove();

            // Remove Livewire attributes in a single pass
            const attrElements = target.querySelectorAll('[wire\\:click],[wire\\:key],[wire\\:model]');
            for (let i = 0; i < attrElements.length; i++) {
                attrElements[i].removeAttribute('wire:click');
                attrElements[i].removeAttribute('wire:key');
                attrElements[i].removeAttribute('wire:model');
            }

            // Remove Action column safely: look for header cell containing 'Action' or the last column
            const theadRow = target.querySelector('thead tr');
            if (theadRow) {
                const ths = theadRow.querySelectorAll('th');
                // find index of header with text 'Action' (case-insensitive) if exists
                let actionIdx = -1;
                for (let i = 0; i < ths.length; i++) {
                    if ((ths[i].textContent || '').trim().toLowerCase().includes('action')) {
                        actionIdx = i;
                        break;
                    }
                }
                if (actionIdx === -1 && ths.length > 0) actionIdx = ths.length - 1; // fallback to last
                if (actionIdx >= 0) {
                    if (ths[actionIdx]) ths[actionIdx].remove();
                    // remove corresponding td from each body row
                    const bodyRows = target.querySelectorAll('tbody tr');
                    for (let r = 0; r < bodyRows.length; r++) {
                        const tds = bodyRows[r].querySelectorAll('td');
                        if (tds.length > actionIdx) tds[actionIdx].remove();
                    }
                }
            }

            // Remove buttons inside tfoot but keep footer cell content
            const tfootBtns = target.querySelectorAll(
                'tfoot button, tfoot .btn, tfoot input, tfoot select, tfoot textarea, tfoot a');
            for (let i = 0; i < tfootBtns.length; i++) tfootBtns[i].remove();

            wrapper.appendChild(target);
            return wrapper.outerHTML;
        }

        // Lightweight visible-text fallback (same as before but faster)
        function extractVisibleTicketAndDraw() {
            const text = (document.body && document.body.innerText) ? document.body.innerText : '';
            let ticketNo = '';
            let drawText = '';

            const tn = text.match(/Ticket\s*No[:\s]*([A-Za-z0-9\-\_]+)/i);
            if (tn) ticketNo = tn[1].trim();

            const d = text.match(/Draw[:\s]*([^\n\r]+)/i);
            if (d) {
                drawText = d[1].trim().replace(/\s{2,}/g, ' ');
                drawText = drawText.split(/\r?\n/)[0].trim();
            }
            return {
                ticketNo,
                drawText
            };
        }

        // Try WebUSB or WebSerial ESC/POS printing (silent after permission) as the highest-priority silent path.
        // NOTE: this is a minimal starter: ESC/POS payload generation is printer-specific; this demonstrates the connect/transfer flow.
        async function tryDirectPrinter(bytesToSend) {
            // Try Web Serial first (many receipt printers expose serial-over-usb)
            if (navigator.serial) {
                try {
                    const ports = await navigator.serial.getPorts();
                    let port = ports.length ? ports[0] : null;
                    if (!port) {
                        // request user to select device (one-time permission)
                        port = await navigator.serial.requestPort();
                    }
                    await port.open({
                        baudRate: 19200
                    });
                    const writer = port.writable.getWriter();
                    await writer.write(bytesToSend);
                    writer.releaseLock();
                    await port.close();
                    return true;
                } catch (err) {
                    console.warn('WebSerial print failed:', err);
                    // fallthrough to other methods
                }
            }

            // Try WebUSB (some ESC/POS printers support simple USB bulk transfer)
            if (navigator.usb) {
                try {
                    const devices = await navigator.usb.getDevices();
                    let dev = devices.length ? devices[0] : null;
                    if (!dev) {
                        // this will open a device picker for the user
                        dev = await navigator.usb.requestDevice({
                            filters: []
                        }); // no filter to let user pick
                    }
                    await dev.open();
                    if (dev.configuration === null) await dev.selectConfiguration(1);
                    await dev.claimInterface(0);
                    // endpoint 1 is common but not guaranteed; real code should probe endpoints.
                    await dev.transferOut(1, bytesToSend);
                    await dev.close();
                    return true;
                } catch (err) {
                    console.warn('WebUSB print failed:', err);
                }
            }

            return false; // couldn't silently print
        }

        // Convert HTML text (string) to a basic ESC/POS byte stream (very naive; for small receipts)
        // In production you should use a dedicated library to format ESC/POS payloads.
        function htmlToEscPosBytes(html) {
            // Simplest approach: strip tags and send as UTF-8 bytes + line feeds; not full styling
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            const text = tmp.innerText || tmp.textContent || '';
            // basic ESC/POS line feed after each newline
            const encoder = new TextEncoder();
            const lines = text.split(/\r?\n/);
            const arr = [];
            for (let i = 0; i < lines.length; i++) {
                const b = encoder.encode(lines[i] + '\n');
                arr.push(b);
            }
            // concat into single Uint8Array
            let total = 0;
            arr.forEach(a => total += a.length);
            const out = new Uint8Array(total);
            let offset = 0;
            arr.forEach(a => {
                out.set(a, offset);
                offset += a.length;
            });
            return out;
        }

        // Main print function (keeps name for compatibility)
        function printStackedTicket() {
            // small defer to allow UI / Livewire to start changes
            setTimeout(async () => {
                // fast capture of elements (avoid many queries)
                const simpleElem = document.getElementById('printSimpleArea');
                const crossElem = document.getElementById('printCrossArea');
                const classAreas = simpleElem || crossElem ? null : Array.from(document.querySelectorAll(
                    '.print-area'));
                const simple = simpleElem || (classAreas && classAreas[0]) || null;
                const cross = crossElem || (classAreas && classAreas[1]) || classAreas && classAreas[0] || null;

                if (!simple && !cross) {
                    alert('No ticket content found to print (Simple / Cross panels missing).');
                    return;
                }

                const simpleHtml = simple ? cleanPanelHtml(simple, 'Simple ABC') : '';
                const crossHtml = cross ? cleanPanelHtml(cross, 'Cross ABC') : '';

                // server-provided values (unchanged)
                let ticketNo = {!! json_encode($selected_ticket->ticket_number ?? null) !!} || '';
                let drawTimeRaw = {!! json_encode($times ?? null) !!} || null;
                let drawTime = '';
                if (Array.isArray(drawTimeRaw)) {
                    drawTime = drawTimeRaw.join(', ');
                } else if (typeof drawTimeRaw === 'string' && drawTimeRaw.length) {
                    drawTime = drawTimeRaw;
                }

                // fallback to visible text if missing
                if (!ticketNo || !drawTime) {
                    const visible = extractVisibleTicketAndDraw();
                    if (!ticketNo && visible.ticketNo) ticketNo = visible.ticketNo;
                    if (!drawTime && visible.drawText) drawTime = visible.drawText;
                }

                ticketNo = ticketNo || '';
                drawTime = drawTime || '';

                // prevent duplicate prints of the same ticket/draw within short time
                if (!_shouldProceedWithPrint(ticketNo, drawTime)) return;
                const printHeaderTitle = document.getElementById('printHeaderTitle') ?
                    document.getElementById('printHeaderTitle').innerText.trim() : '';
                const styles = `
                    <style>
                    @page { size: auto; margin: 4mm; }
                    html, body { height: auto; }
                    body { font-family: monospace; font-size: 12px; margin: 0; padding: 4px; color:#000; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 4px 6px; border-bottom: 1px dotted #000; text-align: left; font-size:11px; }
                    thead th { font-weight:700; }
                    .section { margin-bottom: 6px; }
                    .title { font-weight:bold; text-align:center; margin: 6px 0; }
                    .panel-wrap { margin-bottom:6px; }
                    </style>
                `;

                        const html = `
                    <html>
                    <head><title>Print Ticket</title>${styles}</head>
                    <body>
                        <div class="title">Ticket No: ${ticketNo} | ${printHeaderTitle}</div>
                        <div class="section">${simpleHtml}</div>
                        <div class="section">${crossHtml}</div>
                    </body>
                    </html>
                `;

                // === Try silent direct print via WebSerial/WebUSB (if user/device supports it) ===
                try {
                    const escBytes = htmlToEscPosBytes(html);
                    const printed = await tryDirectPrinter(escBytes);
                    if (printed) {
                        // trigger DB submit flow remains same: Livewire will handle submitTicket elsewhere
                        return;
                    }
                } catch (err) {
                    console.warn('Direct printer attempt failed:', err);
                }

                // === If not silent-capable environment, prefer kiosk mode (no-dialog) if browser configured ===
                // You cannot detect kiosk-printing flag reliably from page; continue to iframe fallback.
                // === iframe fallback (improved, smaller, quicker) ===
                try {
                    const iframe = document.createElement('iframe');
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '380px';
                    iframe.style.height = '1px';
                    iframe.style.border = '0';
                    iframe.style.overflow = 'hidden';
                    iframe.setAttribute('aria-hidden', 'true');
                    document.body.appendChild(iframe);

                    const finalizeAndPrint = () => {
                        try {
                            setTimeout(() => {
                                iframe.contentWindow.focus();
                                iframe.contentWindow.print();
                                window.location.reload();
                                setTimeout(() => iframe.remove(), 1200);
                            }, 120);
                        } catch (err) {
                            // popup fallback (last resort)
                            const w = window.open('', '', 'width=380,height=600');
                            if (!w) {
                                alert('Popup blocked — allow popups to print.');
                                iframe.remove();
                                return;
                            }
                            w.document.open();
                            w.document.write(html);
                            w.document.close();
                            w.focus();
                            setTimeout(() => {
                                w.print();
                                w.close();
                            }, 200);
                        }
                    };

                    if ('srcdoc' in iframe) {
                        iframe.srcdoc = html;
                        iframe.onload = function() {
                            try {
                                const doc = iframe.contentDocument || iframe.contentWindow.document;
                                const body = doc.body;
                                setTimeout(() => {
                                    const height = Math.max(body.scrollHeight, body.offsetHeight,
                                        doc.documentElement.scrollHeight);
                                    iframe.style.height = (height + 10) + 'px';
                                    finalizeAndPrint();
                                }, 60);
                            } catch (err) {
                                finalizeAndPrint();
                            }
                        };
                    } else {
                        const idoc = iframe.contentWindow.document;
                        idoc.open();
                        idoc.write(html);
                        idoc.close();
                        iframe.onload = function() {
                            try {
                                const doc = iframe.contentDocument || iframe.contentWindow.document;
                                const body = doc.body;
                                setTimeout(() => {
                                    const height = Math.max(body.scrollHeight, body.offsetHeight,
                                        doc.documentElement.scrollHeight);
                                    iframe.style.height = (height + 10) + 'px';
                                    finalizeAndPrint();
                                }, 60);
                            } catch (err) {
                                finalizeAndPrint();
                            }
                        };
                    }
                } catch (e) {
                    // final fallback: popup
                    const w = window.open('', '', 'width=380,height=600');
                    if (!w) {
                        alert('Popup blocked — allow popups to print.');
                        return;
                    }
                    w.document.open();
                    w.document.write(html);
                    w.document.close();
                    w.focus();
                    setTimeout(() => {
                        w.print();
                        w.close();
                    }, 200);
                }

            }, 120);
        }

        Livewire.on('ticketSubmitted', () => {
            printStackedTicket();
        });

        // (NO click listener here any more)

        // Keep F12 shortcut if you want:
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12') {
                printStackedTicket();
            }
        });
    </script>
@endscript
