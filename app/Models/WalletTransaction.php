<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id','type','amount','balance','performed_by','related_id','note','meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];
    
 

    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    /**
     * The user who performed this transaction (could be admin or user).
     * performed_by column stores the user id (nullable for system).
     */
    public function performer()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    public function performedByUser()
    {
        return $this->performer();
    }
}
