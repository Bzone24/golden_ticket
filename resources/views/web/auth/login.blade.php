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

    /* small style for the eye button */
    .btn-eye {
      border: 1px solid #555;
      background: #2a2a2a;
      color: #fff;
      height: 38px;
      width: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .input-group .form-control {
      border-right: 0;
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }
    .input-group .btn-eye {
      border-left: 0;
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
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

          <!-- Username / Login ID -->
          <div class="mb-3">
            <label for="login" class="form-label">Username or Login ID</label>
            <input
              type="text"
              name="login"
              id="login"
              value="{{ old('login') }}"
              class="form-control"
              placeholder="username or ABC101"
              autocomplete="username"
              required
              autofocus
              aria-describedby="loginHelp"
            >
            @error('login')
              <div class="text-danger mt-1"><i class="fa fa-exclamation-triangle text-danger"></i>
                {{ $message }}
              </div>
            @enderror
          </div>

          <!-- Password with toggle -->
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>

            <div class="input-group">
              <input
                type="password"
                class="form-control"
                name="password"
                id="password"
                placeholder="Password"
                required
                autocomplete="current-password"
                aria-label="Password"
              >

              <button type="button" class="btn btn-eye" id="togglePassword" tabindex="-1" aria-label="Toggle password visibility">
                <!-- Eye (visible) and eye-slash (hidden) svgs; we'll swap innerHTML in JS -->
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>
                  <path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" fill="#000"/>
                </svg>
              </button>
            </div>

            @error('password')
              <span class="text-danger">{{ $message }}</span>
            @enderror
          </div>

          <div class="mb-3 form-check">
            <input
              type="checkbox"
              class="form-check-input"
              id="remember"
              name="remember"
              value="1"
              {{ old('remember') ? 'checked' : '' }}
            >
            <label class="form-check-label" for="remember">Remember me</label>
          </div>

          <button type="submit" class="btn btn-gold w-100">Login</button>

          {{-- optional signup hint
          <div class="text-center mt-3">
            <small class="text-muted">Don't have an account? <a href="#" class="text-warning">Sign up</a></small>
          </div>
          --}}

        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const toggle = document.getElementById('togglePassword');
      const input = document.getElementById('password');
      const eyeIcon = document.getElementById('eyeIcon');

      if (!toggle || !input || !eyeIcon) return;

      const eyeSVG = {
        visible: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/><path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" fill="#000"/></svg>',
        hidden: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M13.359 11.238C14.134 10.35 15 8 15 8s-3-5.5-8-5.5c-1.048 0-1.99.19-2.841.457l.912.91C6.02 4.033 6.979 4 7.5 4c4.5 0 8 5.5 8 5.5 0 .5-.214 1.138-.641 1.738l-1.5-0.0zM3.646 2.646a.5.5 0 0 0-.708.708l1.768 1.768C3.557 6.065 2.83 6.9 2 8s1 2.935 3 4c1 .5 2.3.344 3.354-.354l1.768 1.768a.5.5 0 0 0 .708-.708L3.646 2.646z"/></svg>'
      };

      // initial state: password hidden (type=password), show eye (visible icon)
      eyeIcon.innerHTML = eyeSVG.visible;

      toggle.addEventListener('click', function () {
        if (input.type === 'password') {
          input.type = 'text';
          eyeIcon.innerHTML = eyeSVG.hidden;
        } else {
          input.type = 'password';
          eyeIcon.innerHTML = eyeSVG.visible;
        }
      });
    });
  </script>
</body>
</html>
