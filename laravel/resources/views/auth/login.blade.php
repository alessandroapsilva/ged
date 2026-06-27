<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ENFAS GED | Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/plugins/fontawesome-free/css/all.min.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; height: 100vh; margin:0; background: radial-gradient(ellipse at top, #1d3441 0%, #243746 50%, #2b3f4c 100%); position: relative; }
        .login-container { height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; z-index: 1; }
        .logo-hero { text-align: center; margin-bottom: 20px; }
        .logo-hero img { height: 120px; width: auto; filter: drop-shadow(0 3px 8px rgba(0,0,0,.25)); }
        .login-card { background: white; border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,.28); padding: 26px 30px; width: 100%; max-width: 420px; }
        .form-group { margin-bottom: 14px; }
        .input-wrapper { position: relative; }
        .input-wrapper input { width: 100%; padding: 12px 44px 12px 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
        .input-wrapper i { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
        .btn-primary { background: #2563eb; border: none; color: #fff; padding: 10px 16px; border-radius: 8px; width:100%; font-weight: 600; cursor:pointer; }
        .btn-primary:hover { background:#1e4fd1; }
        .error { color:#b91c1c; font-size: 12px; margin-top:6px; }
    </style>
    <script> if (localStorage.getItem('theme')==='gray'){ document.documentElement.classList.add('theme-gray'); } </script>
</head>
<body>
<div class="login-container">
    <div class="logo-hero">
        <img src="/assets/dist/img/logo_enfasged.svg" alt="ENFAS GED">
    </div>
    <div class="login-card">
        <div class="login-header" style="text-align:center;margin-bottom:16px;">
            <h2>Bem-vindo</h2>
            <p style="color:#666;font-size:12px;">Acesse sua conta para continuar</p>
        </div>
        <form method="post" action="/login">
            @csrf
            <div class="form-group">
                <label for="email">E-mail ou usuário</label>
                <div class="input-wrapper">
                    <input type="text" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required>
                    <i class="fas fa-user"></i>
                </div>
                @error('email')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <i class="fas fa-lock"></i>
                </div>
                @error('password')<div class="error">{{ $message }}</div>@enderror
            </div>
            <button class="btn-primary" type="submit">Entrar</button>
        </form>
    </div>
    <div style="margin-top:14px;color:#e5e7eb;font-size:12px;">© {{ date('Y') }} ENFAS</div>
    @if ($errors->any())
      <script>setTimeout(()=>{ alert('Falha no login: {{ implode("; ", $errors->all()) }}'); }, 100);</script>
    @endif
  </div>
</body>
</html>

