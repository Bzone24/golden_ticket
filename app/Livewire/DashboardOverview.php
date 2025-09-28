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
        $users_details = User::query()
            ->with([
                'creator.creator.creator',
                'drawDetails' => function ($query) {
                    $query->whereDate('draw_details.date', Carbon::now())
                        ->whereHas('ticketOptions')
                        ->orWhereHas('crossAbcDetail',function($q){
                            $q->whereDate('created_at', Carbon::now());
                        });
                }
            ])
            ->whereHas('roles', fn($r) => $r->where('name', 'user'))
            ->when($user->hasRole('master'), function ($q) use ($user) {
                return $q->whereHas('creator.creator', function ($q2) use ($user) {
                    $q2->where('created_by', $user->id);
                });
            })
            ->when($user->hasRole('admin'), function ($q) use ($user) {
                return $q->whereHas('creator', function ($q2) use ($user) {
                    $q2->where('created_by', $user->id);
                });
            })
            ->when($user->hasRole('shopkeeper'), function ($q) use ($user) {
                return $q->where('created_by', $user->id);
            })
            ->get();
            
            // dd($users_details);
        $this->users = $users_details
            ->map(function ($user) {
                // calculate per-user totals
                $userCrossAmtTotal = 0;
                $totalCrossClaim = 0;
                $totalClaims = 0;
                $userQtyTotal = 0;

                foreach ($user->drawDetails as $draw_detail) {
                    $userQtyTotal     += $this->getTq($draw_detail, $user->id);
                    $totalCrossClaim  += $this->calculateCrossClaim($draw_detail, $user->id);
                    $totalClaims      += $this->getClaim($draw_detail, $user->id);
                    $userCrossAmtTotal += $this->getCrossAmt($draw_detail, $user->id);
                }

                // dynamic club name based on logged-in user role
                $loginUser = auth()->user();
                $clubName = $user->name; // default → leaf user
                $parent_id = null;
                if ($loginUser->hasRole('admin')) {
                    $clubName = $user->creator->name ?? 'N/A'; // shopkeeper
                    $parent_id = $user->creator->id ?? null;
                } elseif ($loginUser->hasRole('master')) {
                    $clubName = $user->creator->creator->name ?? 'N/A'; // admin
                    $parent_id = $user->creator->creator->id ?? null;
                } else {
                    $parent_id = $user->creator->id ?? null;
                }

                return [
                    'parent_id'     => $parent_id,
                    'user_id'        => $user->id,
                    'name'           => $clubName,
                    'total_qty'      => $userQtyTotal,
                    'total_cross_amt' => $userCrossAmtTotal,
                    'record_count'   => $user->drawDetails->count(),
                    'cross_claim'    => $totalCrossClaim,
                    'claim'          => $totalClaims,
                ];
            })
            ->groupBy('name') // club by calculated name
            ->map(function ($grouped) {
                // accumulate fields
                return [
                    'name'           => $grouped->first()['name'],
                    'total_qty'      => $grouped->sum('total_qty'),
                    'total_cross_amt' => $grouped->sum('total_cross_amt'),
                    'record_count'   => $grouped->sum('record_count'),
                    'cross_claim'    => $grouped->sum('cross_claim'),
                    'claim'          => $grouped->sum('claim'),
                    'user_id'       => $grouped->first()['user_id'], 
                    'parent_id'     => $grouped->first()['parent_id'],
                ];
            })
            ->values();


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

        // get Drawdetails->getClaim() : ticket claim for today
        $this->total_claims = $user->children()
            ->with('drawDetails')
            ->get()
            ->flatMap->drawDetails
            ->where('date', Carbon::today()->toDateString())
            ->sum(function ($draw) {
                return $draw->claim_a + $draw->claim_b + $draw->claim_c;
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

        if (auth()->user()->hasRole('user')) {
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

    private function getClaim($draw_detail, $userId)
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

    protected function getTq($draw_detail, $userId)
    {
        $a_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('a_qty');
        $b_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('b_qty');
        $c_qty = $draw_detail->ticketOptions()->where('user_id', $userId)->sum('c_qty');
        return $a_qty + $b_qty + $c_qty;
    }

    protected function getCrossAmt($draw_detail, $userId)
    {
        return $draw_detail->crossAbcDetail()
            ->where('user_id', $userId)
            ->sum('amount');
    }
}
