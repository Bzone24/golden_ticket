<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'MyTest')</title>
@include('admin.includes.css-plugins')
 @stack('custom-css')
 @livewireStyles
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
 {{-- <style> --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sidebar Toggle Example</title>
  {{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"> --}}

  <style>
    /* Sidebar Base */
    /* .left-sidebar {
      width: 250px;
      transition: all 0.3s ease;
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      z-index: 1000;
      background: #fff;
      border-right: 1px solid #ddd;
      padding: 1rem;
    } */

    .body-wrapper {
      margin-left: 250px;
      transition: all 0.3s ease;
    }

    /* Desktop Collapse */
    .left-sidebar.collapsed {
      width: 0 !important;
      overflow: hidden;
    }
    .body-wrapper.expanded {
      margin-left: 0 !important;
    }

    /* Mobile Overlay */
    @media (max-width: 992px) {
      .left-sidebar {
        left: -250px;
        width: 250px;
      }
      .left-sidebar.active {
        left: 0;
      }
      .body-wrapper {
        margin-left: 0 !important; /* never push content on mobile */
      }
    }


 </style>
</head>
<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">

    <!--  App Topstrip -->
   @include('admin.includes.top-strip')
    <!-- Sidebar Start -->
    @include('admin.includes.sidebar')
    <!--  Sidebar End -->
    <!--  Main wrapper -->
    <div class="body-wrapper">
      <!--  Header Start -->
     @include('admin.includes.header')
      <!--  Header End -->
      <div class="body-wrapper-inner">
        @yield('contents')
      </div>
    </div>
  </div>
@include('admin.includes.js-plguins')
 @stack('custom-js')
   @livewireScripts
   @vite(['resources/js/app.js'])

   <script>
   document.getElementById("sidebarToggleBtn").addEventListener("click", function () {
    const sidebar = document.querySelector(".left-sidebar");
    const bodyWrapper = document.querySelector(".body-wrapper");

    // push style
    if (sidebar.classList.contains("collapsed") || sidebar.classList.contains("active")) {
      sidebar.classList.remove("collapsed", "active");
      bodyWrapper.classList.remove("expanded");
      document.getElementById('app-header').classList.remove('w-100')
    } else {
      sidebar.classList.add("collapsed", "active");
      bodyWrapper.classList.add("expanded");
      document.getElementById('app-header').classList.add('w-100')
    }
  });
   </script>
</body>

</html>