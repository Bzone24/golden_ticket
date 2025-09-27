@extends('admin.layouts.base')

@section('contents')
<style>
    /* Ensure the page content is positioned below the fixed header and centered */
    :root {
        --admin-header-height: 72px; /* adjust if your header is taller/shorter */
    }

    /* Outer wrapper takes full viewport minus header */
    .wallet-page-wrap {
        min-height: calc(100vh - var(--admin-header-height));
        display: flex;
        align-items: center;        /* vertical centering */
        justify-content: center;    /* horizontal centering */
        padding: 2rem 1rem;         /* breathing room on small screens */
        box-sizing: border-box;
        background: transparent;    /* keep layout background consistent */
    }

    /* Card that contains the form */
    .wallet-card {
        width: 100%;
        max-width: 960px;           /* limit width so content stays centered */
        background: #ffffff;        /* white card to contrast with page */
        border-radius: .5rem;
        padding: 1.5rem;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        border: 1px solid rgba(0,0,0,0.03);
    }

    /* Make controls a bit taller and more visible */
    .wallet-form .form-select,
    .wallet-form .form-control {
        min-height: 46px;
        padding: .625rem .75rem;
        border-radius: .375rem;
    }

    /* Small visual tweak: label weight */
    .wallet-form .form-label { font-weight: 600; }

    /* On very small screens, reduce padding so it fits nicely */
    @media (max-width: 575px) {
        .wallet-card { padding: 1rem; max-width: 100%; }
    }
</style>

<div class="wallet-page-wrap">
    <div class="wallet-card">
        <h4 class="mb-3">Admin Wallet Transfer (Plain)</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->has('transfer_error'))
            <div class="alert alert-danger">{{ $errors->first('transfer_error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.wallet.transfer.plain.post') }}" class="wallet-form">
            @csrf

            {{-- Shopkeeper / User --}}
            <div class="mb-3 row align-items-center">
                <label for="to_user_id" class="col-sm-3 col-form-label form-label">Shopkeeper / User</label>
                <div class="col-sm-9">
                    @if($shopkeepers->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No users found. Please add shopkeepers or adjust the filter.
                        </div>
                    @else
                        <select id="to_user_id" name="to_user_id" class="form-select" required>
                            <option value="">-- Select user --</option>

                            @foreach($shopkeepers as $s)
                                @php
                                    // Friendly display: prefer first+last, then name, then email
                                    $display = trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? ''));
                                    if (empty($display)) {
                                        $display = $s->name ?? $s->email ?? 'User';
                                    }
                                @endphp

                                <option value="{{ $s->id }}" {{ old('to_user_id') == $s->id ? 'selected' : '' }}>
                                    {{ $display }} (ID: {{ $s->id }}){{ isset($s->email) ? ' — ' . $s->email : '' }}
                                </option>
                            @endforeach
                        </select>

                        @error('to_user_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    @endif
                </div>
            </div>

            {{-- Amount --}}
            <div class="mb-3 row">
                <label for="amount" class="col-sm-3 col-form-label form-label">Amount (INR)</label>
                <div class="col-sm-9">
                    <input id="amount" name="amount" value="{{ old('amount') }}" type="number" step="0.01" class="form-control" />
                    @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Debit admin checkbox --}}
            <div class="mb-3 row">
                <div class="col-sm-3"></div>
                <div class="col-sm-9">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="debit_admin" name="debit_admin" value="1" {{ old('debit_admin') ? 'checked' : '' }}>
                        <label class="form-check-label" for="debit_admin">
                            Debit admin wallet (transfer). Uncheck to just credit recipient.
                        </label>
                    </div>
                </div>
            </div>

            {{-- Note --}}
            <div class="mb-3 row">
                <label for="note" class="col-sm-3 col-form-label form-label">Note</label>
                <div class="col-sm-9">
                    <input id="note" name="note" value="{{ old('note') }}" type="text" class="form-control" />
                    @error('note') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Submit --}}
            <div class="mb-0 row">
                <div class="col-sm-3"></div>
                <div class="col-sm-9">
                    <button class="btn btn-primary" type="submit">Transfer</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
