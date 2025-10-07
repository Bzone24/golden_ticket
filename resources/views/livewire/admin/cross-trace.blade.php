@extends('admin.layouts.base')

@section('title', 'Cross Trace')
@section('contents')
<div class="container-fluid">
    <h2 class="mb-3">Cross Trace</h2>

    {{-- optionally include any date filters, etc. --}}
    <x-date-range-picker-filter />

    <div class="card">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0">Hot Numbers / Cross Trace</h4>
        </div>
        <div class="card-body">
            {{-- Mount the Livewire component you added earlier --}}
           @livewire(\App\Http\Livewire\CrossTrace::class)


        </div>
    </div>
</div>


@endsection
