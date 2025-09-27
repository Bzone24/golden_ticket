<div class="card shadow-lg border-0 rounded-3 bg-dark text-light h-100 py-0">
    <div class="card-header bg-warning bg-gradient text-dark rounded-top">
        <h5 class="mb-0 py-0">
            <i class="bi bi-calendar-week me-2"></i> Draw List
        </h5>
    </div>

    <div class="card-body p-0 d-flex flex-column" wire:poll.visible="loadDraws">
        <!-- Scrollable list -->
        <div class="list-group list-group-flush overflow-auto" style="height:400px; font-size: larger;">

            <!-- TOP: Game checkboxes (N1, N2) -->
            <div class="list-group-item border-0 py-2 bg-dark text-light position-sticky top-0 z-1"
                wire:key="game-select-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
            </div>
            <!-- /TOP: Game checkboxes -->
            @forelse ($draw_list as $draw_detail)
                @php
                    $gameName = strtoupper($draw_detail->draw->game->name);
                    $isActive = $active_draw && $active_draw->id === $draw_detail->id;

                    if ($isActive) {
                        $rowClass = 'active bg-dark text-white';
                    } elseif ($gameName === 'N1') {
                        $rowClass = 'bg-dark text-light'; // Dark shade
                    } elseif ($gameName === 'N2') {
                        $rowClass = 'bg-secondary text-light'; // Little lighter dark shade
                    } else {
                        $rowClass = 'bg-dark text-light';
                    }
                @endphp

                <div class="list-group-item border-0 py-2 d-flex align-items-center {{ $rowClass }}"
                    wire:key="draw-{{ $draw_detail->id }}">

                    <!-- Checkbox -->
                    <input class="form-check-input me-2 draw_checkbox" type="checkbox" id="draw_{{ $draw_detail->id }}"
                        value="{{ $draw_detail->id }}" wire:model="selected_draw">

                    <!-- Label -->
                    <label class="form-check-label flex-grow-1" for="draw_{{ $draw_detail->id }}">
                        <span class="fw-semibold">
                            {{ $draw_detail->formatResultTime() }}
                        </span>

                        <span class="fw-bold text-warning">
                            Game : {{ $draw_detail->draw->game->name }}
                        </span>
                        <span class="ms-2 text-white fw-bold">
                            Cross Amt:
                            {{ $draw_detail->totalAbAmt(auth()->id()) + $draw_detail->totalBcAmt(auth()->id()) + $draw_detail->totalAcAmt(auth()->id()) }}
                        </span>
                        <span class="ms-2 text-white fw-bold">
                            TQ:
                            {{ $draw_detail->totalAqty(user_id: auth()->id()) + $draw_detail->totalCqty(user_id: auth()->id()) }}
                        </span>
                    </label>
                </div>
            @empty
                <div class="text-center text-muted py-3">No draws available.</div>
            @endforelse

        </div>
    </div>
</div>
