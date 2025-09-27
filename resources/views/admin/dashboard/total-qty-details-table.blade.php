@extends('admin.layouts.base')
@section('title', 'Cross ABC Details')
@section('contents')

@push('custom-css')
<style>
    /* ---------- Base / Table styles (kept from original) ---------- */
    body {
        background: #f8fafc !important;
        font-size: 1.05rem !important;
        font-weight: 500 !important;
        color: #15171a !important;
    }
    h4 { font-weight: 700; font-size: 1.3rem; }

    .card {
        border-radius: 14px !important;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08) !important;
        overflow: visible; /* allow pills to pop out */
        position: relative;
    }
    .card-header {
        font-weight: bold;
        font-size: 1.2rem;
        padding: 15px;
        border-radius: 14px 14px 0 0 !important;
    }

    table.table {
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    table thead th {
        background: #121315;
        color: #e70606 !important;
        text-transform: uppercase;
        font-size: 0.95rem;
        padding: 12px;
        text-align: center;
    }
    table tbody td {
        text-align: center;
        font-weight: 600;
        vertical-align: middle;
        padding: 10px;
    }
    .table thead tr { background-color: #1e3a8a !important; }
    .table thead th {
        background-color: #1e3a8a !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        text-align: center;
    }
    tbody tr:hover { background: #f1f5f9 !important; transition: 0.3s; }

    td.bg-success, td.bg-warning, td.bg-info { font-weight: bold; font-size: 1.1rem; }
    td.option-a { background: #166534 !important; color: #fff !important; font-weight: bold !important; }
    td.option-b { background: #b45309 !important; color: #fff !important; font-weight: bold !important; }
    td.option-c { background: #1e40af !important; color: #fff !important; font-weight: bold !important; }

    td.bg-danger { font-weight: bold; font-size: 1rem; }
    td.bg-success.text-white { background-color: #28a745 !important; font-weight: 700; border-radius: 6px; }
    td.bg-danger.text-white  { background-color: #dc3545 !important; font-weight: 700; border-radius: 6px; }

    tbody tr:last-child { background: #f0f4f8; font-size: 1.1rem; font-weight: bold; }
    tbody tr:last-child td { padding: 12px; }

    /* Flash animations */
    .flash-green {
        background-color: #28a745 !important;
        color: #fff !important;
        animation: flashFadeGreen 2s ease forwards;
    }
    .flash-red {
        background-color: #dc3545 !important;
        color: #fff !important;
        animation: flashFadeRed 2s ease forwards;
    }
    @keyframes flashFadeGreen {
        0% { background-color: #28a745; color: #fff; }
        100% { background-color: inherit; color: inherit; }
    }
    @keyframes flashFadeRed {
        0% { background-color: #dc3545; color: #fff; }
        100% { background-color: inherit; color: inherit; }
    }

    /* ---------- Draw strip base & layout ---------- */
    .draw-strip-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 8px;
        margin-top: -20px; /* pull up to overlap card */
        position: relative;
        z-index: 6;
    }
    .draw-strip {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding: 8px 6px;
        scroll-behavior: smooth;
        flex: 1 1 auto;
        align-items: center;
    }

    .draw-scroll-btn {
        flex: 0 0 36px;
        height: 36px;
        border-radius: 50%;
        border: none;
        background: #ffffff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        z-index: 8;
    }
    .draw-scroll-btn:active { transform: translateY(1px); }

    /* ---------- Pill styling (consolidated) ---------- */
    .draw-pill {
        /* min-width: 76px; */
        max-width: 180px;
        border-radius: 999px;
        background: #fff;
        padding: 8px 12px;
        box-shadow: 0 6px 14px rgba(16,24,40,0.06);
        text-align: center;
        font-size: 12px;
        position: relative;
        display: inline-flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: default;
        border: 1px solid #eef2f7;
        transform: translateY(-6px); /* lift above card */
    }

    .draw-pill .pill-top { font-weight: 700; font-size: 10px; color: #6b7280; }
    .draw-pill .pill-time { font-weight: 800; font-size: 14px; margin-top: 2px; color: #111827; }

    /* game-badge inside pill */
    .game-badge {
        display:inline-block;
        padding:4px 8px;
        border-radius:999px;
        font-weight:700;
        font-size:0.78rem;
        color:#fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        white-space: nowrap;
    }

    /* active/current pill */
    .draw-pill.current-draw {
        background: #e74c3c;
        color: #fff;
        box-shadow: 0 12px 28px rgba(231,76,60,0.14);
        border-color: rgba(0,0,0,0.08);
        transform: translateY(-10px) scale(1.02);
        z-index: 10;
    }

    /* live-dot */
    .live-dot {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #00c853;
        box-shadow: 0 0 6px rgba(0,200,83,0.6);
        animation: pulse 1.4s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.9); opacity: 0.9; }
        70% { transform: scale(1.15); opacity: 0.6; }
        100% { transform: scale(0.9); opacity: 0.9; }
    }

    /* small responsive tweaks */
    @media (max-width: 900px) {
        .draw-strip-wrapper { margin-top: -12px; padding: 6px 6px; }
        .draw-pill { min-width: 68px; padding: 6px 8px; gap: 6px; transform: translateY(-4px); }
        .draw-pill.current-draw { transform: translateY(-6px) scale(1.01); }
        .game-badge { padding:3px 6px; font-size:0.7rem; }
        .draw-strip { gap: 10px; }
    }

    /* small thin scroll bar for strip */
    .draw-strip::-webkit-scrollbar { height: 8px; }
    .draw-strip::-webkit-scrollbar-thumb { background: rgba(16,24,40,0.12); border-radius: 999px; }

    /* ensure card-body doesn't clip */
    .card-body { overflow: visible; min-height: 0 !important; }
</style>
@endpush

<div class="container-fluid">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">
                Details of Total Qty: {{ $drawDetail->total_qty }} {{ $drawDetail->formatEndTime() }}
            </li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                    <h4 class="text-white mb-0 d-flex justify-content-between w-100">
                        <span>Details Of Total Qty (Time: {{ $drawDetail->formatEndTime() }})
                            
                        </span>
                        <span>Game: {{$drawDetail->draw->game->name}}</span>
                    </h4>
                </div>

                {{-- ⬇️ Table container (auto-refreshes) --}}
                <div class="card-body" id="crossAbcTableContainer">
                    @include('admin.dashboard.partials.total-qty-details-table', ['drawDetail' => $drawDetail])
                </div>

                {{-- Draw strip generation (kept your PHP logic intact) --}}
                @php
                    use Illuminate\Support\Carbon;

                    function game_color(string $name) {
                        $h = crc32($name) % 360;
                        return "hsl({$h}, 65%, 45%)";
                    }

                    $now = Carbon::now();

                    if(!empty($draw_list) && count($draw_list)) {
                        $items = collect($draw_list)->map(function($d) use ($now, $drawDetail) {
                            $gameName = optional($d->draw->game)->name ?? optional($d->game)->name ?? ($d->game_name ?? 'Game');
                            try {
                                $start = isset($d->start_time) ? Carbon::parse($d->start_time) : null;
                                $end = isset($d->end_time) ? Carbon::parse($d->end_time) : null;
                            } catch (\Exception $e) { $start = $end = null; }
                            $isCurrent = isset($drawDetail) && ($d->id === $drawDetail->id);
                            $isLive = ($start && $end) ? $now->between($start, $end) : false;
                            return [
                                'id' => $d->id,
                                'label' => \Illuminate\Support\Str::upper($d->name ?? ($d->label ?? 'Draw')),
                                'time' => $d->display_time ?? ($d->start_time ? Carbon::parse($d->start_time)->format('H:i') : null),
                                'isCurrent' => $isCurrent,
                                'isLive' => $isLive,
                                'game_name' => $gameName,
                                'game_color' => game_color($gameName),
                                'start_time' => $start ? $start->format('Y-m-d H:i:s') : null,
                                'end_time' => $end ? $end->format('Y-m-d H:i:s') : null,
                            ];
                        })->all();

                    } elseif(!empty($start_time) && !empty($end_time)) {
                        $interval = $interval_minutes ?? 15;
                        try {
                            $start = Carbon::parse($start_time);
                            $end   = Carbon::parse($end_time);
                        } catch (\Exception $e) {
                            $start = Carbon::createFromTimeString('09:00');
                            $end = Carbon::createFromTimeString('10:15');
                        }
                        $items = [];
                        $index = 0;
                        for ($t = $start->copy(); $t->lte($end); $t->addMinutes($interval)) {
                            $gameName = 'N1';
                            $items[] = [
                                'id' => 'fallback-'.$index,
                                'label' => 'Draw',
                                'time' => $t->format('H:i'),
                                'isCurrent' => ($index === 0),
                                'isLive' => ($index === 0),
                                'game_name' => $gameName,
                                'game_color' => game_color($gameName),
                                'start_time' => $t->format('Y-m-d H:i:s'),
                                'end_time' => $t->copy()->addMinutes($interval)->format('Y-m-d H:i:s'),
                            ];
                            $index++;
                        }
                    } else {
                        $sample = ['09:00','09:15','09:30','09:45','10:00','10:15'];
                        $items = array_map(function($t, $i){
                            $gameName = ($i % 2 == 0) ? 'N1' : 'N2';
                            return [
                                'id' => 'sample-'.$i,
                                'label' => 'Draw',
                                'time' => $t,
                                'isCurrent' => $i === 0,
                                'isLive' => $i === 0,
                                'game_name' => $gameName,
                                'game_color' => game_color($gameName),
                                'start_time' => null,
                                'end_time' => null,
                            ];
                        }, $sample, array_keys($sample));
                    }
                @endphp

                {{-- Draw strip markup --}}
                <div class="draw-strip-wrapper">
                    <button class="draw-scroll-btn left" aria-label="scroll left">&lt;</button>

                    <div id="draw-strip" class="draw-strip" tabindex="0" role="list">
                        @foreach($items as $it)
                            @php
                                $gameBorder = $it['game_color'] ?? '#ddd';
                                // parenthesized ternary to avoid PHP parsing ambiguity
                                $bgTint = $it['isCurrent']
                                    ? ''
                                    : (
                                        \Illuminate\Support\Str::startsWith($gameBorder, 'hsl')
                                            ? 'border: 1px solid ' . $gameBorder . ';'
                                            : 'border-color: ' . $gameBorder . ';'
                                      );
                            @endphp

                            <div
                                class="draw-pill {{ $it['isLive'] ? 'current-draw' : '' }}"
                                role="listitem"
                                data-draw-id="{{ $it['id'] }}"
                                data-start-time="{{ $it['start_time'] }}"
                                data-end-time="{{ $it['end_time'] }}"
                                aria-current="{{ $it['isCurrent'] ? 'true' : 'false' }}"
                                title="{{ $it['label'] }} {{ $it['time'] }}"
                                style="{{ $bgTint }}"
                            >
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="text-align:left;">
                                        <div class="pill-top">{{ $it['label'] }}</div>
                                        <div class="pill-time">{{ $it['time'] }}</div>
                                    </div>

                                    {{-- small game badge --}}
                                    <div style="margin-left:8px;">
                                        <span class="game-badge" style="background: {{ $it['game_color'] }};">
                                            {{ $it['game_name'] }}
                                        </span>
                                    </div>
                                </div>

                                @if($it['isLive'] && $it['isLive'])
                                    <span class="live-dot" aria-hidden="true"></span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <button class="draw-scroll-btn right" aria-label="scroll right">&gt;</button>
                </div>
                {{-- end draw strip --}}

            </div>
        </div>
    </div>
</div>

{{-- Auto Refresh + highlight changes (kept as-is) --}}
@push('custom-js')
<script>
    // Auto-refresh table code (kept your behavior)
    setInterval(function () {
        // grab old values before reload
        let oldValues = [];
        $("#crossAbcTableContainer table tbody tr").each(function () {
            let rowVals = [];
            $(this).find("td").each(function () {
                rowVals.push($(this).text().trim());
            });
            oldValues.push(rowVals);
        });

        // reload
        $("#crossAbcTableContainer").load(window.location.href + " #crossAbcTableContainer>*", function () {
            // after reload, compare new values to old ones
            $("#crossAbcTableContainer table tbody tr").each(function (rowIndex) {
                $(this).find("td").each(function (colIndex) {
                    let newVal = $(this).text().trim();
                    let oldVal = (oldValues[rowIndex] ?? [])[colIndex];

                    if (newVal !== oldVal && oldVal !== undefined && newVal !== "") {
                        let newNum = parseFloat(newVal.replace(/,/g, ""));
                        let oldNum = parseFloat(oldVal.replace(/,/g, ""));

                        if (!isNaN(newNum) && !isNaN(oldNum)) {
                            if (newNum > oldNum) {
                                $(this).addClass("flash-green");
                                setTimeout(() => $(this).removeClass("flash-green"), 2000);
                            } else if (newNum < oldNum) {
                                $(this).addClass("flash-red");
                                setTimeout(() => $(this).removeClass("flash-red"), 2000);
                            }
                        } else {
                            // fallback for text changes (non-numeric)
                            $(this).addClass("flash-green");
                            setTimeout(() => $(this).removeClass("flash-green"), 2000);
                        }
                    }
                });
            });
        });
    }, 10000); // refresh every 10s
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const strip = document.getElementById('draw-strip');
    const btnLeft = document.querySelector('.draw-scroll-btn.left');
    const btnRight = document.querySelector('.draw-scroll-btn.right');

    if (!strip) return;

    // Scroll amount: 60% of container width
    const scrollAmount = Math.round(strip.clientWidth * 0.6);

    btnLeft && btnLeft.addEventListener('click', () => strip.scrollBy({ left: -scrollAmount, behavior: 'smooth' }));
    btnRight && btnRight.addEventListener('click', () => strip.scrollBy({ left: scrollAmount, behavior: 'smooth' }));

    // Mouse wheel horizontal scroll
    strip.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            strip.scrollBy({ left: e.deltaY, behavior: 'auto' });
        }
    }, { passive: false });

    // Drag-to-scroll
    let isDown = false, startX, scrollLeft;
    strip.addEventListener('mousedown', (e) => {
        isDown = true;
        strip.classList.add('dragging');
        startX = e.pageX - strip.offsetLeft;
        scrollLeft = strip.scrollLeft;
    });
    document.addEventListener('mouseup', () => { isDown = false; strip.classList.remove('dragging'); });
    document.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - strip.offsetLeft;
        const walk = (x - startX);
        strip.scrollLeft = scrollLeft - walk;
    });

    // Auto-center the current draw
    const current = strip.querySelector('.draw-pill.current-draw');
    if (current) {
        const stripRect = strip.getBoundingClientRect();
        const pillRect = current.getBoundingClientRect();
        const offset = (pillRect.left + pillRect.width/2) - (stripRect.left + stripRect.width/2);
        strip.scrollBy({ left: offset, behavior: 'smooth' });
    } else {
        strip.scrollLeft = 0;
    }

    // Accessibility: arrow keys
    strip.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { strip.scrollBy({ left: -120, behavior: 'smooth' }); e.preventDefault(); }
        if (e.key === 'ArrowRight') { strip.scrollBy({ left: 120, behavior: 'smooth' }); e.preventDefault(); }
    });

    // Keep live-dot updated based on start/end times
    setInterval(() => {
        const now = new Date();
        strip.querySelectorAll('.draw-pill').forEach(p => {
            const s = p.getAttribute('data-start-time');
            const e = p.getAttribute('data-end-time');
            const hasLiveDot = !!p.querySelector('.live-dot');
            if (s && e) {
                const start = new Date(s);
                const end = new Date(e);
                if (now >= start && now <= end) {
                    if (!hasLiveDot) {
                        const dot = document.createElement('span');
                        dot.className = 'live-dot';
                        dot.setAttribute('aria-hidden','true');
                        p.appendChild(dot);
                    }
                } else {
                    if (hasLiveDot) {
                        p.querySelectorAll('.live-dot').forEach(n => n.remove());
                    }
                }
            }
        });
    }, 1000 * 10); // every 10s
});
</script>
@endpush

@endsection
