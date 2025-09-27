<div class="card shadow-lg border-0 rounded-3 bg-dark text-light h-100" >
    <!-- Header -->
    <div class="card-header bg-gradient text-white rounded-top" style="background:#431549;">
        <h3 class="text-center mb-0">
            <i class="bi bi-journal-text me-2" ></i> Simple ABC
        </h3>
    </div>

    <!-- Body -->
    <div class="card-body" style="background:#43233b;">
        {{-- ✅ Ticket Info --}}
        {{-- @if ($selected_ticket)
            <div class="text-center mb-3 p-2 rounded" style="background:#333; color:#fff;">
                <strong>Ticket No:</strong> {{ $selected_ticket->ticket_number }} <br>
                <strong>Time:</strong> {{ is_array($selected_times) ? implode(', ', $selected_times) : $selected_times }}
            </div>
        @endif --}}

        <div class="row mb-3 p-2 rounded" style="background:#5b1e63;"
             x-data
             @focus-qty.window="document.getElementById('qty')?.focus()"
             @focus-abc.window="document.getElementById('abc')?.focus()">

           <!-- ABC Input -->
<div class="col-6">
    <label class="form-label text-white fw-bold" for="abc">ABC</label>
    <input
        type="text"
        class="form-control bg-light text-dark border-dark cross-input"
        wire:model.defer="abc"
        id="abc"
        placeholder="Enter ABC"

        {{-- <!-- keep Enter behaviour (client-side move to qty) --> --}}
        x-on:keydown.enter.prevent="$dispatch('focus-qty')"

        {{-- <!-- arrow navigation --> --}}
        x-on:keydown.right.prevent="$dispatch('focus-qty')"
        x-on:keydown.down.prevent="$dispatch('focus-a')"
        x-on:keydown.left.prevent="$dispatch('focus-abc')"  
        x-on:keydown.up.prevent="$dispatch('focus-abc')"
    >
    @error('abc')
        <span class="text-danger small">{{ $message }}</span>
    @enderror
</div>

<!-- Qty Input -->
<div class="col-6">
    <label class="form-label text-white fw-bold" for="qty">Qty</label>
    <input
        type="text"
        class="form-control bg-light text-dark border-dark cross-input"
        wire:model.defer="abc_qty"
        id="qty"
        placeholder="Enter Qty"

        {{-- <!-- existing Enter still calls server --> --}}
        wire:keydown.enter.prevent="enterKeyPressOnQty"

        {{-- <!-- arrow navigation --> --}}
        x-on:keydown.left.prevent="$dispatch('focus-abc')"
        x-on:keydown.right.prevent="$dispatch('focus-cross-abc')"  
        x-on:keydown.down.prevent="$dispatch('focus-a_qty')"
        x-on:keydown.up.prevent="$dispatch('focus-qty')"
    >
    @error('abc_qty')
        <span class="text-danger small">{{ $message }}</span>
    @enderror
</div>

        </div>

        <!-- Table -->
        <div class="table-responsive" >
            <table class="table table-bordered align-middle mb-0 text-center custom-table2" >
                <thead class="bg-success text-white">
                    <tr>
                        <th>Option</th>
                        <th>#Num (0–9)</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody
    x-data
    @focus-b.window="document.getElementById('input_b')?.focus()"
    @focus-a.window="document.getElementById('input_a')?.focus()"
    @focus-c.window="document.getElementById('input_c')?.focus()"
    @focus-a_qty.window="document.getElementById('input_a_qty')?.focus()"
    @focus-b_qty.window="document.getElementById('input_b_qty')?.focus()"
    @focus-c_qty.window="document.getElementById('input_c_qty')?.focus()">

    <!-- A Row -->
    <tr>
        <td class="bg-success text-white text-center"><b>A</b></td>
        <td>
            <input type="text" id="input_a"
                   wire:model.debounce.250ms="a"
                   class="form-control bg-light text-dark border-warning text-center zeroToNineNumber"
                   x-on:keydown.enter.prevent="$dispatch('focus-a_qty')"                     
                   x-on:keydown.down.prevent="$dispatch('focus-b'); if (window.$wire) $wire.call('calculateTotal','a')"
                   x-on:keydown.right.prevent="$dispatch('focus-a_qty'); if (window.$wire) $wire.call('calculateTotal','a')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','a')"
                   x-on:keydown.up.prevent="$dispatch('focus-abc')" >
        </td>
        <td>
            <input type="text" class="form-control bg-light text-dark border-warning text-center number_qty"
                   id="input_a_qty" wire:model.defer="a_qty"
                   x-on:keydown.left.prevent="$dispatch('focus-a'); if (window.$wire) $wire.call('calculateTotal','a')"
                   x-on:keydown.down.prevent="$dispatch('focus-b_qty'); if (window.$wire) $wire.call('calculateTotal','a')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','a')"
                   wire:keydown.enter.prevent="keyEnter('a','focus-a')"
                   x-on:keydown.up.prevent="$dispatch('focus-abc')"
                   x-on:keydown.right.prevent="$dispatch('focus-cross-a')">  <!-- existing save -> cache -->
        </td>
    </tr>

    <!-- B Row -->
    <tr>
        <td class="bg-warning text-dark text-center"><b>B</b></td>
        <td>
            <input type="text" id="input_b"
                   wire:model.debounce.250ms="b"
                   class="form-control bg-light text-dark border-warning text-center zeroToNineNumber"
                   x-on:keydown.enter.prevent="$dispatch('focus-b_qty')"                     
                   x-on:keydown.up.prevent="$dispatch('focus-a'); if (window.$wire) $wire.call('calculateTotal','b')"
                   x-on:keydown.down.prevent="$dispatch('focus-c'); if (window.$wire) $wire.call('calculateTotal','b')"
                   x-on:keydown.right.prevent="$dispatch('focus-b_qty'); if (window.$wire) $wire.call('calculateTotal','b')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','b')">
        </td>
        <td>
            <input type="text" class="form-control bg-light text-dark border-warning text-center number_qty"
                   id="input_b_qty" wire:model.defer="b_qty"
                   x-on:keydown.left.prevent="$dispatch('focus-b'); if (window.$wire) $wire.call('calculateTotal','b')"
                   x-on:keydown.down.prevent="$dispatch('focus-c_qty'); if (window.$wire) $wire.call('calculateTotal','b')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','b')"
                   x-on:keydown.up.prevent="$dispatch('focus-a_qty'); if (window.$wire) $wire.call('calculateTotal','b')"
                   wire:keydown.enter.prevent="keyEnter('b','focus-b')"
                   x-on:keydown.right.prevent="$dispatch('focus-cross-a')">
        </td>
    </tr>

    <!-- C Row -->
    <tr>
        <td class="bg-info text-white text-center"><b>C</b></td>
        <td>
            <input type="text" id="input_c"
                   wire:model.debounce.250ms="c"
                   class="form-control bg-light text-dark border-warning text-center zeroToNineNumber"
                   x-on:keydown.enter.prevent="$dispatch('focus-c_qty')"                   
                   x-on:keydown.up.prevent="$dispatch('focus-b'); if (window.$wire) $wire.call('calculateTotal','c')"
                   x-on:keydown.right.prevent="$dispatch('focus-c_qty'); if (window.$wire) $wire.call('calculateTotal','c')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','c')">
        </td>
        <td>
            <input type="text" class="form-control bg-light text-dark border-warning text-center number_qty"
                   id="input_c_qty" wire:model.defer="c_qty"
                   x-on:keydown.left.prevent="$dispatch('focus-c'); if (window.$wire) $wire.call('calculateTotal','c')"
                   x-on:keydown.up.prevent="$dispatch('focus-b_qty'); if (window.$wire) $wire.call('calculateTotal','c')"
                   x-on:keydown.tab.prevent="if (window.$wire) $wire.call('keyTab','c')"
                   wire:keydown.enter.prevent="keyEnter('c','focus-c')"
                   x-on:keydown.right.prevent="$dispatch('focus-cross-a')">
        </td>
    </tr>
</tbody>

            </table>
        </div>
    </div>
</div>

@script
<script>
    // Keyboard shortcuts (keep same behaviour)
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && !e.shiftKey && e.key.toLowerCase() === 'a') {
            e.preventDefault();
            document.getElementById('input_a')?.focus();
        }
        if (e.ctrlKey && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            document.getElementById('input_b')?.focus();
        }
        if (e.ctrlKey && e.key.toLowerCase() === 'c') {
            e.preventDefault();
            document.getElementById('input_c')?.focus();
        }
        // if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'a') {
        //     e.preventDefault();
        //     document.getElementById('abc')?.focus();
         if (e.ctrlKey && e.key.toLowerCase() === 'x') {
            e.preventDefault();
            document.getElementById('abc')?.focus();
        }
    });
</script>
@endscript

@push('styles')
<style>
  /* Highlight ONLY the fields you care about */
  #input_a:focus,
  #input_b:focus,
  #input_c:focus,
  #abc:focus {
    outline: none !important;
    border: 2px solid #00ff88 !important;
    box-shadow:
      0 0 0 .25rem rgba(0,255,136,.25),
      0 0 10px rgba(0,255,136,.7) !important;
    background-color: #2a2a2a !important;
    color: #ffffff !important;
  }

.custom-table2 {
    background-color: #5b1e63;
}
.custom-table2 thead th {
    background-color: #5b1e63 !important;
    color: #fff !important;
}
.custom-table2 tbody td {
    background-color: #743194; /* light purple for body */
    color: #000;
}
</style>
@endpush
