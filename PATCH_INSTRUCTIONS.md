# Wallet Integration Patch Instructions (non-invasive)
I added wallet migrations, models, and WalletService into the project WITHOUT modifying existing logic.
Files added:
- database/migrations/2025_09_18_000001_create_wallets_table.php
- database/migrations/2025_09_18_000002_create_wallet_transactions_table.php
- app/Models/Wallet.php
- app/Models/WalletTransaction.php
- app/Services/WalletService.php

Where to plug wallet calls (suggested, non-invasive):
1) Ticket purchase (debit): The ticket creation flow is in `app/Traits/TicketForm/OptonsOperation.php`.
   Find the place where cached options are set to 'COMPLETED' (search for 'status' => 'COMPLETED') and after the code that prepares final $options and before persisting to DB, call:
   ```php
   app(App\Services\WalletService::class)->debit($this->auth_user->id, $totalAmount, $this->auth_user->id, $ticketId ?? null, 'Ticket purchase');
   ```
   Wrap it in try/catch to handle insufficient funds.

2) Claim payout (credit): After claim approval (where you process a payout), call:
   ```php
   app(App\Services\WalletService::class)->credit($userId, $payoutAmount, auth()->id(), $claimId, 'Claim payout');
   ```

3) Admin -> Shopkeeper transfer: Add a new admin route/controller to call WalletService::credit (or transfer if admin has a wallet).

Notes:
- I intentionally did NOT change existing files to avoid breaking anything.
- If you want, I can now patch the exact locations in your repo to call the WalletService (I will create diffs). If you prefer I can first show the exact lines where to paste the snippets.

Next steps (pick one):
- I can apply inline edits to call the wallet service at the precise places (I'll produce unified diffs and a patched ZIP).
- Or I can show exact copy-paste snippets with file/line context so you can apply them yourself.

Which would you like me to do?

## New UI Components Added

1. **Wallet Balance Partial**
   - File: `resources/views/partials/_wallet_balance.blade.php`
   - Include this partial in your layouts (e.g. in `layouts/app.blade.php` header area):
     ```blade
     @include('partials._wallet_balance')
     ```

2. **Admin Wallet Transfer**
   - Livewire class: `app/Http/Livewire/Admin/WalletTransfer.php`
   - Blade view: `resources/views/livewire/admin/wallet-transfer.blade.php`
   - To show this form, include it in an admin page:
     ```blade
     <livewire:admin.wallet-transfer />
     ```

   - This provides a dropdown to pick a shopkeeper, enter amount + notes, and credit their wallet.

Routes: Since this is Livewire, no new route file changes are needed — just include the component in an admin page.
