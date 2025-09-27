@extends('admin.layouts.base')
@section('title', 'Shopkeeper')
@section('contents')
    <div class="container-fluid">
        @if (!$user)
            <h2> @hasrole('admin')Add Shopkeeper @elserole('shopkeeper')Add User @endrole</h2>
        @else
            <h2>Edit {{ $user->name }}</h2>
        @endif

        <div>
            @if ($user)
                @livewire('admin.shop-keeper-form', compact('user'))
            @else
                @livewire('admin.shop-keeper-form')
            @endif
        </div>
    </div>
@endsection
