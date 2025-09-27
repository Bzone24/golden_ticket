<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;
use App\Models\User;

class WalletController extends Controller
{
    public function plainForm()
    {
        // adjust filter to only shopkeepers if you have a role column (e.g. where('role','shopkeeper'))
        $shopkeepers = User::where('id', '!=', auth()->id())->orderBy('first_name')->get();
        return view('admin.wallet.transfer_plain', compact('shopkeepers'));
    }

    public function plainTransfer(Request $request, WalletService $walletService)
    {
        $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'amount'     => 'required|numeric|min:0.01',
            'debit_admin'=> 'nullable|boolean',
            'note'       => 'nullable|string|max:500'
        ]);

        $toUserId = (int) $request->to_user_id;
        $amount   = (float) $request->amount;
        $note     = $request->note;

        try {
            if ($request->has('debit_admin') && $request->boolean('debit_admin')) {
                // perform transfer (debit admin wallet and credit recipient)
                $walletService->transfer(auth()->id(), $toUserId, $amount, auth()->id(), $note);
            } else {
                // credit recipient (admin as system)
                $walletService->credit($toUserId, $amount, auth()->id(), null, $note);
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['transfer_error' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.wallet.transfer.plain')->with('success', 'Transfer completed successfully.');
    }

    public function transactionsPage()
{
    // The view admin.wallet.transactions already exists in your repo.
    // It should mount the Livewire admin component (livewire.admin.wallet-transactions).
    return view('admin.wallet.transactions');
}
}
