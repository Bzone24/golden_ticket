@extends('admin.layouts.base')

@section('contents')
<div class="container py-3">
    <h3 class="mb-3">Wallet Transactions</h3>

    {{-- Mount Livewire component only once --}}
    @if (class_exists(\Livewire\Livewire::class))
        @livewire(\App\Http\Livewire\Admin\WalletTransactions::class)
    @else
        {{-- Fallback for environments without Livewire:
             include the partial only when Livewire is not installed. --}}
        @includeIf('livewire.admin.wallet-transactions')
    @endif
</div>
@endsection
