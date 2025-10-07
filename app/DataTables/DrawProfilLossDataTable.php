<?php

namespace App\DataTables;

use App\Models\DrawDetail;
use App\Models\User;
use App\Traits\CalculatePL;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class DrawProfilLossDataTable extends DataTable
{
    use CalculatePL;

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

    protected function isAdminSeg(): bool
    {
        return request()->segment(1) === 'admin';
    }

    protected function getCrossClaim($draw_detail)
    {
        $ab_claim = $draw_detail->ab;
        $ac_claim = $draw_detail->ac;
        $bc_claim = $draw_detail->bc;
        $user = auth()->user();

        if ($user->hasRole(['user', 'shopkeeper', 'admin'])) {
            if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')->from('users')->where('created_by', $user->id);
                })->pluck('id');

                $totals = $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                        SUM(CASE WHEN number = ? AND type = 'AB' THEN amount ELSE 0 END) as ab_total,
                        SUM(CASE WHEN number = ? AND type = 'AC' THEN amount ELSE 0 END) as ac_total,
                        SUM(CASE WHEN number = ? AND type = 'BC' THEN amount ELSE 0 END) as bc_total
                    ", [$ab_claim, $ac_claim, $bc_claim])
                    ->first();

                $ab_claim = $totals->ab_total ?? 0;
                $ac_claim = $totals->ac_total ?? 0;
                $bc_claim = $totals->bc_total ?? 0;
            } elseif ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');

                $totals = $draw_detail->crossAbcDetail()
                    ->whereIn('user_id', $userIds)
                    ->selectRaw("
                        SUM(CASE WHEN number = ? AND type = 'AB' THEN amount ELSE 0 END) as ab_total,
                        SUM(CASE WHEN number = ? AND type = 'AC' THEN amount ELSE 0 END) as ac_total,
                        SUM(CASE WHEN number = ? AND type = 'BC' THEN amount ELSE 0 END) as bc_total
                    ", [$ab_claim, $ac_claim, $bc_claim])
                    ->first();

                $ab_claim = $totals->ab_total ?? 0;
                $ac_claim = $totals->ac_total ?? 0;
                $bc_claim = $totals->bc_total ?? 0;
            } else {
                $ab_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->where('number', $ab_claim)->where('type', 'AB')->sum('amount');
                $ac_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->where('number', $ac_claim)->where('type', 'AC')->sum('amount');
                $bc_claim = $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->where('number', $bc_claim)->where('type', 'BC')->sum('amount');
            }

            return $ab_claim + $ac_claim + $bc_claim;
        }

        return $draw_detail->claim_ab + $draw_detail->claim_ac + $draw_detail->claim_bc;
    }

    protected function getCrossAmt($draw_detail)
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            $userIds = User::whereIn('created_by', function ($q) use ($user) {
                $q->select('id')->from('users')->where('created_by', $user->id);
            })->pluck('id');

            if ($this->userId) {
                return $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->sum('amount');
            }

            return $draw_detail->crossAbcDetail()->whereIn('user_id', $userIds)->sum('amount');
        }

        if ($user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id');
            return $draw_detail->crossAbcDetail()->whereIn('user_id', $userIds)->sum('amount');
        }

        if ($user->hasRole('user')) {
            return $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->sum('amount');
        }

        if ($user->hasRole('master') && $this->userId) {
            return $draw_detail->crossAbcDetail()->where('user_id', $this->resolveUserId())->sum('amount');
        }

        return $draw_detail->cross_amt;
    }

    protected function getClaim($draw_detail)
    {
        $user = auth()->user();

        if ($user->hasRole(['master'])) {
            return $draw_detail->claim;
        }

        $a_claim = $draw_detail->claim_a;
        $b_claim = $draw_detail->claim_b;
        $c_claim = $draw_detail->claim_c;

        if ($user->hasRole('admin')) {
            $userIds = User::whereIn('created_by', function ($q) use ($user) {
                $q->select('id')->from('users')->where('created_by', $user->id);
            })->pluck('id');

            $totals = $draw_detail->ticketOptions()
                ->whereIn('user_id', $userIds)
                ->selectRaw("
                    SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                    SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                    SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                ", [$a_claim, $b_claim, $c_claim])
                ->first();

            $a_qty = $totals->a_total ?? 0;
            $b_qty = $totals->b_total ?? 0;
            $c_qty = $totals->c_total ?? 0;
        } elseif ($user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id');

            $totals = $draw_detail->ticketOptions()
                ->whereIn('user_id', $userIds)
                ->selectRaw("
                    SUM(CASE WHEN number = ? THEN a_qty ELSE 0 END) as a_total,
                    SUM(CASE WHEN number = ? THEN b_qty ELSE 0 END) as b_total,
                    SUM(CASE WHEN number = ? THEN c_qty ELSE 0 END) as c_total
                ", [$a_claim, $b_claim, $c_claim])
                ->first();

            $a_qty = $totals->a_total ?? 0;
            $b_qty = $totals->b_total ?? 0;
            $c_qty = $totals->c_total ?? 0;
        } else {
            $a_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->where('number', $a_claim)->sum('a_qty');
            $b_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->where('number', $b_claim)->sum('b_qty');
            $c_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->where('number', $c_claim)->sum('c_qty');
        }

        return $a_qty + $b_qty + $c_qty;
    }

    protected function getResult($draw_detail)
    {
        $a_claim = $draw_detail->claim_a;
        $b_claim = $draw_detail->claim_b;
        $c_claim = $draw_detail->claim_c;

        return "$a_claim  $b_claim  $c_claim";
    }

    protected function getTq($draw_detail)
    {
        $user = auth()->user();

        if ($user->hasRole(['user', 'shopkeeper', 'admin']) || $this->userId) {
            if ($user->hasRole('admin')) {
                $userIds = User::whereIn('created_by', function ($q) use ($user) {
                    $q->select('id')->from('users')->where('created_by', $user->id);
                })->pluck('id');

                $totals = $draw_detail->ticketOptions()->whereIn('user_id', $userIds)->selectRaw('SUM(a_qty) as a_total, SUM(b_qty) as b_total, SUM(c_qty) as c_total')->first();
                $a_qty = $totals->a_total ?? 0;
                $b_qty = $totals->b_total ?? 0;
                $c_qty = $totals->c_total ?? 0;
            } elseif ($user->hasRole('shopkeeper')) {
                $userIds = User::where('created_by', $user->id)->pluck('id');

                $totals = $draw_detail->ticketOptions()->whereIn('user_id', $userIds)->selectRaw('SUM(a_qty) as a_total, SUM(b_qty) as b_total, SUM(c_qty) as c_total')->first();
                $a_qty = $totals->a_total ?? 0;
                $b_qty = $totals->b_total ?? 0;
                $c_qty = $totals->c_total ?? 0;
            } else {
                $a_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('a_qty');
                $b_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('b_qty');
                $c_qty = $draw_detail->ticketOptions()->where('user_id', $this->resolveUserId())->sum('c_qty');
            }

            return $a_qty + $b_qty + $c_qty;
        }

        return $draw_detail->tq;
    }

    public function getGameName($draw_detail)
    {
        return $draw_detail->load('draw.game')->draw->game->name;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dt = (new EloquentDataTable($query))
            ->editColumn('game_id', function ($draw_detail) {
                return $this->getGameName($draw_detail);
            })
            ->editColumn('end_time', function ($draw_detail) {
                $end_time = Carbon::parse($draw_detail->end_time)->format('h:i a');

                $url = $this->isAdminSeg()
                    ? route('admin.draw.detail.list', ['drawDetail' => $draw_detail->id, 'user_id' => $this->userId])
                    : route('dashboard.draw.details.list', ['drawDetail' => $draw_detail->id]);

                return "<a href='$url' class='text-primary h6'>$end_time</a>";
            })
            ->filterColumn('end_time', function ($query, $keyword) {
                $keyword = strtolower($keyword);
                $query->whereRaw("TIME_FORMAT(end_time, '%h:%i %p') LIKE ?", ["%{$keyword}%"])
                    ->orWhereRaw("TIME_FORMAT(end_time, '%l %p') LIKE ?", ["%{$keyword}%"])
                    ->orWhereRaw("TIME_FORMAT(end_time, '%H:%i') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('tq', function ($query, $keyword) {
                $query->whereRaw('CAST(computed_tq AS CHAR) LIKE ?', ["%{$keyword}%"]);
            })
            ->filterColumn('cross_amt', function ($query, $keyword) {
                $query->whereRaw('CAST(computed_cross AS CHAR) LIKE ?', ["%{$keyword}%"]);
            })
            ->filterColumn('p_and_l', function ($query, $keyword) {
                $query->whereRaw('
                    (
                        (COALESCE(total_qty,0) * 11) 
                        - (COALESCE(claim,0) * 100) 
                        + COALESCE(total_cross_amt,0) 
                        - ((COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)) * 100)
                    ) LIKE ?
                ', ["%{$keyword}%"]);
            })
            ->filterColumn('cross_claim', function ($query, $keyword) {
                $query->whereRaw('((claim_ab + claim_ac + claim_bc) * 100) LIKE ?', ["%{$keyword}%"]);
            })
           ->editColumn('tq', function ($draw_detail) {
    if (!($this->isAdminSeg() || ($draw_detail->has_user_data ?? 0))) {
        return '<span class="text-muted">-</span>';
    }

    $computed = (int) ($draw_detail->computed_tq ?? 0);
    $display = $computed > 0 ? $computed : 0; // never fall back to stored total
    $active = $computed > 0 ? 1 : 0;

    $url = $this->isAdminSeg()
        ? route('admin.dashboard.total.qty.details.list', ['drawDetail' => $draw_detail->id, 'user_id' => $this->userId])
        : route('dashboard.draw.total.qty.list.details', ['drawDetail' => $draw_detail->id]);

    $class = $active ? 'text-primary h6' : 'text-muted h6';
    $title = $active ? 'Active total' : 'No entries';

    return "<a href=\"{$url}\" class=\"{$class}\" title=\"{$title}\">{$display}</a>";
})

           ->editColumn('cross_amt', function ($draw_detail) {
    if (!($this->isAdminSeg() || ($draw_detail->has_user_data ?? 0))) {
        return '<span class="text-muted">-</span>';
    }

    $computed = (float) ($draw_detail->computed_cross ?? 0);
    $display = $computed > 0 ? $computed : 0;

    $url = $this->isAdminSeg()
        ? route('admin.dashboard.cross.abc', ['drawDetail' => $draw_detail->id, 'user_id' => $this->userId])
        : route('dashboard.draw.cross.abc.details.list', ['drawDetail' => $draw_detail->id]);

    $class = $display > 0 ? 'text-primary h6' : 'text-muted h6';
    $title = $display > 0 ? 'Active cross total' : 'No entries';

    return "<a href=\"{$url}\" class=\"{$class}\" title=\"{$title}\">{$display}</a>";
})

           ->editColumn('cross_claim', function ($draw_detail) {
    if (!($this->isAdminSeg() || ($draw_detail->has_user_data ?? 0))) {
        return '<span class="text-muted">-</span>';
    }
    $claim = $this->getCrossClaim($draw_detail);
    return $claim > 0 ? $claim : '<span class="text-muted">-</span>';
})

            ->editColumn('claim', function ($draw_detail) {

                if (!($this->isAdminSeg() || ($draw_detail->has_user_data ?? 0))) {
        return '<span class="text-muted">-</span>';
    }
                return $this->getClaim($draw_detail);
            })
            ->editColumn('created_at', fn($row) => Carbon::parse($row->created_at)->format('Y-m-d'))
           ->editColumn('p_and_l', function ($row) {
    if (!($this->isAdminSeg() || ($row->has_user_data ?? 0))) {
        return '<div class="text-muted text-center">-</div>';
    }

    $qty = (int) ($row->computed_tq ?? 0);
    $t_amt = $qty * 11;
    $cross_amt = (float) ($row->computed_cross ?? 0);
    $claim_units = (float) ($row->claim ?? 0);
    $cross_claim_units = (float) (
        ($row->claim_ab ?? 0) + ($row->claim_ac ?? 0) + ($row->claim_bc ?? 0)
    );

    $p_and_l = $this->calculateProfitAndLoss($t_amt, $cross_amt, $claim_units, $cross_claim_units);
    if ($p_and_l == 0) {
        return '<div class="text-muted text-center">-</div>';
    }

    $p_and_l_display = is_float($p_and_l) && floor($p_and_l) != $p_and_l
        ? number_format($p_and_l, 2)
        : number_format((int) $p_and_l);

    $bgClass = $p_and_l < 0 ? 'bg-danger text-white' : ($p_and_l > 0 ? 'bg-success text-white' : 'text-dark');
    return "<div class='{$bgClass} text-center'>{$p_and_l_display}</div>";
})

            ->addColumn('action', function ($draw_detail) {
                $draw_detail_id = $draw_detail->id;
                $draw_id = $draw_detail->draw_id ?? null;

                try {
                    $endTimeCarbon = Carbon::parse($draw_detail->end_time)->setTimezone('Asia/Kolkata')->setSecond(0);
                } catch (\Throwable $e) {
                    $endTimeCarbon = Carbon::now()->setTimezone('Asia/Kolkata')->setSecond(0);
                }

                $now = Carbon::now()->setTimezone('Asia/Kolkata')->setSecond(0);
                $hasAnyClaim = !empty($draw_detail->claim_a) || !empty($draw_detail->claim_b) || !empty($draw_detail->claim_c);
                $dataClaimed = $hasAnyClaim ? 1 : 0;
                $segment = request()->segment(1);
                $dataAttrs = "data-draw-detail-id=\"{$draw_detail_id}\" data-draw-id=\"{$draw_id}\" data-claimed=\"{$dataClaimed}\"";

                if (auth()->user()->hasRole('master')) {
                    if ($segment === 'admin' && $now->gte($endTimeCarbon) && !$hasAnyClaim) {
                        return <<<HTML
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-warning addClaim ms-3 text-white" {$dataAttrs}>Claim</button>
                            </div>
                        HTML;
                    }

                    if ($hasAnyClaim) {
                        $result = $this->getResult($draw_detail);
                        $safeResult = e($result);
                        return <<<HTML
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-danger addClaim ms-3 text-white" {$dataAttrs}>
                                    <strong>{$safeResult}</strong>
                                    <i class="fa fa-pencil ms-1"></i>
                                </button>
                            </div>
                        HTML;
                    }

                    return <<<HTML
                        <div class="d-flex justify-content-center">
                            <button class="btn btn-warning ms-3 text-white" disabled>Claim</button>
                        </div>
                    HTML;
                }

                if ($hasAnyClaim) {
                    return "<div class='text-center fw-bold text-success'>{$this->getResult($draw_detail)}</div>";
                }

                return '--';
            })
            ->orderColumn('tq', 'computed_tq $1')
            ->orderColumn('cross_amt', 'computed_cross $1')
            ->orderColumn('p_and_l', 'p_and_l $1')
            ->orderColumn('cross_claim', '(claim_ab + claim_ac + claim_bc) $1')
            ->orderColumn('claim', 'claim $1')
            ->filterColumn('game_id', function ($query, $keyword) {
                $query->whereRaw("LOWER(
                    COALESCE(
                        (SELECT name FROM games WHERE games.id = (SELECT game_id FROM draws WHERE draws.id = draw_details.draw_id)),
                        CONCAT('N', (SELECT game_id FROM draws WHERE draws.id = draw_details.draw_id)),
                        '—'
                    )
                ) LIKE ?", ["%".strtolower($keyword)."%"]);
            })
            ->filterColumn('claim', function ($query, $keyword) {
                $query->whereRaw('CAST(claim AS CHAR) LIKE ?', ["%{$keyword}%"]);
            })
            ->rawColumns(['game_id', 'end_time', 'tq', 'cross_amt', 'p_and_l', 'action', 'cross_claim', 'claim']);

        $sql = $query->toSql();

        $wrapper = DB::table(DB::raw("({$sql}) as sub"))->mergeBindings($query->getQuery())
            ->selectRaw(implode(",\n", [
                "COALESCE(SUM(COALESCE(sub.computed_tq, sub.total_qty, 0)), 0) as tq_total",
                "COALESCE(SUM(COALESCE(sub.computed_cross, sub.total_cross_amt, 0)), 0) as cross_total",
                "COALESCE(SUM(COALESCE(sub.c_amt, (COALESCE(sub.claim,0) * 100))), 0) as claim_amount",
                "COALESCE(SUM(COALESCE(sub.p_and_l, 0)), 0) as p_and_l_total",
                "COALESCE(SUM(COALESCE((COALESCE(sub.claim_ab,0) + COALESCE(sub.claim_ac,0) + COALESCE(sub.claim_bc,0)) * 100, 0)), 0) as cross_claim_total"
            ]));

        $tableTotals = (array) $wrapper->first();

        $dt->with(['tableTotals' => $tableTotals]);

        return $dt;
    }

public function query(\App\Models\DrawDetail $model): EloquentBuilder
{
    // ---------------- user filter for inner ticket/cross subqueries ----------------
    $user = auth()->user();
    $userFilterForTickets = '';

    if ($this->userId) {
        $uid = intval($this->resolveUserId());
        $userFilterForTickets = " AND t.user_id = {$uid} ";
    } else {
        if ($user && $user->hasRole('user')) {
            $uid = intval($this->resolveUserId());
            $userFilterForTickets = " AND t.user_id = {$uid} ";
        } elseif ($user && $user->hasRole('shopkeeper')) {
            $userIds = User::where('created_by', $user->id)->pluck('id')->toArray();
            if (!empty($userIds)) {
                $ids = implode(',', array_map('intval', $userIds));
                $userFilterForTickets = " AND t.user_id IN ({$ids}) ";
            } else {
                // no child users → make subqueries return 0
                $userFilterForTickets = " AND 0 = 1 ";
            }
        } else {
            // admin / other roles => no filter (show everything)
            $userFilterForTickets = '';
        }
    }
    // ------------------------------------------------------------------------------

    $gameValueSql = "COALESCE(games.name, CONCAT('N', draws.game_id), '—') as raw_game_value";
    $gameBadgeSql = "CONCAT(
        '<span class=\"badge badge-sm\" style=\"background:#0d6efd;color:#fff;padding:5px 8px;border-radius:6px;\">',
        COALESCE(games.name, CONCAT('N', draws.game_id), '—'),
        '</span>'
    ) as game_name";

    // ✅ NEW: flag to indicate whether this user/shopkeeper has any data in the draw
    $hasDataSql = "(
        CASE WHEN (
            EXISTS(
                SELECT 1
                FROM ticket_options AS topt
                INNER JOIN tickets AS t ON topt.ticket_id = t.id
                WHERE topt.draw_detail_id = draw_details.id
                  AND topt.voided = 0
                  AND t.deleted_at IS NULL
                  {$userFilterForTickets}
            )
            OR
            EXISTS(
                SELECT 1
                FROM cross_abc_details AS c
                INNER JOIN tickets AS t ON c.ticket_id = t.id
                WHERE c.draw_detail_id = draw_details.id
                  AND c.voided = 0
                  AND t.deleted_at IS NULL
                  {$userFilterForTickets}
            )
        ) THEN 1 ELSE 0 END
    ) as has_user_data";

    $computedTqSql = "(
        SELECT COALESCE(SUM(topt.a_qty + topt.b_qty + topt.c_qty), 0)
        FROM ticket_options AS topt
        INNER JOIN tickets AS t ON topt.ticket_id = t.id
        WHERE topt.draw_detail_id = draw_details.id
          AND topt.voided = 0
          AND t.deleted_at IS NULL
          {$userFilterForTickets}
    ) AS computed_tq";

    $computedTamtSql = "(
        SELECT COALESCE(SUM((topt.a_qty + topt.b_qty + topt.c_qty) * 11), 0)
        FROM ticket_options AS topt
        INNER JOIN tickets AS t ON topt.ticket_id = t.id
        WHERE topt.draw_detail_id = draw_details.id
          AND topt.voided = 0
          AND t.deleted_at IS NULL
          {$userFilterForTickets}
    ) AS computed_t_amt";

    $computedCrossSql = "(
        SELECT COALESCE(SUM(c.amount), 0)
        FROM cross_abc_details AS c
        INNER JOIN tickets AS t ON c.ticket_id = t.id
        WHERE c.draw_detail_id = draw_details.id
          AND c.voided = 0
          AND t.deleted_at IS NULL
          {$userFilterForTickets}
    ) AS computed_cross";

    $cAmtSql = "(COALESCE(claim,0) * 100) as c_amt";
    $crossClaimSql = "(COALESCE((COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)),0)) as cross_claim";

    $pAndLSql = "(
        (
            SELECT COALESCE(SUM((topt.a_qty + topt.b_qty + topt.c_qty) * 11), 0)
            FROM ticket_options AS topt
            INNER JOIN tickets AS t ON topt.ticket_id = t.id
            WHERE topt.draw_detail_id = draw_details.id
              AND topt.voided = 0
              AND t.deleted_at IS NULL
              {$userFilterForTickets}
        )
        - (COALESCE(claim,0) * 100)
        + (
            SELECT COALESCE(SUM(c.amount),0)
            FROM cross_abc_details AS c
            INNER JOIN tickets AS t ON c.ticket_id = t.id
            WHERE c.draw_detail_id = draw_details.id
              AND c.voided = 0
              AND t.deleted_at IS NULL
              {$userFilterForTickets}
        )
        - ((COALESCE(claim_ab,0) + COALESCE(claim_ac,0) + COALESCE(claim_bc,0)) * 100)
    ) as p_and_l";

    $query = $model->newQuery()
        ->leftJoin('draws', 'draws.id', '=', 'draw_details.draw_id')
        ->leftJoin('games', 'games.id', '=', 'draws.game_id')
        ->selectRaw(implode(",\n", [
            "draw_details.*",
            $gameValueSql,
            $gameBadgeSql,
            $computedTqSql,
            $computedTamtSql,
            $computedCrossSql,
            $cAmtSql,
            $crossClaimSql,
            $pAndLSql,
            $hasDataSql, // new flag
        ]));

    // ---------------- DATE FILTERS ----------------
    $startDate = request('start_date');
    $endDate   = request('end_date');
    $singleDate = request('date');
    $dayToken = request('day');

    $parseToYmd = function ($val) {
        try {
            return Carbon::parse($val)->setTimezone('Asia/Kolkata')->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    };

    if ($startDate && $endDate) {
        $sd = $parseToYmd($startDate);
        $ed = $parseToYmd($endDate);
        if ($sd && $ed) {
            $query->whereBetween(DB::raw('DATE(draw_details.created_at)'), [$sd, $ed]);
        }
    } elseif ($singleDate) {
        $d = $parseToYmd($singleDate);
        if ($d) {
            $query->whereDate('draw_details.created_at', $d);
        }
    } elseif ($dayToken) {
        $token = strtolower(str_replace(' ', '', $dayToken));
        switch ($token) {
            case 'today':
                $query->whereDate('draw_details.created_at', Carbon::now('Asia/Kolkata')->toDateString());
                break;
            case 'yesterday':
                $query->whereDate('draw_details.created_at', Carbon::now('Asia/Kolkata')->subDay()->toDateString());
                break;
            case 'last7days':
            case 'last7':
                $query->whereBetween(DB::raw('DATE(draw_details.created_at)'), [
                    Carbon::now('Asia/Kolkata')->subDays(6)->toDateString(),
                    Carbon::now('Asia/Kolkata')->toDateString()
                ]);
                break;
            case 'last30days':
            case 'last30':
                $query->whereBetween(DB::raw('DATE(draw_details.created_at)'), [
                    Carbon::now('Asia/Kolkata')->subDays(29)->toDateString(),
                    Carbon::now('Asia/Kolkata')->toDateString()
                ]);
                break;
            case 'thismonth':
                $query->whereMonth('draw_details.created_at', Carbon::now('Asia/Kolkata')->month)
                      ->whereYear('draw_details.created_at', Carbon::now('Asia/Kolkata')->year);
                break;
            case 'lastmonth':
                $last = Carbon::now('Asia/Kolkata')->subMonth();
                $query->whereMonth('draw_details.created_at', $last->month)
                      ->whereYear('draw_details.created_at', $last->year);
                break;
            default:
                $query->whereDate('draw_details.created_at', Carbon::now('Asia/Kolkata')->toDateString());
                break;
        }
    } else {
        $query->whereDate('draw_details.created_at', Carbon::now('Asia/Kolkata')->toDateString());
    }

    // ✅ DO NOT filter out draws with no user data — we want to show all draws
    // (Removed whereExists block used previously)

    return $query;
}



    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('draw-details-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
            ->addTableClass('table table-bordered table-hover')
            ->setTableHeadClass('bg-warning text-white')
            ->parameters([
                'ordering' => true,
                'searching' => true,
                'language' => ['searchPlaceholder' => 'Enter Hour Or Minute'],
                'paging' => false,
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'responsive' => true,
                'autoWidth' => false,
                'info' => false,
                'lengthChange' => false,
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
            ]);
    }

    public function getColumns(): array
    {
        $columns = [
            Column::make('updated_at')->hidden(),
            Column::make('game_id')->title('Game'),
            Column::make('end_time')->title('Time')->orderable(true)->searchable(true),
            Column::make('tq')->title('TQ'),
            Column::make('claim'),
            Column::make('cross_amt')->title('Cross Amt.'),
            Column::make('cross_claim')->title('Cross Claim'),
            Column::make('p_and_l')->title('P&L'),
            Column::make('created_at')->title('Created At'),
        ];

        $columns[] = Column::make('action')->title('Action')->orderable(false);

        return $columns;
    }

    protected function filename(): string
    {
        return 'Shopkeepers_' . date('YmdHis');
    }
}
