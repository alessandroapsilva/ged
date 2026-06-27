<?php
/**
 * Página de Teste SMTP - ENFAS GED
 * Acesso restrito a administradores
 * 
 * Esta página permite:
 * 1. Configurar SMTP via interface
 * 2. Testar envio de e-mail
 * 3. Ver logs de envio
 */
require_once '../core/init.php';

// Somente administradores
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Processar teste de envio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['testar_email'])) {
    $email_teste = filter_var($_POST['email_teste'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if ($email_teste) {
        try {
            // Dados para o template de teste
            $dados = [
                'nome_usuario' => $_SESSION['nome'] ?? 'Administrador',
                'mensagem_teste' => 'Este é um e-mail de teste enviado pelo sistema ENFAS GED.',
                'data_hora' => date('d/m/Y H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ];
            
            // Tenta enviar usando template 'usuario_criado' como teste
            require_once PROJECT_ROOT . '/core/email.php';
            $sucesso = email_send_template($pdo, $email_teste, 'usuario_criado', $dados, ['smtp_debug' => 0]);
            
            if ($sucesso) {
                $mensagem = "✅ E-mail enviado com sucesso para: {$email_teste}";
                $tipo_mensagem = 'success';
            } else {
                $mensagem = "❌ Falha ao enviar e-mail. Verifique as configurações SMTP e os logs.";
                $tipo_mensagem = 'danger';
            }
        } catch (Exception $e) {
            $mensagem = "❌ Erro: " . htmlspecialchars($e->getMessage());
            $tipo_mensagem = 'danger';
            error_log("Erro teste SMTP: " . $e->getMessage());
        }
    } else {
        $mensagem = "❌ E-mail inválido!";
        $tipo_mensagem = 'warning';
    }
}

// Processar salvamento de configurações SMTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_smtp'])) {
    require_once PROJECT_ROOT . '/core/email.php';
    
    $configs = [
        'smtp_host' => $_POST['smtp_host'] ?? '',
        'smtp_port' => $_POST['smtp_port'] ?? '587',
        'smtp_user' => $_POST['smtp_user'] ?? '',
        'smtp_secure' => $_POST['smtp_secure'] ?? 'tls',
        'mail_from' => $_POST['mail_from'] ?? '',
        'mail_from_name' => $_POST['mail_from_name'] ?? 'ENFAS GED'
    ];
    
    // NÃO salvar senha no banco - usar variável de ambiente
    if (!empty($_POST['smtp_pass'])) {
        $mensagem .= "<br><strong>⚠️ ATENÇÃO:</strong> A senha SMTP deve ser configurada via variável de ambiente <code>GED_SMTP_PASS</code> no Apache/XAMPP, não no banco de dados!";
    }
    
    $sucesso_total = true;
    foreach ($configs as $chave => $valor) {
        if (!app_setting_set($pdo, $chave, $valor)) {
            $sucesso_total = false;
        }
    }
    
    if ($sucesso_total) {
        $mensagem = "✅ Configurações SMTP salvas com sucesso!" . ($mensagem ?? '');
        $tipo_mensagem = 'success';
    } else {
        $mensagem = "⚠️ Algumas configurações não puderam ser salvas.";
        $tipo_mensagem = 'warning';
    }
}

// Buscar configurações atuais
require_once PROJECT_ROOT . '/core/email.php';
$smtp_host = app_setting_get($pdo, 'smtp_host', defined('SMTP_HOST') ? SMTP_HOST : '');
$smtp_port = app_setting_get($pdo, 'smtp_port', defined('SMTP_PORT') ? SMTP_PORT : '587');
$smtp_user = app_setting_get($pdo, 'smtp_user', defined('SMTP_USER') ? SMTP_USER : '');
$smtp_secure = app_setting_get($pdo, 'smtp_secure', defined('SMTP_SECURE') ? SMTP_SECURE : 'tls');
$mail_from = app_setting_get($pdo, 'mail_from', defined('MAIL_FROM') ? MAIL_FROM : '');
$mail_from_name = app_setting_get($pdo, 'mail_from_name', defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'ENFAS GED');

// Buscar últimos 10 logs de e-mail
$stmt = $pdo->query("SELECT * FROM emails_log ORDER BY created_at DESC LIMIT 10");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste SMTP - ENFAS GED</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1d3441 0%, #2b3f4c 100%); min-height: 100vh; padding: 2rem 0; }
        .container { max-width: 1200px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); margin-bottom: 1.5rem; }
        .card-header { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; font-weight: 600; border-radius: 12px 12px 0 0 !important; padding: 1rem 1.25rem; }
        .badge-success { background: #10b981; }
        .badge-danger { background: #ef4444; }
        .btn-primary { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25); }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2 class="text-white"><i class="fas fa-envelope-open-text"></i> Teste de Configuração SMTP</h2>
            <p class="text-white-50">ENFAS GED - Sistema de E-mails</p>
        </div>

        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
            <?= $mensagem ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Card: Configurações SMTP -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-cog"></i> Configurações SMTP
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Servidor SMTP</label>
                            <input type="text" class="form-control" name="smtp_host" value="<?= htmlspecialchars($smtp_host) ?>" placeholder="smtp.gmail.com">
                            <small class="text-muted">Ex: smtp.gmail.com, smtp.office365.com, mail.dominio.com.br</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Porta</label>
                            <input type="number" class="form-control" name="smtp_port" value="<?= htmlspecialchars($smtp_port) ?>" placeholder="587">
                            <small class="text-muted">587 (TLS) ou 465 (SSL)</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuário SMTP</label>
                            <input type="text" class="form-control" name="smtp_user" value="<?= htmlspecialchars($smtp_user) ?>" placeholder="seu-email@dominio.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Senha SMTP</label>
                            <input type="password" class="form-control" name="smtp_pass" placeholder="(usar variável de ambiente)">
                            <small class="text-muted">⚠️ Configure via <code>GED_SMTP_PASS</code> no Apache</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Segurança</label>
                            <select class="form-select" name="smtp_secure">
                                <option value="tls" <?= $smtp_secure === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= $smtp_secure === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">E-mail Remetente</label>
                            <input type="email" class="form-control" name="mail_from" value="<?= htmlspecialchars($mail_from) ?>" placeholder="noreply@ged.enfas.com.br">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Nome Remetente</label>
                            <input type="text" class="form-control" name="mail_from_name" value="<?= htmlspecialchars($mail_from_name) ?>" placeholder="ENFAS GED">
                        </div>
                    </div>

                    <button type="submit" name="salvar_smtp" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Configurações
                    </button>
                </form>
            </div>
        </div>

        <!-- Card: Testar Envio -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-paper-plane"></i> Enviar E-mail de Teste
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-9 mb-3">
                            <label class="form-label">E-mail de Destino</label>
                            <input type="email" class="form-control" name="email_teste" placeholder="seu-email@exemplo.com" required>
                            <small class="text-muted">Enviaremos um e-mail de teste para este endereço</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="testar_email" class="btn btn-primary w-100">
                                <i class="fas fa-flask"></i> Testar Envio
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card: Templates Disponíveis -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-code"></i> Templates Cadastrados
            </div>
            <div class="card-body">
                <?php
                $templates = $pdo->query("SELECT slug, nome, ativo FROM email_templates ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="row">
                    <?php foreach ($templates as $tpl): ?>
                    <div class="col-md-4 mb-2">
                        <span class="badge <?= $tpl['ativo'] ? 'badge-success' : 'badge-secondary' ?>">
                            <?= $tpl['ativo'] ? '✓' : '✗' ?>
                        </span>
                        <code><?= htmlspecialchars($tpl['slug']) ?></code>
                        <small class="text-muted">- <?= htmlspecialchars($tpl['nome']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Card: Logs de Envio -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Últimos 10 Envios
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Destinatário</th>
                                <th>Template</th>
                                <th>Status</th>
                                <th>Erro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhum envio registrado</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($log['destinatario']) ?></td>
                                    <td><code><?= htmlspecialchars($log['template_slug'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <span class="badge <?= $log['status'] === 'sucesso' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $log['status'] === 'sucesso' ? '✓ Enviado' : '✗ Falhou' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($log['erro']): ?>
                                            <small class="text-danger"><?= htmlspecialchars(substr($log['erro'], 0, 80)) ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Botão Voltar -->
        <div class="text-center">
            <a href="index.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left"></i> Voltar ao Sistema
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
