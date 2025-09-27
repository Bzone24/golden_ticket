<?php

namespace App\DataTables;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class TicketDetailsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Ticket>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        $query->forUser($request->user()->id)->where('draw_id', $request->draw_id);

        return (new EloquentDataTable($query))
            ->addColumn('draw_id', function ($ticket) {
                $draw_id = $ticket->draw_id;

                return <<<HTML
                <span>{$draw_id}</span>
                HTML;
            })
            ->addColumn('status', function ($ticket) {
                $status = $ticket->status;
                if ($status === 'COMPLETED') {
                    return <<<'HTML'
                    <div class="d-flex justify-content-center">
                        <div class="bg-success text-white p-0 w-50 text-center rounded h5 px-0">Completed</div>
                    </div>
                    HTML;
                }

                return <<<'HTML'
                    <div class="d-flex justify-content-center">
                        <div class="bg-warning text-white p-0 w-50 text-center rounded h5 px-0">Not Submit</div>
                    </div>
                    HTML;
            })
            ->filterColumn('full_ticket_no', function ($ticket, $keyword) {
                return $ticket->forTicketNumber($keyword);
            })
            ->addColumn('action', function ($ticket) {
                $add_ticket_url = route('ticket.add', $ticket->id);

                return <<<HTML
                <div class="d-flex justify-content-center">
                    <!-- <a href="#" class="btn btn-secondary"><i class="fa fa-eye"></i> View Details</a> -->
                    <a href="$add_ticket_url" class="btn btn-warning ms-3 text-white"><i class="fa fa-pencil"></i> Edit</a>
                </div>
                HTML;
            })
            ->setRowId('id')
            ->rawColumns(['action', 'draw_id', 'status']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Ticket>
     */
    public function query(Ticket $model): QueryBuilder
    {
        $game_id = request()->get('game_id');

        return $model->newQuery()
            ->when($game_id, fn ($q) => $q->where('game_id', $game_id));
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
        return [
            Column::make('id')->title('#ID'),
            Column::make('full_ticket_no')->title('Ticket No.'),
            Column::make('draw_id')->title('Draw No.'),
            Column::make('status')->addClass('text-center'),
            Column::make('action')->addClass('text-center'),
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
