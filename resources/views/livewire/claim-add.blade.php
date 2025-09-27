<div>
    <div class="modal fade" id="claimModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="claimModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light border-0 shadow-lg rounded-3">
                <!-- Header -->
                <div class="modal-header bg-danger bg-gradient text-white rounded-top">
                    <h5 class="modal-title" id="claimModalLabel">
                        Enter Claim Number For <strong>{{ $end_time }}</strong>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Form -->
                <form id="otpForm" wire:submit.prevent="save">
                    <div class="modal-body text-center">
                        <div class="d-flex justify-content-center gap-3">
                            <!-- A -->
                            <div>
                                <label class="form-label mb-1 text-light">A</label>
                                <input type="text" wire:model='claim_a' maxlength="1"
                                    class="form-control text-center bg-dark text-light border-secondary otp-input"
                                    style="width:60px; font-size:24px;">
                            </div>

                            <!-- B -->
                            <div>
                                <label class="form-label mb-1 text-light">B</label>
                                <input type="text" wire:model='claim_b' maxlength="1"
                                    class="form-control text-center bg-dark text-light border-secondary otp-input"
                                    style="width:60px; font-size:24px;">
                            </div>

                            <!-- C -->
                            <div>
                                <label class="form-label mb-1 text-light">C</label>
                                <input type="text" wire:model='claim_c' maxlength="1"
                                    class="form-control text-center bg-dark text-light border-secondary otp-input"
                                    style="width:60px; font-size:24px;">
                            </div>
                        </div>

                        <!-- Errors -->
                        @if ($errors->any())
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li class="text-danger small">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="modal-footer bg-secondary bg-opacity-25 rounded-bottom justify-content-end">
                        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $(document).ready(function () {
        // show modal listener
        $wire.on('show-claim-modal', () => {
            $("#claimModal").modal('show');
        });
    });
</script>
@endscript
