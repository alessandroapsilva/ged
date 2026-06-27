<?php
// scripts/ingest_robo.php (VERSÃO PROFISSIONAL v3.1)

set_time_limit(0);

require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/vendor/autoload.php';
require_once PROJECT_ROOT . '/helpers/pdf_indexer.php';

// ========== Helpers ==========
function ingest_log_file(): string {
    $dir = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    return $dir . DIRECTORY_SEPARATOR . 'ingest_robo-' . date('Ymd') . '.log';
}

function log_msg(string $msg, bool $withTime = true): void {
    $line = ($withTime ? '[' . date('Y-m-d H:i:s') . '] ' : '') . $msg . "\n";
    echo $line;
    @file_put_contents(ingest_log_file(), $line, FILE_APPEND);
}

function get_config(PDO $pdo, string $key, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT config_valor FROM configuracoes WHERE config_chave = ?");
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v !== false ? $v : $default;
    } catch (Throwable $e) { return $default; }
}

function is_file_stable(string $path, int $waitSec = 2): bool {
    clearstatcache(true, $path);
    $size1 = @filesize($path);
    $mtime1 = @filemtime($path);
    sleep($waitSec);
    clearstatcache(true, $path);
    $size2 = @filesize($path);
    $mtime2 = @filemtime($path);
    return ($size1 === $size2) && ($mtime1 === $mtime2);
}

function classify_document(string $titulo, string $arquivo, $jsonRules) {
    // jsonRules esperado: { "rules": [ {"pattern": "regex", "tipo_documento_id": 3, "pasta_id": 10} ] }
    $result = [ 'tipo_documento_id' => null, 'pasta_id' => null ];
    if (!$jsonRules) return $result;
    $data = json_decode($jsonRules, true);
    if (!is_array($data) || empty($data['rules'])) return $result;
    foreach ($data['rules'] as $rule) {
        if (!isset($rule['pattern'])) continue;
        $pattern = '/' . str_replace('/', '\/', $rule['pattern']) . '/i';
        if (preg_match($pattern, $titulo) || preg_match($pattern, $arquivo)) {
            if (isset($rule['tipo_documento_id'])) $result['tipo_documento_id'] = (int)$rule['tipo_documento_id'];
            if (isset($rule['pasta_id'])) $result['pasta_id'] = (int)$rule['pasta_id'];
            break; // primeira correspondência
        }
    }
    return $result;
}

log_msg('--- INICIANDO ROBO DE CAPTURA (v3.1) ---', false);

// Evitar execuções concorrentes (lock de processo simples)
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ged_ingest_robo.lock';
$lockFp = fopen($lockFile, 'c');
if ($lockFp === false || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    log_msg('Outro processo do robô está em execução. Abortando.');
    exit(0);
}

try {
    // ====== Configurações ======
    log_msg('1. Buscando configuracoes...');
    $pasta_monitorada = get_config($pdo, 'INGEST_PASTA_MONITORADA');
    $ingest_user_id = (int) get_config($pdo, 'INGEST_USER_ID', 1);
    $dup_policy = strtolower((string) get_config($pdo, 'INGEST_DUP_POLICY', 'skip')); // skip | version_by_title
    $class_rules = get_config($pdo, 'INGEST_CLASSIFICACAO_REGEX', null); // JSON opcional
    $default_tipo = get_config($pdo, 'INGEST_TIPO_PADRAO', null);
    $default_pasta = get_config($pdo, 'INGEST_PASTA_PADRAO', null);

    if (!$pasta_monitorada || !is_dir($pasta_monitorada)) {
        log_msg('ERRO FATAL: Pasta de captura nao configurada ou invalida. Finalizando.');
        throw new Exception('Pasta monitorada inválida');
    }

    // NOVA ETAPA: Definir e verificar a pasta de arquivos processados
    $pasta_processados = $pasta_monitorada . DIRECTORY_SEPARATOR . 'processados';
    if (!is_dir($pasta_processados)) {
        if (!mkdir($pasta_processados, 0777, true)) {
            log_msg("ERRO FATAL: Nao foi possivel criar a pasta 'processados'. Verifique as permissoes.");
            throw new Exception('Falha ao criar pasta processados');
        }
    }

    log_msg('2. Monitorando a pasta: ' . $pasta_monitorada);
    
    // Garante diretório de storage
    $pasta_storage = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($pasta_storage)) {
        if (!mkdir($pasta_storage, 0777, true)) {
            log_msg('ERRO FATAL: Nao foi possivel criar a pasta de storage.');
            throw new Exception('Falha ao criar storage');
        }
    }
    
    $arquivos = scandir($pasta_monitorada);
    $arquivos_processados_count = 0;
    $stats = [ 'novos' => 0, 'duplicados' => 0, 'falhas' => 0, 'versoes' => 0 ];
    $inicio = microtime(true);

    log_msg('3. Verificando novos arquivos...');
    foreach ($arquivos as $arquivo) {
        if ($arquivo === '.' || $arquivo === '..') {
            continue;
        }

        $caminho_original = $pasta_monitorada . DIRECTORY_SEPARATOR . $arquivo;

        if (is_file($caminho_original) && strtolower(pathinfo($arquivo, PATHINFO_EXTENSION)) === 'pdf') {
            log_msg('   -> Processando: ' . $arquivo);

            // Evita arquivo em cópia: garante estabilidade de tamanho/modificação
            if (!is_file_stable($caminho_original, 2)) {
                log_msg('      -> Arquivo ainda em transferência. Pulando nesta rodada.');
                continue;
            }

            try {
                // Verifica duplicidade por hash antes de inserir (fora da transação)
                $hash_tmp = hash_file('sha256', $caminho_original);
                $stmt_dup = $pdo->prepare("SELECT id FROM documento_versoes WHERE hash_sha256 = ? LIMIT 1");
                $stmt_dup->execute([$hash_tmp]);
                if ($stmt_dup->fetchColumn()) {
                    log_msg("      -> ARQUIVO DUPLICADO (hash já existente). Movendo para 'processados'.");
                    $stats['duplicados']++;
                    $subdir = $pasta_processados . DIRECTORY_SEPARATOR . date('Y-m-d');
                    if (!is_dir($subdir)) { @mkdir($subdir, 0777, true); }
                    $caminho_destino_processado = $subdir . DIRECTORY_SEPARATOR . $arquivo;
                    @rename($caminho_original, $caminho_destino_processado);
                    continue;
                }

                $pdo->beginTransaction();

                // 1. Copiar o arquivo para o storage do GED
                $nome_arquivo_sistema = uniqid() . '-' . preg_replace('/[^A-Za-z0-9.\-_]/', '', $arquivo);
                $caminho_destino_storage = $pasta_storage . DIRECTORY_SEPARATOR . $nome_arquivo_sistema;
                $caminho_relativo_db = 'storage/uploads/' . $nome_arquivo_sistema;
                if (!copy($caminho_original, $caminho_destino_storage)) {
                    throw new Exception('Falha ao copiar o arquivo para o storage.');
                }

                // 2. Determinar metadados básicos
                $titulo = pathinfo($arquivo, PATHINFO_FILENAME);
                $id_usuario_robo = $ingest_user_id > 0 ? $ingest_user_id : 1;

                // Classificação opcional via regex + defaults
                $class = classify_document($titulo, $arquivo, $class_rules);
                $tipo_documento_id = $class['tipo_documento_id'] ?? null;
                if (!$tipo_documento_id && $default_tipo) $tipo_documento_id = (int)$default_tipo;
                $pasta_id = $class['pasta_id'] ?? null;
                if (!$pasta_id && $default_pasta) $pasta_id = (int)$default_pasta;

                // 3. Política de duplicação/versão por título
                $documento_id = null;
                $nova_versao = 1;
                if ($dup_policy === 'version_by_title') {
                    $stmt_exist = $pdo->prepare("SELECT id FROM documentos WHERE titulo_original = ? ORDER BY id LIMIT 1");
                    $stmt_exist->execute([$titulo]);
                    $documento_id = $stmt_exist->fetchColumn();
                    if ($documento_id) {
                        $stmt_maxv = $pdo->prepare("SELECT COALESCE(MAX(versao),0) FROM documento_versoes WHERE documento_id = ?");
                        $stmt_maxv->execute([$documento_id]);
                        $nova_versao = ((int)$stmt_maxv->fetchColumn()) + 1;
                        $stats['versoes']++;
                    }
                }

                if (!$documento_id) {
                    $sql_doc = "INSERT INTO documentos (titulo_original, versao_atual, id_usuario_criador) VALUES (?, ?, ?)";
                    $stmt_doc = $pdo->prepare($sql_doc);
                    $stmt_doc->execute([$titulo, 1, $id_usuario_robo]);
                    $documento_id = $pdo->lastInsertId();
                    $stats['novos']++;
                }

                // 4. Inserir a versão
                $sql_versao = "INSERT INTO documento_versoes 
                                   (documento_id, versao, tipo_documento_id, usuario_id, pasta_id, caminho_arquivo, nome_arquivo_original, nome_arquivo_sistema, hash_sha256) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_versao = $pdo->prepare($sql_versao);
                $stmt_versao->execute([
                    $documento_id,
                    $nova_versao,
                    $tipo_documento_id ?: null,
                    $id_usuario_robo,
                    $pasta_id ?: null,
                    $caminho_relativo_db,
                    $arquivo,
                    $nome_arquivo_sistema,
                    $hash_tmp
                ]);

                // 5. Atualizar documento pai (caminho, hash, páginas, data, versão)
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($caminho_destino_storage);
                    $quantidade_paginas = count($pdf->getPages());
                } catch (Throwable $e) { $quantidade_paginas = null; }

                try {
                    $stmt_upd_doc = $pdo->prepare("UPDATE documentos SET caminho_arquivo = ?, hash_arquivo = ?, quantidade_paginas = ?, data_upload = NOW(), versao_atual = ? WHERE id = ?");
                    $stmt_upd_doc->execute([$caminho_relativo_db, $hash_tmp, $quantidade_paginas, $nova_versao, $documento_id]);
                } catch (Throwable $e) { /* campos podem não existir em alguns esquemas */ }

                // 6. Mover original para processados (subpasta da data)
                $subdir = $pasta_processados . DIRECTORY_SEPARATOR . date('Y-m-d');
                if (!is_dir($subdir)) { @mkdir($subdir, 0777, true); }
                $caminho_destino_processado = $subdir . DIRECTORY_SEPARATOR . $arquivo;
                if (!@rename($caminho_original, $caminho_destino_processado)) {
                    throw new Exception("Falha ao mover arquivo original para a pasta de processados.");
                }

                $pdo->commit();
                log_msg('      -> SUCESSO: Documento ID #' . $documento_id . ' (versão ' . $nova_versao . ')');
                $arquivos_processados_count++;

                // 7. Indexar conteúdo para busca
                try {
                    $indexer = new PDFIndexer($pdo);
                    $resIdx = $indexer->indexarDocumento((int)$documento_id);
                    if (empty($resIdx['ok'])) {
                        log_msg('      -> Aviso: Indexação falhou: ' . ($resIdx['erro'] ?? 'erro'));
                    }
                } catch (Throwable $e) {
                    log_msg('      -> Aviso: Falha ao indexar documento: ' . $e->getMessage());
                }

                // 7.1. Verificar se documento possui assinaturas e acionar eventos especiais
                try {
                    // Verifica flag assinado
                    $stmtAssinado = $pdo->prepare("SELECT assinado FROM documentos WHERE id = ?");
                    $stmtAssinado->execute([$documento_id]);
                    $isAssinado = $stmtAssinado->fetchColumn();

                    // Verifica assinaturas em ambas as tabelas (dual-table)
                    $stmtContaAssinaturas = $pdo->prepare("
                        SELECT COUNT(*) FROM (
                            SELECT documento_id FROM documentos_assinaturas WHERE documento_id = :documento_id
                            UNION ALL
                            SELECT documento_id FROM assinaturas WHERE documento_id = :documento_id
                        ) t
                    ");
                    $stmtContaAssinaturas->execute([':documento_id' => $documento_id]);
                    $totalAssinaturas = (int)$stmtContaAssinaturas->fetchColumn();

                    if ($isAssinado || $totalAssinaturas > 0) {
                        log_msg('      -> DOCUMENTO ASSINADO DETECTADO! Total de assinaturas: ' . $totalAssinaturas);

                        // Atualizar flag assinado se ainda não marcado
                        if (!$isAssinado) {
                            $stmtUpdate = $pdo->prepare("UPDATE documentos SET assinado = 1 WHERE id = :documento_id");
                            $stmtUpdate->execute([':documento_id' => $documento_id]);
                            log_msg('      -> Flag assinado ativada no documento #' . $documento_id);
                        }

                        // Notificar assinantes sobre ingestão de documento assinado
                        $stmtSigners = $pdo->prepare("
                            SELECT DISTINCT usuario_id, nome_signatario 
                            FROM (
                                SELECT usuario_id, nome_signatario FROM documentos_assinaturas WHERE documento_id = :documento_id
                                UNION
                                SELECT usuario_id, nome_signatario FROM assinaturas WHERE documento_id = :documento_id
                            ) signers
                        ");
                        $stmtSigners->execute([':documento_id' => $documento_id]);
                        $signers = $stmtSigners->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($signers as $signer) {
                            try {
                                $msgSigner = "Documento assinado por você foi ingerido no sistema: '$titulo' (#$documento_id)";
                                $stmtNotifSigner = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                                $stmtNotifSigner->execute([$signer['usuario_id'], $msgSigner]);
                                log_msg('      -> Notificação enviada ao signatário: ' . $signer['nome_signatario']);
                            } catch (Throwable $e) { /* ignora se notifications não existe */ }
                        }

                        // Priorizar reindexação de documentos assinados (força refresh)
                        try {
                            $indexer->indexarDocumento((int)$documento_id);
                            log_msg('      -> Reindexação prioritária executada para documento assinado');
                        } catch (Throwable $e) { /* já tentamos antes */ }
                    }
                } catch (Throwable $e) {
                    log_msg('      -> Aviso: Verificação de assinaturas falhou: ' . $e->getMessage());
                }

                // 8. Log de auditoria
                try { registrar_log($pdo, $id_usuario_robo, "Ingestão automática do arquivo '$arquivo' como documento #$documento_id (v$nova_versao)."); } catch (Throwable $e) {}

                // 9. Notificação in-app (opcional)
                try {
                    $msgNotif = "Novo documento ingerido: '$titulo' (#$documento_id)";
                    $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmtNotif->execute([$id_usuario_robo, $msgNotif]);
                } catch (Throwable $e) { /* tabela pode não existir */ }

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                log_msg("      -> ERRO ao processar o arquivo '$arquivo': " . $e->getMessage());
                // Remove cópia do storage em caso de falha
                if (isset($caminho_destino_storage) && file_exists($caminho_destino_storage)) {
                    @unlink($caminho_destino_storage);
                }
                $stats['falhas']++;
            }
        }
    }

    $dur = microtime(true) - $inicio;
    log_msg('4. Processamento concluido. ' . $arquivos_processados_count . ' arquivos novos foram cadastrados.');
    log_msg('   Estatísticas: novos=' . $stats['novos'] . ', duplicados=' . $stats['duplicados'] . ', versoes=' . $stats['versoes'] . ', falhas=' . $stats['falhas']);
    log_msg('   Tempo total: ' . number_format($dur, 2) . 's | Memória: ' . number_format(memory_get_peak_usage(true)/1048576, 2) . ' MB');

} catch (PDOException $e) {
    log_msg('ERRO DE BANCO DE DADOS: ' . $e->getMessage());
} catch (Throwable $e) {
    log_msg('ERRO GERAL: ' . $e->getMessage());
}

log_msg('--- ROBO DE CAPTURA FINALIZADO ---');

// Libera lock
if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
