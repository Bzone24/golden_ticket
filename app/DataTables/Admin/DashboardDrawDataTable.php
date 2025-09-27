<?php

namespace App\DataTables\Admin;

use Illuminate\Support\Facades\DB;
use App\Models\DrawDetail;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DashboardDrawDataTable extends DataTable
{
    /**
     * Build the EloquentDataTable from the provided query (which is draw_details joined to draws/games).
     */
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        $dt = new EloquentDataTable($query);

        return $dt
            // The following columns are returned as HTML by the query/select (end_time, tq, cross_amt, p_and_l, action, game_name)
            ->rawColumns(['end_time', 'tq', 'cross_amt', 'p_and_l', 'action', 'game_name'])
            ->setRowId('id');
    }

    /**
     * Build the query: draw_details LEFT JOIN draws -> games and select a game_name badge.
     */
    public function query(DrawDetail $model): \Illuminate\Database\Eloquent\Builder
    {
        // Raw game value (plain) and rendered badge (HTML)
        $gameValueSql = "COALESCE(games.short_code, games.code, games.name, CONCAT('N', draws.game_id), '—') as raw_game_value";

        $gameBadgeSql = "CONCAT(
            '<span class=\"badge badge-sm\" style=\"background:#0d6efd;color:#fff;padding:5px 8px;border-radius:6px;\">',
            COALESCE(games.short_code, games.code, games.name, CONCAT('N', draws.game_id), '—'),
            '</span>'
        ) as game_name";

        return $model->newQuery()
            ->leftJoin('draws', 'draws.id', '=', 'draw_details.draw_id')
            ->leftJoin('games', 'games.id', '=', 'draws.game_id')
            ->select('draw_details.*', DB::raw($gameValueSql), DB::raw($gameBadgeSql));
    }

    /**
     * HTML builder (columns ordering).
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('draw-details-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1) // now Time is index 1 (after Game)
            ->selectStyleSingle()
            ->addTableClass('table custom-header')
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
            ]);
    }

    /**
     * Columns definition: Game as first column.
     */
    public function getColumns(): array
    {
        return [
            Column::make('game_name')->title('Game')->orderable(false)->searchable(true)->width(80),
            Column::make('end_time')->title('Time')->width(100),
            Column::make('tq')->title('TQ'),
            Column::make('claim')->title('Claim'),
            Column::make('cross_amt')->title('Cross Amt.'),
            Column::make('cross_claim')->title('Cross Claim'),
            Column::make('p_and_l')->title('P&L'),
            Column::make('created_at')->title('Created At'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'draw_'.date('YmdHis');
    }
}
