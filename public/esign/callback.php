<?php
// public/esign/callback.php - retorno do provedor ICP em nuvem (esqueleto genérico)
require_once '../../core/init.php';
require_once PROJECT_ROOT . '/helpers/lgpd_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit(); }

$code = $_GET['code'] ?? '';
$stateRaw = $_GET['state'] ?? '';
$state = ['doc'=>null,'uid'=>null,'ts'=>null];
try { $state = json_decode(base64_decode($stateRaw), true) ?: $state; } catch (Throwable $e) {}
$docId = isset($state['doc']) ? (int)$state['doc'] : 0;

// Valida configuração mínima
function get_setting($k,$def=''){ try{ $st=$GLOBALS['pdo']->prepare('SELECT valor FROM app_settings WHERE chave=?'); $st->execute([$k]); $r=$st->fetch(PDO::FETCH_ASSOC); return $r['valor'] ?? $def; }catch(Throwable $e){ return $def; } }
$provider = get_setting('SIGN_PROVIDER','local');
$base = get_setting('CLOUD_ICP_BASE_URL','');
$cid = get_setting('CLOUD_ICP_CLIENT_ID','');
$secret = get_setting('CLOUD_ICP_CLIENT_SECRET','');
$cb = get_setting('CLOUD_ICP_CALLBACK_URL','');
// Bird ID
$birdAuth = get_setting('BIRD_AUTH_URL','');
$birdToken = get_setting('BIRD_TOKEN_URL','');
$birdSign = get_setting('BIRD_SIGN_URL','');
$birdClient = get_setting('BIRD_CLIENT_ID','');
$birdSecret = get_setting('BIRD_CLIENT_SECRET','');
$birdCallback = get_setting('BIRD_CALLBACK_URL','');

if ($docId <= 0 || $code === '') {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Retorno inválido: documento ou código ausente.'];
    header('Location: index.php?id=' . max(0,$docId));
    exit();
}

// Se for Bird ID, efetua token exchange e tentativa de assinatura PAdES
if ($provider === 'birdid') {
    if ($birdToken === '' || $birdClient === '' || $birdCallback === '') {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Bird ID não está completamente configurado.'];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    // 1) Trocar code por access_token
    $ch = curl_init($birdToken);
    $post = http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $birdCallback,
        'client_id' => $birdClient,
        'client_secret' => $birdSecret,
    ]);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    $tokResp = curl_exec($ch);
    $tokErr = curl_error($ch);
    $tokStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($tokResp === false || $tokStatus >= 400) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao obter token do Bird ID: ' . ($tokErr ?: ('HTTP ' . $tokStatus))];
        header('Location: index.php?id=' . $docId);
        exit();
    }
    $tok = json_decode($tokResp, true) ?: [];
    $accessToken = $tok['access_token'] ?? '';
    if ($accessToken === '') {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Retorno do Bird ID sem access_token.'];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    // 2) Buscar PDF do documento
    try {
        $st = $pdo->prepare('SELECT caminho_arquivo FROM documentos WHERE id = ?');
        $st->execute([$docId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['caminho_arquivo'])) { throw new Exception('Documento não encontrado.'); }
        $origRel = $row['caminho_arquivo'];
        $origAbs = PROJECT_ROOT . '/public/' . $origRel;
        if (!is_file($origAbs)) { throw new Exception('Arquivo do documento não encontrado.'); }
    } catch (Throwable $e) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao localizar PDF: ' . $e->getMessage()];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    // 3) Chamar endpoint de assinatura do Bird ID (padrão multipart). A estrutura exata pode variar por provedor.
    // Tentativa genérica: enviar arquivo como campo 'file'.
    if ($birdSign === '') {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'URL de assinatura (Bird ID) não configurada.'];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    $ch2 = curl_init($birdSign);
    $cfile = new CURLFile($origAbs, 'application/pdf', basename($origAbs));
    $payload = [ 'file' => $cfile ];
    curl_setopt_array($ch2, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ]
    ]);
    $signResp = curl_exec($ch2);
    $signErr = curl_error($ch2);
    $signStatus = (int)curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $ct = curl_getinfo($ch2, CURLINFO_CONTENT_TYPE) ?: '';
    curl_close($ch2);

    if ($signResp === false || $signStatus >= 400) {
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Falha ao solicitar assinatura no Bird ID: ' . ($signErr ?: ('HTTP ' . $signStatus))];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    // 4) Se a resposta for PDF, gravar arquivo e atualizar documento; caso contrário, tentar interpretar JSON.
    $isPdf = stripos($ct, 'application/pdf') !== false || preg_match('/%PDF-/', $signResp) === 1;
    $novoRel = null; $hash = null; $providerDetails = [];
    if ($isPdf) {
        $outDir = PROJECT_ROOT . '/public/uploads/documentos/assinado_cloud/';
        if (!is_dir($outDir)) { @mkdir($outDir, 0755, true); }
        $outAbs = $outDir . 'doc_' . $docId . '_signed_' . date('Ymd_His') . '.pdf';
        if (@file_put_contents($outAbs, $signResp) === false) {
            $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Não foi possível gravar o PDF assinado.'];
            header('Location: index.php?id=' . $docId);
            exit();
        }
        $novoRel = str_replace(PROJECT_ROOT . '/public/', '', $outAbs);
        $hash = hash_file('sha256', $outAbs);
    } else {
        // Tenta decodificar JSON de sucesso com link
        $j = json_decode($signResp, true);
        $providerDetails = is_array($j) ? $j : ['raw' => substr($signResp, 0, 500)];
        if (isset($j['signedPdfBase64'])) {
            $pdfData = base64_decode($j['signedPdfBase64'], true);
            if ($pdfData !== false) {
                $outDir = PROJECT_ROOT . '/public/uploads/documentos/assinado_cloud/';
                if (!is_dir($outDir)) { @mkdir($outDir, 0755, true); }
                $outAbs = $outDir . 'doc_' . $docId . '_signed_' . date('Ymd_His') . '.pdf';
                @file_put_contents($outAbs, $pdfData);
                if (is_file($outAbs)) {
                    $novoRel = str_replace(PROJECT_ROOT . '/public/', '', $outAbs);
                    $hash = hash_file('sha256', $outAbs);
                }
            }
        }
    }

    if ($novoRel) {
        // Atualiza documento e registra assinatura
        try {
            $pdo->beginTransaction();
            $up = $pdo->prepare('UPDATE documentos SET caminho_arquivo = ?, assinado = 1, data_assinatura = NOW(), assinado_por = ? WHERE id = ?');
            $up->execute([$novoRel, (int)$_SESSION['user_id'], $docId]);

            $verificador = hash('sha256', $docId . microtime(true) . random_bytes(8));
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $verificationUrl = sprintf('%s://%s/ged/public/esign/verificar.php?code=%s', $scheme, $host, $verificador);
            $det = [
                'provider' => 'birdid',
                'hash' => $hash,
                'verificador' => $verificador,
                'verification_url' => $verificationUrl,
                'modo' => 'cloud_pades',
                'provider_response' => $providerDetails,
            ];
            if (lgpd_log_ips($pdo)) {
                $det['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
                $det['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
            }
            $ins = $pdo->prepare('INSERT INTO documentos_assinaturas (documento_id, usuario_id, data_assinatura, tipo_assinatura, detalhes) VALUES (?, ?, NOW(), ?, ?)');
            $ins->execute([$docId, (int)$_SESSION['user_id'], 'ICP-Cloud', json_encode($det, JSON_UNESCAPED_UNICODE)]);
            $pdo->commit();
        } catch (Throwable $e) {
            try { $pdo->rollBack(); } catch (Throwable $e2) {}
            $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Assinado no provedor, mas falhou ao registrar localmente: ' . $e->getMessage()];
            header('Location: index.php?id=' . $docId);
            exit();
        }

        $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => 'Documento assinado pelo Bird ID com sucesso.'];
        header('Location: index.php?id=' . $docId);
        exit();
    }

    // Caso não tenha conseguido salvar o PDF
    $_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Bird ID retornou com sucesso, mas foi necessário concluir a integração para obter o PDF assinado.'];
    header('Location: index.php?id=' . $docId);
    exit();
}

// Provedor cloud genérico (ainda não implementado)
if ($base === '' || $cid === '' || $cb === '') {
    $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Provedor ICP em nuvem não configurado.'];
    header('Location: index.php?id=' . $docId);
    exit();
}

$_SESSION['flash_message'] = ['type' => 'info', 'text' => 'Retorno recebido. Falta concluir a integração do provedor cloud genérico.'];
header('Location: index.php?id=' . $docId);
exit();
