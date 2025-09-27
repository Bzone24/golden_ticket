<?php

namespace App\Livewire\Admin;

use App\Models\Draw;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AddDraw extends Component
{
    public ?Draw $draw = null;

    public string $start_time = '';
    public ?string $end_time = null;
    public ?string $result_time = null;
    public int $price = 11; // default if you need it

    protected $rules = [
        'start_time' => 'required|date_format:H:i',
        // add 'price' if it’s editable: 'price' => 'required|integer|min:1'
    ];

    protected $messages = [
        'start_time.required'    => 'Please enter a start time.',
        'start_time.date_format' => 'Use 24h time like 12:45 or 01:00.',
    ];

    public function mount($draw_id = null): void
    {
        if ($draw_id) {
            $this->draw = Draw::findOrFail($draw_id);

            $this->start_time  = $this->draw->start_time;
            $this->end_time    = $this->draw->end_time;
            $this->result_time = $this->draw->result_time;
            $this->price       = $this->draw->price ?? $this->price;
        }
    }

    public function save()
    {
        $data = $this->validate();

        // Auto-calc end & result
        $start  = Carbon::createFromFormat('H:i', $data['start_time']);
        $end    = $start->copy()->addMinutes(14);
        $result = $end->copy()->addMinute();

        $payload = [
            'start_time'  => $start->format('H:i'),
            'end_time'    => $end->format('H:i'),
            'result_time' => $result->format('H:i'),
            'price'       => $this->price,
        ];

        // Optional: prevent duplicate start times
        $exists = Draw::query()
            ->where('start_time', $payload['start_time'])
            ->when($this->draw, fn($q) => $q->where('id', '!=', $this->draw->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_time' => 'A draw with this start time already exists.',
            ]);
        }

        if ($this->draw) {
            $this->draw->update($payload);
        } else {
            $this->draw = Draw::create($payload);
        }

        return redirect()->route('admin.draw');
    }

    public function render()
    {
        return view('livewire.admin.add-draw');
    }
}
