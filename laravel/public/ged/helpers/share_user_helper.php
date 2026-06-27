<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function share_user_create(PDO $pdo, int $documento_id, int $user_id, int $granted_by, bool $view_only, bool $can_download, ?string $expires_at = null, ?string $note = null): array {
    try {
        // Revoga anterior ativo para mesmo doc/usuário (se existir) e cria novo
        $pdo->beginTransaction();
        $upd = $pdo->prepare("UPDATE documento_compartilhamentos_usuario SET revoked_at = NOW() WHERE documento_id = ? AND user_id = ? AND revoked_at IS NULL");
        $upd->execute([$documento_id, $user_id]);
        $ins = $pdo->prepare("INSERT INTO documento_compartilhamentos_usuario (documento_id, user_id, granted_by, can_download, view_only, expires_at, note, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
        $ins->execute([$documento_id, $user_id, $granted_by, $can_download ? 1 : 0, $view_only ? 1 : 0, $expires_at, $note]);
        $pdo->commit();
        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function share_user_list(PDO $pdo, int $documento_id): array {
    $sql = "SELECT s.*, u.nome AS usuario_nome, u.email AS usuario_email, g.nome AS concedente_nome
            FROM documento_compartilhamentos_usuario s
            LEFT JOIN usuarios u ON u.id = s.user_id
            LEFT JOIN usuarios g ON g.id = s.granted_by
            WHERE s.documento_id = ?
            ORDER BY s.created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$documento_id]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function share_user_revoke(PDO $pdo, int $share_id, int $documento_id): bool {
    $st = $pdo->prepare("UPDATE documento_compartilhamentos_usuario SET revoked_at = NOW() WHERE id = ? AND documento_id = ? AND revoked_at IS NULL");
    $st->execute([$share_id, $documento_id]);
    return $st->rowCount() > 0;
}

function share_user_toggle_field(PDO $pdo, int $share_id, int $documento_id, string $field, int $value): bool {
    // Permite alternar apenas campos booleanos controlados
    $allowed = ['view_only', 'can_download'];
    if (!in_array($field, $allowed, true)) {
        throw new InvalidArgumentException('Campo inválido para alternância');
    }
    $value = $value ? 1 : 0;
    $sql = "UPDATE documento_compartilhamentos_usuario SET {$field} = :v WHERE id = :id AND documento_id = :doc AND revoked_at IS NULL";
    $st = $pdo->prepare($sql);
    $st->bindValue(':v', $value, PDO::PARAM_INT);
    $st->bindValue(':id', $share_id, PDO::PARAM_INT);
    $st->bindValue(':doc', $documento_id, PDO::PARAM_INT);
    $st->execute();
    return $st->rowCount() > 0;
}

function share_user_get_active(PDO $pdo, int $documento_id, int $user_id): ?array {
    $sql = "SELECT * FROM documento_compartilhamentos_usuario
            WHERE documento_id = ? AND user_id = ? AND revoked_at IS NULL
              AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY id DESC LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$documento_id, $user_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
