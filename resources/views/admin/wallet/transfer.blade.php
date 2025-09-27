@extends('admin.layouts.base')

@section('content')
<div class="container py-3">
    <h3 class="mb-3">Admin Wallet Transfer</h3>

    @if (class_exists(\Livewire\Livewire::class))
        {{-- Mount Livewire component by class. If mounting fails, include fallback form view. --}}
        @livewire(\App\Http\Livewire\Admin\WalletTransfer::class)
        {{-- Fallback include in case Livewire is not available or the view is directly rendered --}}
        @includeIf('livewire.admin.wallet-transfer')
    @else
        @includeIf('livewire.admin.wallet-transfer')
    @endif
</div>
@endsection
