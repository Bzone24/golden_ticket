 <header class="app-header" id="app-header">
        <nav class="navbar navbar-expand-lg navbar-light">
          <button id="sidebarToggleBtn" class="btn btn-outline-primary me-3">
            <i class="ti ti-menu-2"></i>
          </button>
          <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
<li class="nav-item me-3">
    @include('partials._wallet_balance')
</li>

               
              <li class="nav-item dropdown">
                <a class="nav-link d-flex " href="javascript:void(0)" id="drop2" data-bs-toggle="dropdown"
                  aria-expanded="false">
                    <span class="me-2 d-none d-lg-inline-block">
                     <strong> <span class="text-dark">
                        {{ Auth::user()->name }}
                         {{-- {{ Auth::user()->email }} --}}
 (Username: {{ Auth::user()->username }})
            {{-- show first role if any --}}
             (Role: {{ Auth::user()->getRoleNames()->first() ?? 'User' }})
                      </span></strong>
                      <i class="ti ti-chevron-down text-dark"></i>
                    </span>
                  <img src="{{asset('assets/images/profile/user-1.jpg')}}" alt="" width="35" height="35" class="rounded-circle">
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="drop2">
                  <div class="message-body">
                    <a href="javascript:void(0)" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-user fs-6"></i>
                      <p class="mb-0 fs-3">Profile</p>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
    @csrf
    <button type="submit" class="d-flex align-items-center gap-2 dropdown-item bg-transparent border-0">
        <i class="ti ti-lock fs-6"></i>
        <p class="mb-0 fs-3">Logout</p>
    </button>
</form>
                    {{-- <a href="javascript:void(0)" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-mail fs-6"></i>
                      <p class="mb-0 fs-3">My Account</p>
                    </a>
                    <a href="javascript:void(0)" class="d-flex align-items-center gap-2 dropdown-item">
                      <i class="ti ti-list-check fs-6"></i>
                      <p class="mb-0 fs-3">My Task</p>
                    </a> --}}
                    {{-- <a href="./authentication-login.html" class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a> --}}
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </nav>
      </header>