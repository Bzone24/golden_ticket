@php
    // Fallback safety: if included directly (not mounted by Livewire),
    // load $transactions server-side as a paginator (not a Collection).
    if (!isset($transactions)) {
        try {
            // Use paginate() so $transactions has links() method.
            $transactions = \App\Models\WalletTransaction::orderBy('created_at', 'desc')->paginate(200);
        } catch (\Throwable $ex) {
            $transactions = collect(); // last-resort: empty collection
        }
    }
@endphp

<style>
    /* Wrapper around filters and table */
    .transactions-box {
        background: #ffffff;
        border-radius: .5rem;
        padding: 1.25rem;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    /* Top filter bar */
    .transactions-filters input,
    .transactions-filters select {
        min-height: 42px;
        border-radius: .375rem;
    }

    /* Table tweaks */
    .transactions-table th {
        font-weight: 600;
        white-space: nowrap;
    }

    .transactions-table td {
        vertical-align: middle;
    }

    .transactions-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Pagination area */
    .transactions-pagination {
        display: flex;
        justify-content: flex-end;
    }
</style>

<div class="transactions-box">
    {{-- Filters --}}
    <div class="transactions-filters mb-3 d-flex flex-wrap gap-2">
        <input wire:model="search" class="form-control flex-grow-1" placeholder="🔍 Search type or note..." />
        <select wire:model="perPage" class="form-select" style="max-width:120px;">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="30">30</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm transactions-table">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Wallet ID</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Balance After</th>
                    <th>Performed By</th>
                    <th>Related ID</th>
                    <th>Note</th>
                    <th>When</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $tx)
                    <tr>
                        <td>{{ $tx->id }}</td>

                        {{-- Wallet owner (shopkeeper/user) --}}
                        <td>
                            @if ($tx->wallet && $tx->wallet->user)
                                {{ trim($tx->wallet->user->first_name . ' ' . ($tx->wallet->user->last_name ?? '')) ?: $tx->wallet->user->name }}
                                <small class="text-muted">(#{{ $tx->wallet->user->id }})</small>
                            @else
                                Wallet #{{ $tx->wallet_id }}
                            @endif
                        </td>

                        <td>
                            <span class="badge bg-{{ $tx->type === 'credit' ? 'success' : 'danger' }}">
                                {{ ucfirst($tx->type) }}
                            </span>
                        </td>

                        <td>₹{{ number_format($tx->amount, 2) }}</td>
                        <td>₹{{ number_format($tx->balance, 2) }}</td>

                        {{-- Who performed the transaction (admin or user) --}}
                        <td>
                            @if ($tx->performer)
                                {{ trim($tx->performer->first_name . ' ' . ($tx->performer->last_name ?? '')) ?: $tx->performer->name }}
                                <small class="text-muted">
                                    @if (method_exists($tx->performer, 'is_admin') && $tx->performer->is_admin)
                                        (Admin)
                                    @else
                                        (User)
                                    @endif
                                </small>
                            @elseif($tx->performed_by)
                                {{-- fallback if relation wasn't resolved for some reason --}}
                                #{{ $tx->performed_by }}
                            @else
                                <span class="text-muted">System</span>
                            @endif
                        </td>

                        {{-- Related ID: try to give context if possible --}}
                        <td>
                            @if ($tx->related_id)
                                Related #{{ $tx->related_id }}
                            @else
                                -
                            @endif
                        </td>

                        <td>{{ $tx->note }}</td>
                        <td>{{ $tx->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="transactions-pagination mt-3">
        @if (method_exists($transactions, 'links'))
            {{ $transactions->links() }}
        @endif
    </div>
</div>
