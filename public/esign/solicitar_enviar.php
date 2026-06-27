<?php
require_once '../../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../documentos.php'); exit(); }
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

$documento_id = isset($_POST['documento_id']) ? (int)$_POST['documento_id'] : 0;
$emails_raw = trim((string)($_POST['emails'] ?? ''));
$mensagem = trim((string)($_POST['mensagem'] ?? ''));
if ($documento_id<=0 || $emails_raw==='') { $_SESSION['flash_message']=['type'=>'erro','text'=>'Dados inválidos.']; header('Location: ../documentos.php'); exit(); }

// Documento
$doc = $pdo->prepare('SELECT id, titulo FROM documentos WHERE id = ? AND apagado_em IS NULL');
$doc->execute([$documento_id]);
$d = $doc->fetch(PDO::FETCH_ASSOC);
if (!$d) { $_SESSION['flash_message']=['type'=>'erro','text'=>'Documento não encontrado.']; header('Location: ../documentos.php'); exit(); }

$emails = array_filter(array_map('trim', preg_split('/[,;\n]+/', $emails_raw)));
$okCount = 0; $fail = [];
$link = BASE_URL . '/esign/index.php?id=' . $documento_id;

// Tenta tabela de convites (opcional)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS documentos_assinaturas_convites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        documento_id INT NOT NULL,
        email VARCHAR(190) NOT NULL,
        token VARCHAR(64) DEFAULT NULL,
        mensagem TEXT NULL,
        enviado_em DATETIME NULL,
        status VARCHAR(32) DEFAULT 'enviado'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) {}

foreach ($emails as $to) {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $fail[] = $to; continue; }
    try {
        $render = email_render_template($pdo, 'requisitar_assinatura', [
            'nome_documento' => $d['titulo'],
            'titulo' => $d['titulo'],
            'link_assinatura' => $link,
            'url' => $link,
            'mensagem' => $mensagem,
        ]);
        $mail = ged_mailer();
        $mail->addAddress($to);
        $mail->Subject = $render['assunto'];
        $mail->Body = $render['html'];
        $mail->AltBody = $render['texto'];
        $mail->send();
        $okCount++;
        try {
            $ins = $pdo->prepare('INSERT INTO documentos_assinaturas_convites (documento_id, email, token, mensagem, enviado_em, status) VALUES (?,?,?,?,NOW(),?)');
            $token = bin2hex(random_bytes(16));
            $ins->execute([$documento_id, $to, $token, $mensagem, 'enviado']);
        } catch (Throwable $e) {}
    } catch (Throwable $e) {
        $fail[] = $to;
    }
}

$_SESSION['flash_message'] = [
    'type' => ($okCount>0 && count($fail)===0) ? 'sucesso' : 'aviso',
    'text' => ($okCount>0 ? ("Convites enviados: $okCount.") : 'Nenhum convite enviado.') . (count($fail)? ' Falhou: '.implode(', ',$fail):'')
];
header('Location: ../documentos_propriedades.php?id=' . $documento_id);
exit();

