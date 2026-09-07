<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Universal Brothers</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="admin-login-page">
        <div>
            <div class="admin-login-card">
                <div class="admin-login-brand">
                    <span class="admin-brand-mark">UB</span>
                    <h1>Universal Brothers Administration</h1>
                    <p>Sign in to manage packages, content and inquiries.</p>
                </div>

                <div class="admin-login-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small" role="alert">
                            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.login') }}" id="admin-login-form">
                        @csrf
                        <div class="mb-3">
                            <label for="login-email" class="form-label">Email</label>
                            <input type="email" name="email" id="login-email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label for="login-password" class="form-label">Password</label>
                            <div class="password-toggle-wrap">
                                <input type="password" name="password" id="login-password" class="form-control" required autocomplete="current-password">
                                <button type="button" id="toggle-password" aria-label="Show password" aria-pressed="false">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-check mb-4">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me on this device</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="login-submit">
                            <span id="login-submit-label">Login</span>
                        </button>
                    </form>
                </div>
            </div>
            <p class="admin-login-footer">Universal Brothers (Private) Limited &middot; Internal CMS Access Only</p>
        </div>
    </div>

    <script>
        (function () {
            const toggle = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('login-password');
            toggle.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(isPassword));
                toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                toggle.querySelector('i').className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
            });

            document.getElementById('admin-login-form').addEventListener('submit', function () {
                const btn = document.getElementById('login-submit');
                btn.disabled = true;
                document.getElementById('login-submit-label').innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Logging In…';
            });
        })();
    </script>
</body>
</html>
