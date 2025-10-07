<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'MyTest')</title>

  @include('admin.includes.css-plugins')
  @stack('custom-css')
  @livewireStyles
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <style>
    /* Body / layout adjustments for sidebar behavior */
    .body-wrapper {
      margin-left: 250px;
      transition: all 0.3s ease;
    }

    .left-sidebar.collapsed {
      width: 0 !important;
      overflow: hidden;
    }

    .body-wrapper.expanded {
      margin-left: 0 !important;
    }

    /* Mobile overlay behavior */
    @media (max-width: 992px) {
      .left-sidebar {
        left: -250px;
        width: 250px;
      }
      .left-sidebar.active {
        left: 0;
      }
      .body-wrapper {
        margin-left: 0 !important;
      }
    }
  </style>
</head>

<body>
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
       data-sidebar-position="fixed" data-header-position="fixed">

    @include('admin.includes.top-strip')
    @include('admin.includes.sidebar')

    <div class="body-wrapper">
      @include('admin.includes.header')

      <div class="body-wrapper-inner">
        @yield('contents')
      </div>
    </div>
  </div>

  @include('admin.includes.js-plguins')
  @stack('custom-js')
   @vite(['resources/js/app.js'])
    @livewireScripts

  <script>
    (function () {
      const toggleBtn = document.getElementById("sidebarToggleBtn");
      if (!toggleBtn) return;

      toggleBtn.addEventListener("click", function () {
        const sidebar = document.querySelector(".left-sidebar");
        const bodyWrapper = document.querySelector(".body-wrapper");
        const appHeader = document.getElementById('app-header');

        if (!sidebar || !bodyWrapper) return;

        const collapsedOrActive = sidebar.classList.contains("collapsed") || sidebar.classList.contains("active");

        if (collapsedOrActive) {
          sidebar.classList.remove("collapsed", "active");
          bodyWrapper.classList.remove("expanded");
          if (appHeader) appHeader.classList.remove('w-100');
        } else {
          sidebar.classList.add("collapsed", "active");
          bodyWrapper.classList.add("expanded");
          if (appHeader) appHeader.classList.add('w-100');
        }
      });
    })();
  </script>
</body>
</html>
