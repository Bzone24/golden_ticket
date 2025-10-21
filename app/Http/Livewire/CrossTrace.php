<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class CrossTrace extends Component
{
    use WithPagination;

    // UI state

    // public bool $debug = true;

    public int $perPage = 50;
    public string $search = '';
    public string $optionFilter = '';
    public bool $pairsOnly = true;

    // main sorting
    public string $sortField = 'draw_time';
    public string $sortDirection = 'desc';

    // per-draw sorting
    public string $subSortField = 'users_count';
    public string $subSortDirection = 'desc';

    // date filter default -> today
    public ?string $dateFilter = null;


    // draw/time filter when clicking a draw time
    public $selectedDrawTime = null;

    // row-level details
    public array $detailUsers = [];
    public array $userTickets = [];
    public string $detailsGame = '';
    public string $detailsNormalizedOption = '';
    public $detailsNumber = null;
    public string $ticketUserName = '';

    // debug toggle (set true when debugging)
    public bool $debug = true;

    protected $queryString = [
        'dateFilter' => ['as' => 'end_date'],
    ];

    public function mount()
    {
        // if not set from query string, default to today's date in Asia/Kolkata
        if (empty($this->dateFilter)) {
            $this->dateFilter = \Carbon\Carbon::today('Asia/Kolkata')->toDateString();
        } else {
            // normalize format to Y-m-d (in case UI sends other format)
            try {
                $this->dateFilter = \Carbon\Carbon::parse($this->dateFilter)->toDateString();
            } catch (\Exception $e) {
                $this->dateFilter = \Carbon\Carbon::today('Asia/Kolkata')->toDateString();
            }
        }
    }



    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    public function updatingOptionFilter()
    {
        $this->resetPage();
    }
    public function updatingPairsOnly()
    {
        $this->resetPage();
    }
    public function updatingDateFilter()
    {
        $this->resetPage();
    }
    public function updatingSortField()
    {
        $this->resetPage();
    }

    public function sortBy(string $field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }



    public function sortSubBy(string $field)
    {
        if ($this->subSortField === $field) {
            $this->subSortDirection = $this->subSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->subSortField = $field;
            $this->subSortDirection = 'asc';
        }
    }

    public function filterByDrawTime($dt)
    {
        $this->selectedDrawTime = $dt;
    }

    public function clearDrawFilter()
    {
        $this->selectedDrawTime = null;
        $this->resetPage();
    }

    // show users for main table (by draw_detail_id)
public function showUsers($drawDetailId = 0, string $game = '', string $pairType = '', string $number = '')
{
    // set UI state
    $this->detailsGame = $game;
    $this->detailsNormalizedOption = strtoupper(trim($pairType ?? ''));
    $this->detailsNumber = $number;
    $this->detailUsers = [];

    // if caller passed draw_detail_id, set selectedDrawTime for label (optional)
    if (!empty($drawDetailId)) {
        $drawend = DB::table('draw_details')->where('id', $drawDetailId)->value('end_time');
        if ($drawend) {
            $this->selectedDrawTime = $drawend;
        }
    }

    $pair = strtoupper(trim($pairType ?? ''));
    $normalizedTypeExpr = "UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), '')))";

    $q = DB::table('cross_abc_details as cad')
        ->leftJoin('users as u', 'u.id', '=', 'cad.user_id')
        ->leftJoin('users as sk', 'sk.id', '=', 'u.created_by')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->where('cad.voided', 0)
        ->where('cad.number', $number);

    if (!empty($pair)) {
        $q->whereRaw("{$normalizedTypeExpr} = ?", [$pair]);
    }

    // Prefer exact draw_detail_id (most reliable). If not provided, fall back to date filter (same behavior as showUsersForDraw).
    if (!empty($drawDetailId)) {
        $q->where('cad.draw_detail_id', $drawDetailId);
    } else {
        $date = $this->dateFilter ?? \Carbon\Carbon::today('Asia/Kolkata')->toDateString();
        // use same fallback used by showUsersForDraw: match by created_at date OR dd.date or end_time-date
        $q->where(function($sub) use ($date) {
            $sub->whereDate('cad.created_at', $date)
                ->orWhere('dd.date', $date)
                ->orWhereRaw("DATE(COALESCE(dd.end_time, d.end_time)) = ?", [$date]);
        });
    }

    // select aggregated user rows (only columns that exist)
    $q->selectRaw('
            cad.user_id,
            COALESCE(u.username, u.login_id, CONCAT("User-", cad.user_id)) AS user_name,
            u.username,
            u.login_id,
            COALESCE(sk.username, sk.login_id, "—") AS shopkeeper_name,
            COUNT(DISTINCT cad.ticket_id) as tickets_count,
            SUM(CASE WHEN cad.amount REGEXP "^[0-9]+$" THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) as total_amount
        ')
      ->groupBy('cad.user_id','u.username','u.login_id','sk.username','sk.login_id')
      ->orderByDesc('tickets_count');

    // DEBUG: if you need to inspect generated SQL, uncomment the next two lines before testing
    // \Log::debug('CrossTrace showUsers SQL', ['sql' => $q->toSql(), 'bindings' => $q->getBindings()]);

    $rows = $q->get();

    $this->detailUsers = $rows->map(function ($row) {
        return [
            'user_id' => $row->user_id,
            'user_name' => trim($row->user_name) ?: ('User-'.$row->user_id),
            'username' => $row->username ?? null,
            'login_id' => $row->login_id ?? null,
            'shopkeeper_name' => $row->shopkeeper_name ?? '—',
            'tickets_count' => (int) $row->tickets_count,
            'total_amount' => (int) $row->total_amount,
        ];
    })->toArray();

    if (empty($this->detailUsers)) {
        \Log::debug('CrossTrace showUsers no results', [
            'drawDetailId' => $drawDetailId,
            'game' => $game,
            'pair' => $pair,
            'number' => $number,
            'dateFilter' => $this->dateFilter ?? null,
            // 'sql' => $q->toSql(), 'bindings' => $q->getBindings(), // enable if needed
        ]);
    }

    // open modal using browser event so the frontend JS picks it up
    $this->dispatch('show-users-modal');
}




    // show users for a specific draw time + game table (per-draw view)
public function showUsersForDraw($drawDetailId = 0, string $game = '', string $pairType = '', string $number = '')
{
    $this->detailsGame = $game;
    $this->detailsNormalizedOption = strtoupper(trim($pairType ?? ''));
    $this->detailsNumber = $number;
    $this->detailUsers = [];

    $pair = strtoupper(trim($pairType ?? ''));
    $normalizedTypeExpr = "UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), '')))";

    $q = DB::table('cross_abc_details as cad')
        ->leftJoin('users as u', 'u.id', '=', 'cad.user_id')
        ->leftJoin('users as sk', 'sk.id', '=', 'u.created_by')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->where('cad.voided', 0)
        ->where('cad.number', $number);

    // ✅ use normalized type match (case-insensitive)
    if (!empty($pair)) {
        $q->whereRaw("$normalizedTypeExpr = ?", [$pair]);
    }

    // ✅ precise draw linkage
    if (!empty($drawDetailId)) {
        $q->where('cad.draw_detail_id', $drawDetailId);
    } else {
        $date = $this->dateFilter ?? \Carbon\Carbon::today('Asia/Kolkata')->toDateString();
        $q->whereDate('cad.created_at', $date);
    }

    $q->selectRaw('
        cad.user_id,
        COALESCE(u.username, u.login_id, CONCAT("User-", cad.user_id)) AS user_name,
        u.username,
        u.login_id,
        COALESCE(sk.username, sk.login_id, "—") AS shopkeeper_name,
        COUNT(DISTINCT cad.ticket_id) AS tickets_count,
        SUM(CASE WHEN cad.amount REGEXP "^[0-9]+$" THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) AS total_amount
    ')
    ->groupBy('cad.user_id', 'u.username', 'u.login_id', 'sk.username', 'sk.login_id')
    ->orderByDesc('tickets_count');

    $rows = $q->get();

    // ✅ map cleanly
    $this->detailUsers = $rows->map(function ($row) {
        return [
            'user_id' => $row->user_id,
            'user_name' => trim($row->user_name) ?: ('User-'.$row->user_id),
            'username' => $row->username ?? null,
            'login_id' => $row->login_id ?? null,
            'shopkeeper_name' => $row->shopkeeper_name ?? '—',
            'tickets_count' => (int) $row->tickets_count,
            'total_amount' => (int) $row->total_amount,
        ];
    })->toArray();

    if (empty($this->detailUsers)) {
        \Log::debug('CrossTrace showUsersForDraw no results', [
            'drawDetailId' => $drawDetailId,
            'pair' => $pair,
            'number' => $number,
            'dateFilter' => $this->dateFilter ?? null,
            // 'sql' => $q->toSql(), 'bindings' => $q->getBindings(),
        ]);
    }

    $this->dispatch('show-users-modal');
}






    // show tickets for a single user (in modal)
  public function showUserTickets($userId, $pairType = '', $number = '', $drawDetailId = 0)
{
    $pair = strtoupper(trim($pairType ?? ''));
    $number = trim($number ?? '');
    $this->ticketUserName = $userId ? optional(DB::table('users')->where('id',$userId)->first())->username ?? "User-$userId" : 'User';
    $this->userTickets = [];

    // Build query: find ticket ids from cross_abc_details that match this selection, then select tickets
    $cadQ = DB::table('cross_abc_details as cad')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('tickets as t', 't.id', '=', 'cad.ticket_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->where('cad.voided', 0)
        ->where('cad.user_id', $userId)
        ->where('cad.number', $number);

    if (!empty($pair)) {
        $cadQ->whereRaw("UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), ''))) = ?", [$pair]);
    }

    if (!empty($drawDetailId)) {
        $cadQ->where('cad.draw_detail_id', $drawDetailId);
    } else {
        $date = $this->dateFilter ?? \Carbon\Carbon::today('Asia/Kolkata')->toDateString();
        $cadQ->where(function($sub) use ($date) {
            $sub->where('dd.date', $date)
                ->orWhereRaw("DATE(COALESCE(dd.end_time, d.end_time)) = ?", [$date])
                ->orWhereDate('cad.created_at', $date);
        });
    }

    // select relevant ticket info (distinct tickets)
    $cadQ->selectRaw("
        DISTINCT COALESCE(t.id, cad.ticket_id) AS ticket_id,
        COALESCE(t.created_at, cad.created_at) AS created_at,
        COALESCE(t.amount, SUM(CASE WHEN cad.amount REGEXP '^[0-9]+$' THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END)) AS amount,
        COALESCE(t.voided, 0) AS voided,
        COALESCE(dd.end_time, d.end_time) AS time,
        COALESCE(g.name, CONCAT('Game-', cad.game_id)) AS game,
        cad.number
    ")
    ->groupBy('ticket_id','created_at','voided','time','game','cad.number');

    $tickets = $cadQ->orderBy('created_at','desc')->get();

    $this->userTickets = collect($tickets)->map(function($t) {
        return [
            'id' => $t->ticket_id,
            'created_at' => $t->created_at,
            'amount' => (int)$t->amount,
            'voided' => (int)$t->voided,
            'time' => $t->time,
            'game' => $t->game,
            'number' => $t->number,
        ];
    })->values()->all();

    // open tickets modal
    $this->dispatch('show-user-tickets-modal');
}

    public function closeUsers()
    {
        $this->detailUsers = [];
        $this->dispatch('hide-users-modal');
    }

    public function closeUserTickets()
    {
        $this->userTickets = [];
        $this->dispatch('hide-user-tickets-modal');
    }

    public function render()
{
    // Use the 'type' column (AB/AC/BC) as the canonical pair value
    $normalizedTypeExpr = "UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), '')))";
    $allowedPairs = ['AB', 'AC', 'BC'];

    $base = DB::table('cross_abc_details as cad')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->leftJoin('users as u', 'u.id', '=', 'cad.user_id') // leftJoin so orphaned users don't drop rows
        ->where('cad.voided', 0);

    // Apply date filter (prefer dd.date, fallback to end_time or cad.created_at)
    if (!empty($this->dateFilter)) {
        $date = $this->dateFilter;
        $base->where(function($q) use ($date) {
            $q->where('dd.date', $date)
              ->orWhereRaw("DATE(COALESCE(dd.end_time, d.end_time)) = ?", [$date])
              ->orWhereDate('cad.created_at', $date);
        });
    }

    // search (leave as-is, but when searching option consider cad.type too)
    if ($this->search) {
        $s = trim($this->search);
        $base->where(function($q) use ($s, $normalizedTypeExpr) {
            $q->where('cad.number', 'like', "%{$s}%")
              ->orWhere('cad.option', 'like', "%{$s}%")
              ->orWhereRaw("{$normalizedTypeExpr} LIKE ?", ['%'.strtoupper($s).'%'])
              ->orWhere('g.name', 'like', "%{$s}%")
              ->orWhereRaw("COALESCE(dd.end_time, d.end_time) LIKE ?", ["%{$s}%"]);
        });
    }

    // pairsOnly: filter using cad.type (the 'type' column) — only if pairsOnly is truthy
    if (!empty($this->pairsOnly)) {
        $base->whereIn(DB::raw($normalizedTypeExpr), $allowedPairs);
    }

    // optionFilter: if user explicitly selected an option-type filter, convert to type form
    if (!empty($this->optionFilter)) {
        // if they pass e.g. 'AB' or 'A-B-C', normalize to AB/AC/BC using type pref
        $base->where(DB::raw($normalizedTypeExpr), strtoupper($this->optionFilter));
    }

    // debug counts
    $debugCounts = null;
    if ($this->debug) {
        $baseForTotal = clone $base;
        $debugCounts = [
            'totalRaw' => $baseForTotal->count(),
            'by_draw_time' => null,
            'by_ticket_created' => null,
        ];

        if (!empty($this->dateFilter)) {
            $date = $this->dateFilter;
            $debugCounts['by_draw_time'] = DB::table('cross_abc_details as cad')
                ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
                ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
                ->where('cad.voided', 0)
                ->where(function($q) use ($date) {
                    $q->where('dd.date', $date)
                      ->orWhereRaw("DATE(COALESCE(dd.end_time, d.end_time)) = ?", [$date]);
                })
                ->count();

            $debugCounts['by_ticket_created'] = DB::table('cross_abc_details as cad')
                ->whereDate('cad.created_at', $date)
                ->where('cad.voided', 0)
                ->count();
        }
    }

    // per-draw detailed view
    // per-draw detailed view (replacement)
// per-draw detailed view (replace existing rowsPerGame block)
if (!empty($this->selectedDrawTime)) {
    $dt = $this->selectedDrawTime;

    $rowsPerGame = (clone $base)
        ->selectRaw("
            COALESCE(g.name, CONCAT('Game-', cad.game_id)) AS game,
            UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), ''))) AS pair_type,
            cad.number,
            DATE(MIN(COALESCE(dd.end_time, d.end_time))) AS date,
            MIN(COALESCE(dd.end_time, d.end_time)) AS draw_time,
            COUNT(DISTINCT cad.user_id) AS users_count,
            COUNT(DISTINCT u.created_by) AS shopkeepers_count,
            SUM(CASE WHEN cad.amount REGEXP '^[0-9]+$' THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) AS total_amount,
            COUNT(*) AS total_rows,
            cad.draw_detail_id
        ")
        ->where(function($q) use ($dt) {
            // allow matching by dd.date or exact end_time fallback
            $q->where('dd.date', $dt)
              ->orWhereRaw("COALESCE(dd.end_time, d.end_time) = ?", [$dt]);
        })
        ->groupBy('game','pair_type','cad.number','cad.draw_detail_id')
        ->orderBy('game')
        ->orderBy($this->subSortField ?? 'users_count', $this->subSortDirection ?? 'desc')
        ->get()
        ->groupBy('game');

    $perGame = [];
    foreach ($rowsPerGame as $game => $collect) {
        $perGame[$game] = $collect->map(fn($r) => (array)$r)->values()->all();
    }

    return view('livewire.cross-trace', [
        'rows' => collect([]),
        'perGame' => $perGame,
        'debugCounts' => $debugCounts,
    ]);
}




    // DEFAULT aggregated table
$query = (clone $base)->selectRaw("
    COALESCE(g.name, CONCAT('Game-', cad.game_id)) AS game,
    MIN(COALESCE(dd.end_time, d.end_time)) AS draw_time,               -- aggregated
    DATE(MIN(COALESCE(dd.end_time, d.end_time))) AS date,             -- derived from aggregated
    cad.draw_detail_id,
    MIN(cad.option) AS option_sample,
    UPPER(TRIM(COALESCE(NULLIF(cad.type, ''), ''))) AS pair_type,
    cad.number,
    COUNT(DISTINCT cad.user_id) AS users_count,
    COUNT(DISTINCT u.created_by) AS shopkeepers_count,
    SUM(CASE WHEN cad.amount REGEXP '^[0-9]+$' THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) AS total_amount,
    COUNT(*) AS total_rows
");

// group rows per game, draw_detail_id, pair_type, number
$query = $query->groupBy('game','cad.draw_detail_id','pair_type','cad.number');

// ordering by aggregated draw_time (use alias)
$query = $query->orderByRaw('MIN(COALESCE(dd.end_time, d.end_time)) DESC');

    // sorting
    if ($this->sortField === 'draw_time') {
        $query->orderByRaw("COALESCE(dd.end_time, d.end_time) {$this->sortDirection}");
    } elseif ($this->sortField === 'date') {
        $query->orderByRaw("COALESCE(dd.date, DATE(COALESCE(dd.end_time, d.end_time))) {$this->sortDirection}");
    } else {
        $query->orderBy($this->sortField, $this->sortDirection);
    }

    // log the final query (optional/debug)
    \Log::debug('CrossTrace AGG SQL', [
        'sql' => $query->toSql(),
        'bindings' => $query->getBindings(),
        'dateFilter' => $this->dateFilter,
    ]);

    $rows = $query->paginate($this->perPage);

    return view('livewire.cross-trace', [
        'rows' => $rows,
        'perGame' => [],
        'debugCounts' => $debugCounts,
    ]);
}

}
