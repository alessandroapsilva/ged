<?php
// public/documentos_compartilhar_process.php (VERSÃO COM CAMINHO DO ANEXO CORRIGIDO)
require_once '../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';
require_once PROJECT_ROOT . '/helpers/share_user_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header('Location: documentos.php'); exit();
}
if (!csrf_validate()) { $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Sessão expirada. Recarregue a página.']; header('Location: documentos.php'); exit(); }

try {
    $documento_id = $_POST['documento_id'] ?? null;
    $emails_str = $_POST['emails'] ?? '';
    $users_raw = trim($_POST['users'] ?? '');
    $view_only = isset($_POST['view_only']);
    $can_download = isset($_POST['can_download']);
    $expires_in = trim($_POST['expires_at'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $aceite = isset($_POST['aceite_responsabilidade']);
    $remetente_nome = $_SESSION['user_name'] ?? 'Usuário do Sistema';

    if (!$aceite) { throw new Exception('É necessário aceitar a responsabilidade de compartilhamento.'); }

    $emails_sujos = preg_split('/[\s,;]+/', $emails_str);
    $emails_limpos = [];
    foreach ($emails_sujos as $email) {
        if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            $emails_limpos[] = trim($email);
        }
    }
    
    if (!$documento_id) { throw new Exception('ID de documento inválido.'); }

    $stmt = $pdo->prepare("SELECT titulo, caminho_arquivo, pasta_id FROM documentos WHERE id = ?");
    $stmt->execute([(int)$documento_id]);
    $documento = $stmt->fetch();

    if (!$documento) { throw new Exception("Documento com ID {$documento_id} não encontrado."); }

    // ##### CORREÇÃO PRINCIPAL AQUI #####
    // O caminho para os uploads é relativo à pasta 'public', que é onde este script está.
    // Vamos usar um caminho relativo a partir do PROJECT_ROOT para encontrar a pasta de uploads corretamente.
    $caminho_anexo = PROJECT_ROOT . '/public/' . ltrim($documento['caminho_arquivo'], '/\\');

    if (!file_exists($caminho_anexo)) { 
        throw new Exception("Arquivo físico não encontrado no servidor no caminho: " . $caminho_anexo); 
    }

    // Compartilhamento interno por usuário (opcional)
    $okShares = 0; $errShares = 0; $mailErrInt = 0;
    $expires_at = null;
    if ($expires_in !== '') {
        $exp_norm = str_replace('T',' ', $expires_in);
        $dt = DateTime::createFromFormat('Y-m-d H:i', $exp_norm) ?: DateTime::createFromFormat('Y-m-d H:i:s', $exp_norm);
        if ($dt && $dt > new DateTime()) { $expires_at = $dt->format('Y-m-d H:i:s'); }
    }
    if ($users_raw !== '') {
        $ids = array_filter(array_map('trim', preg_split('/[\s,;]+/', $users_raw)), 'strlen');
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (count($ids) > 0) {
            // Busca e-mails dos usuários para notificação opcional
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stU = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id IN ($ph)");
            $stU->execute($ids);
            $usuarios = $stU->fetchAll(PDO::FETCH_ASSOC);
            foreach ($usuarios as $u) {
                $res = share_user_create($pdo, (int)$documento_id, (int)$u['id'], (int)$_SESSION['user_id'], (bool)$view_only, (bool)$can_download, $expires_at, $message);
                if ($res['ok']) {
                    $okShares++;
                    // Notificação interna
                    try {
                        $msgNotif = 'Você recebeu acesso ao documento "' . (string)$documento['titulo'] . '".';
                        $tipoNotif = 'compartilhamento';
                        $stn = $pdo->prepare("INSERT INTO workflow_notificacoes (workflow_documento_id, usuario_id, tipo, mensagem) VALUES (NULL, ?, ?, ?)");
                        $stn->execute([(int)$u['id'], $tipoNotif, $msgNotif]);
                    } catch (Throwable $e) {}
                    // E-mail para usuário interno (opcional)
                    if (!empty($u['email']) && function_exists('email_send_template')) {
                        try {
                            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                            // URL limpa sem expor /public e sem extensão
                            $base = defined('BASE_URL') ? BASE_URL : '/ged';
                            $url = sprintf('%s://%s%s/documentos_ver?id=%d', $scheme, $host, $base, (int)$documento_id);
                            $data = [ 'nome' => (string)$u['nome'], 'titulo' => (string)$documento['titulo'], 'url' => $url, 'mensagem' => $message, 'validade' => $expires_at ? date('d/m/Y H:i', strtotime($expires_at)) : 'Sem expiração' ];
                            $ok = email_send_template($pdo, $u['email'], 'compartilhar_interno', $data);
                            if (!$ok) { $mailErrInt++; }
                        } catch (Throwable $e) { $mailErrInt++; }
                    }
                } else { $errShares++; }
            }
        }
    }

    // E-mails externos (opcional)
    $erros_email = 0;
    foreach ($emails_limpos as $email_destinatario) {
        $dados_email = [
            'assunto_email'   => 'Compartilhamento de Documento: ' . $documento['titulo'],
            'nome_documento'  => $documento['titulo'],
            'nome_remetente'  => $remetente_nome
        ];
        // Preferir template quando disponível
        $sent = false;
        if (function_exists('email_send_template')) {
            $sent = email_send_template($pdo, $email_destinatario, 'compartilhar_documento', [ 'titulo' => $documento['titulo'], 'mensagem' => $remetente_nome ], [ 'attachments' => [[ 'path' => $caminho_anexo, 'name' => $documento['titulo'] . '.pdf' ]] ]);
        }
        if (!$sent) {
            if (function_exists('enviar_email')) {
                if (!enviar_email($email_destinatario, '', 'compartilhar_documento', $dados_email, $caminho_anexo, $documento['titulo'] . '.pdf')) {
                    $erros_email++;
                }
            } else {
                // Se não houver fallback, considera erro para este destinatário
                $erros_email++;
            }
        }
    }
    
    $redirect_url = 'documentos.php' . ($documento['pasta_id'] ? '?pasta_id=' . $documento['pasta_id'] : '');
    
    $msgs = [];
    if ($okShares>0 || $errShares>0) { $msgs[] = sprintf('Interno: %d ok, %d falha(s)%s', $okShares, $errShares, $mailErrInt?", $mailErrInt e-mail(s) falharam":""); }
    if (!empty($emails_limpos)) { $msgs[] = $erros_email>0 ? 'E-mails externos: com falhas' : 'E-mails externos: ok'; }
    $_SESSION['flash_message'] = ['type' => ($erros_email||$errShares ? 'alerta' : 'sucesso'), 'text' => 'Compartilhamento processado. ' . implode(' | ', $msgs)];
    header('Location: ' . $redirect_url);
    exit();

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro: ' . $e->getMessage()];
    header('Location: documentos_compartilhar.php?id=' . (int)($documento_id ?? 0));
    exit();
}