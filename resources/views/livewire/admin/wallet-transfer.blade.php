@php
// Fallback safety: if included directly (not mounted by Livewire), load $shopkeepers server-side
if (!isset($shopkeepers)) {
    try {
        $shopkeepers = \App\Models\User::where('id','!=', auth()->id())->limit(200)->get();
    } catch (\Throwable $ex) {
        $shopkeepers = collect();
    }
}
@endphp

<div>
    @if(session()->has('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->has('transfer_error'))
        <div class="alert alert-danger">{{ $errors->first('transfer_error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Shopkeeper / User</label>
                <select wire:model="to_user_id" class="form-control">
                    <option value="">-- Select user --</option>
                    @foreach($shopkeepers as $s)
                        <option value="{{ $s->id }}">{{ $s->first_name ?? $s->name ?? $s->email }} (ID: {{ $s->id }})</option>
                    @endforeach
                </select>
                @error('to_user_id') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Amount (INR)</label>
                <input wire:model="amount" type="number" step="0.01" class="form-control" />
                @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Note</label>
                <input wire:model="note" type="text" class="form-control" />
                @error('note') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            <button wire:click="transfer" class="btn btn-primary">Transfer</button>
        </div>
    </div>
</div>
