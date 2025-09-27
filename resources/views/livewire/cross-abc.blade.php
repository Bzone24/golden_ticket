<div class="card shadow-lg border-0 rounded-3 bg-dark text-light py-0">
    <!-- Header -->
    <div class="card-header bg-danger bg-gradient text-white rounded-top py-0">
        <h3 class="text-center mb-0 py-0">
            <i class="bi bi-shuffle me-2"></i> Cross ABC
        </h3>
    </div>

    <!-- Body -->
    <div class="card-body cross-body" style="background-color:#212120;"
         x-data
         x-init="$nextTick(() => { /* Alpine root — required for your window events */ })">

        <!-- ABC + Amt + Comb -->
        <div class="row mb-3 p-2 rounded bg-cross-abc"
             aria-label="Cross ABC quick entry">
            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_abc">ABC</label>
                <input type="text" id="cross_abc"
                       wire:model.defer="cross_abc_input"
                       x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-abc-qty'))"
                       class="form-control bg-light text-dark border-warning cross-input cross_number"
                       placeholder="Enter ABC">
                @error('cross_abc_input') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_qty">Amt</label>
                <input type="text" id="cross_qty"
                       wire:model.defer="cross_abc_amt"
                       x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-abc-combination'))"
                       class="form-control bg-light text-dark border-warning cross-input mynumber"
                       placeholder="Enter Amt">
                @error('cross_abc_amt') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>

            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_combination">Comb</label>
                <!-- final Enter still calls Livewire save method (unchanged) -->
                <input type="text" id="cross_combination"
                       wire:model.defer="cross_combination"
                       wire:keydown.enter.prevent="enterKeyPressOnCrossAbc('focus-cross-a','cross_combination')"
                       class="form-control bg-light text-dark border-warning cross-input"
                       placeholder="Enter Combination">
                @error('cross_combination') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Single A + B + C -->
        <div class="row mb-3 p-2 rounded bg-single-abc">
            <div class="col-12 d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="form-label text-dark fw-bold" for="cross_a">A</label>
                    <input type="text" id="cross_a"
                           wire:model.defer="cross_a"
                           x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-b'))"
                           class="form-control bg-light text-dark border-danger text-center cross-input cross_number">
                    @error('cross_a') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="flex-grow-1">
                    <label class="form-label text-dark fw-bold" for="cross_b">B</label>
                    <input type="text" id="cross_b"
                           wire:model.defer="cross_b"
                           x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-c'))"
                           class="form-control bg-light text-dark border-danger text-center cross-input cross_number">
                    @error('cross_b') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div class="flex-grow-1">
                    <label class="form-label text-dark fw-bold" for="cross_c">C</label>
                    <input type="text" id="cross_c"
                           wire:model.defer="cross_c"
                           x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-single-amt'))"
                           class="form-control bg-light text-dark border-danger text-center cross-input cross_number">
                    @error('cross_c') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>

                <div style="width:120px">
                    <label class="form-label text-dark fw-bold" for="cross_single_amount">Amt</label>
                    <input type="text" id="cross_single_amount"
                           wire:model.defer="cross_single_amount"
                           wire:keydown.enter.prevent="enterKeyPressOnCrossA('focus-cross-a','cross_single_amount')"
                           class="form-control bg-light text-dark border-danger text-center cross-input mynumber">
                    @error('cross_single_amount') <span class="text-danger small">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <!-- AB -->
        <div class="row mb-3 p-2 rounded bg-pair-abc">
            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_ab">AB</label>
                <input type="text" id="cross_ab"
                       wire:model.defer="cross_ab"
                       x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-ab-amt'))"
                       class="form-control bg-light text-dark border-dark cross-input cross_number"
                       placeholder="Enter AB">
                @error('cross_ab') <span class="text-danger small">{{ $message }}</span> @enderror
            

            {{-- <div class="col-6"> --}}
                <label class="form-label text-dark fw-bold" for="cross_ab_amt">Amt</label>
                <input type="text" id="cross_ab_amt"
                       wire:model.defer="cross_ab_amt"
                       {{-- x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-bc'))" --}}
                       wire:keydown.enter.prevent="enterKeyPressOnCrossAb('focus-cross-ab','cross_ab_amt')"
                       class="form-control bg-light text-dark border-dark cross-input mynumber"
                       placeholder="Enter Amt">
                @error('cross_ab_amt') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
       
        <!-- BC -->
        {{-- <div class="row mb-3 p-2 rounded bg-pair-abc"> --}}
            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_bc">BC</label>
                <input type="text" id="cross_bc"
                       wire:model.defer="cross_bc"
                       x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-bc-amt'))"
                       class="form-control bg-light text-dark border-dark cross-input cross_number"
                       placeholder="Enter BC">
                @error('cross_bc') <span class="text-danger small">{{ $message }}</span> @enderror
            

            {{-- <div class="col-6"> --}}
                <label class="form-label text-dark fw-bold" for="cross_bc_amt">Amt</label>
                <input type="text" id="cross_bc_amt"
                       wire:model.defer="cross_bc_amt"
                       {{-- x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-ac'))" --}}
                       wire:keydown.enter.prevent="enterKeyPressOnCrossBc('focus-cross-bc','cross_bc_amt')"
                       class="form-control bg-light text-dark border-dark cross-input mynumber"
                       placeholder="Enter Amt">
                @error('cross_bc_amt') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        {{-- </div> --}}
        {{-- </div> --}}
         


        <!-- AC -->
        {{-- <div class="row mb-0 p-2 rounded bg-pair-abc"> --}}
            <div class="col-4">
                <label class="form-label text-dark fw-bold" for="cross_ac">AC</label>
                <input type="text" id="cross_ac"
                       wire:model.defer="cross_ac"
                       x-on:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-ac-amt'))"
                       class="form-control bg-light text-dark border-dark cross-input cross_number"
                       placeholder="Enter AC">
                @error('cross_ac') <span class="text-danger small">{{ $message }}</span> @enderror
            {{-- </div> --}}

            <div class="col-12">
                <label class="form-label text-dark fw-bold" for="cross_ac_amt">Amt</label>
                <input type="text" id="cross_ac_amt"
                       wire:model.defer="cross_ac_amt"
                       {{-- wire:keydown.enter.prevent="window.dispatchEvent(new CustomEvent('focus-cross-ac'))" --}}
                       wire:keydown.enter.prevent="enterKeyPressOnCrossAc('focus-cross-ac','cross_ac_amt')"
                       class="form-control bg-light text-dark border-dark cross-input mynumber"
                       placeholder="Enter Amt">
                @error('cross_ac_amt') <span class="text-danger small">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>
</div>
</div>
        </div>



@script
<script>

let _activeIntervals = [];
function _setManagedInterval(fn, ms) {
    let id = setInterval(fn, ms);
    _activeIntervals.push({id, fn, ms});
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
        _activeIntervals.push({id, fn: obj.fn, ms: obj.ms});
    });
}
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        _clearManagedIntervals();
    } else {
        _restartManagedIntervals();
    }
});


(function () {
    // ---- Input sanitizers (kept using jQuery for compatibility) ----
    $(document).on('input', '.cross_number', function () {
        let v = $(this).val().replace(/[^0-9]/g, '');
        const uniq = Array.from(new Set(v.split(''))).join('').substring(0, 3);
        if (v !== uniq) $(this).val(uniq);
    });

    $(document).on('input', '.mynumber', function () {
        const v = $(this).val().replace(/[^0-9]/g, '');
        if (v !== $(this).val()) $(this).val(v);
    });

    // ---- Focus mapping for dispatched CustomEvents ----
    const focusMap = {
        'focus-cross-abc': 'cross_abc',
        'focus-cross-abc-qty': 'cross_qty',
        'focus-cross-abc-combination': 'cross_combination',
        'focus-cross-a': 'cross_a',
        'focus-cross-b': 'cross_b',
        'focus-cross-c': 'cross_c',
        'focus-cross-single-amt': 'cross_single_amount',
        'focus-cross-ab': 'cross_ab',
        'focus-cross-ab-amt': 'cross_ab_amt',
        'focus-cross-bc': 'cross_bc',
        'focus-cross-bc-amt': 'cross_bc_amt',
        'focus-cross-ac': 'cross_ac',
        'focus-cross-ac-amt': 'cross_ac_amt'
    };

    Object.keys(focusMap).forEach(evtName => {
        window.addEventListener(evtName, function () {
            const el = document.getElementById(focusMap[evtName]);
            if (el) setTimeout(() => el.focus(), 0);
        });
    });

  // ---- Keyboard shortcuts (global) ----
document.addEventListener('keydown', function (e) {
    const tag = (document.activeElement && document.activeElement.tagName) || '';
    const editable = document.activeElement && (document.activeElement.isContentEditable ||
        ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag));

    if (editable && !(e.ctrlKey || e.metaKey)) return;

    // Ctrl+Shift+C → focus Cross ABC
    if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'c') {
        e.preventDefault();
        document.getElementById('cross_abc')?.focus();
        return;
    }

    // Ctrl+1 / Ctrl+2 / Ctrl+3 → focus fields
    if (e.ctrlKey && ['1', '2', '3'].includes(e.key)) {
        e.preventDefault(); // stop browser tab switching
        const map = {
            '1': 'cross_abc', // full ABC
            '2': 'cross_a',   // single A
            '3': 'cross_ab'   // pair AB
        };
        document.getElementById(map[e.key])?.focus();
        return;
    }
}, { passive: false });  // <-- important change



    // ---- Arrow-key navigation for cross-inputs (non-destructive) ----
    // directional mapping: for each input id specify neighbor ids for left/right/up/down
    const arrowMap = {
        cross_abc:       { right: 'cross_qty',      down: 'cross_a', left: 'abc' },
        cross_qty:       { left: 'cross_abc',       right: 'cross_combination', down: 'cross_a' },
        cross_combination:{ left: 'cross_qty',      down: 'cross_a' },

        cross_a:         { up: 'cross_abc',        right: 'cross_b',   down: 'cross_ab', left: 'input_a' },
        cross_b:         { left: 'cross_a',        right: 'cross_c',   up: 'cross_qty', down: 'cross_bc' },
        cross_c:         { left: 'cross_b',        right: 'cross_single_amount', up: 'cross_combination', down: 'cross_ac' },
        cross_single_amount: { left: 'cross_c',    up: 'cross_qty',   down: 'cross_ab' },

        cross_ab:        { up: 'cross_combination', right: 'cross_bc',  down: 'cross_ab_amt', left: 'input_a' },
        cross_bc:        { left: 'cross_ab',       right: 'cross_ac',  down: 'cross_bc_amt' },
        cross_ac:        { left: 'cross_bc',       up: 'cross_c',     down: 'cross_ac_amt' },

        cross_ab_amt:    { up: 'cross_ab',        right: 'cross_bc_amt' },
        cross_bc_amt:    { left: 'cross_ab_amt',  right: 'cross_ac_amt', up: 'cross_bc'  },
        cross_ac_amt:    { left: 'cross_bc_amt',  up: 'cross_ac' }
    };

    // handle arrow keys when a cross-input is focused
    document.addEventListener('keydown', function (e) {
        // only act on plain arrow keys
        if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key)) return;

        const active = document.activeElement;
        if (!active) return;
        // we only take over when focused element has class 'cross-input'
        if (!active.classList || !active.classList.contains('cross-input')) return;

        const id = active.id;
        if (!id) return;
        const dir = e.key.replace('Arrow', '').toLowerCase(); // 'left'|'right'|'up'|'down'
        const neighborId = arrowMap[id] && arrowMap[id][dir];
        if (!neighborId) return; // nothing mapped

        const target = document.getElementById(neighborId);
        if (target) {
            e.preventDefault();    // stop caret movement / scroll
            target.focus();
            // optionally select text for quick replace (uncomment if desired)
            // if (typeof target.select === 'function') target.select();
        }
    }, { passive: false });

})();
</script>
@endscript


<style>
.bg-cross-abc { background: #df4c5a !important; }
.bg-single-abc { background: #e1e7e0 !important; }
.bg-pair-abc   { background: #0dcaf0 !important; }

.cross-input {
  font-size: 1.1rem !important;
  font-weight: 700 !important;
  text-align: center !important;
  letter-spacing: 2px;
  padding: 0.5rem 0.6rem !important;
}

.cross-input:focus {
  border: 2px solid #00ff88 !important;
  background-color: #2f2f2f !important;
  color: #ffffff !important;
  outline: none !important;
}

@media (max-width: 576px) {
  .cross-input { font-size: 1rem !important; letter-spacing: 1px; }
}
</style>
