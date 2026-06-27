<?php
// public/esign/assinar_process.php - Assinatura ICP-Brasil via módulo esign (REFATORADO)
require_once '../../core/init.php';
require_once '../../core/assinatura_digital.php';
require_once '../../helpers/pdf_signer.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';
require_once PROJECT_ROOT . '/core/email.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: ../login.php'); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: ../documentos.php'); 
    exit(); 
}

$documento_id = filter_input(INPUT_POST, 'documento_id', FILTER_VALIDATE_INT);
$senha_certificado = $_POST['senha_certificado'] ?? '';
$carimbar = isset($_POST['carimbar']) ? 1 : 0;
$stamp_pos = $_POST['stamp_pos'] ?? 'br';
$stamp_size = $_POST['stamp_size'] ?? 'md';
$stamp_page = $_POST['stamp_page'] ?? 'last';
$usuario_id = (int)$_SESSION['user_id'];
$usuario_nome = $_SESSION['user_name'] ?? 'Usuário';
// LGPD: validar consentimento se exigido
if (lgpd_is_consent_required($pdo)) {
    if (!isset($_POST['lgpd_consent']) || $_POST['lgpd_consent'] !== '1') {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Você deve concordar com a Política de Privacidade para prosseguir.'];
        header('Location: index.php?id=' . ($documento_id ?? 0));
        exit();
    }
}

// Validação básica
if (!$documento_id || empty($senha_certificado)) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Documento e senha do certificado são obrigatórios.'];
    header('Location: index.php?id=' . ($documento_id ?? 0));
    exit();
}

try {
    // Resolve origem do certificado: upload (preferência) ou PFX já vinculado no perfil
    $certificado_temp = null;
    if (isset($_FILES['certificado']) && $_FILES['certificado']['error'] === UPLOAD_ERR_OK) {
        $certificado_temp = tempnam(sys_get_temp_dir(), 'cert_');
        if (!move_uploaded_file($_FILES['certificado']['tmp_name'], $certificado_temp)) {
            throw new Exception('Erro ao processar certificado enviado');
        }
    } else {
        // tenta buscar no cadastro do usuário
        try {
            $chk = $pdo->query("SHOW TABLES LIKE 'usuario_certificados'");
            if ($chk && $chk->rowCount() > 0) {
                $s = $pdo->prepare('SELECT caminho_pfx FROM usuario_certificados WHERE usuario_id = ? AND ativo = 1 ORDER BY id DESC LIMIT 1');
                $s->execute([$usuario_id]);
                $rel = $s->fetchColumn();
                if ($rel) {
                    $orig = PROJECT_ROOT . '/public/' . ltrim($rel, '/');
                    if (is_file($orig)) {
                        $certificado_temp = tempnam(sys_get_temp_dir(), 'cert_');
                        if (!copy($orig, $certificado_temp)) {
                            $certificado_temp = null; // força erro mais abaixo
                        }
                    }
                }
            }
        } catch (Throwable $e) { /* ignora */ }
        if (!$certificado_temp) {
            throw new Exception('Nenhum certificado informado. Anexe um .pfx/.p12 ou vincule um PFX no seu perfil.');
        }
    }

    // Usa a classe centralizada de assinatura digital ICP-Brasil
    $assinatura = new AssinaturaDigital($pdo, $usuario_id);
    $stampOpts = [
        'page' => in_array($stamp_page, ['first','last','all']) ? $stamp_page : 'last',
        'position' => in_array($stamp_pos, ['br','bl','tr','tl']) ? $stamp_pos : 'br',
        'size' => in_array($stamp_size, ['sm','md','lg']) ? $stamp_size : 'md',
        'headerColor' => [0,123,255],
        'style' => 'usp'
    ];
    $res = $assinatura->assinarDocumento($documento_id, $certificado_temp, $senha_certificado, (bool)$carimbar, $stampOpts);

    // Remove o certificado temporário
    if (file_exists($certificado_temp)) {
        unlink($certificado_temp);
    }

    // Compatibilidade legada já tratada dentro da classe; atualiza nome do signatário para exibição
    try {
        if (!empty($res['verificador'])) {
            $up = $pdo->prepare("UPDATE assinaturas SET nome_signatario = ? WHERE verificador = ?");
            $up->execute([$usuario_nome, $res['verificador']]);
        }
    } catch (Throwable $e) { /* opcional */ }

    // LGPD: anotar consentimento no registro mais recente da assinatura
    // E-mail de confirmação para o signatário (estilo eDok, com nossa marca)
    try {
        // Busca e-mail do usuário
        $q = $pdo->prepare('SELECT email FROM usuarios WHERE id = ?');
        $q->execute([$usuario_id]);
        $user_email = $q->fetchColumn();

        if ($user_email && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
            // Busca título do documento
            $qd = $pdo->prepare('SELECT titulo FROM documentos WHERE id = ?');
            $qd->execute([$documento_id]);
            $doc_titulo = $qd->fetchColumn() ?: ('Documento #' . (int)$documento_id);

            // Decide se podemos exibir IP (LGPD)
            $ip_info = null;
            if (function_exists('lgpd_log_ips') && lgpd_log_ips($pdo)) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $hostname = $ip ? @gethostbyaddr($ip) : '';
                $ip_info = $ip . ($hostname && $hostname !== $ip ? (' (' . $hostname . ')') : '');
            }

            $dados_email = [
                'nome' => $usuario_nome,
                'cpf_cnpj' => '',
                'qualificacao' => 'Responsável',
                'localizacao' => 'Não fornecida',
                'assinatura_info' => 'Fornecida',
                'ip_info' => $ip_info ?: 'N/D',
                'verificador' => (string)($res['verificador'] ?? ''),
                'documento' => (string)$doc_titulo,
                'documento_id' => (int)$documento_id,
                'chave' => substr((string)($res['verificador'] ?? ''), 0, 8),
                'link_verificacao' => (string)($res['verification_url'] ?? '')
            ];

            // Envia e-mail; falhas não interrompem o fluxo
            if (function_exists('email_send_template')) {
                @email_send_template($pdo, $user_email, 'assinatura_confirmada', $dados_email);
            }
        }
    } catch (Throwable $e) {
        error_log('Falha ao enviar email de confirmação de assinatura: ' . $e->getMessage());
    }

    try {
        $stmt = $pdo->prepare("UPDATE documentos_assinaturas SET detalhes = JSON_SET(COALESCE(detalhes,'{}'), '$.lgpd_consent', JSON_OBJECT('agreed', true, 'timestamp', NOW())) WHERE documento_id = ? ORDER BY data_assinatura DESC LIMIT 1");
        $stmt->execute([$documento_id]);
    } catch (Throwable $e) { /* silencioso */ }

    // Registra no log (se a tabela existir)
    try {
        $sql = "INSERT INTO log_sistema (usuario_id, acao, tabela, registro_id, detalhes) 
                VALUES (?, 'assinar_icp_esign', 'documentos', ?, 'Documento assinado via módulo esign com ICP-Brasil')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario_id, $documento_id]);
    } catch (Throwable $e) {
        // Tabela de log pode não existir; evitar quebrar o fluxo de assinatura
        error_log('esign/assinar_process: log_sistema indisponível - ' . $e->getMessage());
    }

    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Documento assinado com sucesso! ' . ($carimbar ? 'Carimbo visual aplicado. ' : 'Sem carimbo visual. ') . 'Link de verificação: ' . ($res['verification_url'] ?? '')];
    header('Location: index.php?id=' . $documento_id);
    exit();

} catch (Exception $e) {
    if (isset($certificado_temp) && file_exists($certificado_temp)) {
        unlink($certificado_temp);
    }
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao assinar: ' . $e->getMessage()];
    header('Location: index.php?id=' . ($documento_id ?? 0));
    exit();
}
?>