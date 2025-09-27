<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - GameTicketHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font for body -->
  <link href="https://fonts.googleapis.com/css2?family=Segoe+UI&display=swap" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(#111111, #1a1a1a);
      color: #fff;
      font-family: 'Segoe UI', sans-serif;
    }
    .login-card {
      background-color: #1f1f1f;
      border: 1px solid #333;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
    }
    .form-control {
      background-color: #2a2a2a;
      border: 1px solid #555;
      color: #fff;
    }
    .form-control:focus {
      border-color: #FFD700;
      box-shadow: none;
    }
    .btn-gold {
      background-color: #FFD700;
      color: #000;
      font-weight: 600;
    }
    .btn-gold:hover {
      background-color: #e5c100;
    }
    .logo-text {
      font-size: 2rem;
      font-weight: bold;
      color: #FFD700;
    }
  </style>
</head>
<body>

  <div class="container d-flex align-items-center justify-content-center vh-100">
    <div class="col-md-5">
      <div class="text-center mb-4">
        <div class="logo-text">GameTicketHub</div>
        <p class="text-muted">Login to continue booking your favorite games</p>
      </div>
      <div class="login-card">
        <form method="post" action="{{ route('login') }}">
            @csrf
          <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" value="{{ old('email') }}" name="email" placeholder="Enter email">
             @error('email')
            <span class="text-danger">{{$message}}</span>
            @enderror
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" name="password" id="password" placeholder="Password">
            @error('password')
            <span class="text-danger">{{$message}}</span>
            @enderror
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Remember me</label>
          </div>
          <button type="submit" class="btn btn-gold w-100">Login</button>
          {{-- <div class="text-center mt-3">
            <small class="text-muted">Don't have an account? <a href="#" class="text-warning">Sign up</a></small>
          </div> --}}
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
