<?php

namespace App\DataTables;

use App\Models\CrossAbcDetail;
use App\Models\Shopkeeper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CrossAbDataTable extends DataTable
{
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
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Shopkeeper>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {

        return (new EloquentDataTable($query))
            ->addIndexColumn() // ✅ Add index column here
            ->addColumn('action', function ($abc_detail) {
                return '--';
            })
            ->editColumn('number', function ($abc_detail) {
                $number = $abc_detail->number;
                $ab = $abc_detail->drawDetail?->ab; // why this empty

                if ($number == $ab) {
                    return "<span class='bg-danger text-white p-2'>$number</span>";
                }

                return $number;
            })

            ->setRowId('id')
            ->rawColumns(['action', 'number']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Shopkeeper>
     */
    public function query(CrossAbcDetail $model, Request $request): QueryBuilder
    {
        $draw_detail_id = $request->get('draw_detail_id');
        $game_id = $request->get('game_id'); // ✅ capture game_id
        
        return $model->newQuery()
            ->selectRaw('draw_detail_id, number, SUM(amount) as amount, MAX(updated_at) as updated_at')
            ->where('draw_detail_id', $draw_detail_id)
            ->where('type', 'AB')
            ->when($game_id, function ($q) use ($game_id) {
                // ✅ now game_id is being used
                $q->whereHas('drawDetail', function ($sub) use ($game_id) {
                    $sub->where('game_id', $game_id);
                });
            })
            ->when(true, function ($q) {
                return $q->where('user_id', $this->resolveUserId());
            })
            ->with('drawDetail')
            ->groupBy('draw_detail_id', 'number');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('cross-ab-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->parameters(
                [
                    'paging' => false,
                    'info' => false,
                    'scrollY' => '100%',       // ✅ let it auto adjust inside parent
                    'scrollCollapse' => true,
                    'scrollX' => false,        // ✅ disable horizontal scroll (not needed here)
                    'autoWidth' => true,
                    'searching' => true,
                    'language' => [
                        'searchPlaceholder' => 'Enter The Number',
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
        $columes = [
            Column::make('DT_RowIndex')
                ->title('#') // ✅ Table heading
                ->searchable(false)
                ->orderable(false),
            Column::make('updated_at')->hidden(),
            Column::make('number')->title('Number')->orderable(true)->searchable(true),
            Column::make('amount')->title('Amount')->orderable(true),
        ];

        return $columes;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ab_cross-' . date('YmdHis');
    }
}
