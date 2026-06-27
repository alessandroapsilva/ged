<?php
// ForÃ§a o navegador a recarregar sem cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// DEBUG: Confirma que este arquivo estÃ¡ sendo executado
define('DOCUMENTOS_EDITAR_VERSION', '2.0_ENFASGED_2025-10-29');

require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/version_helper.php';
// Usado para contagem de pÃ¡ginas (PDF)
require_once PROJECT_ROOT . '/vendor/autoload.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$documento_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($documento_id === 0) { header('Location: documentos.php'); exit(); }

// --- LÃ“GICA PARA SALVAR O FORMULÃRIO (QUANDO FOR POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PermissÃ£o: sÃ³ bloqueia se um sistema de permissÃµes estiver ativo E o usuÃ¡rio nÃ£o tiver 'document.edit'.
    if (function_exists('usuario_tem_permissao')) {
        $hasPermSystem = (isset($_SESSION['permissoes']) && is_array($_SESSION['permissoes']))
                      || (isset($_SESSION['user_permissions']) && is_array($_SESSION['user_permissions']));
        if ($hasPermSystem && !usuario_tem_permissao('document.edit')) {
            header('Location: acesso_negado.php');
            exit();
        }
    }
    if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }
    // Logger temporÃ¡rio de upload (para diagnosticar substituiÃ§Ã£o)
    $logFile = PROJECT_ROOT . '/storage/logs/upload_debug.log';
    if (!is_dir(dirname($logFile))) { @mkdir(dirname($logFile), 0777, true); }
    $dbg = function(string $msg, $ctx = null) use ($logFile) {
        try {
            if ($ctx !== null) {
                if (is_array($ctx) || is_object($ctx)) { $msg .= ' ' . print_r($ctx, true); }
                else { $msg .= ' ' . (string)$ctx; }
            }
            @file_put_contents($logFile, '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND);
        } catch (Throwable $e) { /* ignore */ }
    };

    $dbg('POST documentos_editar iniciado', [
        'documento_id' => $documento_id,
        'user_id' => $_SESSION['user_id'] ?? null,
    ]);

    // ProteÃ§Ã£o contra estouro silencioso de post_max_size (quando $_FILES fica vazio)
    $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxIni = ini_get('post_max_size');
    $toBytes = function($val) {
        $val = trim((string)$val);
        if ($val === '') return 0;
        $unit = strtolower(substr($val, -1));
        $num = (float)$val;
        switch ($unit) {
            case 'g': return (int)($num * 1024 * 1024 * 1024);
            case 'm': return (int)($num * 1024 * 1024);
            case 'k': return (int)($num * 1024);
            default: return (int)$num; // jÃ¡ em bytes
        }
    };
    $postMaxBytes = $toBytes($postMaxIni);
    $dbg('CONTENT_LENGTH/post_max_size', ['content_length' => $contentLength, 'post_max_size' => $postMaxIni, 'bytes' => $postMaxBytes]);
    if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
        $dbg('Bloqueado por post_max_size excedido');
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'A requisiÃ§Ã£o excede o limite post_max_size ('.htmlspecialchars($postMaxIni).'). Aumente post_max_size e upload_max_filesize no php.ini ou use o Ingest para arquivos grandes.'];
        header('Location: documentos_editar.php?id=' . $documento_id);
        exit();
    }
    $pdo->beginTransaction();
    try {
        $stmt_old = $pdo->prepare("SELECT caminho_arquivo, pasta_id FROM documentos WHERE id = ?");
        $stmt_old->execute([$documento_id]);
        $doc_antigo = $stmt_old->fetch(PDO::FETCH_ASSOC);
        $caminho_arquivo_antigo = $doc_antigo['caminho_arquivo'];

        $novo_titulo = trim($_POST['titulo'] ?? '');
        $nova_descricao = isset($_POST['descricao']) && $_POST['descricao'] !== '' ? trim($_POST['descricao']) : null;
        $novo_tipo_id = (int)($_POST['tipo_documento_id'] ?? 0);
        $proprietario_id = isset($_POST['proprietario_id']) ? (int)$_POST['proprietario_id'] : 0;
        $funcoes_permitidas = isset($_POST['funcoes_permitidas']) && is_array($_POST['funcoes_permitidas']) ? array_map('intval', $_POST['funcoes_permitidas']) : [];
        $metadados = $_POST['meta'] ?? [];
        
        // ValidaÃ§Ãµes bÃ¡sicas
        if (empty($novo_titulo)) {
            throw new Exception("O tÃ­tulo do documento Ã© obrigatÃ³rio.");
        }
        if ($novo_tipo_id <= 0) {
            throw new Exception("Selecione um tipo de documento vÃ¡lido.");
        }

        // Mapeia colunas existentes para montar UPDATE de forma compatível
        static $colsUpd = null;
        if ($colsUpd === null) {
            $colsUpd = [];
            try {
                $rc = $pdo->query("SHOW COLUMNS FROM documentos");
                foreach ($rc->fetchAll(PDO::FETCH_COLUMN, 0) as $c) { $colsUpd[strtolower($c)] = true; }
            } catch (Throwable $e) {}
        }
        $sql_parts = [];
        $params = [];
        // Mapeia colunas compatíveis entre esquemas diferentes
        $colTitulo = null; foreach (['titulo','titulo_original','nome'] as $c) { if (isset($colsUpd[$c])) { $colTitulo = $c; break; } }
        $colDesc   = null; foreach (['descricao','descricao_documento','observacao'] as $c) { if (isset($colsUpd[$c])) { $colDesc = $c; break; } }
        $colTipo   = null; foreach (['tipo_documento_id','tipo_id'] as $c) { if (isset($colsUpd[$c])) { $colTipo = $c; break; } }
        if ($colTitulo) { $sql_parts[] = "$colTitulo = ?"; $params[] = $novo_titulo; }
        if ($colDesc)   { $sql_parts[] = "$colDesc = ?";   $params[] = $nova_descricao; }
        if ($colTipo)   { $sql_parts[] = "$colTipo = ?";   $params[] = $novo_tipo_id; }
        // Ajusta proprietário se a coluna existir
        try {
            if ($proprietario_id > 0) {
                if (isset($colsUpd['proprietario_id'])) { $sql_parts[] = 'proprietario_id = ?'; $params[] = $proprietario_id; }
                elseif (isset($colsUpd['owner_id'])) { $sql_parts[] = 'owner_id = ?'; $params[] = $proprietario_id; }
                elseif (isset($colsUpd['usuario_id_proprietario'])) { $sql_parts[] = 'usuario_id_proprietario = ?'; $params[] = $proprietario_id; }
                elseif (isset($colsUpd['usuario_id'])) { $sql_parts[] = 'usuario_id = ?'; $params[] = $proprietario_id; }
            }
        } catch (Throwable $e) { /* ignora */ }

        // Tratamento robusto do upload (substituiÃ§Ã£o de arquivo)
    $arquivo_substituido = false;
    if (isset($_FILES['arquivo'])) {
        $dbg('FILES[arquivo] recebido', $_FILES['arquivo']);
            $uploadErr = (int)($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE);

            // Se o usuÃ¡rio tentou enviar um arquivo mas houve erro, informar claramente
            if ($uploadErr !== UPLOAD_ERR_OK && $uploadErr !== UPLOAD_ERR_NO_FILE) {
                $limite_upload = ini_get('upload_max_filesize');
                $limite_post = ini_get('post_max_size');
                $dbg('Erro de upload detectado', ['code' => $uploadErr]);
                switch ($uploadErr) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        throw new Exception("O arquivo excede o limite permitido (upload_max_filesize={$limite_upload}, post_max_size={$limite_post}). Dica: aumente os limites no PHP ou use o Ingest para arquivos grandes.");
                    case UPLOAD_ERR_PARTIAL:
                        throw new Exception("Upload interrompido. Tente novamente ou verifique a estabilidade da conexÃ£o.");
                    case UPLOAD_ERR_NO_TMP_DIR:
                        throw new Exception("Falha no upload: diretÃ³rio temporÃ¡rio ausente (upload_tmp_dir). Verifique a configuraÃ§Ã£o do PHP.");
                    case UPLOAD_ERR_CANT_WRITE:
                        throw new Exception("Falha ao gravar no disco. Verifique permissÃµes da pasta temporÃ¡ria e de 'public/storage/uploads'.");
                    case UPLOAD_ERR_EXTENSION:
                        throw new Exception("Upload bloqueado por extensÃ£o do PHP. Verifique extensÃµes que interfiram no upload.");
                    default:
                        throw new Exception("Falha no upload (cÃ³digo {$uploadErr}).");
                }
            }
        }

    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $nome_original = basename($_FILES["arquivo"]["name"]);
            $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
            if ($extensao === '') { $extensao = 'bin'; }
            // Checagem de tamanho lÃ³gico (100 MB), alÃ©m dos limites do PHP
            $tamanho_bytes = (int)($_FILES['arquivo']['size'] ?? 0);
            $dbg('Tamanho do arquivo', ['bytes' => $tamanho_bytes]);
            if ($tamanho_bytes > 100 * 1024 * 1024) {
                throw new Exception("Arquivo maior que 100 MB. Utilize o Ingest para processar arquivos grandes.");
            }
            $nome_unico = uniqid('doc_', true) . ($extensao ? '.' . $extensao : '');
            $novo_caminho_db = 'storage/uploads/' . $nome_unico;
            $novo_caminho_servidor = PROJECT_ROOT . '/public/' . $novo_caminho_db;
            $dir_uploads = dirname($novo_caminho_servidor);
            if (!is_dir($dir_uploads)) {
                if (!mkdir($dir_uploads, 0777, true) && !is_dir($dir_uploads)) {
                    throw new Exception('Falha ao criar diretÃ³rio de uploads.');
                }
            }
            $dbg('DiretÃ³rio de destino', ['dir' => $dir_uploads, 'exists' => is_dir($dir_uploads), 'writable' => is_writable($dir_uploads)]);

            $tmp = $_FILES["arquivo"]["tmp_name"] ?? '';
            $dbg('PrÃ©-movimento', ['tmp' => $tmp, 'is_uploaded' => $tmp ? is_uploaded_file($tmp) : null, 'target' => $novo_caminho_servidor]);
            if (!move_uploaded_file($tmp, $novo_caminho_servidor)) {
                $dbg('move_uploaded_file falhou');
                throw new Exception("Falha ao mover o novo arquivo enviado.");
            }
            $dbg('Arquivo movido com sucesso', ['dst' => $novo_caminho_servidor, 'size' => @filesize($novo_caminho_servidor)]);
            // Atualiza hash e quantidade de pÃ¡ginas ao substituir o arquivo
            $novo_hash = hash_file('sha256', $novo_caminho_servidor);
            $nova_qtd_paginas = null;
            if ($extensao === 'pdf') {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($novo_caminho_servidor);
                    $nova_qtd_paginas = count($pdf->getPages());
                } catch (Throwable $e) {
                    $nova_qtd_paginas = null; // mantÃ©m nulo se falhar contagem
                }
            }
            $dbg('Hash/PÃ¡ginas', ['hash' => $novo_hash, 'pages' => $nova_qtd_paginas]);
            
            $sql_parts[] = "caminho_arquivo = ?";
            $params[] = $novo_caminho_db;
            $sql_parts[] = "hash_arquivo = ?";
            $params[] = $novo_hash;
            // Ajusta campos opcionais conforme colunas existentes
            try {
                static $cols = null;
                if ($cols === null) {
                    $cols = [];
                    $rs = $pdo->query("SHOW COLUMNS FROM documentos");
                    foreach ($rs->fetchAll(PDO::FETCH_COLUMN, 0) as $c) { $cols[strtolower($c)] = true; }
                }
                if (isset($cols['quantidade_paginas'])) {
                    $sql_parts[] = "quantidade_paginas = ?";
                    $params[] = $nova_qtd_paginas;
                }
                if (isset($cols['assinado'])) { $sql_parts[] = "assinado = 0"; }
                if (isset($cols['data_assinatura'])) { $sql_parts[] = "data_assinatura = NULL"; }
                if (isset($cols['assinado_por'])) { $sql_parts[] = "assinado_por = NULL"; }
            } catch (Throwable $e) { /* ignora, segue sem colunas opcionais */ }
            $arquivo_substituido = true;
        }

        // Atualiza coluna de timestamp apenas se existir no schema
        try {
            static $tsCols = null;
            if ($tsCols === null) {
                $tsCols = [];
                $rc2 = $pdo->query("SHOW COLUMNS FROM documentos");
                foreach ($rc2->fetchAll(PDO::FETCH_COLUMN, 0) as $c) { $tsCols[strtolower($c)] = true; }
            }
        } catch (Throwable $e) { $tsCols = []; }
        $tsCol = null;
        foreach (['atualizado_em','data_atualizacao','updated_at'] as $cand) { if (isset($tsCols[$cand])) { $tsCol = $cand; break; } }
        $sql = "UPDATE documentos SET " . implode(", ", $sql_parts) . ($tsCol ? ", $tsCol = NOW()" : "") . " WHERE id = ?";
        $params[] = $documento_id;
    $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // âœ… LÃ“GICA PARA SALVAR METADADOS (tolerante a ausÃªncia de tabelas)
        if (!empty($metadados)) {
            $canSaveMeta = true;
            try {
                // Verifica existÃªncia das tabelas antes de operar
                $chk = $pdo->query("SHOW TABLES LIKE 'documento_metadados'")->fetchColumn();
                $chk2 = $pdo->query("SHOW TABLES LIKE 'metadado_campos'")->fetchColumn();
                if (!$chk || !$chk2) { $canSaveMeta = false; }
            } catch (Throwable $e) { $canSaveMeta = false; }

            if ($canSaveMeta) {
                try {
                    foreach($metadados as $campo_id => $valor) {
                        $meta_stmt = $pdo->prepare("SELECT id FROM documento_metadados WHERE documento_id = ? AND campo_id = ?");
                        $meta_stmt->execute([$documento_id, $campo_id]);
                        $meta_existente = $meta_stmt->fetch();
                        if ($meta_existente) {
                            $update_meta_stmt = $pdo->prepare("UPDATE documento_metadados SET valor = ? WHERE id = ?");
                            $update_meta_stmt->execute([$valor, $meta_existente['id']]);
                        } else {
                            $insert_meta_stmt = $pdo->prepare("INSERT INTO documento_metadados (documento_id, campo_id, valor) VALUES (?, ?, ?)");
                            $insert_meta_stmt->execute([$documento_id, $campo_id, $valor]);
                        }
                    }
                } catch (Throwable $e) {
                    // NÃ£o deixa a ediÃ§Ã£o falhar por metadados; registra e segue
                    error_log('Falha ao salvar metadados do documento '.$documento_id.': '.$e->getMessage());
                }
            }
        }

        // Cria versÃ£o pela ediÃ§Ã£o apenas se habilitado; caso contrÃ¡rio substitui sem histÃ³rico
        if (defined('ENABLE_VERSIONING') && ENABLE_VERSIONING) {
            $motivo = isset($novo_caminho_db) ? 'SubstituiÃ§Ã£o de arquivo' : 'EdiÃ§Ã£o de metadados';
            criar_versao_documento($pdo, (int)$documento_id, (int)$_SESSION['user_id'], $motivo);
        }

        if (isset($novo_caminho_db) && $caminho_arquivo_antigo && file_exists(PROJECT_ROOT . '/public/' . $caminho_arquivo_antigo)) {
            $arquivar = isset($_POST['arquivar_original']) && $_POST['arquivar_original'] === 'sim';
            if ($arquivar) {
                $destDir = PROJECT_ROOT . '/public/storage/arquivados';
                if (!is_dir($destDir)) { @mkdir($destDir, 0777, true); }
                $base = basename($caminho_arquivo_antigo);
                $novoNome = 'orig_' . date('Ymd_His') . '_' . $base;
                @rename(PROJECT_ROOT . '/public/' . $caminho_arquivo_antigo, $destDir . '/' . $novoNome);
            } else {
                // Remove para economizar espaÃ§o
                @unlink(PROJECT_ROOT . '/public/' . $caminho_arquivo_antigo);
            }
        }

        // Atualiza mapeamento de funções por documento, se a tabela existir
        try {
            if (!empty($funcoes_permitidas)) {
                $pdo->query("CREATE TABLE IF NOT EXISTS documento_funcoes (documento_id INT NOT NULL, funcao_id INT NOT NULL, PRIMARY KEY(documento_id, funcao_id))");
            }
            $pdo->query("SELECT 1 FROM documento_funcoes LIMIT 1");
            // Se chegou até aqui a tabela existe
            $del = $pdo->prepare('DELETE FROM documento_funcoes WHERE documento_id = ?');
            $del->execute([$documento_id]);
            if (!empty($funcoes_permitidas)) {
                $ins = $pdo->prepare('INSERT INTO documento_funcoes (documento_id, funcao_id) VALUES (?, ?)');
                foreach ($funcoes_permitidas as $fid) { $ins->execute([$documento_id, (int)$fid]); }
            }
        } catch (Throwable $e) {
            // ambiente sem a tabela; segue sem bloquear o salvar
        }

        // registrar_log($pdo, $_SESSION['user_id'], "Editou o documento '{$novo_titulo}' (ID: {$documento_id}).");
    $pdo->commit();
    $dbg('UPDATE concluÃ­do', ['arquivo_substituido' => $arquivo_substituido, 'id' => $documento_id]);
        
    $msg = $arquivo_substituido
        ? 'Arquivo substituÃ­do e metadados atualizados.'
        : 'Nenhum novo arquivo foi enviado; o arquivo atual foi mantido. Metadados atualizados com sucesso.';
    $_SESSION['flash_message'] = ['type' => 'sucesso', 'text' => $msg];
        header('Location: documentos_propriedades.php?id=' . $documento_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Log detalhado do erro
        error_log("Erro ao atualizar documento {$documento_id}: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro ao atualizar: ' . htmlspecialchars($e->getMessage())];
        header('Location: documentos_editar.php?id=' . $documento_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        
        error_log("Erro PDO ao atualizar documento {$documento_id}: " . $e->getMessage());
        
        $_SESSION['flash_message'] = ['type' => 'erro', 'text' => 'Erro de banco de dados: ' . htmlspecialchars($e->getMessage())];
        header('Location: documentos_editar.php?id=' . $documento_id);
        exit();
    }
}

// --- LÃ“GICA PARA EXIBIR O FORMULÃRIO (QUANDO FOR GET) ---
try {
    $stmt = $pdo->prepare("SELECT d.*, p.nome as nome_pasta FROM documentos d LEFT JOIN pastas p ON d.pasta_id = p.id WHERE d.id = ? AND d.apagado_em IS NULL");
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento) { die("Documento nÃ£o encontrado ou jÃ¡ estÃ¡ na lixeira."); }

    $tipos_stmt = $pdo->query("SELECT id, nome FROM tipos_documento ORDER BY nome");
    $tipos_documento = $tipos_stmt->fetchAll(PDO::FETCH_ASSOC);

    // âœ… BUSCA OS CAMPOS DE METADADOS E SEUS VALORES
    // Ajuste: a tabela metadado_campos nÃ£o possui a coluna 'nome'; usamos 'rotulo' (rÃ³tulo exibido)
    $campos_meta_sql = "SELECT mc.id, mc.rotulo, dm.valor 
                        FROM metadado_campos mc
                        LEFT JOIN documento_metadados dm ON mc.id = dm.campo_id AND dm.documento_id = :documento_id
                        WHERE mc.tipo_documento_id = :tipo_id
                        ORDER BY mc.ordem ASC, mc.rotulo ASC";
    $campos_meta_stmt = $pdo->prepare($campos_meta_sql);
    $campos_meta_stmt->execute([
        ':documento_id' => $documento_id,
        ':tipo_id' => $documento['tipo_documento_id']
    ]);
    $campos_metadados = $campos_meta_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normaliza campos de título/descrição para exibição
    $doc_titulo = $documento['titulo'] ?? ($documento['titulo_original'] ?? ($documento['nome'] ?? ''));
    $doc_descricao = $documento['descricao'] ?? ($documento['descricao_documento'] ?? ($documento['observacao'] ?? ''));

    // Carrega usuários e funções para selects
    $usuarios = [];
    $funcoes = [];
    try { $usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { $usuarios = []; }
    try { $funcoes = $pdo->query("SELECT id, nome_funcao FROM funcoes ORDER BY nome_funcao ASC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e) { $funcoes = []; }
    // Seleção atual de funções por documento (se tabela existir)
    $doc_funcoes_ids = [];
    try { $stf = $pdo->prepare('SELECT funcao_id FROM documento_funcoes WHERE documento_id = ?'); $stf->execute([$documento_id]); $doc_funcoes_ids = $stf->fetchAll(PDO::FETCH_COLUMN) ?: []; } catch (Throwable $e) { $doc_funcoes_ids = []; }

} catch (PDOException $e) {
    die("Erro ao carregar o documento: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<style>
/* Estilo ENFAS GED para a tela de ediÃ§Ã£o */
.content-wrapper {
    background-color: #2c3e50;
}
.card {
    background-color: #34495e;
    border: none;
}
.form-control, .custom-file-label {
    background-color: #3e4d5f;
    border: 1px solid #4a5f7f;
    color: #ecf0f1;
}
.form-control:focus {
    background-color: #485563;
    border-color: #5dade2;
    color: #fff;
}
.form-control:disabled {
    background-color: #2c3e50;
    color: #95a5a6;
}
label {
    color: #ecf0f1;
    font-weight: 500;
}
.text-muted {
    color: #95a5a6 !important;
}
h1, h5 {
    color: #ecf0f1;
}
.alert-warning {
    background-color: #f39c12;
    border-color: #e67e22;
    color: #212529;
}
.btn-success {
    background-color: #27ae60;
    border-color: #229954;
    font-weight: 600;
    padding: 12px 40px;
}
.btn-success:hover {
    background-color: #229954;
    border-color: #1e8449;
}
.btn-secondary {
    background-color: #5d6d7e;
    border-color: #4d5d6e;
}
.btn-secondary:hover {
    background-color: #4d5d6e;
}
</style>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Editar: <?= htmlspecialchars($doc_titulo ?: ("Documento #".$documento_id)) ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="documentos_propriedades.php?id=<?= $documento_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Voltar para o Documento
                    </a>
                    <a href="documentos.php?pasta_id=<?= $documento['pasta_id']; ?>" class="btn btn-secondary ml-2">
                        <i class="fas fa-folder mr-1"></i> Voltar para a Pasta Anterior
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- DEBUG: VersÃ£o do arquivo -->
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>VersÃ£o do arquivo:</strong> <?= DOCUMENTOS_EDITAR_VERSION; ?> 
                <small class="ml-2">(Se ainda vir erro de mc.nome, pressione CTRL+SHIFT+R para limpar cache)</small>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>

            <!-- Alerta Laranja estilo ENFAS GED -->
            <div class="alert alert-warning" role="alert">
                <strong>ATENÃ‡ÃƒO:</strong> alteraÃ§Ãµes nos campos <strong>Nome, Tipo do Documento, Identificadores do Documento e Arquivo do Documento INVALIDA assinaturas eletrÃ´nicas</strong> e atualiza metadados, assinaturas digitais, chave de integridade, verificadores e datas.
            </div>

            <div class="card card-dark card-outline">
                <form method="post" action="documentos_editar.php?id=<?= $documento_id; ?>" enctype="multipart/form-data">
                    <div class="card-body">
                        <?= function_exists('csrf_input') ? csrf_input() : '' ?>
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?= 100 * 1024 * 1024 ?>" />
                        <?php
                            $php_upload_max = ini_get('upload_max_filesize');
                            $php_post_max = ini_get('post_max_size');
                            $caminho_atual_abs = !empty($documento['caminho_arquivo']) ? PROJECT_ROOT . '/public/' . $documento['caminho_arquivo'] : null;
                            $tam_atual = ($caminho_atual_abs && file_exists($caminho_atual_abs)) ? filesize($caminho_atual_abs) : null;
                            function tamanho_humanizado($bytes) {
                                if ($bytes === null) return null;
                                $units = ['B','KB','MB','GB','TB'];
                                $i = 0;
                                while ($bytes >= 1024 && $i < count($units)-1) { $bytes /= 1024; $i++; }
                                return sprintf('%.2f %s', $bytes, $units[$i]);
                            }
                        ?>
                        
                        <!-- Local -->
                        <div class="form-group">
                            <label>Local</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($documento['nome_pasta'] ?? 'Documentos'); ?>" disabled>
                        </div>

                        <!-- Nome -->
                        <div class="form-group">
                            <label for="titulo">Nome</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($doc_titulo) ?>" required>
                        </div>

                        <!-- DescriÃ§Ã£o -->
                        <div class="form-group">
                            <label for="descricao">DescriÃ§Ã£o</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($doc_descricao) ?></textarea>
                        </div>

                        <!-- Tipo do Documento -->
                        <div class="form-group">
                            <label for="tipo_documento_id">Tipo do Documento</label>
                            <select class="form-control" id="tipo_documento_id" name="tipo_documento_id" required>
                                <option value="">-- Selecione --</option>
                                <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?= $tipo['id']; ?>" <?= ($tipo['id'] == $documento['tipo_documento_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($tipo['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Identificadores do Documento (Metadados) -->
                        <?php if (!empty($campos_metadados)): ?>
                            <h5 class="mt-4 mb-3">Identificadores do Documento</h5>
                            <p class="text-muted small">
                                Preencha os identificadores conforme os exemplos. Os exemplos nÃ£o sÃ£o salvos. 
                                Campos marcados com asterisco (<span class="text-danger">*</span>) sÃ£o obrigatÃ³rios.
                            </p>
                            <?php foreach ($campos_metadados as $campo): ?>
                                <div class="form-group">
                                    <label for="meta-<?= $campo['id']; ?>">
                                        <?= htmlspecialchars($campo['rotulo']); ?>
                                        <!-- Aqui vocÃª pode adicionar lÃ³gica para mostrar * se obrigatÃ³rio -->
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="meta-<?= $campo['id']; ?>" 
                                        name="meta[<?= $campo['id']; ?>]" 
                                        value="<?= htmlspecialchars($campo['valor'] ?? ''); ?>"
                                    >
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <!-- Arquivo do Documento -->
                        <h5 class="mt-4 mb-3">Arquivo do Documento</h5>
                        <div class="alert alert-secondary">
                            <div class="d-flex flex-wrap align-items-center">
                                <div class="mr-3 mb-1"><strong>Limites do servidor:</strong> arquivo atÃ© <?= htmlspecialchars($php_upload_max) ?>; requisiÃ§Ã£o atÃ© <?= htmlspecialchars($php_post_max) ?>.</div>
                                <?php if (!empty($documento['caminho_arquivo'])): ?>
                                    <div class="mr-3 mb-1"><strong>Atual:</strong> <?= htmlspecialchars(basename($documento['caminho_arquivo'])) ?></div>
                                    <?php if ($tam_atual !== null): ?><div class="mr-3 mb-1">Tamanho: <?= tamanho_humanizado($tam_atual) ?></div><?php endif; ?>
                                    <?php if (!empty($documento['quantidade_paginas'])): ?><div class="mr-3 mb-1">PÃ¡ginas: <?= (int)$documento['quantidade_paginas'] ?></div><?php endif; ?>
                                    <?php if (!empty($documento['hash_arquivo'])): ?><div class="mb-1">Hash: <code><?= htmlspecialchars(substr($documento['hash_arquivo'],0,12)) ?>â€¦</code></div><?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="arquivo" name="arquivo">
                                <label class="custom-file-label" for="arquivo">
                                    Escolha um novo arquivo para substituir o atual
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Arquivos de atÃ© <strong>100 MB</strong> sÃ£o aceitos. Use o Ingest para arquivos maiores.
                            </small>
                            <div id="novo-arquivo-info" class="small mt-2 text-info" style="display:none"></div>
                        </div>

                        <!-- Arquivar Original -->
                        <div class="form-group">
                            <label for="arquivar_original">Arquivar Original</label>
                            <select class="form-control" id="arquivar_original" name="arquivar_original">
                                <option value="nao" selected>NÃ£o</option>
                                <option value="sim">Sim</option>
                            </select>
                        </div>

                        <!-- ProprietÃ¡rio -->
                        <div class="form-group">
                            <label for="proprietario_id">Proprietário</label>
                            <select class="form-control" id="proprietario_id" name="proprietario_id">
                                <option value="">Selecione...</option>
                                <?php foreach ($usuarios as $u): $uid=(int)$u['id']; $sel = ''; $currOwner = $documento['proprietario_id'] ?? ($documento['owner_id'] ?? ($documento['usuario_id_proprietario'] ?? null)); if ($currOwner && (int)$currOwner === $uid) { $sel='selected'; } ?>
                                  <option value="<?= $uid ?>" <?= $sel ?>><?= htmlspecialchars($u['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Funções Permitidas -->

                        <!-- FunÃ§Ãµes Permitidas -->
                        <div class="form-group">
                            <label for="funcoes_permitidas">FunÃ§Ãµes Permitidas</label>
                            <label for="funcoes_permitidas">Funções Permitidas</label>
                            <select class="form-control" id="funcoes_permitidas" name="funcoes_permitidas[]" multiple>
                                <?php foreach ($funcoes as $f): $fid=(int)$f['id']; $sel = in_array($fid, $doc_funcoes_ids) ? 'selected' : ''; ?>
                                  <option value="<?= $fid ?>" <?= $sel ?>><?= htmlspecialchars($f['nome_funcao']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Selecione as funções/grupos que podem acessar este documento.</small>

                    </div>

                    <!-- BotÃ£o de Salvar (verde, grande, estilo ENFAS GED) -->
                    <div class="card-footer text-center" style="background-color: transparent; border: none;">
                        <button type="submit" class="btn btn-success btn-lg" style="min-width: 300px;">
                            <i class="fas fa-save mr-2"></i> Salvar AlteraÃ§Ãµes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>
<script>
// Ativa o nome do arquivo no input de upload
$(function () {
    try { if (window.bsCustomFileInput && typeof bsCustomFileInput.init === 'function') { bsCustomFileInput.init(); } } catch(e) { /* sem plugin, segue normal */ }
    const input = document.getElementById('arquivo');
        const info = document.getElementById('novo-arquivo-info');
        // Usa o label irmÃ£o imediato do input (fallback mais robusto com/sem plugin)
        let label = null;
        if (input) {
            const sibling = input.nextElementSibling;
            if (sibling && sibling.classList && sibling.classList.contains('custom-file-label')) label = sibling;
        }
    if (input) {
        input.addEventListener('change', function() {
            const f = this.files && this.files[0] ? this.files[0] : null;
                        if (!f) {
                            if (info) { info.style.display = 'none'; info.textContent = ''; }
                            if (label) { label.textContent = 'Escolha um novo arquivo para substituir o atual'; }
                            return;
                        }
            const max = 100 * 1024 * 1024; // 100 MB
            if (f.size > max) {
                alert('Arquivo maior que 100 MB. Utilize o Ingest para arquivos grandes.');
                this.value = '';
                                if (info) { info.style.display = 'none'; info.textContent = ''; }
                                if (label) { label.textContent = 'Escolha um novo arquivo para substituir o atual'; }
            }
            // Atualiza feedback visual
                        if (label) { label.textContent = f.name; }
            if (info) {
                const kb = (f.size/1024).toFixed(1);
                info.textContent = 'Novo arquivo selecionado: ' + f.name + ' (' + kb + ' KB)';
                info.style.display = '';
            }
        });
    }
});
</script>






