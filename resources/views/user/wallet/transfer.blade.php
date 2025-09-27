@extends('web.layouts.base')

@section('contents')
<div class="container py-4">
    <h4>Transfer Wallet to Your Users</h4>

    {{-- Show current user's wallet balance (shopkeeper) --}}
    @php
        $current = auth()->user();
        // Adjust the property name if your wallet column is different
        $currentBalance = number_format($current->wallet_balance ?? 0, 2);
        $isSuperAdmin = $current->is_super_admin ?? false; // replace with your role-check if different
    @endphp

    <div class="mb-3">
        <strong>Wallet:</strong> ₹{{ $currentBalance }}
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($recipients->isEmpty())
        <div class="alert alert-info">You have no child users to transfer to.</div>
    @else
        <form method="POST" action="{{ route('user.wallet.transfer.post') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">To</label>
                <select name="to_user_id" class="form-control" required>
                    <option value="">-- select user --</option>
                    @foreach ($recipients as $r)
                        <option value="{{ $r->id }}" {{ old('to_user_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->first_name }} {{ $r->last_name }} — {{ $r->email }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Transaction Type</label>

                @if($isSuperAdmin)
                    <select name="type" class="form-control" required>
                        <option value="credit" {{ old('type')=='credit' ? 'selected' : '' }}>
                            Credit (Add to user wallet)
                        </option>
                        <option value="debit" {{ old('type')=='debit' ? 'selected' : '' }}>
                            Debit (Deduct from user wallet)
                        </option>
                        <option value="transfer" {{ old('type')=='transfer' ? 'selected' : '' }}>
                            Transfer (From your wallet to user)
                        </option>
                    </select>
                @else
                    {{-- Shopkeepers: only allow transfer and debit (no credit/generate) --}}
                    <select name="type" class="form-control" required>
                        <option value="transfer" {{ old('type')=='transfer' ? 'selected' : '' }}>
                            Transfer (From your wallet to user)
                        </option>
                        <option value="debit" {{ old('type')=='debit' ? 'selected' : '' }}>
                            Debit (Deduct from user wallet)
                        </option>
                    </select>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Amount</label>
                <input name="amount" type="number" step="0.01" min="0.01"
                       class="form-control" required value="{{ old('amount') }}">
                @if ($errors->has('amount'))
                    <small class="text-danger">{{ $errors->first('amount') }}</small>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Note (optional)</label>
                <textarea name="note" rows="2" class="form-control">{{ old('note') }}</textarea>
            </div>

            <button class="btn btn-primary">Send</button>
        </form>
    @endif
</div>
@endsection
