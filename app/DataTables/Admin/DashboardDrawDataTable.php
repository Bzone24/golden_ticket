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
use Illuminate\Support\Facades\Log;


class DashboardDrawDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        $dt = new EloquentDataTable($query);

        Log::info('DashboardDrawDataTable::dataTable() executed — file: ' . __FILE__);


        return $dt
            ->addColumn('tq', function ($row) {
                $tq = (int) ($row->computed_tq ?? 0);
                $url = route('admin.dashboard.total_qty_details', ['draw_detail_id' => $row->id]);
                return "<a href=\"{$url}\" class='text-primary h6'>{$tq}</a>";
            })
            ->addColumn('cross_amt', function ($row) {
                $cross = (float) ($row->computed_cross ?? 0);
                $url = route('admin.dashboard.cross-abc', ['draw_detail_id' => $row->id]);
                return "<a href=\"{$url}\" class='text-primary h6'>{$cross}</a>";
            })
            ->rawColumns(['end_time', 'tq', 'cross_amt', 'p_and_l', 'action', 'game_name'])
            ->setRowId('id');
    }

    public function query(DrawDetail $model): \Illuminate\Database\Eloquent\Builder
    {
        Log::info('DashboardDrawDataTable::query() executed — file: ' . __FILE__);

        $gameValueSql = "COALESCE(games.short_code, games.code, games.name, CONCAT('N', draws.game_id), '—') as raw_game_value";
        $gameBadgeSql = "CONCAT(
            '<span class=\"badge badge-sm\" style=\"background:#0d6efd;color:#fff;padding:5px 8px;border-radius:6px;\">',
            COALESCE(games.short_code, games.code, games.name, CONCAT('N', draws.game_id), '—'),
            '</span>'
        ) as game_name";

        $computedTq = DB::raw("(
            SELECT COALESCE(SUM(topt.a_qty + topt.b_qty + topt.c_qty), 0)
            FROM ticket_options AS topt
            INNER JOIN tickets AS t ON topt.ticket_id = t.id
            WHERE topt.draw_detail_id = draw_details.id
              AND topt.voided = 0
              AND t.deleted_at IS NULL
        ) as computed_tq");

        $computedCross = DB::raw("(
            SELECT COALESCE(SUM(c.amount), 0)
            FROM cross_abc_details AS c
            INNER JOIN tickets AS t ON c.ticket_id = t.id
            WHERE c.draw_detail_id = draw_details.id
              AND c.voided = 0
              AND t.deleted_at IS NULL
        ) as computed_cross");

        return $model->newQuery()
            ->leftJoin('draws', 'draws.id', '=', 'draw_details.draw_id')
            ->leftJoin('games', 'games.id', '=', 'draws.game_id')
            ->select(
                'draw_details.*',
                DB::raw($gameValueSql),
                DB::raw($gameBadgeSql),
                $computedTq,
                $computedCross
            );
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('draw-details-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(1)
            ->selectStyleSingle()
            ->addTableClass('table custom-header')
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('game_name')->title('Game')->orderable(false)->searchable(true)->width(80),
            Column::make('end_time')->title('Time')->width(100),
            Column::computed('tq')->title('TQ'),
            Column::make('claim')->title('Claim'),
            Column::computed('cross_amt')->title('Cross Amt.'),
            Column::make('cross_claim')->title('Cross Claim'),
            Column::computed('p_and_l')->title('P&L'),
            Column::make('created_at')->title('Created At'),
            Column::computed('action')->title('Action')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'draw_'.date('YmdHis');
    }
}
