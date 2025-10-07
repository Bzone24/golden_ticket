<?php

namespace App\DataTables;

use Illuminate\Support\Facades\Route;
use App\Models\CrossAbcDetail;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CrossBcDataTable extends DataTable
{
    // Allow controller to set this via ->with('draw_detail_id', $id')
    public $draw_detail_id = null;

    public function setDrawDetailId($id)
    {
        $this->draw_detail_id = $id;
    }

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('action', function ($abc_detail) {
                return '--';
            })
            ->editColumn('number', function ($abc_detail) {
                $number = $abc_detail->number;
                $bc = $abc_detail->drawDetail?->bc ?? null;

                if ($bc !== null && $number == $bc) {
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
     * @return QueryBuilder
     */
    public function query(CrossAbcDetail $model, Request $request): QueryBuilder
    {
        // Priority: property set via ->with() or setter, then request payload keys
        $drawDetailId = $this->draw_detail_id
            ?? $request->get('drawDetail')
            ?? $request->get('draw_detail_id');

        $game_id = $request->get('game_id');

        $builder = $model->newQuery()
            ->selectRaw('draw_detail_id, number, SUM(amount) as amount, MAX(updated_at) as updated_at')
            ->where('type', 'BC')
            ->when($game_id, function ($q) use ($game_id) {
                $q->whereHas('drawDetail', fn($sub) => $sub->where('game_id', $game_id));
            })
            // apply non-admin user filter like original
            ->when(request()->segment(1) !== 'admin' && auth()->user(), fn($q) =>
                $q->where('user_id', auth()->user()->id)
            )
            ->with('drawDetail')
            ->groupBy('draw_detail_id', 'number');

        // Apply draw filter only if a non-empty value exists to avoid "where null"
        if (!is_null($drawDetailId) && $drawDetailId !== '') {
            $builder->where('draw_detail_id', $drawDetailId);
        }

        return $builder;
    }

    /**
     * Optional method if you want to use the html builder.
     */
 public function html(): HtmlBuilder
{
    // Prefer property set via controller ($dataTable->with('draw_detail_id', $id))
    $drawDetailId = $this->draw_detail_id
        ?? request()->get('draw_detail_id')
        ?? request()->get('drawDetail');

    // determine admin vs frontend
    $isAdmin = request()->segment(1) === 'admin';

    // choose route name and fallback path per table:
    // for AB: adminName = 'admin.dashboard.cross.get.ab', frontName = 'dashboard.draw.cross.ab.list', fallback '/dashboard/cross-ab-list'
    // for AC: adminName = 'admin.dashboard.cross.get.ac', frontName = 'dashboard.draw.cross.ac.list', fallback '/dashboard/cross-ac-list'
    // for BC: adminName = 'admin.dashboard.cross.get.bc', frontName = 'dashboard.draw.cross.bc.list', fallback '/dashboard/cross-bc-list'

    // Replace the values below per file: (example here uses the AC names)
    $adminName = 'admin.dashboard.cross.get.bc';
    $frontName = 'dashboard.draw.cross.bc.list';
    $fallbackPath = url('/dashboard/cross-bc-list');

    // pick base URL using named routes if present, otherwise fallback path
    if ($isAdmin && Route::has($adminName)) {
        $base = route($adminName);
    } elseif (!$isAdmin && Route::has($frontName)) {
        $base = route($frontName);
    } else {
        $base = $fallbackPath;
    }

    $json_url = $drawDetailId ? ($base . '?draw_detail_id=' . $drawDetailId) : $base;

    return $this->builder()
        // setTableId must match table type (change id for AB/BC)
        ->setTableId('cross-bc-table')
        ->columns($this->getColumns())
        ->minifiedAjax($json_url)
        ->orderBy(0, 'desc')
        ->selectStyleSingle()
        ->parameters([
            'paging' => false, 'info' => false, 'scrollY' => '100%',
            'scrollCollapse' => true, 'scrollX' => false, 'autoWidth' => true,
            'searching' => true,
            'language' => ['searchPlaceholder' => 'Enter The Number'],
        ])
        ->buttons([
            Button::make('excel'), Button::make('csv'),
            Button::make('pdf'), Button::make('print'),
        ]);
}


    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columes = [
            Column::make('DT_RowIndex')
                ->title('#')
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
        return 'bc_cross-' . date('YmdHis');
    }
}
