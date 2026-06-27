<?php
require_once '../core/init.php';

if (isset($_SESSION['user_id'])) {
    header('Location: documentos');
    exit();
}

$brandName = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
$brandPrimary = defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#2563eb';
$brandAccent = defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#3b82f6';
$brandLogo = defined('BRAND_LOGO') ? BRAND_LOGO : (BASE_URL . '/assets/dist/img/logo_enfasged.svg');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($brandName) ?> | Recuperar Senha</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --brand-primary: <?= $brandPrimary ?>;
            --brand-accent: <?= $brandAccent ?>;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            height: 100vh;
            overflow: hidden;
            background: radial-gradient(ellipse at top, #1d3441 0%, #243746 50%, #2b3f4c 100%);
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(29, 52, 65, 0.9) 0%, rgba(43, 63, 76, 0.7) 100%);
            pointer-events: none;
        }
        
        .login-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        
        .logo-hero {
            text-align: center;
            margin-bottom: 36px;
            animation: fadeInDown 0.8s ease-out;
        }
        
        .logo-hero img {
            height: 100px;
            width: auto;
            background: transparent;
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.25));
            transition: all 0.3s ease;
        }
        
        .logo-hero img:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.3));
        }
        
        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
            padding: 32px 36px;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 24px;
        }
        
        .login-header i {
            font-size: 48px;
            color: var(--brand-primary);
            margin-bottom: 16px;
        }
        
        .login-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 6px;
        }
        
        .login-header p {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 6px;
            font-size: 13px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 12px 44px 12px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        
        .input-wrapper i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 16px;
        }
        
        .input-wrapper input:focus + i {
            color: var(--brand-primary);
        }
        
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-secondary {
            width: 100%;
            padding: 13px;
            background: transparent;
            color: var(--brand-primary);
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            border-color: var(--brand-primary);
            background: rgba(37, 99, 235, 0.05);
        }
        
        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInDown 0.4s ease-out;
            font-size: 13px;
        }
        
        .alert-success {
            background: #e8f5e9;
            border-left: 3px solid #4caf50;
            color: #2e7d32;
        }
        
        .alert i {
            font-size: 16px;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .footer-text {
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin-top: 24px;
            animation: fadeInUp 1s ease-out;
        }
        
        .footer-text a {
            color: var(--brand-accent);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .footer-text a:hover {
            color: white;
            text-decoration: underline;
        }
        
        @media (max-width: 640px) {
            .login-card {
                margin: 16px;
                padding: 28px 24px;
                max-width: 360px;
            }
            
            .logo-hero {
                margin-bottom: 28px;
            }
            
            .logo-hero img {
                height: 80px;
            }
            
            .login-header h2 {
                font-size: 18px;
            }
            
            .login-header i {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-hero">
            <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>">
        </div>
        
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-key"></i>
                <h2>Recuperar Senha</h2>
                <p>Digite seu e-mail e enviaremos instruções para redefinir sua senha</p>
            </div>
            
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span>Se o e-mail estiver em nosso sistema, um link de recuperação foi enviado!</span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="esqueci_senha_process.php" id="forgotForm">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            autofocus
                            autocomplete="email"
                            placeholder="seu@email.com"
                        >
                        <i class="far fa-envelope"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-paper-plane"></i>
                    Enviar Link de Recuperação
                </button>
                
                <a href="login.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar ao Login
                </a>
            </form>
        </div>
        
        <div class="footer-text">
            O ENFAS GED utiliza cookies e registros individualizados.<br>
            Saiba mais na <a href="privacidade.php">Política de Privacidade</a>.
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#forgotForm').on('submit', function(e) {
                const btn = $('.btn-login');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
                btn.prop('disabled', true);
            });
        });
    </script>
</body>
</html>
