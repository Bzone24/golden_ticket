<div>
    <div class="col-12"> <!-- Increased to col-md-8 for more space -->
        <div class="card shadow rounded-4">
            <div class="card-body p-4">
                <form wire:submit.prevent='save'>

                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" wire:model='first_name'
                                id="firstName" placeholder="Enter your first name">
                            @error('first_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" wire:model='last_name'
                                id="lastName" placeholder="Enter your last name">
                            @error('last_name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" name="email" wire:model='email' class="form-control" id="email"
                                placeholder="example@domain.com">
                            @error('email')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3 col-md-6">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="tel" wire:model='mobile_number' name="mobile_number" class="form-control"
                                id="mobile" placeholder="Enter your mobile number">
                            @error('mobile_number')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

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
                        @hasrole(['admin'])
                        <div class="mb-3 col-md-6 position-relative">
                            <label for="maximum_cross_amount" class="form-label">Maximum Cross Amount</label>
                            <div class="input-group">
                                <input type="number"
                                    name="maximum_cross_amount" wire:model="maximum_cross_amount" class="form-control"
                                    id="maximum_cross_amount" placeholder="Maximum Cross Amount">
                                </span>
                            </div>
                            @error('maximum_cross_amount')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                         <div class="mb-3 col-md-6 position-relative">
                            <label for="maximum_tq" class="form-label">Maximum Tq</label>
                            <div class="input-group">
                                <input type="number"
                                    name="maximum_tq" wire:model="maximum_tq" class="form-control"
                                    id="maximum_tq" placeholder="Maximum Tq">
                                </span>
                            </div>
                            @error('maximum_tq')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        @endhasrole

                    </div>

                    <div class="row">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary rounded-pill">Submit</button>

                        </div>
                    </div>


                </form>
            </div>
        </div>
    </div>
</div>
