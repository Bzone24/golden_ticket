<div>

  {{-- Controls row (search / option / pairs / perPage / refresh) --}}
  <div class="p-3">
    <div class="d-flex gap-2 align-items-center mb-3">
      <input wire:model.debounce.300ms="search" class="form-control" style="width:320px" placeholder="Search number / option / game / time" />

      <select wire:model="optionFilter" class="form-select" style="width:140px">
        <option value="">All options</option>
        <option value="A">A</option>
        <option value="B">B</option>
        <option value="C">C</option>
        <option value="AB">AB</option>
        <option value="AC">AC</option>
        <option value="BC">BC</option>
        <option value="ABC">ABC</option>
        <option value="TEAS">TEAS</option>
      </select>

      <div class="form-check ms-2">
        <input wire:model="pairsOnly" class="form-check-input" type="checkbox" id="pairsOnly">
        <label class="form-check-label" for="pairsOnly">Pairs only (AB/AC/BC)</label>
      </div>

      <select wire:model="perPage" class="form-select ms-auto" style="width:90px">
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>

      <button wire:click="$refresh" class="btn btn-outline-secondary ms-2">Refresh</button>
    </div>

    {{-- Main card header --}}
    <div class="card">
      <div class="card-header bg-info text-white">
        Hot Numbers / Cross Trace
      </div>

      <div class="card-body p-0">
        {{-- table scrollable --}}
   <div style="max-height:560px; overflow:auto;">
  @if(!empty($perGame) && count($perGame))
    <div class="d-flex justify-content-between align-items-center mb-2 text-dark">
      <div >
        <strong>Filtered draw:</strong>
        @if($selectedDrawTime)
          {{ \Carbon\Carbon::parse($selectedDrawTime)->format('h:i a') }}
        @endif
      </div>
      <div>
        <button wire:click="clearDrawFilter" class="btn btn-sm btn-outline-secondary">Clear draw filter</button>
      </div>
    </div>

    @foreach($perGame as $game => $rowsForGame)
      <div class="card mb-3">
        <div class="card-header bg-light">
          <strong>{{ $game }}</strong>
        </div>
        <div class="card-body p-2">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
          <thead class="table-light">
  <tr>
    <th wire:click="sortSubBy('normalized_option')" class="cursor-pointer">
      Type
      @if($subSortField === 'normalized_option')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
    <th wire:click="sortSubBy('number')" class="cursor-pointer">
      Number
      @if($subSortField === 'number')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
    <th wire:click="sortSubBy('users_count')" class="cursor-pointer text-end">
      Users
      @if($subSortField === 'users_count')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
    <th wire:click="sortSubBy('shopkeepers_count')" class="cursor-pointer text-end">
      Shopkeepers
      @if($subSortField === 'shopkeepers_count')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
    <th wire:click="sortSubBy('total_amount')" class="cursor-pointer text-end">
      Amount
      @if($subSortField === 'total_amount')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
    <th wire:click="sortSubBy('total_rows')" class="cursor-pointer text-end">
      Rows
      @if($subSortField === 'total_rows')
        <i class="fa {{ $subSortDirection === 'asc' ? 'fa-sort-asc' : 'fa-sort-desc' }}"></i>
      @endif
    </th>
  </tr>
</thead>

<tbody>
  @foreach($rowsForGame as $r)
    <tr>
      <td>{{ $r['normalized_option'] }}</td>
      <td>{{ $r['number'] }}</td>

      <td class="text-end">
        <button
          wire:click="showUsersForDraw('{{ $selectedDrawTime }}', '{{ $game }}', '{{ $r['normalized_option'] }}', '{{ $r['number'] }}')"
          class="btn btn-sm btn-outline-primary">
          {{ $r['users_count'] }}
        </button>
      </td>

      <td class="text-end">{{ $r['shopkeepers_count'] }}</td>
      <td class="text-end">{{ number_format($r['total_amount']) }}</td>
      <td class="text-end">{{ $r['total_rows'] }}</td>
    </tr>
  @endforeach
</tbody>

            </table>
          </div>
        </div>
      </div>
    @endforeach

  @else
    {{-- default main table (unchanged) --}}
    <table class="table table-hover mb-0">
      <thead class="table-light position-sticky top-0">
        <tr>
          <th wire:click="sortBy('game')" class="cursor-pointer">Game @if($sortField==='game') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('draw_time')" class="cursor-pointer">Draw @if($sortField==='draw_time') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('normalized_option')" class="cursor-pointer">Type @if($sortField==='normalized_option') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('number')" class="cursor-pointer">Number @if($sortField==='number') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('users_count')" class="cursor-pointer text-end">Users @if($sortField==='users_count') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('shopkeepers_count')" class="cursor-pointer text-end">Shopkeepers @if($sortField==='shopkeepers_count') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('total_amount')" class="cursor-pointer text-end">Total Amount @if($sortField==='total_amount') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
          <th wire:click="sortBy('total_rows')" class="cursor-pointer text-end">Rows @if($sortField==='total_rows') <span class="sort-icon" data-dir="{{ $sortDirection }}"></span> @endif</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            <td>{{ $row->game ?? '—' }}</td>
            <td>
              @if(!empty($row->draw_time))
                <button wire:click="filterByDrawTime('{{ $row->draw_time }}')" class="btn btn-link p-0">
                  {{ \Carbon\Carbon::parse($row->draw_time)->format('h:i a') }}
                </button>
              @else
                —
              @endif
            </td>
            <td>{{ $row->normalized_option }}</td>
            <td>
              <button wire:click="showUsers({{ $row->draw_detail_id ?? 0 }}, '{{ addslashes($row->game ?? '') }}', '{{ $row->normalized_option }}', '{{ $row->number }}')"
                      class="btn btn-link p-0">
                {{ $row->number }}
              </button>
            </td>
            <td class="text-end">
              <button wire:click="showUsers({{ $row->draw_detail_id ?? 0 }}, '{{ addslashes($row->game ?? '') }}', '{{ $row->normalized_option }}', '{{ $row->number }}')"
                      class="btn btn-sm btn-outline-primary">
                {{ $row->users_count }}
              </button>
            </td>
            <td class="text-end">{{ $row->shopkeepers_count }}</td>
            <td class="text-end">{{ number_format($row->total_amount) }}</td>
            <td class="text-end">{{ $row->total_rows }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-muted py-4">No data</td></tr>
        @endforelse
      </tbody>
    </table>

   @if(method_exists($rows, 'links'))
  <div class="p-3">
    {{ $rows->links() }}
  </div>
@endif
  @endif
</div>


       
      </div>
    </div>
  </div>

  {{-- Users modal (who played that number) --}}
  <div wire:ignore.self class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Users — {{ $detailsGame }} — {{ $detailsNormalizedOption }} {{ $detailsNumber }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeUsers"></button>
        </div>
        <div class="modal-body">
          @if(empty($detailUsers))
            <div class="alert alert-info">No users found for this draw/number.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm table-hover">
               <thead>
  <tr>
    <th>User</th>
    <th>Username</th>
    <th>Login ID</th>
    <th>Shopkeeper</th>
    <th class="text-end">Tickets</th>
    <th class="text-end">Amount</th>
  </tr>
</thead>
<tbody>
  @foreach($detailUsers as $du)
    <tr>
      <td>{{ $du['user_name'] }}</td>
      <td>{{ $du['username'] ?? '—' }}</td>
      <td>{{ $du['login_id'] ?? '—' }}</td>
      <td>{{ $du['shopkeeper_name'] ?? '—' }}</td>
      <td class="text-end">{{ $du['tickets_count'] }}</td>
      <td class="text-end">{{ number_format($du['total_amount']) }}</td>
    </tr>
  @endforeach
</tbody>

              </table>
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button wire:click="closeUsers" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- User tickets modal --}}
  <div wire:ignore.self class="modal fade" id="userTicketsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tickets — {{ $ticketUserName }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeUserTickets"></button>
        </div>
        <div class="modal-body">
          @if(empty($userTickets))
            <div class="alert alert-info">No tickets found for this user & selection.</div>
          @else
            <div class="table-responsive">
              <table class="table table-sm table-hover">
                <thead>
                  <tr>
                    <th>Ticket ID</th>
                    <th>Created</th>
                    <th>Option</th>
                    <th>Number</th>
                    <th class="text-end">Amount</th>
                    <th class="text-center">Voided</th>
                    <th>Draw</th>
                    <th>Game</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($userTickets as $t)
                    <tr>
                      <td>{{ $t['id'] }}</td>
                      <td>{{ \Carbon\Carbon::parse($t['created_at'])->format('Y-m-d H:i') }}</td>
                      <td>{{ $t['option'] }}</td>
                      <td>{{ $t['number'] }}</td>
                      <td class="text-end">{{ number_format($t['amount']) }}</td>
                      <td class="text-center">{{ $t['voided'] ? 'Yes' : 'No' }}</td>
                      {{-- <td>{{ $t['time'] ?? '-' }}</td> --}}
                      <td>
  @if(!empty($t['time']))
    {{ \Carbon\Carbon::parse($t['time'])->format('h:i a') }}
  @else
    -
  @endif
</td>
                      <td>{{ $t['game'] ?? '-' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button wire:click="closeUserTickets" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- JS hooks to open/close modals when Livewire dispatches events --}}
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      window.addEventListener('show-users-modal', () => {
        const modalEl = document.getElementById('usersModal');
        if (!modalEl) return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      });
      window.addEventListener('hide-users-modal', () => {
        const modalEl = document.getElementById('usersModal');
        if (!modalEl) return;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      });

      window.addEventListener('show-user-tickets-modal', () => {
        const modalEl = document.getElementById('userTicketsModal');
        if (!modalEl) return;
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      });
      window.addEventListener('hide-user-tickets-modal', () => {
        const modalEl = document.getElementById('userTicketsModal');
        if (!modalEl) return;
        bootstrap.Modal.getOrCreateInstance(modalEl).hide();
      });
    });
  </script>

 


</div>
@push('custom-css')
<style>
/* 🌟 GLOBAL CARD + TABLE DESIGN FOR CROSS TRACE */

/* Base container tone */
body, .container-fluid {
  color: #111 !important;
  font-size: 1.1rem !important;
  font-weight: 600 !important;
  background-color: #f9fafc !important;
}

/* 🌟 Cards */
.card {
  border: none !important;
  border-radius: 14px !important;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08) !important;
  background-color: #fff !important;
}

.card-header {
  font-weight: 700 !important;
  font-size: 1.15rem !important;
  text-transform: uppercase;
  background: linear-gradient(90deg, #2980b9, #3498db) !important;
  color: #fff !important;
  border-radius: 14px 14px 0 0 !important;
  padding: 10px 14px !important;
}

.card-body {
  font-size: 1.1rem !important;
  font-weight: 600 !important;
  padding: 12px 14px !important;
  background-color: #fff !important;
}

/* 🌟 Table styling (applies everywhere) */
.table {
  font-size: 1.1rem !important;
  color: #111 !important;
  font-weight: 600 !important;
  border-collapse: separate !important;
  border-spacing: 0 !important;
}

.table th {
  background: #e9edf5 !important;
  font-weight: 700 !important;
  color: #111 !important;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  border-bottom: 2px solid #d1d5db !important;
  padding: 12px 14px !important;
  position: sticky;
  top: 0;
  z-index: 2;
}

.table td {
  vertical-align: middle !important;
  font-weight: 600 !important;
  color: #000 !important;
  padding: 10px 14px !important;
  border-bottom: 1px solid #e5e7eb !important;
}

/* Row hover */
.table-hover tbody tr:hover {
  background-color: #f4f7fb !important;
  transition: background 0.25s ease;
}

/* Alternate row tone for readability */
.table-striped tbody tr:nth-of-type(odd) {
  background-color: #fafbfe !important;
}

/* Buttons inside tables */
.table button {
  font-size: 1rem !important;
  font-weight: 700 !important;
  color: #0b74de !important;
}
.table button:hover {
  color: #084b8a !important;
}

/* 🌟 Sort arrows */
th.cursor-pointer {
  cursor: pointer;
  user-select: none;
}
th.cursor-pointer:hover {
  background: #dde6f0 !important;
}
.sort-icon {
  margin-left: 6px;
  font-size: 0.9em;
}
.sort-icon[data-dir="asc"]::after { content: " ▲"; }
.sort-icon[data-dir="desc"]::after { content: " ▼"; }

/* 🌟 Modal design */
.modal-content {
  border-radius: 14px !important;
  box-shadow: 0 4px 18px rgba(0,0,0,0.2);
}
.modal-header {
  background: linear-gradient(90deg, #34495e, #2c3e50);
  color: #fff;
  font-weight: 700;
  border-radius: 14px 14px 0 0 !important;
}
.modal-body {
  font-size: 1.05rem;
  font-weight: 600;
  color: #111;
  background-color: #fff;
}
.modal-footer {
  background: #f9fafc;
  border-top: 1px solid #e5e7eb;
}

/* 🌟 Tables inside modals */
.modal .table {
  font-size: 1.05rem;
  font-weight: 600;
  color: #111;
}
.modal .table th {
  background-color: #f1f4f9 !important;
  color: #222 !important;
  font-weight: 700 !important;
}
.modal .table td {
  color: #111 !important;
  font-weight: 600 !important;
}

/* 🌟 Form controls */
.form-control, .form-select {
  font-size: 1.05rem !important;
  font-weight: 600 !important;
  color: #111 !important;
  border-radius: 10px !important;
  border: 1px solid #cbd5e1 !important;
}
.form-control:focus, .form-select:focus {
  border-color: #3498db !important;
  box-shadow: 0 0 0 0.15rem rgba(52,152,219,0.25) !important;
}

/* 🌟 Buttons */
.btn {
  font-size: 1rem !important;
  font-weight: 700 !important;
  border-radius: 8px !important;
  padding: 6px 14px !important;
}
.btn-outline-secondary {
  color: #2c3e50 !important;
  border-color: #2c3e50 !important;
}
.btn-outline-secondary:hover {
  background: #2c3e50 !important;
  color: #fff !important;
}

/* 🌟 Utilities */
.text-end { text-align: right !important; }
.text-center { text-align: center !important; }

</style>
@endpush

