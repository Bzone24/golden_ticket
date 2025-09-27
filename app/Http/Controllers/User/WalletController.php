<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        return view('user.wallet.index');
    }

    public function transactions()
    {
        // ✅ Only this one version should exist
        // It calls the wrapper blade which mounts your Livewire component
        return view('user.wallet.transactions');
    }

    public function showTransferForm()
    {
        return view('user.wallet.transfer');
    }

    public function submitTransfer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:500',
        ]);

        // TODO: connect with WalletService if you want actual logic
        return redirect()->back()->with('status', 'Transfer request submitted (stub).');
    }
}
