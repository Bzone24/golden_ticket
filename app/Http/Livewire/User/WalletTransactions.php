<?php

namespace App\Http\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WalletTransaction;

class WalletTransactions extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';

    protected $queryString = ['search'];
    protected $paginationTheme = 'bootstrap';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = WalletTransaction::with(['performer', 'wallet'])
            ->whereHas('wallet', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc');

        if (!empty($this->search)) {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('note', 'like', "%{$s}%")
                  ->orWhere('type', 'like', "%{$s}%");
            });
        }

        $transactions = $query->paginate($this->perPage);

        return view('livewire.user.wallet-transactions', [
            'transactions' => $transactions,
        ]);
    }
}
