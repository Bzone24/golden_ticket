<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Models\User;
use App\Models\Wallet;

class WalletController extends Controller
{
    public function index()
    {
        return view('user.wallet.index');
    }

   public function transactions()
{
    $wallet = Wallet::firstOrCreate(['user_id' => auth()->id()], ['balance' => 0]);
    $transactions = $wallet->transactions()->orderBy('id', 'desc')->paginate(25);

    return view('user.wallet.transactions', compact('wallet', 'transactions'));
}

  public function showTransferForm()
{
    $recipients = collect();

    // If the authenticated user is a shopkeeper, show their child users only
    if (auth()->user()->hasRole('shopkeeper')) {
        $recipients = auth()->user()->children()->orderBy('first_name')->get();
    }

    return view('user.wallet.transfer', compact('recipients'));
}

 public function submitTransfer(Request $request, WalletService $walletService)
{
    $request->validate([
        'to_user_id' => 'required|exists:users,id',
        'type'       => 'required|in:credit,debit,transfer',
        'amount'     => 'required|numeric|min:0.01',
        'note'       => 'nullable|string|max:255',
    ]);

    $current = auth()->user();
    $toUser = User::findOrFail($request->to_user_id);

    // Shopkeeper can only operate on their child users
    if (!$current->is_super_admin && $toUser->created_by != $current->id) {
        abort(403, 'Target user is not your child user.');
    }

    $type = $request->type;

    // Server-side permission enforcement
    if ($type === 'credit' && !$current->is_super_admin) {
        // Non-super-admins cannot call credit
        return redirect()->back()->withErrors(['type' => 'Only super admin can credit wallets.']);
    }

    $amount = (float) $request->amount;
    $note = $request->note;
    $performedBy = $current->id;

    try {
        if ($type === 'credit') {
            // only super-admin reaches here due to previous check
            $walletService->credit($toUser->id, $amount, $performedBy, null, $note, 'manual_credit');
            $msg = 'Wallet credited by admin.';
        } elseif ($type === 'debit') {
            // debit target user's wallet (checks inside service)
            $walletService->debit($toUser->id, $amount, $performedBy, null, $note, 'manual_debit');
            $msg = 'Wallet debited successfully.';
        } else { // transfer
            // transfer from current (shopkeeper) to target child
            $walletService->transfer($current->id, $toUser->id, $amount, $performedBy, $note);
            $msg = 'Transfer completed successfully.';
        }

        return redirect()->back()->with('success', $msg);

    } catch (\RuntimeException $e) {
        return redirect()->back()->withErrors(['amount' => $e->getMessage()]);
    } catch (\Exception $e) {
        \Log::error('Wallet op failed', [
            'error' => $e->getMessage(),
            'actor' => $current->id,
            'target' => $toUser->id,
            'type' => $type,
            'amount' => $amount,
        ]);
        return redirect()->back()->withErrors(['general' => 'Failed to perform wallet operation.']);
    }
}

}
