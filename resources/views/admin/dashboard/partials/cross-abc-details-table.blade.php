<div class="row g-4">
    {{-- AB --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success text-center">
                <h5 class="fw-bold text-dark mb-0">
                    Details Of AB ({{ $drawDetail->formatEndTime() }})
                </h5>
                <h5 class="text-white mb-0 fw-bold">
                    Game:{{$drawDetail->draw->game->name}}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    {{ $dataTable->table(['class' => 'table table-bordered table-hover text-center align-middle custom-dt'], true) }}
                </div>
            </div>
        </div>
    </div>

    {{-- AC --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning text-center">
                <h5 class="fw-bold text-dark mb-0">
                    Details Of AC ({{ $drawDetail->formatEndTime() }}) 
                </h5>
                <h5 class="text-white mb-0 fw-bold">
                    Game:{{$drawDetail->draw->game->name}}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    {!! $crossAcDataTable->table(['class' => 'table table-bordered table-hover text-center align-middle custom-dt'], true) !!}
                </div>
            </div>
        </div>
    </div>

    {{-- BC --}}
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-info text-center">
                <h5 class="fw-bold text-dark mb-0">
                    Details Of BC ({{ $drawDetail->formatEndTime() }})
                </h5>
                <h5 class="text-white mb-0 fw-bold">
                    Game:{{$drawDetail->draw->game->name}}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
                    {!! $crossBcDataTable->table(['class' => 'table table-bordered table-hover text-center align-middle custom-dt'], true) !!}
                </div>
            </div>
        </div>
    </div>
</div>
