<?php

namespace App\DataTables\Admin;

use App\Models\Shopkeeper;
use App\Models\TicketOption;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class NumberTicketListDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('ticket_number', function ($ticket_option) {
                return $ticket_option->ticket_number;
            })
            ->filterColumn('ticket_number', function ($query, $keyword) {
                $query->whereRaw('LOWER(tickets.ticket_number) like ?', ['%'.strtolower($keyword).'%']);
            })

            // Use the server-side computed sums (sum_a, sum_b, sum_c, sum_cross)
            ->addColumn('total_collection_of_a', fn ($row) => $row->totalCollection($row->sum_a ?? 0))
            ->addColumn('total_collection_of_b', fn ($row) => $row->totalCollection($row->sum_b ?? 0))
            ->addColumn('total_collection_of_c', fn ($row) => $row->totalCollection($row->sum_c ?? 0))
            ->addColumn('total_distribution_of_a', fn ($row) => $row->totalDistributions($row->sum_a ?? 0))
            ->addColumn('total_distribution_of_b', fn ($row) => $row->totalDistributions($row->sum_b ?? 0))
            ->addColumn('total_distribution_of_c', fn ($row) => $row->totalDistributions($row->sum_c ?? 0))
            ->addColumn('numbers', fn ($row) => $row->number)
            ->addColumn('shopkeeper', function ($row) {
                // keep existing behavior; assume TicketOption has a user relation or it's available
                return "<a href='#'>{$row->user->name}</a>";
            })
            ->setRowId('ticket_number')
            ->editColumn('numbers', function ($row) {
                return "<a href='$row->number'>$row->number</a>";
            })
            ->addColumn('action', function ($ticket_option) {
                $add_ticket_url = route('admin.draw.ticke.details.list', [
                    'draw_id'   => $ticket_option->draw_id,
                    'number'    => $ticket_option->number,
                    'ticket_id' => $ticket_option->ticket_id,
                ]);

                return <<<HTML
                <div class="d-flex justify-content-center">
                    <a href="$add_ticket_url" class="btn btn-primary btn-sm ms-3 text-white">More Details <i class="fa fa-arrow-circle-right"></i></a>
                </div>
                HTML;
            })
            ->rawColumns([
                'ticket_number',
                'action',
                'numbers',
                'total_collection_of_a',
                'total_collection_of_b',
                'total_collection_of_c',
                'total_distribution_of_a',
                'total_distribution_of_b',
                'total_distribution_of_c',
                'shopkeeper',
            ]);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Shopkeeper>
     */
    public function query(TicketOption $model, Request $request): QueryBuilder
    {
        // Subselects compute per-ticket sums while excluding voided rows.
        // We retain ticket_options.* as the primary row to avoid breaking callers expecting option rows.
        $sumA = DB::raw("(select COALESCE(SUM(a_qty), 0) from ticket_options to2 where to2.ticket_id = ticket_options.ticket_id and to2.voided = 0) as sum_a");
        $sumB = DB::raw("(select COALESCE(SUM(b_qty), 0) from ticket_options to2 where to2.ticket_id = ticket_options.ticket_id and to2.voided = 0) as sum_b");
        $sumC = DB::raw("(select COALESCE(SUM(c_qty), 0) from ticket_options to2 where to2.ticket_id = ticket_options.ticket_id and to2.voided = 0) as sum_c");

        // cross sum from cross_abc_details per ticket
        $sumCross = DB::raw("(select COALESCE(SUM(amount), 0) from cross_abc_details c where c.ticket_id = ticket_options.ticket_id and c.voided = 0) as sum_cross");

        return $model->newQuery()
            ->select(array_merge([
                'ticket_options.*',
                'tickets.ticket_number as ticket_number',
            ], [$sumA, $sumB, $sumC, $sumCross]))
            ->join('tickets', 'ticket_options.ticket_id', '=', 'tickets.id')
            // filter for the requested draw and number
            ->where('ticket_options.draw_id', $request->draw_id)
            ->where('ticket_options.number', $request->number)
            // exclude voided ticket_option rows from the operational table
            ->where('ticket_options.voided', 0)
            // exclude soft-deleted tickets for operational totals (audit views should use withTrashed explicitly)
            ->whereNull('tickets.deleted_at');
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
                    'language'  => [
                        'searchPlaceholder' => 'Ticket Number',
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
            Column::make('ticket_number')->title('Ticket Number')->orderable(true)->searchable(true),
            Column::make('total_collection_of_a')->title('TTL. Coll. Of A'),
            Column::make('total_distribution_of_a')->title('TTL.  Dist. Of A'),
            Column::make('total_collection_of_b')->title('TTL. Coll. Of B'),
            Column::make('total_distribution_of_b')->title('TTL.  Dist. Of B'),
            Column::make('total_collection_of_c')->title('TTL. Coll. Of C'),
            Column::make('total_distribution_of_c')->title('TTL.  Dist. Of C'),
            Column::make('shopkeeper')->title('Shopkeeper'),
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
