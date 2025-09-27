<?php

namespace App\DataTables\Admin;

use App\Models\Options;
use App\Models\Shopkeeper;
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
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    public function query(Options $model, Request $request): QueryBuilder
    {
        $ticket_id = $request->ticket_id;
        $userId = auth()->user()->id;
        $drawDetailId = $request->draw_detail_id;
        if (request()->segment(1) === 'admin') {
            $ticket_id = $request->ticket->id;
            $userId = $request->user->id;
            $drawDetailId = $request->drawDetail->id;
        }

        return $model->newQuery()
            ->selectRaw('
            ticket_id,
            `option` as option_name,
            number,
            SUM(qty) as total_qty
        ')
            ->where('ticket_id', $ticket_id)
            ->whereJsonContains('draw_details_ids', $drawDetailId)
            ->where('user_id', $userId)
            ->groupBy('ticket_id', 'option_name', 'number');
    }

    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('option', function ($row) {
                return $row->option_name;
            })
            ->addColumn('number', function ($row) {
                return $row->number;
            })
            ->addColumn('qty', function ($row) {
                return $row->total_qty;
            })
            ->addColumn('amt', function ($row) {
                return $row->total_qty * 11;
            })
            ->rawColumns([
                'option', 'number', 'qty', 'amt',
            ]);
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
            ->orderBy(1)
            ->selectStyleSingle()
            ->parameters([
                'searching' => true,
                'language' => [
                    'searchPlaceholder' => 'Number(0-9)',
                ],
            ])
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
            Column::make('option')->title('Option')->orderable(true)->searchable(true),
            Column::make('number')->title('Number'),
            Column::make('qty')->title('Qty'),
            Column::make('amt')->title('Amt'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Ticket-details'.date('YmdHis');
    }
}
