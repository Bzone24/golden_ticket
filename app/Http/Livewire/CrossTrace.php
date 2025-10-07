<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class CrossTrace extends Component
{
    use WithPagination;

    public int $perPage = 50;
    public string $search = '';
    public string $optionFilter = '';
    public bool $pairsOnly = true;
    public ?int $draw_detail_id = null;

    // details state (users modal)
    public bool $showingUsers = false;
    public string $detailsNormalizedOption = '';
    public string $detailsNumber = '';

    public int|null $detailsDrawId = null;
    public string $detailsGame = '';
    public array $detailUsers = [];

    // tickets state (user's tickets modal)
    public bool $showingUserTickets = false;
    public array $userTickets = [];
    public int|null $ticketUserId = null;
    public string $ticketUserName = '';

 public string $sortField = 'draw_time';
public string $sortDirection = 'desc';

// per-draw table sorting
public string $subSortField = 'users_count';
public string $subSortDirection = 'desc';


public ?string $selectedDrawTime = null;


    protected $listeners = [
        'ticketCreated' => '$refresh',
        'ticketUpdated' => '$refresh',
        'ticketDeleted' => '$refresh',
    ];

    public function updatingSearch()       { $this->resetPage(); }
    public function updatingOptionFilter() { $this->resetPage(); }
    public function updatingPairsOnly()    { $this->resetPage(); }
    public function updatingPerPage()      { $this->resetPage(); }

    /**
     * Main render: aggregated rows grouped by draw (game + time) and normalized option+number
     */

 public function sortBy(string $field)
{
    if ($this->sortField === $field) {
        $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    $this->resetPage();
}

public function sortSubBy(string $field)
{
    if ($this->subSortField === $field) {
        $this->subSortDirection = $this->subSortDirection === 'asc' ? 'desc' : 'asc';
    } else {
        $this->subSortField = $field;
        $this->subSortDirection = 'asc';
    }

    // no pagination to reset here
}

public function filterByDrawTime(string $drawTime)
{
    // store the raw draw_time (from DB) to filter by
    $this->selectedDrawTime = $drawTime;
    $this->resetPage();
}

public function clearDrawFilter()
{
    $this->selectedDrawTime = null;
    $this->resetPage();
}



public function render()
{
    $normalizedExpr = "UPPER(REPLACE(COALESCE(NULLIF(TRIM(cad.option), ''), 'TEAS'), '-', ''))";
    $allowedPairs = ['AB', 'AC', 'BC'];

    // Base builder used in both modes
    $base = DB::table('cross_abc_details as cad')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->join('users as u', 'u.id', '=', 'cad.user_id')
        ->where('cad.voided', 0);

    // common search filter
    if ($this->search) {
        $s = trim($this->search);
        $base->where(function($q) use ($s, $normalizedExpr) {
            $q->where('cad.number', 'like', "%{$s}%")
              ->orWhere('cad.option', 'like', "%{$s}%")
              ->orWhereRaw("{$normalizedExpr} LIKE ?", ['%'.strtoupper($s).'%'])
              ->orWhere('g.name', 'like', "%{$s}%")
              ->orWhereRaw("COALESCE(dd.start_time, d.start_time) LIKE ?", ["%{$s}%"]);
        });
    }

    // groups-only filter
    if ($this->pairsOnly) {
        $base->whereIn(DB::raw($normalizedExpr), $allowedPairs);
    }

    if (!empty($this->optionFilter)) {
        $base->where(DB::raw($normalizedExpr), strtoupper($this->optionFilter));
    }

    // -- DRAW-TIME FILTER MODE --
    if ($this->selectedDrawTime) {
        $dt = $this->selectedDrawTime;

        // compute aggregated rows grouped by game + normalized_option + number for the selected draw_time
        // we match COALESCE(dd.start_time, d.start_time) exactly to the selected draw_time string
     $rowsPerGame = (clone $base)
    ->selectRaw("
        g.name AS game,
        {$normalizedExpr} AS normalized_option,
        cad.number,
        COUNT(DISTINCT cad.user_id) AS users_count,
        COUNT(DISTINCT u.created_by) AS shopkeepers_count,
        SUM(CASE WHEN cad.amount REGEXP '^[0-9]+$' THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) AS total_amount,
        COUNT(*) AS total_rows
    ")
    ->whereRaw("COALESCE(dd.start_time, d.start_time) = ?", [$dt])
    ->groupBy('game','normalized_option','cad.number')
    ->orderBy('game')
    ->orderBy($this->subSortField, $this->subSortDirection)
    ->get()
    ->groupBy('game');


        // rowsPerGame is a collection keyed by game name; convert to array for Blade
        $perGame = [];
        foreach ($rowsPerGame as $game => $collect) {
            $perGame[$game] = $collect->map(fn($r) => (array)$r)->values()->all();
        }

        return view('livewire.cross-trace', [
            'rows' => collect([]), // keep rows as empty so original list not shown
            'perGame' => $perGame,
        ]);
    }

    // -- DEFAULT MODE (main table) --
    // apply draw_detail_id filter if provided (keeps existing behavior)
    if ($this->draw_detail_id) {
        $base->where('cad.draw_detail_id', $this->draw_detail_id);
    }

    // group and order using alias-aware sortField
    $query = (clone $base)->selectRaw("
        g.name AS game,
        COALESCE(dd.start_time, d.start_time) AS draw_time,
        cad.draw_detail_id,
        MIN(cad.option) AS option_sample,
        {$normalizedExpr} AS normalized_option,
        cad.number,
        COUNT(DISTINCT cad.user_id) AS users_count,
        COUNT(DISTINCT u.created_by) AS shopkeepers_count,
        SUM(CASE WHEN cad.amount REGEXP '^[0-9]+$' THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) AS total_amount,
        COUNT(*) AS total_rows
    ");

    $query = $query->groupBy('game','dd.start_time','d.start_time','cad.draw_detail_id','normalized_option','cad.number');

    // apply sorting: special-case draw_time alias
    if ($this->sortField === 'draw_time') {
        $query->orderByRaw("COALESCE(dd.start_time, d.start_time) {$this->sortDirection}");
    } else {
        // ensure safe fields - using alias names from selectRaw
        $query->orderBy($this->sortField, $this->sortDirection);
    }

    $rows = $query->paginate($this->perPage);

    return view('livewire.cross-trace', [
        'rows' => $rows,
        'perGame' => [], // empty when not filtering by draw_time
    ]);
}



    /**
     * Show users who contributed to a specific draw+game+option+number
     * Called from blade: wire:click="showUsers(drawId, game, normalizedOption, number)"
     */
 public function showUsers(int $drawDetailId, string $game, string $normalizedOption, string $number)
{
    $this->detailsGame = $game;
    $this->detailsNormalizedOption = $normalizedOption;
    $this->detailsNumber = $number;

    $normalizedExpr = "UPPER(REPLACE(COALESCE(NULLIF(TRIM(cad.option), ''), 'TEAS'), '-', ''))";

    $rows = DB::table('cross_abc_details as cad')
        ->selectRaw('
            cad.user_id,
            COALESCE(u.username, u.login_id, "") as user_name,
            u.username,
            u.login_id,
            COALESCE(sk.username, sk.login_id, "—") as shopkeeper_name,
            COUNT(cad.id) as tickets_count,
            SUM(CASE WHEN cad.amount REGEXP "^[0-9]+$" THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) as total_amount
        ')
        ->join('users as u', 'u.id', '=', 'cad.user_id')
        ->leftJoin('users as sk', 'sk.id', '=', 'u.created_by')
        ->where('cad.voided', 0)
        ->where('cad.draw_detail_id', $drawDetailId)
        ->whereRaw("{$normalizedExpr} = ?", [strtoupper($normalizedOption)])
        ->where('cad.number', $number)
        ->groupBy('cad.user_id', 'u.username', 'u.login_id', 'sk.username', 'sk.login_id')
        ->orderByDesc('tickets_count')
        ->get();

    $this->detailUsers = $rows->map(function ($row) {
        return [
            'user_id' => $row->user_id,
            'user_name' => $row->user_name,
            'username' => $row->username ?? null,
            'login_id' => $row->login_id ?? null,
            'shopkeeper_name' => $row->shopkeeper_name ?? '—',
            'tickets_count' => (int) $row->tickets_count,
            'total_amount' => (int) $row->total_amount,
        ];
    })->toArray();

    $this->dispatch('show-users-modal');
}


public function showUsersForDraw(string $drawTime, string $game, string $normalizedOption, string $number)
{
    // keep UI state for modal
    $this->detailsGame = $game;
    $this->detailsNormalizedOption = $normalizedOption;
    $this->detailsNumber = $number;
    $this->selectedDrawTime = $drawTime;

    // normalization expression (same as other queries)
    $normalizedExpr = "UPPER(REPLACE(COALESCE(NULLIF(TRIM(cad.option), ''), 'TEAS'), '-', ''))";

    // Build query: use parameter binding (no raw unquoted values)
    $rows = DB::table('cross_abc_details as cad')
        ->selectRaw('
            cad.user_id,
            COALESCE(u.username, u.login_id, "") as user_name,
            u.username,
            u.login_id,
            COALESCE(sk.username, sk.login_id, "—") as shopkeeper_name,
            COUNT(cad.id) as tickets_count,
            SUM(CASE WHEN cad.amount REGEXP "^[0-9]+$" THEN CAST(cad.amount AS UNSIGNED) ELSE 0 END) as total_amount
        ')
        ->join('users as u', 'u.id', '=', 'cad.user_id')
        ->leftJoin('users as sk', 'sk.id', '=', 'u.created_by')
        ->leftJoin('draw_details as dd', 'dd.id', '=', 'cad.draw_detail_id')
        ->leftJoin('draws as d', 'd.id', '=', 'dd.draw_id')
        ->leftJoin('games as g', 'g.id', '=', 'd.game_id')
        ->whereRaw("{$normalizedExpr} = ?", [strtoupper($normalizedOption)])
        ->where('cad.number', $number)
        ->whereRaw("COALESCE(dd.start_time, d.start_time) = ?", [$drawTime])
        ->where('cad.voided', 0)
        ->groupBy('cad.user_id', 'u.username', 'u.login_id', 'sk.username', 'sk.login_id')
        ->orderByDesc('tickets_count')
        ->get();

    // Map into array matching your blade (safe conversion)
    $this->detailUsers = $rows->map(function ($row) {
        return [
            'user_id' => $row->user_id,
            'user_name' => $row->user_name,
            'username' => $row->username ?? null,
            'login_id' => $row->login_id ?? null,
            'shopkeeper_name' => $row->shopkeeper_name ?? '—',
            'tickets_count' => (int) $row->tickets_count,
            'total_amount' => (int) $row->total_amount,
        ];
    })->toArray();

    $this->dispatch('show-users-modal');
}




    /**
     * Show a selected user's ticket rows for the given draw/game/option/number.
     * Called from Users modal: wire:click="showUserTickets(user_id)"
     */
public function showUserTickets(int $userId)
{
    $this->ticketUserId = $userId;
    $this->ticketUserName = DB::table('users')->where('id', $userId)->value('name') ?? 'User';

    $normalizedExpr = "UPPER(REPLACE(COALESCE(NULLIF(TRIM(cad.option), ''), 'TEAS'), '-', ''))";

    $tickets = DB::table('cross_abc_details as cad')
        ->selectRaw('
            cad.id,
            cad.created_at,
            cad.option,
            cad.number,
            CAST(CASE WHEN cad.amount REGEXP "^[0-9]+$" THEN cad.amount ELSE 0 END AS UNSIGNED) AS amount,
            cad.voided,
            COALESCE(dd.start_time, d.start_time, dd.time, d.time) AS time,
            g.name AS game,
        ')
        ->leftJoin('draw_details as dd','dd.id','=','cad.draw_detail_id')
        ->leftJoin('draws as d','d.id','=','dd.draw_id')
        ->leftJoin('games as g','g.id','=','d.game_id')
        ->where('cad.user_id', $this->ticketUserId)
        ->where('cad.draw_detail_id', $this->detailsDrawId)
        ->whereRaw("{$normalizedExpr} = ?", [$this->detailsNormalizedOption])
        ->where('cad.number', $this->detailsNumber)
        ->orderByDesc('cad.created_at')
        ->get()
        ->map(function($t) {
            return [
                'id' => $t->id,
                'created_at' => $t->created_at,
                'option' => $t->option,
                'number' => $t->number,
                'amount' => (int) $t->amount,
                'voided' => (int) $t->voided,
                'time' => $t->time,   // matches Blade $t['time']
                'game' => $t->game,   // matches Blade $t['game']
            ];
        })->toArray();

    $this->userTickets = $tickets;
    $this->showingUserTickets = true;
    $this->dispatch('show-user-tickets-modal');
}



    public function closeUsers()
    {
        $this->showingUsers = false;
        $this->detailUsers = [];
        $this->dispatch('hide-users-modal');
    }

    public function closeUserTickets()
    {
        $this->showingUserTickets = false;
        $this->userTickets = [];
        $this->dispatch('hide-user-tickets-modal');
    }
}
