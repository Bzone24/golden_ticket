<?php

namespace App\DataTables\Admin;

use App\Models\Shopkeeper;
use App\Models\UserDraw;
use App\Traits\CalculatePL;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DrawDetailsDataTable extends DataTable
{
    use CalculatePL;

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    protected function getTq($user_draw)
    {
        $ticket_option = $user_draw->ticketOptions;

        return $ticket_option->sum('a_qty') + $ticket_option->sum('b_qty') + $ticket_option->sum('c_qty');

    }

    protected function getClaim($user_draw)
    {
        $draw_details = $user_draw->drawDetail;
        $ticket_option = $user_draw->ticketOptions;
        $total_a_claim = $ticket_option->where('number', $draw_details->claim_a)->sum('a_qty');
        $total_b_claim = $ticket_option->where('number', $draw_details->claim_b)->sum('b_qty');
        $total_c_claim = $ticket_option->where('number', $draw_details->claim_c)->sum('c_qty');

        return $total_a_claim + $total_b_claim + $total_c_claim;
    }

    protected function getCrossAmt($user_draw)
    {
        $draw_detail = $user_draw->drawDetail;

        return $draw_detail->crossAbcDetail()
            ->where('user_id', $user_draw->user_id)
            ->sum('amount');

    }

    protected function getCrossClaim($user_draw)
    {
        $crossAbcDetail = $user_draw->crossAbcDetail ?? collect();

        return (int) $crossAbcDetail
            ->where('draw_detail_id', request()->drawDetail->id)
            ->where('type', 'AB')
            ->where('number', $user_draw->drawDetail->ab)
            ->sum('amount')
        + (int) $crossAbcDetail
            ->where('draw_detail_id', request()->drawDetail->id)
            ->where('type', 'AC')
            ->where('number', $user_draw->drawDetail->ac)
            ->sum('amount')
        + (int) $crossAbcDetail
            ->where('draw_detail_id', request()->drawDetail->id)
            ->where('type', 'BC')
            ->where('number', $user_draw->drawDetail->bc)
            ->sum('amount');
    }

    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('shop_keeper', function ($user_draw) {
                $url = route('admin.draw.details.shopkeeper', [
                    'drawDetail' => $user_draw->draw_detail_id,
                    'user' => $user_draw->user_id,
                ]);
                $shopkeeper = $user_draw->user->name;

                return "<a href='$url' class='text-primary'>$shopkeeper</a>";
            })
            ->addColumn('tq', function ($user_draw) {
                return $this->getTq($user_draw);
            })
            ->addColumn('t_amt', function ($user_draw) {
                $ticket_option = $user_draw->ticketOptions;

                return ($ticket_option->sum('a_qty') + $ticket_option->sum('b_qty') + $ticket_option->sum('c_qty')) * 100;
            })
            ->addColumn('cross_amt', function ($user_draw) {
                return $this->getCrossAmt($user_draw);
            })
            ->addColumn('cross_claim', function ($user_draw) {
                return $this->getCrossClaim($user_draw);
            })
            ->addColumn('claim', function ($user_draw) {
                return $this->getClaim($user_draw);
            })
            ->addColumn('c_amt', function ($user_draw) {
                $draw_details = $user_draw->drawDetail;
                $ticket_option = $user_draw->ticketOptions;
                $total_a_claim = $ticket_option->where('number', $draw_details->claim_a)->sum('a_qty');
                $total_b_claim = $ticket_option->where('number', $draw_details->claim_b)->sum('b_qty');
                $total_c_claim = $ticket_option->where('number', $draw_details->claim_c)->sum('c_qty');

                return ($total_a_claim + $total_b_claim + $total_c_claim) * 100;
            })
            ->addColumn('p_and_l', function ($user_draw) {
                $tq = $this->getTq($user_draw);
                $claim = $this->getClaim($user_draw);
                $crossClaim = $this->getCrossClaim($user_draw);
                $crossclaimAmt = $this->getCrossAmt($user_draw);
                $p_and_l = $this->calculateProfitAndLoss($tq * 11, $crossclaimAmt, $claim, $crossClaim);
                $bgClass = $p_and_l < 0 ? 'bg-danger text-white' : 'bg-success text-white';
                if ($p_and_l == 0) {
                    $bgClass = 'text-dark';
                }

                return <<<HTML
            <div class="{$bgClass} text-center">{$p_and_l}</div>
            HTML;
            })
            ->filterColumn('shop_keeper', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->forName($keyword);
                });
            })
            ->filterColumn('tq', function ($query, $keyword) {
                $query->whereHas('ticketOptions', function ($q) use ($keyword) {
                    $q->whereRaw('(a_qty + b_qty + c_qty) LIKE ?', ["%{$keyword}%"]);
                });
            })
            ->filterColumn('cross_amt', function ($query, $keyword) {
                $query->whereHas('crossAbcDetail', function ($q) use ($keyword) {
                    $q->where('amount', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('cross_claim', function ($query, $keyword) {
                $query->whereHas('crossAbcDetail', function ($q) use ($keyword) {
                    $q->where('amount', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('claim', function ($query, $keyword) {
                $query->whereHas('ticketOptions', function ($q) use ($keyword) {
                    $q->where('a_qty', 'like', "%{$keyword}%")
                        ->orWhere('b_qty', 'like', "%{$keyword}%")
                        ->orWhere('c_qty', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns([
                'tq', 't_amt', 'claim', 'c_amt', 'p_and_l',
                'shop_keeper', 'cross_amt', 'cross_claim',
            ]);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Shopkeeper>
     */
    public function query(UserDraw $model, Request $request): QueryBuilder
    {

        return $model->newQuery()->where('draw_detail_id', $request->drawDetail->id);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('shopkeepers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0)
            ->selectStyleSingle()
            ->parameters(
                [
                    'searching' => true,
                    'language' => [
                        'searchPlaceholder' => 'Search By Name',
                    ],
                ]
            )
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
            Column::make('shop_keeper')->title('Shopkeeper'),
            Column::make('tq')->title('TQ'),
            // Column::make('t_amt')->title('T Amt'),
            Column::make('claim')->title('Claim'),
            Column::make('cross_amt')->title('Cross Amt.'),
            Column::make('cross_claim')->title('Cross Claim.'),

            // Column::make('c_amt')->title('C Amt.'),
            Column::make('p_and_l')->title('P&L'),

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Shopkeepers_'.date('YmdHis');
    }
}
