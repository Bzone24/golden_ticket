<?php
namespace App\Http\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WalletTransaction;

class WalletTransactions extends Component
{
    use WithPagination;

    // Use bootstrap pagination markup
    protected $paginationTheme = 'bootstrap';

    public $perPage = 15;
    public $search = '';

    protected $queryString = ['search'];

    // Reset to first page whenever search changes
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Reset to first page whenever perPage changes
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Base query, eager-load wallet.owner and performer to avoid N+1
        $query = WalletTransaction::with(['wallet.user', 'performer'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->search)) {
            $search = $this->search;

            $query->where(function($q) use ($search) {
                // match against wallet transaction fields
                $q->where('note', 'like', '%'.$search.'%')
                  ->orWhere('type', 'like', '%'.$search.'%')
                  ->orWhere('wallet_id', $search);

                // match against related wallet owner (shopkeeper/user)
                $q->orWhereHas('wallet.user', function($uq) use ($search) {
                    $uq->where('first_name', 'like', '%'.$search.'%')
                       ->orWhere('last_name', 'like', '%'.$search.'%')
                       ->orWhere('name', 'like', '%'.$search.'%')
                       ->orWhere('email', 'like', '%'.$search.'%');
                });

                // match against performer (admin/user who performed the tx)
                $q->orWhereHas('performer', function($pq) use ($search) {
                    $pq->where('first_name', 'like', '%'.$search.'%')
                       ->orWhere('last_name', 'like', '%'.$search.'%')
                       ->orWhere('name', 'like', '%'.$search.'%')
                       ->orWhere('email', 'like', '%'.$search.'%');
                });
            });
        }

        $transactions = $query->paginate($this->perPage);

        return view('livewire.admin.wallet-transactions', [
            'transactions' => $transactions,
        ]);
    }
}
