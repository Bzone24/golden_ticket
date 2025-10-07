<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'GameTicketHub')</title>
    @include('web.includes.css-plugins')
    @stack('custom-css')
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>

    <!-- Header -->
    @include('web.includes.header')

    @yield('contents')

    @include('web.includes.js-plugins')
    @stack('custom-js')
    @stack('scripts')
    @stack('styles')
    @livewireScripts
    @vite(['resources/js/app.js'])

    <script>
    // Mirror Livewire/DOM events → localStorage so other tabs/pages react
    (function(){
      function bumpLS(key){
        try {
          localStorage.setItem(key, String(Date.now()));
          setTimeout(() => { try { localStorage.removeItem(key); } catch(_){} }, 500);
        } catch(_) {}
      }

      if (typeof Livewire !== 'undefined') {
        try {
          Livewire.on('ticket-submitted', () => bumpLS('ticket-submitted'));
          Livewire.on('ticketSubmitted',  () => bumpLS('ticket-submitted'));
          Livewire.on('ticket-deleted',   () => bumpLS('ticket-deleted'));
          Livewire.on('ticketDeleted',    () => bumpLS('ticket-deleted'));
        } catch (e) {}
      }

      window.addEventListener('ticket-submitted', () => bumpLS('ticket-submitted'), false);
      window.addEventListener('ticket-deleted',   () => bumpLS('ticket-deleted'),   false);
    })();
    </script>

    <script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('swal', event => {
            const message = event[0];
            Swal.fire({
                icon: message.icon,
                title: message.title,
                text: message.text,
                confirmButtonColor: '#3085d6',
            });
        });
    });
    </script>

</body>

</html>
