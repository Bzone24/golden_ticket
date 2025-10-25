<div class="ticket-modal-content">
  <h5>Ticket #{{ $ticket->ticket_number }}</h5>

  <div class="mb-3">
    <table id="modal-ticket-details" class="table table-sm table-striped" style="width:100%">
      <thead>
        <tr>
          <th>Option</th>
          <th>Number</th>
          <th>Qty</th>
          <th>Amt</th>
        </tr>
      </thead>
    </table>
  </div>

  <div class="mb-3">
    <table id="modal-cross-details" class="table table-sm table-striped" style="width:100%">
      <thead>
        <tr>
          <th>Option</th>
          <th>Type</th>
          <th>Numbers</th>
          <th>Comb</th>
          <th>Amount</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<script>
(function(){
  // Init ticket details table (use your existing AJAX route)
  if ($.fn.dataTable.isDataTable('#modal-ticket-details')) {
    $('#modal-ticket-details').DataTable().destroy(); $('#modal-ticket-details').empty();
  }
 $('#modal-ticket-details').DataTable({
  processing: true,
  serverSide: true,
  ajax: {
    url: "{{ route('admin.draw.ticke.details.list', [$drawDetail->id, $ticket->id, $user->id]) }}",
    dataSrc: function(json) {
      json.data = (json.data || []).map(item => {
        // if server doesn't provide `number`, try alternatives
        if (!('number' in item)) {
          item.number = item.ticket_number ?? item.numbers ?? '';
        }
        return item;
      });
      return json.data;
    }
  },
  columns: [
    { data: 'option', title: 'Option' },
    { data: 'number', title: 'Number' },   // now guaranteed to exist
    { data: 'qty', title: 'Qty' },
    { data: 'amt', title: 'Amt' }
  ]
});


  // Init cross details table
  if ($.fn.dataTable.isDataTable('#modal-cross-details')) {
    $('#modal-cross-details').DataTable().destroy(); $('#modal-cross-details').empty();
  }
  $('#modal-cross-details').DataTable({
    processing: true, serverSide: true,
    ajax: "{{ route('admin.draw.cross.ticket.details.list', [$drawDetail->id, $ticket->id, $user->id]) }}",
    columns: [
      { data: 'option' }, { data: 'type' }, { data: 'numbers' }, { data: 'combination' }, { data: 'amount' }
    ],
    pageLength: 10
  });
})();


</script>
