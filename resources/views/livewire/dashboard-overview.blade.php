<div wire:poll.5s>
    <div class="row mb-3">
        <div class="card-header bg-primary text-white text-center">
            <h4 class="mb-0">@hasrole('master')Total Admin @elserole('admin') Total shopkeepers @elserole('shopkeeper') Total User @endrole</h4>
        </div>
        <div class="card user-totals-table">
            <div class="card-body p-0">
                @if ($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 25%;">User</th>
                                    <th style="width: 15%;">TQ</th>
                                    <th style="width: 15%;">CL</th>
                                    <th style="width: 20%;">CA</th>
                                    <th style="width: 20%;">CC</th>
                                </tr>
                            </thead>
                            <tbody>
                                
                                @foreach ($users as $user)
                                    {{-- @if($user['parent_id'])--}}
                                    <tr onclick="location.href='{{ route('admin.shopkeeper.get.user.details', [$user['parent_id']]) }}'"
                                        style="cursor: pointer;">
                                    {{--@else 
                                        <tr> @endif --}}
                                        <td class="user-name-cell">
                                            <div class="d-flex align-items-center">
                                                {{-- <div class="bg-primary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                                style="width: 35px; height: 35px; font-size: 12px;">
                                                {{ substr($user['name'], 0, 2) }}
                                            </div> --}}
                                                {{ $user['name'] }}
                                            </div>
                                        </td>
                                        <td class="number-cell tq-cell">
                                            {{ number_format($user['total_qty'] ?? 0) }}
                                        </td>
                                        <td class="number-cell claim-cell">
                                            {{ number_format($user['claim'] ?? 0) }}
                                        </td>
                                        <td class="number-cell cross-amt-cell">
                                            ₹{{ number_format($user['total_cross_amt'] ?? 0) }}
                                        </td>
                                        <td class="number-cell cross-claim-cell">
                                            ₹{{ number_format($user['cross_claim'] ?? 0) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>

                            <!-- Totals Row -->

                        </table>
                    </div>
                @else
                    <div class="no-data">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p>No user data available for the selected period.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- <div class="row">
        <div class="card-header bg-info text-white text-center">
            <h4 class="mb-0">Draw Overview For Today</h4>
        </div>
        <div class="card user-totals-table">
            <div class="card-body p-0">
                <div class="row text-center mb-3 g-3">
                    <div class="col-3 text-success fw-bold">
                        Tickets: <strong>{{ $total_tickets }}</strong>
                    </div>
                    <div class="col-3 text-danger fw-bold">
                        Ticket Claim: <strong>{{ $total_claims }}</strong>
                    </div>
                    <div class="col-3 text-success fw-bold">
                        Cross Amt: <strong>{{ $total_cross_amt }}</strong>
                    </div>
                    <div class="col-3 text-danger fw-bold">
                        Cross Claim: <strong>{{ $total_cross_claim }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
</div>