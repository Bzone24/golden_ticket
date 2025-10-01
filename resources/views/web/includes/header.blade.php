<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
  <div class="container">
    <a class="navbar-brand fw-bold" href="{{ url('/') }}">GameTicketHub</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse fw-bold" id="navbarNav">
      <ul class="navbar-nav me-auto">
        @if (Auth::check())
          <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
          </li>
        @endif
        {{-- <li class="nav-item"><a class="nav-link" href="#">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#">Contact</a></li> --}}
      </ul>

      <ul class="navbar-nav align-items-center">
        {{-- wallet / other small items can stay here --}}
        @if(Auth::check())
            <li class="nav-item me-3 d-none d-lg-flex align-items-center">
                @include('partials._wallet_balance')
                <strong> <span class="text-light">
                        {{ Auth::user()->login_id }} </span>
            </li>
             
        @endif

        {{-- Profile image dropdown (right corner) --}}
        @if(Auth::check())
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
               role="button" data-bs-toggle="dropdown" aria-expanded="false">
              {{-- avatar (small circular) --}}
              <img
                src="{{ Auth::user()->profile_photo_url ?? ( 'https://www.gravatar.com/avatar/' . md5(strtolower(trim(Auth::user()->email))) . '?s=80&d=mp' ) }}"
                alt="{{ Auth::user()->name }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;border:2px solid rgba(255,255,255,0.15);">
            </a>

            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown" style="min-width: 220px;">
              <li class="px-3 py-2">
                <div class="d-flex align-items-center">
                  <img
                    src="{{ Auth::user()->profile_photo_url ?? ( 'https://www.gravatar.com/avatar/' . md5(strtolower(trim(Auth::user()->email))) . '?s=80&d=mp' ) }}"
                    alt="{{ Auth::user()->name }}" class="rounded-circle me-2" style="width:48px;height:48px;object-fit:cover;">
                  <div>
                    <div class="fw-bold">{{ Auth::user()->name }}</div>
                    <div class="text-muted small">Username: {{ Auth::user()->username }}</div>
                  </div>
                </div>
              </li>

              <li><hr class="dropdown-divider"></li>

              <li class="px-3">
                <div class="small text-muted mb-1">Role</div>
                <div class="fw-semibold mb-2">{{ Auth::user()->getRoleNames()->first() ?? 'User' }}</div>
              </li>

              <li><hr class="dropdown-divider"></li>

              <li class="px-3">
                {{-- Logout form inside dropdown --}}
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="btn btn-outline-danger w-100">Logout</button>
                </form>
              </li>
            </ul>
          </li>
        @else
          {{-- Guest: show Login button at right --}}
          <li class="nav-item">
            <a class="btn btn-outline-light" href="{{ route('login') }}">Login</a>
          </li>
        @endif
      </ul>
    </div>
  </div>
</nav>
