<div class="row g-1 mb-2 h-50" style="background: #150512;">
    <!-- Simple ABC Display -->
    <div class="col-lg-4 col-md-6">
        @include('livewire.number-display-list', ['type' => 'simple'])
    </div>

    <!-- Cross ABC Display -->
    <div class="col-lg-4 col-md-6">
        @include('livewire.number-display-list-cross', ['type' => 'cross'])
    </div>


    <div class="col-lg-4 col-md-6 d-flex flex-column gap-2">
        @include('livewire.ticket-list')
        {{-- @include('livewire.latest-draw-details-list') --}}
    </div>

    <!-- ===== Lower Section: 3 cards side by side ===== -->
    <div class="row g-1">
         <!-- 🔹 Game Selection Dropdown -->
 
        <!-- Simple ABC Entry -->
            <!-- Simple ABC Entry + Shortcuts -->
        <div class="col-lg-4 col-md-6 h-100">
            <div class="d-flex flex-column gap-2">
                <!-- Simple ABC -->
                @include('livewire.simple-abc')

                <!-- Shortcuts -->
                {{-- <div class="card shadow-sm border-0 rounded-3 bg-dark text-light">
                    <div class="card-header bg-danger bg-gradient text-white py-2">
                        <h6 class="mb-0">
                            <i class="bi bi-keyboard me-2"></i> Shortcuts
                        </h6>
                    </div>
                    <div class="card-body p-2">
                        <ul class="list-unstyled small mb-0">
                            <li><kbd>Ctrl</kbd> + <kbd>A</kbd> → Focus <strong>A</strong> <kbd>Ctrl</kbd> + <kbd>B</kbd> → Focus <strong>B</strong> <kbd>Ctrl</kbd> + <kbd>C</kbd> → Focus <strong>C</strong> </li>
                            <li></li>
                            
                            <li><kbd>Ctrl</kbd> + <kbd>Shift</kbd> + <kbd>A</kbd> → Focus <strong>ABC</strong></li>
                            <li><strong style="color:red;">For Cross Shortcuts</strong></li>
                            <li><strong>Ctrl + 1   → Focus A --- Ctrl + 2   → Focus B  ---- Ctrl + 3   → Focus C </strong></li>
                            <li><strong> Ctrl+ Shift + C    → Focus ABC </strong></li>

                           

                        </ul>
                    </div>
                </div> --}}
            </div>
        </div>

        <!-- Cross ABC Entry -->
        <div class="col-lg-4 col-md-6">
            @include('livewire.cross-abc')
       

        <!-- Draw List -->
        <div class="col-lg-4 col-md-6">
            @include('livewire.draw-list')
        </div>
         </div>
    </div>
</div>

@script
<script>
    $(document).ready(function () {
        // Generic infinite scroll listeners
        [
            'ticket-scroller-box',
            'draw-box',
            'option-list',
            'cross-data-list',
            'latest-draw-list'
        ].forEach(cls => {
            $(document).on('scroll', '.' + cls, function () {
                let box = $(this);
                let scrollTop = box.scrollTop();
                let innerHeight = box.innerHeight();
                let scrollHeight = box[0].scrollHeight;

                // keep page variables for Livewire
                let pageVar = cls.replace(/-./g, x => x[1].toUpperCase()) + '_page';
                let page = $wire.get(pageVar);

                if (scrollTop + innerHeight >= scrollHeight - 10) {
                    page++;
                    $wire.set(pageVar, page);
                }
            });
        });

        // Refresh listener
        $wire.on('refresh-window', () => {
            window.location.reload();
        });
    });


      // 🔹 Immediate reload when client timer hits 0
    window.addEventListener('countdownZero', () => location.reload());

    // 🔹 Also reload when Livewire server dispatches its authoritative event
    window.addEventListener('drawsRefreshed', () => location.reload());
    window.addEventListener('draws-refreshed', () => location.reload());
    if (window.Livewire && Livewire.on) {
        Livewire.on('drawsRefreshed', () => location.reload());
    }
    
</script>
@endscript
