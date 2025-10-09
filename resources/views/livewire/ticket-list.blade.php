<div class="card shadow-lg border-0 rounded-3 bg-dark text-light h-100">
    <div class="card-header text-white rounded-top" style="background:#43233b;">
        <h5 class="mb-0"><i class="bi bi-ticket-perforated me-2"></i> Ticket List</h5>
    </div>

    <div class="card-body p-0" style="background-color: #5b1e63;">
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-dark table-bordered table-striped table-hover mb-0">
                <thead class="text-white position-sticky top-0" style="z-index: 1; background:#43233b;">
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Ticket No</th>
                        <th>TQ</th>
                        <th>C.Amt.</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($ticket_list as $ticket_number)
                    @php
                        // Prefer the preloaded keyed collection from Livewire ($ticketsForView)
                        $ticketModel = null;

                        if (isset($ticketsForView) && $ticketsForView instanceof \Illuminate\Support\Collection) {
                            $ticketModel = $ticketsForView->get($ticket_number);
                        } elseif (isset($ticketsForView) && is_array($ticketsForView)) {
                            $ticketModel = $ticketsForView[$ticket_number] ?? null;
                        }

                        // Defensive fallback: if not preloaded, fetch single ticket (rare)
                        if (!$ticketModel) {
                            $ticketModel = \App\Models\Ticket::withTrashed()
                                ->where('ticket_number', $ticket_number)
                                ->first();
                        }

                        // last event for audit info (you can also preload this in loadTickets() later)
                        $lastEvent = $ticketModel
                            ? DB::table('ticket_events')
                                ->where('ticket_id', $ticketModel->id)
                                ->orderBy('created_at', 'desc')
                                ->first()
                            : null;

                        // Compute totals using the safe "active" relations or precomputed sums
                        $tq = 0;
                        $crossAmt = 0;

                        if ($ticketModel) {
                            // Prefer database-summed attributes (set by withSum in loadTickets)
                            if (isset($ticketModel->total_a_qty) || isset($ticketModel->total_b_qty) || isset($ticketModel->total_c_qty)) {
                                $tq = (int)(
                                    ($ticketModel->total_a_qty ?? 0)
                                    + ($ticketModel->total_b_qty ?? 0)
                                    + ($ticketModel->total_c_qty ?? 0)
                                );
                            } else {
                                // fallback: use activeOptions relation (ensures voided rows excluded)
                                try {
                                    $tq = (int)(
                                        ($ticketModel->activeOptions()->sum('a_qty') ?? 0)
                                        + ($ticketModel->activeOptions()->sum('b_qty') ?? 0)
                                        + ($ticketModel->activeOptions()->sum('c_qty') ?? 0)
                                    );
                                } catch (\Throwable $e) {
                                    $tq = (int) ($ticketModel->tq ?? 0);
                                }
                            }

                            if (isset($ticketModel->total_cross_amt)) {
                                $crossAmt = (int) ($ticketModel->total_cross_amt ?? 0);
                            } else {
                                try {
                                    $crossAmt = (int) ($ticketModel->activeCrossDetails()->sum('amount') ?? 0);
                                } catch (\Throwable $e) {
                                    $crossAmt = (int) ($ticketModel->total_cross_amt ?? 0);
                                }
                            }
                        }

                        // Determine view-only: ticket exists and is not trashed (submitted)
                        $isSubmitted = (bool) $ticketModel && ! $ticketModel->trashed();
                    @endphp

                    <tr
                        data-ticket-id="{{ optional($ticketModel)->id }}"
                        data-deleted="{{ $ticketModel && $ticketModel->trashed() ? '1' : '0' }}"
                        data-view-only="{{ $isSubmitted ? '1' : '0' }}"
                    >
                        <td class="align-middle text-center">
    @php
        $isDeleted = $ticketModel && $ticketModel->trashed();
        $isSubmitted = $ticketModel && !$ticketModel->trashed();
    @endphp

    <input
        class="form-check-input"
        type="radio"
        name="selected_ticket"
        id="ticket_{{ $ticket_number }}"
        value="{{ $ticket_number }}"
        @checked($ticket_number == $selected_ticket_number)
        wire:click="handleTicketSelect('{{ $ticket_number }}')"
        @if($isDeleted || $isSubmitted) disabled @endif
    >
</td>
    

                        <td class="align-middle">
                            <label for="ticket_{{ $ticket_number }}" class="mb-0">
                                {{ $ticket_number }}
                            </label>

                            @if(($ticketModel && $ticketModel->trashed()) || ($lastEvent && $lastEvent->event_type === 'DELETE'))
                                <span class="badge bg-danger ms-2">Deleted</span>
                            @elseif($isSubmitted)
                                <span class="badge bg-secondary ms-2">Submitted</span>
                            @elseif($lastEvent)
                                <span class="badge bg-warning ms-2">Edited</span>
                            @endif
                        </td>

                        <td class="text-warning fw-bold align-middle">{{ $tq }}</td>
                        <td class="text-info fw-bold align-middle">{{ $crossAmt }}</td>

                        <td class="align-middle text-center">
                            @if($ticketModel && (!$lastEvent || $lastEvent->event_type !== 'DELETE') && !$ticketModel->trashed())
                                <button
                                    class="btn btn-sm btn-danger"
                                    onclick="confirmDelete({{ $ticketModel->id }}, '{{ $ticket_number }}')">
                                    Delete
                                </button>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No tickets found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(ticketId, ticketNo) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete Ticket No: " + ticketNo,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Livewire v3: call component method by id
            const el = document.querySelector('[wire\\:id]');
            if (el) {
                Livewire.find(el.getAttribute('wire:id')).call('deleteTicket', ticketId);
            } else {
                // fallback: dispatch global event
                Livewire.dispatch('deleteTicket', { ticketId });
            }
        }
    });
}
</script>
