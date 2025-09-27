@extends('web.layouts.base')

@section('contents')
<div class="container py-4">
    <h4>Wallet Transactions</h4>

    <p><strong>Balance:</strong> ₹{{ number_format($wallet->balance, 2) }}</p>

    @if ($transactions->count())
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>By</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($transactions as $tx)
                    <tr>
                        <td>{{ $tx->id }}</td>
                        <td>{{ $tx->type }}</td>
                        <td>{{ number_format($tx->amount,2) }}</td>
                        <td>{{ number_format($tx->balance,2) }}</td>

                        {{-- By column: prefer relation (performer), fallback to performedByUser, then id --}}
                        <td>
                            @php
                                $actor = $tx->performer ?? $tx->performedByUser ?? null;
                            @endphp

                            @if ($actor)
                                {{ $actor->first_name ?? $actor->name ?? '' }}
                                @if (!empty($actor->last_name))
                                    {{ ' ' . $actor->last_name }}
                                @endif
                            @else
                                {{ $tx->performed_by }}
                            @endif
                        </td>

                        <td>{{ $tx->note }}</td>
                        <td>{{ $tx->created_at }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{ $transactions->links() }}
    @else
        <div class="alert alert-info">No transactions found.</div>
    @endif
</div>
@endsection
