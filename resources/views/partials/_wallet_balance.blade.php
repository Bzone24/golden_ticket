{{-- resources/views/partials/wallet_balance.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $balance = '0.00';
    $roleName = null;

    if (auth()->check()) {
        $user = auth()->user();

        // Detect role name (Spatie getRoleNames() assumed)
        try {
            $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : null;
            $roleName = ($roles && $roles->count()) ? strtolower(trim($roles->first())) : null;
        } catch (\Throwable $e) {
            $roleName = null;
        }

        // Fallback: treat user id 1 as admin (only if role detection fails)
        if (!$roleName && $user->id === 1) {
            $roleName = 'admin';
        }

        // Safely get/create wallet (do not break UI)
        try {
            $wallet = app(\App\Services\WalletService::class)->ensureWallet($user->id);
            $balance = number_format((float) ($wallet->balance ?? 0), 2);
        } catch (\Throwable $e) {
            $balance = '0.00';
        }
    }

    $isAdmin = in_array($roleName, ['admin', 'super admin', 'super_admin', 'super-admin', 'superadmin']);
    $isShopkeeper = in_array($roleName, ['shopkeeper', 'shop_keeper', 'shop-keeper']);
@endphp

<div class="dropdown">
  <a class="nav-link dropdown-toggle" href="#" id="walletDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="cursor:pointer;">
    <small>Wallet:</small>
    <strong>₹{{ $balance }}</strong>
  </a>

  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="walletDropdown">
    {{-- Admin links (use the exact named route you requested) --}}
    @if($isAdmin)
        @if(Route::has('admin.wallet.transfer.plain'))
            <li><a class="dropdown-item" href="{{ route('admin.wallet.transfer.plain') }}">Wallet Transfer</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('admin/wallet/transfer/plain') }}">Wallet Transfer</a></li>
        @endif

        @if(Route::has('admin.wallet.transactions'))
            <li><a class="dropdown-item" href="{{ route('admin.wallet.transactions') }}">Transactions</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('admin/wallet/transactions') }}">Transactions</a></li>
        @endif

    {{-- Shopkeeper: show Transfer + Transactions --}}
   {{-- Shopkeeper: prefer named routes, fallback to simple URL --}}
@elseif($isShopkeeper)
    @if(Route::has('user.wallet.transfer'))
        <li><a class="dropdown-item" href="{{ route('user.wallet.transfer') }}">Wallet Transfer</a></li>
    @else
        <li><a class="dropdown-item" href="{{ url('wallet/transfer') }}">Wallet Transfer</a></li>
    @endif

    @if(Route::has('user.wallet.transactions'))
        <li><a class="dropdown-item" href="{{ route('user.wallet.transactions') }}">Transactions</a></li>
    @else
        <li><a class="dropdown-item" href="{{ url('wallet/transactions') }}">Transactions</a></li>
    @endif


    {{-- Plain user: only Transactions (no Transfer link) --}}
    @else
        @if(Route::has('user.wallet.transactions'))
            <li><a class="dropdown-item" href="{{ route('user.wallet.transactions') }}">Transactions</a></li>
        @else
            <li><a class="dropdown-item" href="{{ url('wallet/transactions') }}">Transactions</a></li>
        @endif
    @endif
  </ul>
</div>
