<?php
// Login estilo eDok - GED System
require_once '../core/init.php';

// Se já estiver logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: documentos');
    exit();
}

$brandName = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
$brandPrimary = defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#2563eb';
$brandAccent = defined('BRAND_ACCENT_COLOR') ? BRAND_ACCENT_COLOR : '#3b82f6';
$brandLogo = defined('BRAND_LOGO') ? BRAND_LOGO : (BASE_URL . '/assets/dist/img/logo_enfasged.svg');
$brandSlogan = defined('BRAND_SLOGAN') ? BRAND_SLOGAN : 'Gestão Eletrônica de Documentos';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($brandName) ?> | Login</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --brand-primary: <?= $brandPrimary ?>;
            --brand-accent: <?= $brandAccent ?>;
            --brand-orange: #f38b3b;
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
            margin-bottom: 20px;
            animation: fadeInDown 0.6s ease-out;
        }
        
        .logo-hero img {
            height: 120px; /* proporção semelhante ao eDok */
            width: auto;
            background: transparent;
            filter: drop-shadow(0 3px 8px rgba(0, 0, 0, 0.25));
            transition: all 0.3s ease;
        }
        
        .logo-hero img:hover {
            transform: translateY(-2px);
            filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.3));
        }
        
        .info-banner {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 13px;
            line-height: 1.5;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            animation: fadeInDown 0.8s ease-out;
            max-width: 600px;
        }
        
        .info-banner strong {
            font-weight: 700;
        }
        
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.28);
            padding: 26px 30px;
            width: 100%;
            max-width: 420px; /* similar ao card do eDok */
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
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
            margin-bottom: 16px;
        }
        
        .login-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .login-header p {
            color: #666;
            font-size: 11px;
        }
        
        .form-group {
            margin-bottom: 14px;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 12px 44px 12px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.25s ease;
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            margin-bottom: 4px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            padding-left: 24px;
            user-select: none;
            font-size: 11px;
            color: #555;
        }
        
        .checkbox-container input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkmark {
            position: absolute;
            left: 0;
            height: 15px;
            width: 15px;
            background-color: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .checkbox-container:hover .checkmark {
            border-color: var(--brand-primary);
        }
        
        .checkbox-container input:checked ~ .checkmark {
            background-color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
        
        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 2px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }
        
        .checkbox-label {
            font-weight: 500;
        }

        /* Responsivo: logo e card no mobile */
        @media (max-width: 576px) {
            .logo-hero img { height: 90px; }
            .login-card { max-width: 92vw; padding: 22px 24px; }
        }
        
        .forgot-link {
            color: var(--brand-primary);
            text-decoration: none;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .forgot-link:hover {
            color: var(--brand-accent);
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-accent));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 16px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(243, 139, 59, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            animation: slideInDown 0.35s ease-out;
            font-size: 11px;
        }
        
        .alert-error {
            background: #fff3e0;
            border-left: 3px solid #ffcc80;
            color: #e65100;
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
            color: rgba(255, 255, 255, 0.75);
            font-size: 12px;
            margin-top: 18px;
            animation: fadeInUp 0.6s ease-out;
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
                margin: 12px;
                padding: 20px 20px;
                max-width: 320px;
            }
            
            .logo-hero {
                margin-bottom: 16px;
            }
            
            .logo-hero img {
                height: 60px;
            }
            
            .login-header h2 {
                font-size: 17px;
            }
            
            .input-wrapper input {
                padding: 9px 36px 9px 11px;
                font-size: 12px;
            }
            .btn-login {
                padding: 9px;
                font-size: 12px;
                margin-top: 14px;
            }
            .footer-text {
                font-size: 11px;
                margin-top: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-hero">
            <img src="<?= htmlspecialchars($brandLogo) ?>" alt="<?= htmlspecialchars($brandName) ?>">
        </div>
        
        <?php if (defined('IS_DEMO') && IS_DEMO): ?>
        <div class="info-banner">
            Este <strong>ambiente de demonstração</strong> é refeito diariamente às 1:17 (BRT), quando as alterações são perdidas.
        </div>
        <?php endif; ?>
        
        <div class="login-card">
            <div class="login-header">
                <h2>Informe seus Dados de Acesso</h2>
                <p>Entre com suas credenciais para acessar o sistema</p>
            </div>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>
                        <?php
                        switch ($_GET['error']) {
                            case 'rate':
                                echo 'Muitas tentativas. Aguarde alguns minutos e tente novamente.';
                                break;
                            default:
                                echo 'Usuário ou senha inválidos.';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login_process.php" id="loginForm">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required 
                            autocomplete="username"
                            placeholder="Digite seu usuário"
                        >
                        <i class="far fa-envelope"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            required 
                            autocomplete="current-password"
                            placeholder="Digite sua senha"
                        >
                        <i class="fas fa-lock"></i>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        <span class="checkbox-label">Lembrar-me</span>
                    </label>
                    <a href="esqueci_senha.php" class="forgot-link">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>
        </div>
        
        <div class="footer-text">
            O ENFAS GED utiliza cookies e registros individualizados.<br>
            Saiba mais na <a href="privacidade_publica.php">Política de Privacidade</a>.
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                const btn = $('.btn-login');
                btn.html('<i class="fas fa-spinner fa-spin"></i> Entrando...');
                btn.prop('disabled', true);
            });
        });
    </script>
</body>
</html>
