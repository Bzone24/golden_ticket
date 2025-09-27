<div class="card shadow-lg border-0 rounded-3 bg-dark text-light h-100">
    <div class="card-header bg-success bg-gradient text-white rounded-top">
        <h5 class="mb-0"><i class="bi bi-ticket-perforated me-2"></i> Ticket List</h5>
    </div>

    <div class="card-body p-0" style="background-color: #212529;">
        <!-- ✅ Scrollable Table Wrapper -->
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-dark table-bordered table-striped table-hover mb-0">
                <thead class="bg-secondary text-white position-sticky top-0" style="z-index: 1;">
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Ticket No</th>
                        <th>TQ</th>
                        <th>C.Amt.</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($ticket_list as $ticket_number)
                    @php
                        $ticketModel = \App\Models\Ticket::with(['options','crossAbc'])
                            ->where('ticket_number', $ticket_number)
                            ->first();

                        $tq = $ticketModel
                            ? $ticketModel->options->sum('a_qty')
                                + $ticketModel->options->sum('b_qty')
                                + $ticketModel->options->sum('c_qty')
                            : 0;

                        $crossAmt = $ticketModel
                            ? $ticketModel->crossAbc->sum('amount')
                            : 0;
                    @endphp

                    <tr>
                        <td>
                            <input
                                class="form-check-input"
                                type="radio"
                                name="selected_ticket"
                                id="ticket_{{ $ticket_number }}"
                                value="{{ $ticket_number }}"
                                @checked($ticket_number == $selected_ticket_number)
                                wire:click="handleTicketSelect('{{ $ticket_number }}')"
                            >
                        </td>
                        <td>
                            <label for="ticket_{{ $ticket_number }}" class="mb-0">
                                {{ $ticket_number }}
                            </label>
                        </td>
                        <td class="text-warning fw-bold">{{ $tq }}</td>
                        <td class="text-info fw-bold">{{ $crossAmt }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No tickets found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
