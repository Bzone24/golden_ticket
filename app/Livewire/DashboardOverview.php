<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\DrawDetail;
use App\Models\User; // assuming shopkeepers are users with a role
use Illuminate\Support\Carbon;

class DashboardOverview extends Component
{
    public $total_shopkeepers = 0;
    public $total_tickets = 0;
    public $total_claims = 0;
    public $total_cross_amt = 0;
    public $total_cross_claim = 0;
    public $users = [];

   public function loadData()
    {
        $today = Carbon::today('Asia/Kolkata');
        $user = auth()->user();
        $users_details = User::with(['drawDetails'=> function ($query) {
            $query->whereDate('draw_details.date', Carbon::now())->whereHas('ticketOptions');
        }])->when($user->hasRole('admin'), function ($q) {
            return $q;
        })
        ->when($user->hasRole('shopkeeper'), function ($q) use ($user) {
            return $q->where('created_by', $user->id);
        })
        ->get();
        

        
        $this->users = $users_details->map(function ($user,$key) {
            $userCrossAmtTotal = 0;
            $totalCrossClaim = 0;
            $totalClaims = 0;
            $userQtyTotal = 0;
            foreach ($user->drawDetails as $draw_detail) {
                $userQtyTotal += $this->getTq($draw_detail,$user->id);
                $totalCrossClaim += $this->calculateCrossClaim($draw_detail, $user->id);
                $totalClaims += $this->getClaim($draw_detail, $user->id);
                $userCrossAmtTotal += $this->getCrossAmt($draw_detail, $user->id);
            }
            
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'total_qty' => $userQtyTotal,
                'total_cross_amt' => $userCrossAmtTotal,
                'record_count' => $user->drawDetails->count(),
                'cross_claim' => $totalCrossClaim,
                'claim' => $totalClaims,
            ];
        });
        
        // All users = shopkeepers
        $this->total_shopkeepers = User::count();

        // Tickets created today
        // $this->total_tickets = Ticket::whereDate('created_at', $today)->count();
        $totalTicketQuery = Ticket::whereDate('created_at', $today);

         if ($user->hasRole('shopkeeper')) {
            $totalTicketQuery->whereHas('user', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        }
        
        $this->total_tickets = $totalTicketQuery->count();

        // Claims (sum of all claim columns for today)
        $this->total_claims = $user->children()
                                            ->with('drawDetails')
                                            ->get()
                                            ->flatMap->drawDetails
                                            ->where('date', Carbon::today()->toDateString())
                                            ->sum(function ($draw) {
                                                return $draw->claim_a + $draw->claim_b + $draw->claim_c
                                                    + $draw->claim_ab + $draw->claim_ac + $draw->claim_bc;
                                            });

        // Total Cross Amount
        $this->total_cross_amt = DrawDetail::whereDate('date', $today)->sum('total_cross_amt');

        // Cross Claim (if you want to count cross tickets separately, maybe = total_qty?)
        $this->total_cross_claim = DrawDetail::whereDate('date', $today)->sum('total_qty');
    }


    public function render()
    {
        $this->loadData();
        return view('livewire.dashboard-overview');
    }

    private function calculateCrossClaim($draw_detail, $userId = null)
    {
        $ab_claim = $draw_detail->ab;
        $ac_claim = $draw_detail->ac;
        $bc_claim = $draw_detail->bc;
        
        if (request()->segment(1) != 'admin') {
            $ab_claim = $draw_detail->crossAbcDetail()
                ->where('user_id', $userId ?? auth()->id())
                ->where('number', $ab_claim)
                ->where('type', 'AB')
                ->sum('amount');
                
            $ac_claim = $draw_detail->crossAbcDetail()
                ->where('user_id', $userId ?? auth()->id())
                ->where('number', $ac_claim)
                ->where('type', 'AC')
                ->sum('amount');
                
            $bc_claim = $draw_detail->crossAbcDetail()
                ->where('user_id', $userId ?? auth()->id())
                ->where('number', $bc_claim)
                ->where('type', 'BC')
                ->sum('amount');

            return $ab_claim + $ac_claim + $bc_claim;
        }

        return $draw_detail->claim_ab + $draw_detail->claim_ac + $draw_detail->claim_bc;
    }

    private function getClaim($draw_detail,$userId)
    {
            $a_claim = $draw_detail->claim_a;
            $b_claim = $draw_detail->claim_b;
            $c_claim = $draw_detail->claim_c;

            $a_qty = $draw_detail->ticketOptions()->where('user_id', $userId)
                ->where('number', $a_claim)
                ->sum('a_qty');
            $b_qty = $draw_detail->ticketOptions()->where('user_id', $userId)
                ->where('number', $b_claim)
                ->sum('b_qty');

            $c_qty = $draw_detail->ticketOptions()->where('user_id', $userId)
                ->where('number', $c_claim)
                ->sum('c_qty');

            return $a_qty + $b_qty + $c_qty;
            // ->claim_a_qty + $draw_detail->ticketOption->claim_b_qty + $draw_detail->ticketOption->claim_c_qty;

    }

    protected function getTq($draw_detail,$userId)
    {
        $a_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('a_qty');
        $b_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('b_qty');
        $c_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('c_qty');
        return $a_qty + $b_qty + $c_qty;
    }

    protected function getCrossAmt($draw_detail,$userId)
    {
        return $draw_detail->crossAbcDetail()
            ->where('user_id', $userId)
            ->sum('amount');
    }
}
