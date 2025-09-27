<div>
  <div class="col-12"> <!-- Increased to col-md-8 for more space -->
    <div class="card shadow rounded-4">
      <div class="card-body p-4">
        <form wire:submit.prevent='save'>

          <div class="row">
            <div class="mb-3 col-md-6">
              <label for="start_time" class="form-label">Start Time</label>
              <input type="time" name="start_time" class="form-control" wire:model='start_time' id="start_time" placeholder="Enter your first name">
              @error('start_time')
              <span class="text-danger">{{ $message }}</span>
              @enderror
            </div>

            <div class="mb-3 col-md-6">
              <label for="end_time" class="form-label">End Time</label>
              <input type="time" name="end_time" class="form-control" wire:model='end_time' id="end_time" placeholder="Enter your last name">
              @error('end_time')
              <span class="text-danger">{{ $message }}</span>
              @enderror
            </div>

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


