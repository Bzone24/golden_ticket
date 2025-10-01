<?php

namespace App\DataTables;

use App\Models\DrawDetail;
use App\Models\User;
use App\Traits\CalculatePL;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DrawProfilLossDataTable extends DataTable
{
    use CalculatePL;

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<DrawDetail>  $query
     */

    protected ?int $userId = null;

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    protected function resolveUserId(): ?int
    {
        return $this->userId ?? auth()->id();
    }

    protected function isAdminSeg()
    {
        return request()->segment(1) === 'admin';
    }

    protected function getCrossClaim($draw_detail)
    {
        $ab_claim = $draw_detail->ab;
        $ac_claim = $draw_detail->ac;
        $bc_claim = $draw_detail->bc;
        $user = auth()->user();
        if ($user->hasRole(['user', 'shopkeeper', 'admin'])) {
            if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')
                        ->from('users')
                        ->where('created_by', $user->id);
                })->pluck('id');

                $totals = $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                        SUM(CASE WHEN number = ? AND type = 'AB' THEN amount ELSE 0 END) as ab_total,
                        SUM(CASE WHEN number = ? AND type = 'AC' THEN amount ELSE 0 END) as ac_total,
                        SUM(CASE WHEN number = ? AND type = 'BC' THEN amount ELSE 0 END) as bc_total
                    ", [$ab_claim, $ac_claim, $bc_claim])
                    ->first();

                $ab_claim = $totals->ab_total ?? 0;
                $ac_claim = $totals->ac_total ?? 0;
                $bc_claim = $totals->bc_total ?? 0;
            } elseif ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');
                $totals = $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                    SUM(CASE WHEN number = ? AND type = 'AB' THEN amount ELSE 0 END) as ab_total,
                    SUM(CASE WHEN number = ? AND type = 'AC' THEN amount ELSE 0 END) as ac_total,
                    SUM(CASE WHEN number = ? AND type = 'BC' THEN amount ELSE 0 END) as bc_total
                ", [$ab_claim, $ac_claim, $bc_claim])
                    ->first();

                $ab_claim = $totals->ab_total ?? 0;
                $ac_claim = $totals->ac_total ?? 0;
                $bc_claim = $totals->bc_total ?? 0;
            } else {
                $ab_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())
                    ->where('number', $ab_claim)
                    ->where('type', 'AB')->sum('amount');
                $ac_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())
                    ->where('number', $ac_claim)
                    ->where('type', 'AC')->sum('amount');
                $bc_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())
                    ->where('number', $bc_claim)
                    ->where('type', 'BC')->sum('amount');
            }


            return $ab_claim + $ac_claim + $bc_claim;
        }

        return $draw_detail->claim_ab + $draw_detail->claim_ac + $draw_detail->claim_bc;
    }

    protected function getCrossAmt($draw_detail)
    {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            $userIds = User::whereIn('created_by', function ($q) use ($user) {
                $q->select('id')
                    ->from('users')
                    ->where('created_by', $user->id);
            })->pluck('id');
            if ($user->hasRole('admin') && $this->userId) {
                return $draw_detail->crossAbcDetail()
                    ->where('user_id', $this->resolveUserId())
                    ->sum('amount');
            } else {
                return $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->sum('amount');
            }
        } elseif ($user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id');
            return $draw_detail->crossAbcDetail()
                ->whereIn('user_id', $userIds)
                ->sum('amount');
        } elseif ($user->hasRole('user')) {
            return $draw_detail->crossAbcDetail()
                ->where('user_id', $this->resolveUserId())
                ->sum('amount');
        } else {
            if ($user->hasRole('master') && $this->userId) {
                return $draw_detail->crossAbcDetail()
                    ->where('user_id', $this->resolveUserId())
                    ->sum('amount');
            } else {
                return $draw_detail->cross_amt;
            }
        }
    }

    protected function getClaim($draw_detail)
    {
        $user = auth()->user();
        if ($user->hasRole(['master'])) {
            return $draw_detail->claim;
        } else {
            $a_claim = $draw_detail->claim_a;
            $b_claim = $draw_detail->claim_b;
            $c_claim = $draw_detail->claim_c;
            if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')
                        ->from('users')
                        ->where('created_by', $user->id);
                })->pluck('id');

                $totals = $draw_detail->ticketOptions()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                                                    SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                                                    SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                                                    SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                                                ", [$a_claim, $b_claim, $c_claim])
                    ->first();

                $a_qty = $totals->a_total ?? 0;
                $b_qty = $totals->b_total ?? 0;
                $c_qty = $totals->c_total ?? 0;
            } else if ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');
                $totals = $draw_detail->ticketOptions()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                                                    SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                                                    SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                                                    SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                                                ", [$a_claim, $b_claim, $c_claim])
                    ->first();

                $a_qty = $totals->a_total ?? 0;
                $b_qty = $totals->b_total ?? 0;
                $c_qty = $totals->c_total ?? 0;
            } else {
                $a_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())
                    ->where('number', $a_claim)
                    ->sum('a_qty');
                $b_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())
                    ->where('number', $b_claim)
                    ->sum('b_qty');

                $c_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())
                    ->where('number', $c_claim)
                    ->sum('c_qty');
            }

            return $a_qty + $b_qty + $c_qty;
        }
    }

    protected function getResult($draw_detail)
    {
        $a_claim = $draw_detail->claim_a;
        $b_claim = $draw_detail->claim_b;
        $c_claim = $draw_detail->claim_c;

        return "$a_claim  $b_claim  $c_claim";
    }

    protected function getTq($draw_detail)
    {
        $user = auth()->user();
        if ($user->hasRole(['user', 'shopkeeper', 'admin']) || $this->userId) {
            if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')
                        ->from('users')
                        ->where('created_by', $user->id);
                })->pluck('id');
                $draw_detail =   $draw_detail->ticketOptions()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw('SUM(a_qty) as a_total, SUM(b_qty) as b_total, SUM(c_qty) as c_total')
                    ->first();
                $a_qty = $draw_detail->a_total ?? 0;
                $b_qty = $draw_detail->b_total ?? 0;
                $c_qty = $draw_detail->c_total ?? 0;
            } else if ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');
                $draw_detail =   $draw_detail->ticketOptions()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw('SUM(a_qty) as a_total, SUM(b_qty) as b_total, SUM(c_qty) as c_total')
                    ->first();
                $a_qty = $draw_detail->a_total ?? 0;
                $b_qty = $draw_detail->b_total ?? 0;
                $c_qty = $draw_detail->c_total ?? 0;
            } else {
                $a_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('a_qty');
                $b_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('b_qty');
                $c_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('c_qty');
            }

            return $a_qty + $b_qty + $c_qty;
        }

        return $draw_detail->tq;
    }
    //Sourabh 
    public function getGameName($draw_detail)
    {
        return $draw_detail->load('draw.game')->draw->game->name;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            //Sourabh 
            ->editColumn('game_id', function ($draw_detail) {
                return $this->getGameName($draw_detail);
            })
            ->editColumn('end_time', function ($draw_detail) {
                $end_time = Carbon::parse($draw_detail->end_time)->format('h:i a');
                $url = request()->segment(1) === 'admin'  
                    ? route('admin.draw.detail.list', [$draw_detail->id,'user_id'=>$this->userId])
                    : route('dashboard.draw.details.list', ['draw_detail_id' => $draw_detail->id]);

                return "<a href='$url' class='text-primary h6'>$end_time</a>";
            })
            ->filterColumn('end_time', function ($query, $keyword) {
                $keyword = strtolower($keyword);
                $query->whereRaw("TIME_FORMAT(end_time, '%h:%i %p') LIKE ?", ["%{$keyword}%"])
                    ->orWhereRaw("TIME_FORMAT(end_time, '%l %p') LIKE ?", ["%{$keyword}%"])
                    ->orWhereRaw("TIME_FORMAT(end_time, '%H:%i') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('tq', function ($query, $keyword) {
                // tq is derived column, so handle manually
                $query->whereRaw('CAST(total_qty AS CHAR) LIKE ?', ["%{$keyword}%"]);
            })
            ->filterColumn('cross_amt', function ($query, $keyword) {
                $query->whereRaw('CAST(total_cross_amt AS CHAR) LIKE ?', ["%{$keyword}%"]);
            })
            ->filterColumn('p_and_l', function ($query, $keyword) {
                $query->whereRaw('
            (
                (COALESCE(total_qty,0) * 11) 
                - (COALESCE(claim,0) * 100) 
                + COALESCE(total_cross_amt,0) 
                - ((COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)) * 100)
            ) LIKE ?
            ', ["%{$keyword}%"]);
            })
            ->filterColumn('cross_claim', function ($query, $keyword) {
                $query->whereRaw('((claim_ab + claim_ac + claim_bc) * 100) LIKE ?', ["%{$keyword}%"]);
            })
            ->editColumn('tq', function ($draw_detail) {
                $tq = $this->getTq($draw_detail);
                $url = $this->isAdminSeg() ? route('admin.dashboard.total.qty.details.list', [$draw_detail->id, 'user_id' => $this->userId])
                    : route('dashboard.draw.total.qty.list.details', $draw_detail->id);

                return "<a href='$url' class='text-primary h6'>{$tq}</a>";
            })
            ->editColumn('cross_amt', function ($draw_detail) {
                $cross_amt = $this->getCrossAmt($draw_detail);
                $url = $this->isAdminSeg() ? route('admin.dashboard.cross.abc', ['draw_detail_id' => $draw_detail->id, 'user_id' => $this->userId])
                    : route('dashboard.draw.cross.abc.details.list', ['draw_detail_id' => $draw_detail->id]);

                return "<a href='$url' class='text-primary h6'>{$cross_amt}</a>";
            })
            ->editColumn('cross_claim', function ($draw_detail) {
                return $this->getCrossClaim($draw_detail);
            })
            ->editColumn('claim', function ($draw_detail) {
                return $this->getClaim($draw_detail);
            })
            ->editColumn('created_at', fn($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
            ->editColumn('p_and_l', function ($row) {
                // $p_and_l = (int) $row->p_and_l;
                $tq = $this->getTq($row) * 11;
                $cross_amt = $this->getCrossAmt($row);
                $claim = $this->getClaim($row);
                $crossClaim = $this->getCrossClaim($row);
                $p_and_l = $this->calculateProfitAndLoss($tq, $cross_amt, $claim, $crossClaim);
                $bgClass = $p_and_l < 0 ? 'bg-danger text-white' : 'bg-success text-white';
                if ($p_and_l == 0) {
                    $bgClass = 'text-dark';
                }

                return "<div class='{$bgClass} text-center'>{$p_and_l}</div>";
            })
            ->addColumn('action', function ($draw_detail) {
                $draw_details = route('dashboard.draw.details.list', ['draw_id' => $draw_detail->id]);
                $draw_detail_id = $draw_detail->id;
                $end_time = Carbon::createFromTimeString($draw_detail->end_time)->format('H:i');
                $now = Carbon::now()->setSecond(0)->timezone('Asia/Kolkata');
                $segment = request()->segment(1);
                if (auth()->user()->hasRole('master')) {
                    if (
                        $segment === 'admin' && $now->gte($end_time) &&
                        (empty($draw_detail->claim_a) && empty($draw_detail->claim_a) && empty($draw_detail->claim_b))
                    ) {

                        return <<<HTML
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-warning addClaim ms-3 text-white" data-draw-detail-id="{$draw_detail_id}">Claim</button>
                            </div>
                        HTML;
                    } else {
                        if (!empty($draw_detail->claim_a) || !empty($draw_detail->claim_a) || !empty($draw_detail->claim_b)) {
                            return <<<HTML
                                <div class="d-flex justify-content-center justify-space-between">
                                 
                                    <button class="btn btn-danger addClaim ms-3 text-white" data-draw-detail-id="{$draw_detail_id}">
                                       <strong>{$this->getResult($draw_detail)}</strong>
                                       <i class="fa fa-pencil"></i> 
                                    </button>
                                </div>
                            HTML;
                        }
                    }
                }

                 // Other roles: just show result (no button)
    if (!empty($draw_detail->claim_a) || !empty($draw_detail->claim_b) || !empty($draw_detail->claim_c)) {
        return "<div class='text-center fw-bold text-success'>{$this->getResult($draw_detail)}</div>";
    }

                return '--';
            })
            ->rawColumns(['game_id', 'end_time', 'tq', 'cross_amt', 'p_and_l', 'action', 'cross_claim', 'claim']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(DrawDetail $model, Request $request): QueryBuilder
    {
        // dd(auth()->user());
        $game_id = $request->get('game_id');
        return $model->newQuery()
            ->select('draw_details.*')
            ->when($game_id, fn($q) => $q->where('game_id', $game_id))
            // ✅ Pre-computed values for ordering/searching
            ->selectRaw('COALESCE(total_qty,0) as tq')
            ->selectRaw('(COALESCE(total_qty,0) * 11) as t_amt')
            // ->selectRaw('COALESCE(claim,0) as claim')
            ->selectRaw('(COALESCE(claim,0) * 100) as c_amt')
            ->selectRaw('COALESCE(total_cross_amt,0) as cross_amt')
            ->selectRaw('(COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)) as cross_claim')
            ->selectRaw('
                ((COALESCE(total_qty,0) * 11) 
                - (COALESCE(claim,0) * 100) 
                + COALESCE(total_cross_amt,0) 
                - ((COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)) * 100)
                ) as p_and_l
            ')
            // ✅ Date filters
            ->when(
                ! $request->get('start_date') && ! $request->get('end_date') && ! $request->get('da7'),
                fn($q) => $q->whereDate('date', now())
            )
            ->when(
                $request->filled('start_date') && $request->filled('end_date'),
                fn($q) => $q->whereBetween('date', [$request->get('start_date'), $request->get('end_date')])
            )
            ->when(
                $request->filled('start_date') && ! $request->filled('end_date'),
                fn($q) => $q->whereDate('date', $request->get('start_date'))
            )
            ->when($request->filled('day'), function ($q) use ($request) {
                return match ($request->get('day')) {
                    'Today' => $q->whereDate('date', now()),
                    'Yesterday' => $q->whereDate('date', now()->subDay()),
                    'Last 7 Days' => $q->whereBetween('date', [now()->subDays(6), now()]),
                    'Last 30 Days' => $q->whereBetween('date', [now()->subDays(29), now()]),
                    'This Month' => $q->whereBetween('date', [now()->startOfMonth(), now()->endOfMonth()]),
                    'Last Month' => $q->whereBetween('date', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]),
                    default => $q
                };
            })
            // ✅ For non-admin restrict by user
            // ->when(
            //     auth()->user()->hasRole(['user']) || $this->userId,
            //     fn($q) => $q->forUserTicketOption($this->resolveUserId())
            // )
            //  For user new added showing all draw list in dashboard


            // ✅ Order handling
            ->when($request->has('order'), function ($q) use ($request) {
                $columns = collect($this->getColumns())->pluck('name')->values()->all();
                $order = $request->get('order')[0];
                $columnIndex = $order['column'];
                $direction = $order['dir'];
                if (isset($columns[$columnIndex])) {
                    $q->orderBy($columns[$columnIndex], $direction);
                }
            });
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('draw-details-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->addTableClass('table table-bordered table-hover')
            ->setTableHeadClass('bg-warning text-white')
            ->parameters([
                'ordering' => true,
                'searching' => true,
                'language' => ['searchPlaceholder' => 'Enter Hour Or Minute'],
                'paging' => false, // Disable pagination
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'responsive' => true,
                'autoWidth' => false,
                'info' => false, // Also disable "Showing X of Y entries" info
                'lengthChange' => false, // Disable "Show X entries" dropdown
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
            ]);
    }

    public function getColumns(): array
    {
        $columns = [
            Column::make('updated_at')->hidden(),
            //Sourabh
            Column::make('game_id')->title('Game'),
            Column::make('end_time')->title('Time')->orderable(true)->searchable(true),
            Column::make('tq')->title('TQ'),
            Column::make('claim'),
            Column::make('cross_amt')->title('Cross Amt.'),
            Column::make('cross_claim')->title('Cross Claim'),
            Column::make('p_and_l')->title('P&L'),
            Column::make('created_at')->title('Created At'),
            // Column::make('action')->title('Action')->orderable(false),
        ];

       if ($this->isAdminSeg() && !$this->userId) {
        // Admin side action column
        $columns[] = Column::make('action')->title('Action')->orderable(false);
    } else {
        // User side action column
        $columns[] = Column::make('action')->title('Action')->orderable(false);
    }

    return $columns;
}

    protected function filename(): string
    {
        return 'Shopkeepers_' . date('YmdHis');
    }
}
