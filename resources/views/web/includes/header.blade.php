<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ url('/') }}">GameTicketHub</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav me-3">
                @if(Auth::check())
                    <li class="nav-item ms-2 text-white d-flex align-items-center">
                        <strong><span class="me-2">
                            {{ Auth::user()->name }}
                           (Username: {{ Auth::user()->username }})
                            (Role: {{ Auth::user()->getRoleNames()->first() ?? 'User' }})
                        </span></strong>
                        @include('partials._wallet_balance')
                    </li>
                @endif

                @if (Auth::check())
                    <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a></li>
                @endif
                <li class="nav-item"><a class="nav-link" href="#">About</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Contact</a></li>
            </ul>

            @if (Auth::check())
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-light">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-outline-light">Login</a>
            @endif
        </div>
    </div>
</nav>
