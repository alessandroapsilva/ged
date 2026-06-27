<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    try {
        $st = $pdo->prepare('SELECT senha FROM usuarios WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $hash = $st->fetchColumn();
        if ($hash && password_verify($senha, (string)$hash)) {
            $pdo->prepare('UPDATE usuarios SET twofa_enabled = 0, twofa_secret = NULL WHERE id = ?')->execute([(int)$_SESSION['user_id']]);
            $_SESSION['flash_message'] = ['type'=>'sucesso','text'=>'2FA desativada para sua conta.'];
            header('Location: perfil.php');
            exit();
        } else {
            $error = 'Senha incorreta';
        }
    } catch (Throwable $e) {
        $error = 'Erro: '.$e->getMessage();
    }
}
include '../templates/header.php'; include '../templates/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header"><div class="container-fluid"><h1>Desativar 2FA</h1></div></section>
    <section class="content"><div class="container-fluid">
        <div class="card card-outline card-danger">
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <p>Confirme sua senha para desativar a verificação em duas etapas desta conta.</p>
                <form method="post"><div class="form-group"><label>Senha</label><input type="password" name="senha" class="form-control" required></div><button type="submit" class="btn btn-danger">Desativar 2FA</button> <a href="perfil.php" class="btn btn-secondary">Cancelar</a></form>
            </div>
        </div>
    </div></section>
</div>
<?php include '../templates/footer.php'; ?>
