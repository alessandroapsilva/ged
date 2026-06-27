<?php
require_once '../core/init.php';

$token = $_GET['token'] ?? null;
$token_valido = false;
$mensagem_erro = '';

if ($token) {
    $stmt = $pdo->prepare("SELECT id, reset_expires FROM usuarios WHERE reset_token = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        if (new DateTime() > new DateTime($usuario['reset_expires'])) {
            $mensagem_erro = "Este link de recuperação de senha expirou. Por favor, solicite um novo.";
        } else {
            $token_valido = true;
        }
    } else {
        $mensagem_erro = "Link de recuperação inválido ou já utilizado.";
    }
} else {
    $mensagem_erro = "Nenhum token de recuperação fornecido.";
}

// AÇÃO: A linha "define('BASE_URL', ...)" foi REMOVIDA daqui, pois o init.php já faz isso.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GED System | Redefinir Senha</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
    <div class="login-logo">
    <a href="login">
            <img src="<?= BASE_URL ?>/assets/dist/img/logo_enfasged.svg" alt="Logo do Sistema" style="width: 280px;">
        </a>
    </div>
    
    <div class="card card-outline card-primary">
        <div class="card-body">

            <?php if (isset($_GET['erro'])): ?>
                <div class="alert alert-warning text-center p-2">
                    <?php 
                        switch ($_GET['erro']) {
                            case 'nao_conferem':
                                echo 'As senhas digitadas não conferem.';
                                break;
                            case 'curta':
                                echo 'A senha deve ter no mínimo 8 caracteres.';
                                break;
                            default:
                                echo 'Ocorreu um erro. Tente novamente.';
                                break;
                        }
                    ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valido): ?>
                <p class="login-box-msg">Você está a um passo de criar sua nova senha.</p>
                <form action="redefinir_senha_process" method="post">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="input-group mb-3">
                        <input type="password" name="nova_senha" class="form-control" placeholder="Nova Senha" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="confirmar_senha" class="form-control" placeholder="Confirme a Nova Senha" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Alterar Senha</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-danger text-center">
                    <h4><i class="icon fas fa-ban"></i> Erro!</h4>
                    <?= htmlspecialchars($mensagem_erro) ?>
                </div>
                <p class="mt-3 mb-1 text-center">
                    <a href="esqueci_senha">Tentar novamente</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/plugins/jquery/jquery.min.js"></script>
<script src="<?= BASE_URL ?>/assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/dist/js/adminlte.min.js"></script>
</body>
</html>