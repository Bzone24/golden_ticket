<div class="row g-1 mb-2 h-50" style="background: #150512;">
    <!-- Simple ABC Display -->
    <div class="col-lg-4 col-md-6">
        @include('livewire.number-display-list', ['type' => 'simple'])
    </div>

    <!-- Cross ABC Display -->
    <div class="col-lg-4 col-md-6">
        @include('livewire.number-display-list-cross', ['type' => 'cross'])
    </div>


    <div class="col-lg-4 col-md-6 d-flex flex-column gap-2">
        @include('livewire.ticket-list')
        {{-- @include('livewire.latest-draw-details-list') --}}
    </div>

    <!-- ===== Lower Section: 3 cards side by side ===== -->
    <div class="row g-1">
         <!-- 🔹 Game Selection Dropdown -->
 
        <!-- Simple ABC Entry -->
            <!-- Simple ABC Entry + Shortcuts -->
        <div class="col-lg-4 col-md-6 h-100">
            <div class="d-flex flex-column gap-2">
                <!-- Simple ABC -->
                @include('livewire.simple-abc')

                <!-- Shortcuts -->
                {{-- <div class="card shadow-sm border-0 rounded-3 bg-dark text-light">
                    <div class="card-header bg-danger bg-gradient text-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-keyboard me-2"></i> Shortcuts
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <ul class="list-unstyled small mb-0">
                            <li><kbd>Ctrl</kbd> + <kbd>A</kbd> → Focus <strong>A</strong> <kbd>Ctrl</kbd> + <kbd>B</kbd> → Focus <strong>B</strong> <kbd>Ctrl</kbd> + <kbd>C</kbd> → Focus <strong>C</strong> </li>
                            <li></li>
                            
                            <li><kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>A</kbd> → Focus <strong>ABC</strong></li>
                            <li><strong style="color:red;">For Cross Shortcuts</strong></li>
                            <li><strong>Ctrl + 1   → Focus A --- Ctrl + 2   → Focus B  ---- Ctrl + 3   → Focus C </strong></li>
                            <li><strong> Ctrl+ Shift + C    → Focus ABC </strong></li>

                           

                        </ul>
                    </div>
                </div> --}}
            </div>
        </div>

        <!-- Cross ABC Entry -->
        <div class="col-lg-4 col-md-6">
            @include('livewire.cross-abc')
       

        <!-- Draw List -->
        <div class="col-lg-4 col-md-6">
            @include('livewire.draw-list')
        </div>
         </div>
    </div>
</div>

@script
<script>
  $(document).ready(function () {
    // Generic infinite scroll listeners (preserved)
    [
      'ticket-scroller-box',
      'draw-box',
      'option-list',
      'cross-data-list',
      'latest-draw-list'
    ].forEach(cls => {
      $(document).on('scroll', '.' + cls, function () {
        let box = $(this);
        let scrollTop = box.scrollTop();
        let innerHeight = box.innerHeight();
        let scrollHeight = box[0].scrollHeight;

        // keep page variables for Livewire
        let pageVar = cls.replace(/-./g, x => x[1].toUpperCase()) + '_page';
        let page = $wire.get(pageVar);

        if (scrollTop + innerHeight >= scrollHeight - 10) {
          page++;
          $wire.set(pageVar, page);
        }
      });
    });

    // Route the server "refresh-window" event through our lightweight handler
    // (server-side Livewire dispatch('refresh-window') will trigger this)
    $wire.on('refresh-window', (payload) => {
      onCountdownZero(payload ?? {});
    });
  });

  // ---------- Lightweight, single-shot refresh handler ----------
  window.__drawCountdownFired = window.__drawCountdownFired || false;

  function onCountdownZero(payload = {}) {
    if (window.__drawCountdownFired) return;
    window.__drawCountdownFired = true;

    // 1) Dispatch the same browser events your server dispatches on ticket submit
    //    (these are listened by your frontend and are very fast)
    try {
      window.dispatchEvent(new CustomEvent('ticket-submitted', { detail: payload }));
      window.dispatchEvent(new CustomEvent('ticketSubmitted', { detail: payload }));
      window.dispatchEvent(new Event('refresh-window')); // keep for backward compatibility
      window.dispatchEvent(new CustomEvent('drawsRefreshed', { detail: payload }));
      window.dispatchEvent(new CustomEvent('draws-refreshed', { detail: payload }));
    } catch (e) {
      console.debug('dispatch events error', e);
    }

    // 2) Lightweight client-side reload helper (if present)
    try {
      if (typeof window.__reloadDrawsNow === 'function') {
        window.__reloadDrawsNow();
      }
    } catch (e) {
      console.debug('__reloadDrawsNow failed', e);
    }

    // 3) Optional: call $wire.refresh() / $wire.call('refresh') if available on the client.
    // This attempts to re-render the Livewire component in-place (fast) but will not error if not present.
    try {
      if (window.$wire) {
        // prefer explicit refresh() if provided
        if (typeof window.$wire.refresh === 'function') {
          window.$wire.refresh();
        } else if (typeof window.$wire.call === 'function') {
          // call a method 'refresh' on the component if you expose one server-side
          // (you can create a public method refresh() in the component that just calls $this->refresh())
          window.$wire.call('refresh');
        }
      }
    } catch (e) {
      console.debug('$wire refresh/call failed', e);
    }

    // 4) Cross-tab sync (so other open tabs can react)
    try {
      localStorage.setItem('draw-ended', JSON.stringify({ payload, ts: Date.now() }));
    } catch (e) {
      // ignore storage errors
    }

    // 5) LAST RESORT fallback: tiny full reload if nothing else handled it.
    // Delay slightly to allow handlers to run first.
    setTimeout(() => {
      const didSomethingLight = (
        (typeof window.__reloadDrawsNow === 'function') ||
        (window.$wire && (typeof window.$wire.refresh === 'function' || typeof window.$wire.call === 'function'))
      );
      if (!didSomethingLight) {
        location.reload();
      }
    }, 250);
  }

  // ---------- Wire up existing events to the new handler ----------
  (function initCountdownBindings() {
    // Attach our handler to the events other code might fire.
    // Use { once: true } to avoid duplicate handling.
    window.addEventListener('countdownZero', (e) => onCountdownZero(e?.detail ?? {}), { once: true });
    window.addEventListener('drawsRefreshed', (e) => onCountdownZero(e?.detail ?? {}), { once: true });
    window.addEventListener('draws-refreshed', (e) => onCountdownZero(e?.detail ?? {}), { once: true });
    window.addEventListener('refresh-window', (e) => onCountdownZero(e?.detail ?? {}), { once: true });

    // Some server-side dispatches may trigger Livewire JS hooks — keep them routed here
    if (window.Livewire && typeof Livewire.on === 'function') {
      Livewire.on('drawsRefreshed', (payload) => onCountdownZero(payload ?? {}));
      Livewire.on('refresh-window', (payload) => onCountdownZero(payload ?? {}));
    }

    // Storage listener for cross-tab sync
    window.addEventListener('storage', (e) => {
      if (e.key === 'draw-ended') {
        try {
          const p = JSON.parse(e.newValue || '{}');
          onCountdownZero(p.payload ?? {});
        } catch (err) {
          // ignore parse errors
        }
      }
    });

    // BroadcastChannel for faster tab messaging (if supported)
    try {
      if ('BroadcastChannel' in window) {
        const bc = new BroadcastChannel('draw-channel');
        bc.onmessage = (m) => {
          if (m?.data?.type === 'draw-ended') onCountdownZero(m.data.payload ?? {});
        };
        window.__drawBroadcastChannel = bc;
      }
    } catch (err) {
      // ignore
    }
  })();
</script>
@endscript

