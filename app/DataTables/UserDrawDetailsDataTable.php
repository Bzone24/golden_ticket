<?php

namespace App\DataTables;

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

class UserDrawDetailsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<TicketOption>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('total_collection_of_a', fn ($row) => $row->totalCollection($row->a_qty))
            ->addColumn('total_collection_of_b', fn ($row) => $row->totalCollection($row->b_qty))
            ->addColumn('total_collection_of_c', fn ($row) => $row->totalCollection($row->c_qty))
            ->addColumn('total_distribution_of_a', fn ($row) => $row->totalDistributions($row->a_qty))
            ->addColumn('total_distribution_of_b', fn ($row) => $row->totalDistributions($row->b_qty))
            ->addColumn('total_distribution_of_c', fn ($row) => $row->totalDistributions($row->c_qty))
            ->addColumn('numbers', fn ($row) => $row->number)
            ->addColumn('action', fn ($row) => '<a href="#" class="btn btn-primary">Details</a>')
            ->setRowId('number')
            ->editColumn('number', function ($row) {
                $ticket_number_url = route('dashboard.draw.ticket.number.list', ['draw_id' => $row->draw_id, 'number' => $row->number]);

                return "<a href='$ticket_number_url'>$row->number</a>";
            })
            ->rawColumns([
                'action',
                'number',
                'total_collection_of_a',
                'total_collection_of_b',
                'total_collection_of_c',
                'total_distribution_of_a',
                'total_distribution_of_b',
                'total_distribution_of_c',
            ]);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<TicketOption>
     */
    public function query(TicketOption $model): QueryBuilder
    {
        // use the global request helper — $request wasn't defined in this method
        $game_id = request()->get('game_id');

        return $model->newQuery()
            ->when($game_id, function ($q) use ($game_id) {
                $q->whereHas('ticket', fn ($t) => $t->where('game_id', $game_id));
            })
            ->select([
                'number', 'draw_id',
                DB::raw('SUM(a_qty) as a_qty'),
                DB::raw('SUM(b_qty) as b_qty'),
                DB::raw('SUM(c_qty) as c_qty'),
            ])
            ->forUser(auth()->user()->id)
            ->groupBy('number', 'draw_id');
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
            ->parameters(
                [
                    'searching' => true,
                    'language' => [
                        'searchPlaceholder' => 'Number(0-9)',
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
            Column::make('number')->title('Number(0-9)')->orderable(true),
            Column::make('total_collection_of_a')->title('TTL. Coll. Of A'),
            Column::make('total_distribution_of_a')->title('TTL.  Dist. Of A'),
            Column::make('total_collection_of_b')->title('TTL. Coll. Of B'),
            Column::make('total_distribution_of_b')->title('TTL.  Dist. Of B'),
            Column::make('total_collection_of_c')->title('TTL. Coll. Of C'),
            Column::make('total_distribution_of_c')->title('TTL.  Dist. Of C'),
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
