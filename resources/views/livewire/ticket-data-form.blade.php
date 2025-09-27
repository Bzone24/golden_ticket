<div class="card shadow-lg border-0 rounded-3 bg-dark text-light">

    <!-- Header -->
    <div class="card-header bg-primary bg-gradient text-white rounded-top">
        <div class="d-flex justify-content-between align-items-center w-100">

            <!-- Left side: Ticket Info -->
            <div class="d-flex flex-column">
                <h5 class="fw-bold mb-0">Ticket No: {{ $selected_ticket_number }}</h5>
            </div>

            <!-- Right side: Countdown -->
            <!-- Right side: Countdown - minimal replacement preserving UI -->
<div class="fw-bold"
     id="ticket-countdown"
     data-end-at="{{ $endAtMs ?? 0 }}"
     data-server-now="{{ $serverNowIso ?? '' }}"
     x-data="{
       endAtMs: null,
       serverNowMs: null,
       timeOffset: 0,
       secondsLeft: 0,
       timerId: null,

       init() {
         /* read authoritative values */
         this.endAtMs = Number($el.dataset.endAt) || null;
         this.serverNowMs = $el.dataset.serverNow ? Date.parse($el.dataset.serverNow) : null;

         /* compute offset between server and client if server time provided */
         if (this.serverNowMs) {
           this.timeOffset = this.serverNowMs - Date.now();
         } else {
           this.timeOffset = 0;
         }

         /* start ticking if we have an end time */
         if (this.endAtMs && this.endAtMs > 0) {
           this.tick();
           this.timerId = setInterval(() => this.tick(), 1000);
         } else {
           this.secondsLeft = 0;
         }
       },

       formatMMSS(sec) {
         const m = Math.floor((sec % 3600) / 60).toString().padStart(2, '0');
         const s = Math.floor(sec % 60).toString().padStart(2, '0');
         return m + ':' + s;
       },

       tick() {
         const now = Date.now() + this.timeOffset;
         const remainingMs = Math.max(0, (this.endAtMs || 0) - now);
         this.secondsLeft = Math.ceil(remainingMs / 1000);

         if (this.secondsLeft <= 0) {
           // stop timer
           if (this.timerId) { clearInterval(this.timerId); this.timerId = null; }

           // reload page when countdown reaches zero so server can provide next draw/time
           // small delay to ensure UI shows 00:00 briefly
           setTimeout(() => { window.location.reload(); }, 250);
         }
       }
     }"
     x-init="init()"
>
    <span class="badge bg-danger text-white px-3 py-2" style="font-size: larger;">
        ⏳
        <span x-text="'Time Left: ' + (formatMMSS(secondsLeft))"></span>
    </span>
</div>

        </div>
    </div>

    <!-- Body -->
</div>
