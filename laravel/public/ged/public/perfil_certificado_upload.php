<?php
// Upload e vínculo de certificado PFX por usuário
require_once '../core/init.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: perfil_editar.php'); exit(); }

$userId = (int)$_SESSION['user_id'];
if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Selecione um arquivo .pfx/.p12 válido.'];
    header('Location: perfil_editar.php'); exit();
}
$senha = $_POST['senha'] ?? '';
if ($senha === '') { $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Informe a senha do certificado.']; header('Location: perfil_editar.php'); exit(); }

// Pasta dedicada do usuário
$base = PROJECT_ROOT . '/public/uploads/pfx';
if (!is_dir($base)) { @mkdir($base, 0755, true); }
$userDir = $base . '/user_' . $userId;
if (!is_dir($userDir)) { @mkdir($userDir, 0700, true); }

$tmpPath = $userDir . '/cert_' . time() . '.pfx';
if (!move_uploaded_file($_FILES['certificado']['tmp_name'], $tmpPath)) {
    $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Falha ao salvar o arquivo. Verifique permissões.'];
    header('Location: perfil_editar.php'); exit();
}

// Extração de informações básicas do certificado
$subjectCN = $issuerCN = $validFrom = $validTo = $thumb = null;
try {
    // Tenta abrir o PFX e extrair o X509
    $p12 = file_get_contents($tmpPath);
    $certs = [];
    if (@openssl_pkcs12_read($p12, $certs, $senha)) {
        if (!empty($certs['cert'])) {
            $x = openssl_x509_parse($certs['cert']);
            $subjectCN = $x['subject']['CN'] ?? null;
            $issuerCN = $x['issuer']['CN'] ?? null;
            if (isset($x['validFrom_time_t'])) { $validFrom = date('Y-m-d H:i:s', (int)$x['validFrom_time_t']); }
            if (isset($x['validTo_time_t'])) { $validTo = date('Y-m-d H:i:s', (int)$x['validTo_time_t']); }
            // thumbprint
            $der = null; @openssl_x509_export($certs['cert'], $pem);
            if ($pem) { $res = openssl_x509_read($pem); $der = $pem; }
            $thumb = strtoupper(hash('sha1', preg_replace('/\s+/', '', $pem)));
        }
    } else {
        // senha inválida ou PFX corrompido
        @unlink($tmpPath);
        $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Senha do certificado incorreta ou arquivo inválido.'];
        header('Location: perfil_editar.php'); exit();
    }
} catch (Throwable $e) {
    @unlink($tmpPath);
    $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Erro ao processar o certificado: '.$e->getMessage()];
    header('Location: perfil_editar.php'); exit();
}

// Persiste registro
try {
    // garante tabela
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuario_certificados (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        caminho_pfx VARCHAR(255) NOT NULL,
        subject_cn VARCHAR(255) NULL,
        issuer_cn VARCHAR(255) NULL,
        valid_from DATETIME NULL,
        valid_to DATETIME NULL,
        thumbprint VARCHAR(128) NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (usuario_id), INDEX (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // marca anteriores como inativos
    $pdo->prepare('UPDATE usuario_certificados SET ativo = 0 WHERE usuario_id = ?')->execute([$userId]);
    // guarda caminho relativo
    $relPath = str_replace(PROJECT_ROOT . '/public/', '', $tmpPath);
    $ins = $pdo->prepare('INSERT INTO usuario_certificados (usuario_id, caminho_pfx, subject_cn, issuer_cn, valid_from, valid_to, thumbprint, ativo) VALUES (?,?,?,?,?,?,?,1)');
    $ins->execute([$userId, $relPath, $subjectCN, $issuerCN, $validFrom, $validTo, $thumb]);

    $_SESSION['flash_message'] = ['type'=>'sucesso','text'=>'Certificado vinculado com sucesso.'];
} catch (Throwable $e) {
    $_SESSION['flash_message'] = ['type'=>'erro','text'=>'Erro ao salvar certificado: '.$e->getMessage()];
}

header('Location: perfil.php');
exit();
