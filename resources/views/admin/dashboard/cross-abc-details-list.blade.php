<div class="col-12">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header custom-header d-flex justify-content-between align-items-center">
            <h5 class="text-white mb-0 fw-bold">
                <i class="bi bi-bar-chart-fill me-2"></i> Details Of Cross ABC
            </h5>
            <h5 class="text-white mb-0 fw-bold">
                Game:{{$drawDetail->draw->game->name}}
            </h5>
        </div>

        {{-- ⬇️ Auto-refresh wrapper --}}
        <div class="card-body p-0" id="crossAbcDetailsContainerList">
            @include('admin.dashboard.partials.cross-abc-details-list', ['drawDetail' => $drawDetail])

            <div class="text-muted small text-end pe-3 pb-2">
                Last updated: <span id="crossAbcUpdatedAt">{{ now()->format('H:i:s') }}</span>
            </div>
        </div>
    </div>
</div>

@push('custom-css')
<style>
    .flash-green {
        background-color: #28a745 !important;
        color: #fff !important;
        animation: flashFadeGreen 2s ease forwards;
    }
    .flash-red {
        background-color: #dc3545 !important;
        color: #fff !important;
        animation: flashFadeRed 2s ease forwards;
    }

    @keyframes flashFadeGreen {
        0% { background-color: #28a745; color: #fff; }
        100% { background-color: inherit; color: inherit; }
    }
    @keyframes flashFadeRed {
        0% { background-color: #dc3545; color: #fff; }
        100% { background-color: inherit; color: inherit; }
    }
</style>
@endpush

@push('custom-js')
<script>
    setInterval(function () {
        // grab old values before reload
        let oldValues = [];
        $("#crossAbcDetailsContainerList table tbody tr").each(function () {
            let rowVals = [];
            $(this).find("td").each(function () {
                rowVals.push($(this).text().trim());
            });
            oldValues.push(rowVals);
        });

        // reload
        $("#crossAbcDetailsContainerList").load(window.location.href + " #crossAbcDetailsContainerList>*", function () {
            // after reload, compare new values to old ones
            $("#crossAbcDetailsContainerList table tbody tr").each(function (rowIndex) {
                $(this).find("td").each(function (colIndex) {
                    let newVal = $(this).text().trim();
                    let oldVal = (oldValues[rowIndex] ?? [])[colIndex];

                    if (newVal !== oldVal && oldVal !== undefined && newVal !== "") {
                        let newNum = parseFloat(newVal.replace(/,/g, ""));
                        let oldNum = parseFloat(oldVal.replace(/,/g, ""));

                        if (!isNaN(newNum) && !isNaN(oldNum)) {
                            if (newNum > oldNum) {
                                $(this).addClass("flash-green");
                                setTimeout(() => $(this).removeClass("flash-green"), 2000);
                            } else if (newNum < oldNum) {
                                $(this).addClass("flash-red");
                                setTimeout(() => $(this).removeClass("flash-red"), 2000);
                            }
                        } else {
                            // fallback for text values
                            $(this).addClass("flash-green");
                            setTimeout(() => $(this).removeClass("flash-green"), 2000);
                        }
                    }
                });
            });

            // update timestamp
            document.getElementById("crossAbcUpdatedAt").innerText =
                new Date().toLocaleTimeString();
        });
    }, 10000); // refresh every 10s
</script>
@endpush
