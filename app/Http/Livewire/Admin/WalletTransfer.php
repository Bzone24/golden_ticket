<?php
namespace App\Http\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Services\WalletService;

class WalletTransfer extends Component
{
    public $to_user_id;
    public $amount;
    public $note;
    public $shopkeepers = [];

    protected $rules = [
        'to_user_id' => 'required|exists:users,id',
        'amount' => 'required|numeric|min:0.01',
        'note' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        // Simple heuristic: list users except current admin. Adjust as needed.
        $this->shopkeepers = User::where('id', '!=', auth()->id())->limit(200)->get();
    }

    public function transfer(WalletService $walletService)
    {
        $this->validate();

        try {
            // For admin transfers we credit the target (admin funds are treated as system)
            $tx = $walletService->credit((int)$this->to_user_id, (float)$this->amount, auth()->id(), null, $this->note);
            session()->flash('success', 'Transferred ₹' . number_format($this->amount,2) . ' successfully.');
            $this->reset(['to_user_id','amount','note']);
            $this->mount();
        } catch (\Throwable $e) {
            $this->addError('transfer_error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.wallet-transfer', [
            'shopkeepers' => $this->shopkeepers,
        ]);
    }
}
