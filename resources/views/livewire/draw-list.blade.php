    <div class="card shadow-lg border-0 rounded-3  text-light h-90 py-0 mb-5" style="background:#43233b;">
        <div class="card-header   text-light rounded-top" style="background:#431549;">
            <h5 class="mb-0 py-0">
                <i class="bi bi-calendar-week me-2"></i> Draw List
            </h5>
        </div>

  <div class="mb-0 mt-3 ms-3 d-flex align-items-center">
    <label for="drawCount" class="me-2 ">Select Next Draws:</label>
    <input type="number"
           id="drawCount"
           wire:model.defer="drawCount"
           wire:keydown.enter="applyDrawCount"
           class="form-control me-2"
           style="width: 100px;">

    <button type="button"
            wire:click="applyDrawCount"
            class="btn btn-primary btn-sm me-2">
        Apply
    </button>

    <button type="button"
            wire:click="clearSelectedDraws"
            class="btn btn-danger btn-sm">
        Clear
    </button>
</div>


        <div class="card-body p-0 d-flex flex-column" wire:poll.visible.15s="loadDraws">
            <!-- Scrollable list -->
            <div class="list-group list-group-flush overflow-auto mb-2" style="height:380px; font-size: larger;">

                <!-- TOP: Game checkboxes (N1, N2) -->
                <div class="list-group-item border-1 py-2 text-light position-sticky top-0 z-1" style="background:#43233b;" 
                    wire:key="game-select-header"
                    style="border-bottom: 1px solid rgba(240, 226, 226, 0.951);">
                </div>

                @php
                    $allowedGameIds = auth()->user()->games->pluck('id')->toArray();
                @endphp

                @forelse ($draw_list as $draw_detail)
                    @if (in_array($draw_detail->draw->game->id, $allowedGameIds))
                        @php
                            $gameName = strtoupper($draw_detail->draw->game->name);
                            $isActive = $active_draw && $active_draw->id === $draw_detail->id;

                            if ($isActive) {
                                $rowClass = 'active bg-custom text-white';
                            } elseif ($gameName === 'N1') {
                                $rowClass = 'bg-custom text-light';
                            } elseif ($gameName === 'N2') {
                                $rowClass = 'bg-custom text-light';
                            } else {
                                $rowClass = 'bg-custom text-light';
                            }

                            $isChecked = in_array((string) $draw_detail->id, $selected_draw ?? []);
                        @endphp

                        <div class="list-group-item border-0 py-2 d-flex align-items-center {{ $rowClass }}"
                            wire:key="draw-{{ $draw_detail->id }}">

                 <input class="form-check-input me-2 draw_checkbox"
       type="checkbox"
       id="draw_{{ $draw_detail->id }}"
       value="{{ $draw_detail->id }}"
       wire:model.lazy="selected_draw">

        {{-- <input class="form-check-input me-2 draw_checkbox"
                               type="checkbox"
                               id="draw_{{ $draw_detail->id }}"
                               value="{{ $draw_detail->id }}"
                               wire:model="selected_draw"
                               @if($isChecked) checked @endif> --}}

                                {{-- @if($isChecked) checked @endif> --}}

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
                    @endif
                @empty
                    <div class="text-center text-muted py-3">No draws available.</div>
                @endforelse

            </div>
        </div>
    </div>

    @push('styles')
<style>
    .bg-custom {
        background-color: #43233b !important;
    }

    /* optional hover effect */
    .bg-custom:hover {
        background-color: #5a2f4e !important; /* a lighter shade */
    }
</style>
@endpush

