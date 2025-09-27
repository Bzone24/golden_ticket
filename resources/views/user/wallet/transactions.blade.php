@extends('web.layouts.base')

@section('contents')
<div class="container">
    <h4>My Wallet Transactions</h4>
    @livewire(\App\Http\Livewire\User\WalletTransactions::class)

</div>
@endsection
