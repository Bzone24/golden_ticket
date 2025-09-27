<?php

namespace App\DataTables\Admin;

use App\Models\Draw;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DrawDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {

        return (new EloquentDataTable($query))
            ->addIndexColumn() // ✅ Add index column here

            // ->addColumn('action', function ($draw) {
            //     return view('admin.draw.draw-action', compact('draw'))->render();
            // })
            ->editColumn('end_time', function ($draw) {
                return $draw->formatEndTime();

            })
            ->editColumn('start_time', function ($draw) {
                return $draw->formatStartTime();
            })
            ->editColumn('total_collection', function ($draw) {
                return $draw->total_collection ?: 0;

            })
            ->editColumn('total_rewards', function ($draw) {
                return $draw->total_rewards ?: 0;

            })
            ->editColumn('created_at', function ($draw) {
                return Carbon::parse($draw->created_at)->format('Y-m-d');
            })
            ->editColumn('updated_at', function ($draw) {
                return Carbon::parse($draw->updated_at)->format('Y-m-d');
            })
            ->setRowId('id')
            ->rawColumns(['id', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Shopkeeper>
     */
    public function query(Draw $model): QueryBuilder
    {
        return $model->newQuery();
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
            // ->orderBy(0)
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
            Column::make('DT_RowIndex')
                ->title('#') // ✅ Table heading
                ->searchable(false)
                ->orderable(false),
            Column::make('start_time')->title('Start Time'),
            Column::make('end_time')->title('End Time'),
            Column::make('created_at'),
            Column::make('updated_at'),

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'draw_'.date('YmdHis');
    }
}
