@extends('web.layouts.base')
@section('title','GameTicketHub')
@section('contents')
<div class="card">
    <div class="card-header d-flex justify-content-start">
        <h5 class="me-auto">My Tickets</h5>
        <a href="{{ route('ticket.add') }}" class="btn btn-primary">Add A New Ticket <i class="fa fa-ticket"></i></a>
    </div>
    <div class="card-body">
        this is body
    </div>
</div>
@endsection