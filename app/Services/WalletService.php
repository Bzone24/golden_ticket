<?php
namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WalletService
{
    public function ensureWallet(int $userId): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0]);
    }

    protected function getWalletForUpdate(int $userId): Wallet
    {
        // Use transaction caller to ensure locking; caller below wraps in DB::transaction()
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        if (! $wallet) {
            $wallet = Wallet::create(['user_id' => $userId, 'balance' => 0]);
            // reload and lock
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->first();
        }
        return $wallet;
    }

    /**
     * Credit user wallet.
     *
     * @return WalletTransaction
     */
    public function credit(int $userId, float $amount, ?int $performedBy = null, $relatedId = null, ?string $note = null, string $type = 'manual_credit'): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return DB::transaction(function () use ($userId, $amount, $performedBy, $relatedId, $note, $type) {
            $wallet = $this->getWalletForUpdate($userId);

            // safe decimal math: prefer bc functions if available
            if (function_exists('bcadd')) {
                $wallet->balance = bcadd((string)$wallet->balance, number_format($amount, 2, '.', ''), 2);
            } else {
                $wallet->balance = round($wallet->balance + $amount, 2);
            }
            $wallet->save();

            // build meta with performed_by so it is always available for audit
            $meta = [
                'performed_by' => $performedBy,
                'original_type' => $type,
            ];

            return WalletTransaction::create([
                'wallet_id'    => $wallet->id,
                'type'         => $type,   // e.g. 'manual_credit', 'manual_debit', 'transfer_in', 'transfer_out'
                'amount'       => $amount,
                'balance'      => $wallet->balance,
                'performed_by' => $performedBy,
                'related_id'   => $relatedId,
                'note'         => $note,
                'meta'         => json_encode($meta),
            ]);
        });
    }

    /**
     * Debit user wallet (throws RuntimeException on insufficient balance).
     *
     * @return WalletTransaction
     */
    public function debit(int $userId, float $amount, ?int $performedBy = null, $relatedId = null, ?string $note = null, string $type = 'manual_debit'): WalletTransaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return DB::transaction(function () use ($userId, $amount, $performedBy, $relatedId, $note, $type) {
            $wallet = $this->getWalletForUpdate($userId);

            // Check bccomp existence first
            if (function_exists('bccomp')) {
                if (bccomp((string)$wallet->balance, number_format($amount, 2, '.', ''), 2) < 0) {
                    throw new \RuntimeException('Insufficient wallet balance');
                }
            } else {
                if ($wallet->balance < $amount) {
                    throw new \RuntimeException('Insufficient wallet balance');
                }
            }

            if (function_exists('bcsub')) {
                $wallet->balance = bcsub((string)$wallet->balance, number_format($amount, 2, '.', ''), 2);
            } else {
                $wallet->balance = round($wallet->balance - $amount, 2);
            }
            $wallet->save();

            $meta = [
                'performed_by' => $performedBy,
                'original_type' => $type,
            ];

            return WalletTransaction::create([
                'wallet_id'    => $wallet->id,
                'type'         => $type,
                'amount'       => $amount,
                'balance'      => $wallet->balance,
                'performed_by' => $performedBy,
                'related_id'   => $relatedId,
                'note'         => $note,
                'meta'         => json_encode($meta),
            ]);
        });
    }

    /**
     * Transfer from one user to another (debit + credit).
     *
     * Returns array with 'debit' and 'credit' WalletTransaction objects.
     *
     * @return array
     */
    public function transfer(int $fromUserId, int $toUserId, float $amount, ?int $performedBy = null, ?string $note = null): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }

        return DB::transaction(function () use ($fromUserId, $toUserId, $amount, $performedBy, $note) {
            // debit from -> transfer_out
            $debitTx = $this->debit($fromUserId, $amount, $performedBy, null, $note, 'transfer_out');
            // credit to -> transfer_in
            $creditTx = $this->credit($toUserId, $amount, $performedBy, null, $note, 'transfer_in');

            return ['debit' => $debitTx, 'credit' => $creditTx];
        });
    }
}
