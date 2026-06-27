<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/**
 * Cria uma versão do documento com snapshot dos principais campos/arquivo.
 */
function criar_versao_documento(PDO $pdo, int $documento_id, int $usuario_id, ?string $motivo = null): ?int {
    try {
        $stmt = $pdo->prepare("SELECT titulo, descricao, caminho_arquivo, hash_arquivo, quantidade_paginas FROM documentos WHERE id = ?");
        $stmt->execute([$documento_id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$doc) { return null; }

        $stmtV = $pdo->prepare("SELECT COALESCE(MAX(versao),0) FROM documento_versoes WHERE documento_id = ?");
        $stmtV->execute([$documento_id]);
        $versaoNova = (int)$stmtV->fetchColumn() + 1;

        $ins = $pdo->prepare("INSERT INTO documento_versoes (documento_id, versao, titulo, descricao, caminho_arquivo, hash_arquivo, quantidade_paginas, criado_em, criado_por, motivo) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ins->execute([
            $documento_id,
            $versaoNova,
            $doc['titulo'],
            $doc['descricao'],
            $doc['caminho_arquivo'],
            $doc['hash_arquivo'] ?? null,
            $doc['quantidade_paginas'] ?? null,
            date('Y-m-d H:i:s'),
            $usuario_id,
            $motivo
        ]);

        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        error_log('criar_versao_documento falhou: ' . $e->getMessage());
        return null;
    }
}
