<?php

namespace App\DataTables\Admin;

use App\Models\Shopkeeper;
use App\Models\TicketOption;
use App\Traits\CalculatePL;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ShopKeeperDrawDetailsDataTable extends DataTable
{
    use CalculatePL;

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    protected function isAdminSeg()
    {
        return request()->segment(1) === 'admin';
    }

    protected function getCrossAmt($ticketOption)
    {
        $cross_amt = 0;
        $user_id = $this->isAdminSeg() ? request()->user?->id : auth()->user()->id;
        $cross_amt = $ticketOption->drawDetail->crossAbcDetail()->where('ticket_id', $ticketOption->ticket_id)
            ->where('user_id', $user_id)
            ->sum('amount');

        return $cross_amt;
    }

    protected function getClaim($ticketOption)
    {
        return $ticketOption->claim_a_qty + $ticketOption->claim_b_qty + $ticketOption->claim_c_qty;

    }

    protected function getCrossClaim($ticketOption)
    {
        $ab_claim = $ticketOption->drawDetail->ab;
        $ac_claim = $ticketOption->drawDetail->ac;
        $bc_claim = $ticketOption->drawDetail->bc;
        $user_id = request()->segment(1) !== 'admin' ? auth()->id() : request()->user?->id;

        $ab_claim_amt = $ticketOption->drawDetail->crossAbcDetail()->where('user_id', $user_id)
            ->where('ticket_id', $ticketOption->ticket_id)
            ->where('number', $ab_claim)
            ->where('type', 'AB')->sum('amount');

        $ac_claim_amt = $ticketOption->drawDetail->crossAbcDetail()->where('user_id', $user_id)
            ->where('ticket_id', $ticketOption->ticket_id)
            ->where('number', $ac_claim)
            ->where('type', 'AC')->sum('amount');
        $bc_claim_amt = $ticketOption->drawDetail->crossAbcDetail()->where('user_id', $user_id)
            ->where('ticket_id', $ticketOption->ticket_id)
            ->where('number', $bc_claim)
            ->where('type', 'BC')->sum('amount');

        return $ab_claim_amt + $ac_claim_amt + $bc_claim_amt;

    }

    public function query(TicketOption $model, Request $request): QueryBuilder
    {
        $user_id = auth()->user()->id;
        $draw_id = $request->draw_detail_id;
        if (request()->segment(1) === 'admin') {
            $user_id = $request->user?->id;
            $draw_id = $request->drawDetail->id;
        }
        if (request()->segment(1) === 'admin' && request()->get('claim') === 1) {
            $user_id = null;
        }

        return $model->newQuery()
            ->selectRaw('
            draw_detail_id,user_id,
            ticket_id,
            SUM(a_qty) as total_a_qty,
            SUM(b_qty) as total_b_qty,
            SUM(c_qty) as total_c_qty,
            SUM(CASE WHEN number = draw_details.claim_a THEN a_qty ELSE 0 END) as claim_a_qty,
            SUM(CASE WHEN number = draw_details.claim_b THEN b_qty ELSE 0 END) as claim_b_qty,
            SUM(CASE WHEN number = draw_details.claim_c THEN c_qty ELSE 0 END) as claim_c_qty
        ')
            ->join('draw_details', 'ticket_options.draw_detail_id', '=', 'draw_details.id')
            ->where('draw_detail_id', $draw_id)
            ->when($user_id, fn ($q) => $q->where('user_id', $user_id))
            ->when($request->get('claim') == 1, function ($q) {
                return $q->whereHas('drawDetail', function ($drawDetail) {
                    return $drawDetail->where('claim', '!=', 0);
                });
            })
            // $row->total_a_qty + $row->total_b_qty + $row->total_c_qty
            // ->groupBy('ticket_id', 'draw_details.claim_a', 'draw_details.claim_b', 'draw_details.claim_c')
            ->groupBy(
                'ticket_options.draw_detail_id',
                'ticket_options.user_id',
                'ticket_options.ticket_id',
                'draw_details.claim_a',
                'draw_details.claim_b',
                'draw_details.claim_c'
            )

            ->with('ticket', 'drawDetail'); // eager load ticket for ticket_number
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('ticket_no', function ($TicketOption) {
                $ticket_number = $TicketOption->ticket->ticket_number;
                if (request()->segment(1) === 'admin') {
                    $url = route('admin.draw.ticke.details.list', ['drawDetail' => $TicketOption->draw_detail_id, 'ticket' => $TicketOption->ticket_id, 'user' => $TicketOption->user_id]);

                    return "<a href='$url' class='text-primary'>{$ticket_number}</a>";
                }

                return $ticket_number;

            })
            ->addColumn('tq', function ($row) {
                return $row->total_a_qty + $row->total_b_qty + $row->total_c_qty;
            })
            ->addColumn('t_amt', function ($row) {
                $total_qty = $row->total_a_qty + $row->total_b_qty + $row->total_c_qty;

                return $total_qty * 11;
            })
            ->addColumn('claim', function ($row) {
                return $this->getClaim($row);
            })
            ->addColumn('c_amt', function ($row) {
                $total_claim = $row->claim_a_qty + $row->claim_b_qty + $row->claim_c_qty;

                return $total_claim * 100;
            })
            ->addColumn('cross_claim', function ($ticketOption) {
                return $this->getCrossClaim($ticketOption);

            })
            ->addColumn('cross_amt', function ($ticketOption) {
                return $this->getCrossAmt($ticketOption);

            })
            ->addColumn('c_amt', function ($row) {
                $total_claim = $row->claim_a_qty + $row->claim_b_qty + $row->claim_c_qty;

                return $total_claim * 100;
            })
            ->addColumn('p_and_l', function ($row) {
                $total_amount = ($row->total_a_qty + $row->total_b_qty + $row->total_c_qty) * 11;
                $tq = $total_amount;
                $p_and_l = $this->calculateProfitAndLoss($tq, $this->getCrossAmt($row), $this->getClaim($row), $this->getCrossClaim($row));
                $bgClass = $p_and_l < 0 ? 'bg-danger text-white' : 'bg-success text-white';
                if ($p_and_l == 0) {
                    $bgClass = 'text-dark';
                }

                return <<<HTML
                <div class="{$bgClass} text-center">{$p_and_l}</div>
            HTML;
            })
            ->addColumn('action', function ($ticketOption) {
                // if ($ticketOption->ticket->status !== 'COMPLETED') {
                //     $url = route('ticket.add',['ticket_id'=>$ticketOption->ticket_id]);

                //     return "<a href='$url' class='btn btn-sm btn-warning text-white'>Edit</a>";
                // } else {
                //     return '-';
                // }
                $url = route('ticket.add', ['ticket_id' => $ticketOption->ticket_id]);

                return "<a href='$url' class='btn btn-sm btn-warning text-white'>Edit</a>";

            })
            ->rawColumns(['ticket_no', 'tq', 't_amt', 'claim', 'c_amt', 'p_and_l',
                'action', 'cross_claim', 'cross_amt']);
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
        $columes = [
            Column::make('ticket_no')->title('Tno.'),
            Column::make('tq')->title('TQ'),
            // Column::make('t_amt')->title('T amt.'),
            Column::make('claim')->title('Claim'),
            Column::make('cross_amt')->title('Cross Amt'),
            Column::make('cross_claim')->title('Cross Claim'),

            // Column::make('c_amt')->title('C Amt'),
            Column::make('p_and_l')->title('P&L'),

        ];
        // if (request()->segment(1) !== 'admin') {
        //     $columes[] = Column::make('action');

        // }

        return $columes;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Shopkeepers_'.date('YmdHis');
    }
}
