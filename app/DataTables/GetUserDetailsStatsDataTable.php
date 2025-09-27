<?php

namespace App\DataTables;

use App\Models\DrawDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class GetUserDetailsStatsDataTable extends DataTable
{

    protected function getTq($user)
    {
        if ($user->hasRole(['user', 'shopkeeper', 'admin'])) {
            if ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');
                $draw_detail =  DrawDetail::with(['ticketOptions' => function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds);
                }])
                    ->whereDate('date', Carbon::now())
                    ->whereHas('ticketOptions', function ($q) use ($userIds) {
                        $q->whereIn('user_id', $userIds);
                    })
                    ->get();
                $a_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('a_qty'))->sum() : 0;
                $b_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('b_qty'))->sum() : 0;
                $c_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('c_qty'))->sum() : 0;
            } else {
                $draw_detail =  DrawDetail::with(['ticketOptions' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                    ->whereDate('date', Carbon::now())
                    ->whereHas('ticketOptions', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })
                    ->get();

                $a_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('a_qty'))->sum() : 0;
                $b_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('b_qty'))->sum() : 0;
                $c_qty = $draw_detail ? $draw_detail->map(fn($item) => $item->ticketOptions->sum('c_qty'))->sum() : 0;
            }

            return $a_qty + $b_qty + $c_qty;
        }
    }

    protected function getCrossAmt($user)
    {
        if ($user->hasRole(['user', 'shopkeeper', 'admin'])) {
             if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')
                        ->from('users')
                        ->where('created_by', $user->id);
                })->pluck('id');

                $draw_detail =  DrawDetail::whereDate('date', Carbon::now())->get();
                return  $draw_detail->flatMap->crossAbcDetail->whereIn('user_id',$userIds)->sum('amount');
             } elseif ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');
                $draw_detail =  DrawDetail::whereDate('date', Carbon::now())->get();
                return $draw_detail->flatMap->crossAbcDetail->whereIn('user_id',$userIds)->sum('amount');
             } else {
                $draw_detail =  DrawDetail::whereDate('date', Carbon::now())->get();
                return $draw_detail->flatMap->crossAbcDetail->where('user_id',$user->id)->sum('amount');
             }
        }

        // return $draw_detail->cross_amt;
    }

    protected function getClaim($user)
    {
        $a_qty = 0;
        $b_qty = 0;
        $c_qty = 0;
        if ($user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id');
            $draw_details = DrawDetail::whereHas('ticketOptions',function($q) use ($userIds){
                return $q->whereIn('user_id',$userIds);
            })->whereDate('date', Carbon::now())->get();
            foreach ($draw_details as $key => $draw_detail) {
                $a_claim = $draw_detail->claim_a;
                $b_claim = $draw_detail->claim_b;
                $c_claim = $draw_detail->claim_c;

                $totals = $draw_detail->ticketOptions()
                    ->selectRaw("
                                    SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                                    SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                                    SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                                ", [$a_claim, $b_claim, $c_claim])
                    ->first();

                $a_qty += $totals->a_total ?? 0;
                $b_qty += $totals->b_total ?? 0;
                $c_qty += $totals->c_total ?? 0;
            }
        } else if($user->hasRole('user')) {
            $draw_details = DrawDetail::whereHas('ticketOptions',function($q) use ($user){
                return $q->where('user_id',$user->id);
            })->whereDate('date', Carbon::now())->get();
                
            foreach ($draw_details as $key => $draw_detail) {
                $a_claim = $draw_detail->claim_a;
                $b_claim = $draw_detail->claim_b;
                $c_claim = $draw_detail->claim_c;
                $a_qty += $draw_detail->ticketOptions()->where('user_id', $user->id)
                    ->where('number', $a_claim)
                    ->sum('a_qty');
                $b_qty += $draw_detail->ticketOptions()->where('user_id', $user->id)
                    ->where('number', $b_claim)
                    ->sum('b_qty');

                $c_qty += $draw_detail->ticketOptions()->where('user_id', $user->id)
                    ->where('number', $c_claim)
                    ->sum('c_qty');
            }
        }

        return $a_qty + $b_qty + $c_qty;
    }

    protected function getCrossClaim($user)
    {
       
        // $user = auth()->user();
        // if ($user->hasRole(['shopkeeper'])) {
        $output_ab_claim = 0;
        $output_ac_claim = 0;
        $output_bc_claim = 0;
        if ($user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id');
            $draw_details = DrawDetail::whereHas('crossAbcDetail',function($q) use ($userIds){
                return $q->whereIn('user_id',$userIds);
            })->whereDate('date', Carbon::now())->get();

            foreach ($draw_details as $key => $draw_detail) {
                $ab_claim = $draw_detail->ab;
                $ac_claim = $draw_detail->ac;
                $bc_claim = $draw_detail->bc;
                $totals = $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                    SUM(CASE WHEN number = ? AND type = 'AB' THEN amount ELSE 0 END) as ab_total,
                    SUM(CASE WHEN number = ? AND type = 'AC' THEN amount ELSE 0 END) as ac_total,
                    SUM(CASE WHEN number = ? AND type = 'BC' THEN amount ELSE 0 END) as bc_total
                ", [$ab_claim, $ac_claim, $bc_claim])
                    ->first();

                $output_ab_claim += $totals->ab_total ?? 0;
                $output_ac_claim += $totals->ac_total ?? 0;
                $output_bc_claim += $totals->bc_total ?? 0;
            }
        } else {
             $draw_details = DrawDetail::whereHas('crossAbcDetail',function($q) use ($user){
                return $q->where('user_id',$user->id);
            })->whereDate('date', Carbon::now())->get();

              foreach ($draw_details as $key => $draw_detail) {
                $ab_claim = $draw_detail->ab;
                $ac_claim = $draw_detail->ac;
                $bc_claim = $draw_detail->bc;

                $output_ab_claim = $draw_detail->crossAbcDetail()->where('user_id', $user->id)
                    ->where('number', $ab_claim)
                    ->where('type', 'AB')->sum('amount');
                $output_ac_claim = $draw_detail->crossAbcDetail()->where('user_id', $user->id)
                    ->where('number', $ac_claim)
                    ->where('type', 'AC')->sum('amount');
                $output_bc_claim = $draw_detail->crossAbcDetail()->where('user_id', $user->id)
                    ->where('number', $bc_claim)
                    ->where('type', 'BC')->sum('amount');
              }

        }
        return $output_ab_claim + $output_ac_claim + $output_bc_claim;
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<GetUserDetailsStat> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('name', function ($user) {
                if (!$user->hasRole('user')) {
                    return "<a href='" . route('admin.shopkeeper.get.user.details', $user->id) . "'>$user->name</a>";
                } else {
                    return "<a href='" . route('admin.shopkeeper.drawlist', $user->id) . "'>$user->name</a>";
                }
            })
            ->editColumn('tq', function ($user) {
                $tq = $this->getTq($user);
                return $tq;
            })
            ->editColumn('cl', function ($user) {
                return  $this->getClaim($user);
            })
             ->editColumn('ca', function ($user) {
                $tq = $this->getCrossAmt($user);
                return $tq;
            })
             ->editColumn('cc', function ($user) {
                $tq = $this->getCrossClaim($user);
                return $tq;
            })
            ->rawColumns(['name', 'tq','cl','ca','cc']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<GetUserDetailsStat>
     */
    public function query(User $model): QueryBuilder
    {
        $userId = request()->route('user_id');
        if (!$userId) {
            abort(404, 'User ID is required');
        }
        return $model->newQuery()->where('created_by', $userId)->select('users.*');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('getuserdetailsstats-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Name'),
            Column::make('tq')->title('TQ'),
            Column::make('cl')->title('CL'),
            Column::make('ca')->title('CA'),
            Column::make('cc')->title('CC'),
            Column::make('created_at')->title('Created At'),
            // Column::make('updated_at')->title('Updated At'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'GetUserDetailsStats_' . date('YmdHis');
    }
}
