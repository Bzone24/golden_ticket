<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="mb-0">My Wallet Transactions</h6>
    <input wire:model="search" class="form-control form-control-sm" placeholder="Search type or note..." style="max-width:240px;">
  </div>

  <div class="card-body p-0">
    <table class="table table-sm mb-0">
      <thead>
        <tr>
          <th>#</th><th>Type</th><th>Amount</th><th>Balance</th><th>Performed By</th><th>Note</th><th>Date</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $t)
          <tr>
            <td>{{ $t->id }}</td>
            <td><span class="badge bg-{{ $t->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span></td>
            <td>₹{{ number_format($t->amount,2) }}</td>
            <td>₹{{ number_format($t->balance,2) }}</td>
            <td>{{ optional($t->performer)->first_name ? trim($t->performer->first_name.' '.($t->performer->last_name ?? '')) : (optional($t->performer)->name ?? 'System') }}</td>
            <td>{{ $t->note }}</td>
            <td>{{ $t->created_at->format('Y-m-d H:i') }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center">No transactions yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="card-footer">
    {{ $transactions->links() }}
  </div>
</div>
