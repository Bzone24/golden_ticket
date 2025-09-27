<?php
namespace App\Services;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
class WalletService
{
    public function ensureWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
    }
    public function debit(int $userId, float $amount, ?int $performedBy = null, $relatedId = null, $note = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        return DB::transaction(function () use ($userId, $amount, $performedBy, $relatedId, $note) {
            $wallet = $this->ensureWallet($userId);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            if (bccomp($wallet->balance, $amount, 2) < 0) {
                throw new \RuntimeException('Insufficient wallet balance');
            }
            $wallet->balance = bcsub($wallet->balance, $amount, 2);
            $wallet->save();
            $tx = WalletTransaction::create([
                'wallet_id'   => $wallet->id,
                'type'        => 'debit',
                'amount'      => $amount,
                'balance'     => $wallet->balance,
                'performed_by'=> $performedBy,
                'related_id'  => $relatedId,
                'note'        => $note,
            ]);
            return $tx;
        });
    }
    public function credit(int $userId, float $amount, ?int $performedBy = null, $relatedId = null, $note = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        return DB::transaction(function () use ($userId, $amount, $performedBy, $relatedId, $note) {
            $wallet = $this->ensureWallet($userId);
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
            $wallet->balance = bcadd($wallet->balance, $amount, 2);
            $wallet->save();
            $tx = WalletTransaction::create([
                'wallet_id'   => $wallet->id,
                'type'        => 'credit',
                'amount'      => $amount,
                'balance'     => $wallet->balance,
                'performed_by'=> $performedBy,
                'related_id'  => $relatedId,
                'note'        => $note,
            ]);
            return $tx;
        });
    }
    public function transfer(int $fromUserId, int $toUserId, float $amount, ?int $performedBy = null, $note = null)
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        return DB::transaction(function () use ($fromUserId, $toUserId, $amount, $performedBy, $note) {
            $debitTx = $this->debit($fromUserId, $amount, $performedBy, null, 'transfer_out: '.$note);
            $creditTx = $this->credit($toUserId, $amount, $performedBy, null, 'transfer_in: '.$note);
            return ['debit' => $debitTx, 'credit' => $creditTx];
        });
    }
}
