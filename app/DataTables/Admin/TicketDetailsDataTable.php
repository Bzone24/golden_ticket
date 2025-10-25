<?php

namespace App\DataTables\Admin;

use App\Models\Options;
use App\Models\TicketOption;
use App\Models\Shopkeeper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\QueryDataTable;
class TicketDetailsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
    
     */
   // add at top if not present

public function query(Options $model, Request $request)
{
    // collect ids (same robust logic)
    $ticketId = $request->input('ticket_id') ?: null;
    $userId   = $request->input('user_id') ?: null;
    $drawDetailId = $request->input('draw_detail_id') ?: null;

    if (!$ticketId && $request->route('ticket')) {
        $routeTicket = $request->route('ticket');
        $ticketId = is_object($routeTicket) ? ($routeTicket->id ?? null) : $routeTicket;
    }
    if (!$userId && $request->route('user')) {
        $routeUser = $request->route('user');
        $userId = is_object($routeUser) ? ($routeUser->id ?? null) : $routeUser;
    }
    if (!$drawDetailId && $request->route('drawDetail')) {
        $routeDD = $request->route('drawDetail');
        $drawDetailId = is_object($routeDD) ? ($routeDD->id ?? null) : $routeDD;
    }

    // fallback to URL segments (/draw-ticket-details-list/{draw}/{ticket}/{user})
    if (!$drawDetailId) $drawDetailId = (int) ($request->segment(3) ?? 0);
    if (!$ticketId) $ticketId = (int) ($request->segment(4) ?? 0);
    if (!$userId) $userId = (int) ($request->segment(5) ?? auth()->id());

    // cast & validate
    $t = $ticketId !== null ? (int)$ticketId : 0;
    $u = $userId !== null ? (int)$userId : (int)auth()->id();
    $d = $drawDetailId !== null ? (int)$drawDetailId : 0;

    if (empty($t) || empty($d)) {
        return \DB::table(\DB::raw('(select 1 where 0=1) as empty_table'));
    }

    // original UNION ALL unpivot (per-number totals per option)
    $union = "
        SELECT 'A' AS option_name, number, SUM(COALESCE(a_qty,0)) AS total_qty
        FROM ticket_options
        WHERE ticket_id = {$t}
          AND draw_detail_id = {$d}
          AND user_id = {$u}
          AND (voided IS NULL OR voided = 0)
        GROUP BY number
        HAVING SUM(COALESCE(a_qty,0)) > 0

        UNION ALL

        SELECT 'B' AS option_name, number, SUM(COALESCE(b_qty,0)) AS total_qty
        FROM ticket_options
        WHERE ticket_id = {$t}
          AND draw_detail_id = {$d}
          AND user_id = {$u}
          AND (voided IS NULL OR voided = 0)
        GROUP BY number
        HAVING SUM(COALESCE(b_qty,0)) > 0

        UNION ALL

        SELECT 'C' AS option_name, number, SUM(COALESCE(c_qty,0)) AS total_qty
        FROM ticket_options
        WHERE ticket_id = {$t}
          AND draw_detail_id = {$d}
          AND user_id = {$u}
          AND (voided IS NULL OR voided = 0)
        GROUP BY number
        HAVING SUM(COALESCE(c_qty,0)) > 0
    ";

    // Wrap union as subquery and group by option to combine numbers
    $wrapped = "(
        SELECT option_name, number, total_qty
        FROM ({$union}) AS ticket_detail_unpivot
    ) as unp";

    // Build final grouped query using DB query builder
    $qb = \DB::table(\DB::raw("({$union}) as ticket_detail_unpivot"))
        ->select([
            'option_name',
            \DB::raw('GROUP_CONCAT(DISTINCT number ORDER BY number ASC SEPARATOR ", ") as numbers'),
            \DB::raw('SUM(total_qty) as total_qty'),
            // compute amount — change multiplier if not 11
            \DB::raw('SUM(total_qty) * 11 as total_amt')
        ])
        ->groupBy('option_name')
        ->orderBy('option_name');

    return $qb;
}


public function dataTable($query, Request $request)
{
    // If query is Query\Builder, use QueryDataTable; else EloquentDataTable
    if ($query instanceof \Illuminate\Database\Query\Builder) {
        $dt = new QueryDataTable($query);
    } else {
        $dt = new EloquentDataTable($query);
    }

    return $dt
        ->addColumn('option', function ($row) {
            return $row->option_name;
        })
        ->addColumn('numbers', function ($row) {
            return $row->numbers;
        })
        ->addColumn('qty', function ($row) {
            return $row->total_qty;
        })
        ->addColumn('amt', function ($row) {
            return $row->total_amt;
        })
        ->rawColumns(['option','numbers','qty','amt']);
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
        Column::make('numbers')->title('Numbers'),
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
