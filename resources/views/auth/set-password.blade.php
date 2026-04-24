<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Imposta password - Rapportini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .login-container { width: 100%; max-width: 420px; padding: 15px; }
        .login-card {
            background: white; border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white; padding: 40px 30px; text-align: center;
        }
        .login-header h1 { font-size: 24px; font-weight: 700; margin: 0 0 8px 0; }
        .login-header p { margin: 0; opacity: 0.9; font-size: 14px; }
        .login-body { padding: 32px 30px; }
        .form-control {
            border: 2px solid #e9ecef; border-radius: 10px;
            padding: 12px 15px; font-size: 15px;
        }
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }
        .btn-primary-custom {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border: none; border-radius: 10px; color: white;
            font-weight: 600; font-size: 16px; padding: 14px; width: 100%;
        }
        .btn-primary-custom:hover { color: white; box-shadow: 0 10px 20px rgba(220,38,38,0.4); }
        .alert { border-radius: 10px; border: none; padding: 12px 16px; font-size: 14px; }
        .alert-danger { background-color: #fee; color: #c33; }
        .invalid-feedback { display: block; font-size: 13px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock" style="font-size: 44px; margin-bottom: 8px;"></i>
                <h1>Imposta la tua password</h1>
                <p>Scegli la password per accedere a Rapportini</p>
            </div>
            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-exclamation-circle me-1"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('password.set.store', ['token' => $token]) }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label small text-muted">Email</label>
                        <input type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email"
                               value="{{ old('email', $email) }}"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small text-muted">Nuova password</label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password"
                               required minlength="6" autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small text-muted">Conferma password</label>
                        <input type="password"
                               class="form-control"
                               id="password_confirmation" name="password_confirmation"
                               required minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary-custom">
                        <i class="bi bi-check-circle me-2"></i>Imposta password
                    </button>
                </form>

                <div class="text-center mt-4">
                    <a href="{{ route('login') }}" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left me-1"></i>Torna al login
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
