<div>
    <div class="col-12">
        <div class="card shadow rounded-4">
            <div class="card-body p-4">
                <form wire:submit.prevent='save' id="shopkeeper-form">

                    <div class="row">

                        <!-- Username -->
                        <div class="mb-3 col-md-6">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" wire:model.lazy="username"
                                class="form-control" placeholder="Choose a username (e.g. ram123)">
                            @error('username')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                            <!-- client-side warning for ABC### pattern -->
                            <div id="usernameWarning" class="mt-1" style="display:none;">
                                <small class="text-danger">Usernames that look like <code>ABC123</code> are reserved.
                                    Please choose a different username.</small>
                            </div>
                        </div>

                        <!-- Full name -->
                        <div class="mb-3 col-md-6">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" wire:model.lazy='full_name'
                                id="fullName" placeholder="Enter full name">
                            @error('full_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3 col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" name="email" wire:model='email' class="form-control" id="email"
                                placeholder="example@domain.com">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Mobile -->
                        <div class="mb-3 col-md-6">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" wire:model='mobile_number' name="mobile_number" class="form-control"
                                id="mobile" placeholder="Enter your mobile number">
                            @error('mobile_number')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="mb-3 col-md-6 position-relative">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="{{ $showPassword ? 'text' : 'password' }}" name="password"
                                    wire:model="password" class="form-control" id="password"
                                    placeholder="Enter password">

                                <span class="input-group-text" wire:click="togglePasswordVisibility('password')"
                                    style="cursor: pointer;">
                                    <i class="{{ $showPassword ? 'fa fa-eye' : 'fa fa-eye-slash' }}"></i>
                                </span>
                            </div>
                            @error('password')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3 col-md-6 position-relative">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="{{ $showConfirmPassword ? 'text' : 'password' }}"
                                    name="password_confirmation" wire:model="password_confirmation" class="form-control"
                                    id="confirmPassword" placeholder="Confirm password">

                                <span class="input-group-text" wire:click="togglePasswordVisibility('confirm')"
                                    style="cursor: pointer;">
                                    <i class="{{ $showConfirmPassword ? 'fa fa-eye' : 'fa fa-eye-slash' }}"></i>
                                </span>
                            </div>
                            @error('password_confirmation')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        @hasrole('shopkeeper')
                            <div class="mb-3 col-12">
                                <label class="form-label">Per-game limits</label>
                                <div class="row g-3">
                                    @foreach ($games as $game)
                                        @php $gk = strtoupper($game->key ?? $game->code ?? $game->name); @endphp
                                        <div class="col-md-6">
                                            <div class="card p-2 h-100">
                                                <div class="card-body p-2">
                                                    <h6 class="mb-2">{{ $game->name ?? $gk }}</h6>

                                                    <div class="mb-2">
                                                        <label for="maximum_cross_amount_{{ $gk }}"
                                                            class="form-label">Maximum Cross Amount
                                                            ({{ $gk }})</label>
                                                        <input type="number" id="maximum_cross_amount_{{ $game->id }}"
                                                            wire:model.defer="per_game_limits.{{ $game->id }}.maximum_cross_amount"
                                                            class="form-control" placeholder="Maximum Cross Amount">
                                                        @error("per_game_limits.{$game->id}.maximum_cross_amount")
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="mb-0">
                                                        <label for="maximum_tq_{{ $gk }}"
                                                            class="form-label">Maximum Tq ({{ $gk }})</label>
                                                        <input type="number" id="maximum_tq_{{ $game->id }}"
                                                            wire:model.defer="per_game_limits.{{ $game->id }}.maximum_tq"
                                                            class="form-control" placeholder="Maximum Tq">
                                                        @error("per_game_limits.{$game->id}.maximum_tq")
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endhasrole

                    </div>

      @hasrole('shopkeeper')
    <div class="form-group mt-3">
        <label class="form-label fw-bold fs-5 text-dark">Allowed Games</label>
        <div class="d-flex gap-4 flex-wrap text-dark">
            @foreach($games as $game)
                <label class="form-check-label d-flex align-items-center text-dark" style="font-size: 1.1rem;">
                    <input type="checkbox"
                           wire:model="allowed_games"
                           value="{{ $game->id }}"
                           class="form-check-input me-2"
                           style="width: 30px; height: 30px; color: black;">
                    {{ $game->name }}
                </label>
            @endforeach
        </div>
        @error('allowed_games')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        @error('allowed_games.*')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
@endhasrole



                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <button type="submit" id="shopkeeperSubmit"
                                class="btn btn-primary rounded-pill">Submit</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- Client-side username guard script -->
    <script>
        (function() {
            const abcPattern = /^ABC\d+$/i;

            function bindUsernameGuard() {
                const usernameInput = document.querySelector('input[name="username"]');
                const submitBtn = document.getElementById('shopkeeperSubmit');
                const warningEl = document.getElementById('usernameWarning');

                if (!usernameInput || !submitBtn || !warningEl) return;

                function check() {
                    const val = usernameInput.value.trim();
                    if (val && abcPattern.test(val)) {
                        // show warning and disable submit
                        warningEl.style.display = 'block';
                        submitBtn.disabled = true;
                    } else {
                        warningEl.style.display = 'none';
                        submitBtn.disabled = false;
                    }
                }

                // initial check
                check();

                // events
                usernameInput.removeEventListener('input', check);
                usernameInput.addEventListener('input', check);
                usernameInput.removeEventListener('change', check);
                usernameInput.addEventListener('change', check);
            }

            // Bind when Livewire loads and after every update (re-attach)
            document.addEventListener('livewire:load', function() {
                bindUsernameGuard();
            });

            document.addEventListener('livewire:update', function() {
                // small timeout to allow DOM to settle
                setTimeout(bindUsernameGuard, 50);
            });

            // In case Livewire isn't present (fallback)
            if (!window.livewire) {
                document.addEventListener('DOMContentLoaded', function() {
                    bindUsernameGuard();
                });
            }
        })();
    </script>
</div>
