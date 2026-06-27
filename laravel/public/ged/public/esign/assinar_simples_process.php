<?php
// public/esign/assinar_simples_process.php - Assinatura Simples centralizada no esign
require_once '../../core/init.php';
require_once '../../helpers/pdf_signer.php';

if (!isset($_SESSION['user_id'])) { 
    http_response_code(403);
    exit('Acesso negado');
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { throw new Exception('Método inválido'); }

    $documento_id = (int)($_POST['documento_id'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $usuario_id = (int)$_SESSION['user_id'];
    $carimbar = isset($_POST['carimbar']) ? 1 : 0;
    $usuario_nome = $_SESSION['user_name'] ?? 'Usuário';

    // Busca documento
    $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ? AND apagado_em IS NULL");
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$documento) { throw new Exception('Documento não encontrado'); }

    $caminho_original = PROJECT_ROOT . '/public/' . $documento['caminho_arquivo'];
    if (!file_exists($caminho_original)) { throw new Exception('Arquivo físico não encontrado'); }

    // Obter imagem de assinatura (arquivo upload ou data URI)
    $imagem_assinatura_path = null;
    $upload_dir = PROJECT_ROOT . '/public/storage/assinaturas/';
    if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0755, true); }

    if ($carimbar && !empty($_POST['assinatura_data'])) {
        $imagem_assinatura_path = $upload_dir . uniqid('sig_') . '.png';
        $img_data = explode(',', $_POST['assinatura_data']);
        $img_data = end($img_data);
        file_put_contents($imagem_assinatura_path, base64_decode($img_data));
    } elseif ($carimbar && !empty($_FILES['assinatura_arquivo'])) {
        if ($_FILES['assinatura_arquivo']['error'] === UPLOAD_ERR_OK) {
            $imagem_assinatura_path = $upload_dir . uniqid('sig_') . '.png';
            if (!move_uploaded_file($_FILES['assinatura_arquivo']['tmp_name'], $imagem_assinatura_path)) {
                throw new Exception('Falha ao salvar a imagem de assinatura (permissões ou diretório temporário).');
            }
        } else {
            switch ($_FILES['assinatura_arquivo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new Exception('A imagem da assinatura excede o limite permitido (ajuste upload_max_filesize/post_max_size no php.ini).');
                case UPLOAD_ERR_NO_FILE:
                    throw new Exception('Nenhuma imagem de assinatura foi selecionada.');
                case UPLOAD_ERR_NO_TMP_DIR:
                    throw new Exception('Diretório temporário ausente no servidor (configure upload_tmp_dir no php.ini).');
                case UPLOAD_ERR_CANT_WRITE:
                    throw new Exception('Falha ao gravar o arquivo temporário (permissões de pasta).');
                default:
                    throw new Exception('Falha ao enviar a imagem de assinatura (erro ' . ($_FILES['assinatura_arquivo']['error']) . ').');
            }
        }
    } else {
        // Sem carimbo: não exige imagem
        $imagem_assinatura_path = null;
    }

    // Destino do PDF assinado
    $nome_assinado = pathinfo($documento['caminho_arquivo'], PATHINFO_FILENAME) . '_assinado_' . time() . '.pdf';
    $caminho_assinado = 'storage/uploads/' . $nome_assinado;
    $caminho_assinado_completo = PROJECT_ROOT . '/public/' . $caminho_assinado;

    // Verificador e URL de verificação centralizada
    $verificador = hash('sha256', $documento_id . microtime(true) . random_bytes(8));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $verificationUrl = sprintf('%s://%s/ged/public/esign/verificar.php?code=%s', $scheme, $host, $verificador);

    // QR Code temporário (apenas se for carimbar)
    $qrPng = null;
    if ($carimbar) {
        $qrLibPaths = [
            PROJECT_ROOT . '/libraries/phpqrcode/qrlib.php',
            PROJECT_ROOT . '/public/libraries/phpqrcode/qrlib.php'
        ];
        foreach ($qrLibPaths as $qrLib) {
            if (file_exists($qrLib)) {
                require_once $qrLib;
                $qrPng = $upload_dir . 'qr_' . substr(hash('sha1', $verificationUrl), 0, 12) . '.png';
                try { \QRcode::png($verificationUrl, $qrPng, QR_ECLEVEL_L, 4, 2); } catch (\Throwable $e) { $qrPng = null; }
                break;
            }
        }
    }

    // Carimbo visual no PDF
    $texto_extra = $usuario_nome . "\n" . date('d/m/Y H:i:s');
    if ($motivo) { $texto_extra .= "\nMotivo: " . $motivo; }
    $texto_extra .= "\nVerifique: " . $verificationUrl . "\nCódigo: " . substr($verificador, 0, 12) . '...';

    if ($carimbar) {
        // Carrega helper se ainda não estiver disponível
        if (!class_exists('PDFSigner')) {
            require_once PROJECT_ROOT . '/helpers/pdf_signer.php';
        }
        $stamp_pos = $_POST['stamp_pos'] ?? 'br';
        $stamp_size = $_POST['stamp_size'] ?? 'md';
        $stamp_page = $_POST['stamp_page'] ?? 'last';
        $ok = PDFSigner::signWithProfessionalStamp($caminho_original, $caminho_assinado_completo, [
            'page' => in_array($stamp_page, ['first','last','all']) ? $stamp_page : 'last',
            'position' => in_array($stamp_pos, ['br','bl','tr','tl']) ? $stamp_pos : 'br',
            'size' => in_array($stamp_size, ['sm','md','lg']) ? $stamp_size : 'md',
            'headerColor' => [0,123,255],
            'style' => 'usp',
            'imagePath' => $imagem_assinatura_path,
            'qrPath' => $qrPng,
            'title' => 'Assinado eletronicamente',
            'lines' => array_values(array_filter(array_map('trim', explode("\n", $texto_extra))))
        ]);
        if (!$ok) { throw new Exception('Erro ao aplicar assinatura visual no PDF'); }
    }

    // Atualiza documento
    if ($carimbar) {
        $stmt = $pdo->prepare("UPDATE documentos SET caminho_arquivo = ?, assinado = 1, data_assinatura = NOW(), assinado_por = ? WHERE id = ?");
        $stmt->execute([$caminho_assinado, $usuario_id, $documento_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE documentos SET assinado = 1, data_assinatura = NOW(), assinado_por = ? WHERE id = ?");
        $stmt->execute([$usuario_id, $documento_id]);
    }

    // Detalhes
    $detalhes = [
        'tipo' => 'Simples',
        'usuario_nome' => $usuario_nome,
        'data' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'motivo' => $motivo,
        'imagem_assinatura' => $imagem_assinatura_path ? str_replace(PROJECT_ROOT . '/public/', '', $imagem_assinatura_path) : null,
        'verificador' => $verificador,
        'verification_url' => $verificationUrl,
        'hash' => $carimbar ? hash_file('sha256', $caminho_assinado_completo) : hash_file('sha256', $caminho_original),
        'carimbo_visual' => (bool)$carimbar
    ];

    // Insert nova tabela
    $stmt = $pdo->prepare("INSERT INTO documentos_assinaturas (documento_id, usuario_id, data_assinatura, tipo_assinatura, detalhes) VALUES (?, ?, NOW(), 'Simples', ?)");
    $stmt->execute([$documento_id, $usuario_id, json_encode($detalhes, JSON_UNESCAPED_UNICODE)]);

    // Compat legada `assinaturas`
    try {
        $chk = $pdo->query("SHOW TABLES LIKE 'assinaturas'");
        if ($chk && $chk->rowCount() > 0) {
            $versaoId = null;
            try {
                $s = $pdo->prepare("SELECT id FROM documento_versoes WHERE documento_id = ? ORDER BY versao DESC, id DESC LIMIT 1");
                $s->execute([$documento_id]);
                $versaoId = $s->fetchColumn() ?: null;
            } catch (\Throwable $e) {}
            $legacy = $pdo->prepare("INSERT INTO assinaturas (documento_id, versao_id, usuario_id, nome_signatario, ip_assinatura, verificador, data_assinatura, status) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'assinado')");
            $legacy->execute([$documento_id, $versaoId, $usuario_id, $usuario_nome, $_SERVER['REMOTE_ADDR'] ?? null, $verificador]);
        }
    } catch (\Throwable $e) { error_log('esign/assinar_simples_process: insert legado falhou - ' . $e->getMessage()); }

    // Log
    try {
        $stmt = $pdo->prepare("INSERT INTO log_sistema (usuario_id, acao, tabela, registro_id, detalhes) VALUES (?, 'assinar_simples_esign', 'documentos', ?, 'Assinatura simples via esign')");
        $stmt->execute([$usuario_id, $documento_id]);
    } catch (\Throwable $e) {}

    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Documento assinado (Simples) com sucesso! ' . ($carimbar ? 'Carimbo visual aplicado.' : 'Sem carimbo visual.')];
    // Permanece na mesma página para visualizar o resultado
    header('Location: index.php?id=' . $documento_id);
    exit();

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro: ' . $e->getMessage()];
    header('Location: index.php?id=' . ($documento_id ?? 0));
    exit();
}
