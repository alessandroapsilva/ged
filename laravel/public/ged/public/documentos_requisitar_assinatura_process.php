<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: documentos.php');
    exit();
}

$documento_id = $_POST['documento_id'] ?? null;
$emails_str = $_POST['emails'] ?? '';
$usuarios_ids = isset($_POST['usuarios']) && is_array($_POST['usuarios']) ? array_filter(array_map('intval', $_POST['usuarios'])) : [];
$requisitante_id = $_SESSION['user_id'];

// 1. Limpa e valida a lista de e-mails
$emails_sujos = preg_split('/[\s,;]+/', $emails_str); // Divide a string por vírgulas, ponto e vírgulas ou espaços/novas linhas
$emails_limpos = [];
foreach ($emails_sujos as $email) {
    $email_sanitizado = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if ($email_sanitizado) {
        $emails_limpos[] = $email_sanitizado;
    }
}
// Acrescenta e-mails de usuários internos
$usuarios_emails = [];
if (!empty($usuarios_ids)) {
    try {
        $place = implode(',', array_fill(0, count($usuarios_ids), '?'));
        $st = $pdo->prepare("SELECT id, email FROM usuarios WHERE status='ativo' AND id IN ($place)");
        $st->execute($usuarios_ids);
        while ($u = $st->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($u['email']) && filter_var($u['email'], FILTER_VALIDATE_EMAIL)) {
                $usuarios_emails[] = strtolower(trim($u['email']));
            }
        }
    } catch (Throwable $e) { /* silencioso */ }
}

$emails_unicos = array_values(array_unique(array_merge($emails_limpos, $usuarios_emails)));

// 2. Verifica se há dados válidos para prosseguir
if (!$documento_id || empty($emails_unicos)) {
    header('Location: documentos_requisitar_assinatura.php?id=' . (int)$documento_id . '&erro=dados_invalidos');
    exit();
}

try {
    $pdo->beginTransaction();

    $stmtDoc = $pdo->prepare("SELECT titulo FROM documentos WHERE id = ?");
    $stmtDoc->execute([(int)$documento_id]);
    $documento_titulo = $stmtDoc->fetchColumn();

    if (!$documento_titulo) {
        throw new Exception("Documento não encontrado.");
    }

    $stmtInsert = $pdo->prepare(
        "INSERT INTO assinaturas (documento_id, requisitante_id, email_signatario, token, status) VALUES (?, ?, ?, ?, 'pendente')"
    );

    $erros_email = 0;

    // 3. Loop para cada e-mail válido
    foreach ($emails_unicos as $email_signatario) {
        $token = bin2hex(random_bytes(32));

        // Insere no banco de dados
        $stmtInsert->execute([(int)$documento_id, $requisitante_id, $email_signatario, $token]);

        // Prepara os dados para o template de e-mail
        $dados_para_template = [
            'assunto_email'   => 'Requisição de Assinatura: ' . $documento_titulo,
            'nome_documento'  => $documento_titulo,
            'link_assinatura' => "http://localhost/ged/public/esign/verificar.php?token=" . $token
        ];

        // Envia o e-mail usando sua função helper
        if (!enviar_email($email_signatario, '', 'requisitar_assinatura', $dados_para_template)) {
            $erros_email++;
            error_log("Falha ao enviar e-mail de assinatura para {$email_signatario}");
        }
    }

    $pdo->commit();

    // Notificações internas para usuários selecionados
    if (!empty($usuarios_ids)) {
        try {
            $sn = $pdo->prepare("INSERT INTO workflow_notificacoes (usuario_id, tipo, mensagem, data_envio, lida) VALUES (?, 'assinatura', ?, NOW(), 0)");
            $msgNotif = 'Assinatura requisitada: ' . (string)$documento_titulo;
            foreach ($usuarios_ids as $uid) { $sn->execute([(int)$uid, $msgNotif]); }
        } catch (Throwable $e) { /* silencioso */ }
    }

    if ($erros_email > 0) {
        header('Location: documentos.php?alerta=parcialmente_enviado');
    } else {
        header('Location: documentos.php?sucesso=requisicoes_enviadas');
    }
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro em requisitar_assinatura_process.php: " . $e->getMessage());
    header('Location: documentos_requisitar_assinatura.php?id=' . (int)$documento_id . '&erro=processamento');
    exit();
}