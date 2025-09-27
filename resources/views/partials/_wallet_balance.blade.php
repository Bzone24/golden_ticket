

@php
    // Decide how to detect admin — adjust to match your app:
    // Option A: if you have an is_admin boolean on users:
    $isAdmin = auth()->check() && (auth()->user()->hasRole('admin'));

    // // Option B: or if you use a 'role' column:
    // if (!($isAdmin ?? false) && auth()->check()) {
    //     $isAdmin = (auth()->user()->role ?? '') === 'admin';
    // }

    // // Option C (fallback): treat user id 1 as admin (only if you need quick fallback)
    // if (!($isAdmin ?? false) && auth()->check()) {
    //     $isAdmin = auth()->id() === 1;
    // }


$balance = 0;
try {
    if(auth()->check()){
        $wallet = app(\App\Services\WalletService::class)->ensureWallet(auth()->id());
        $balance = number_format((float)$wallet->balance, 2);
    }
} catch (Exception $e) {
    $balance = '0.00';
}

@endphp

<div class="dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="walletDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
    <small>Wallet:</small>
    <strong>₹{{ $balance }}</strong>
  </a>
 <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="walletDropdown">
    @if($isAdmin)
        {{-- Admin links (use named routes if available, fallback to URL) --}}
        @if(\Illuminate\Support\Facades\Route::has('admin.wallet.transfer'))
            <li><a class="dropdown-item" href="{{ route('admin.wallet.transfer.plain') }}">Wallet Transfer</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('wallet/transfer/plain') }}">Wallet Transfer</a></li>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('admin.wallet.transactions'))
            <li><a class="dropdown-item" href="{{ route('admin.wallet.transactions') }}">Transactions</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('admin/wallet/transactions') }}">Transactions</a></li>
        @endif
    @else
        {{-- Regular user links (named routes preferred) --}}
        @if(\Illuminate\Support\Facades\Route::has('user.wallet.transfer'))
            <li><a class="dropdown-item" href="{{ route('user.wallet.transfer') }}">Wallet Transfer</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('wallet/transfer') }}">Wallet Transfer</a></li>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('user.wallet.transactions'))
            <li><a class="dropdown-item" href="{{ route('user.wallet.transactions') }}">Transactions</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('wallet/transactions') }}">Transactions</a></li>
        @endif
    @endif
</ul>
</div>

