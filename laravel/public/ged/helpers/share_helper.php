<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function _doc_links_has_flag(PDO $pdo, string $col): bool {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM documento_links LIKE ?");
        $st->execute([$col]);
        return (bool)$st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { return false; }
}

function criar_link_compartilhado(PDO $pdo, int $documento_id, int $usuario_id, ?string $senha = null, ?string $expira_em = null, ?int $max_downloads = null, ?bool $view_only = null, ?bool $force_watermark = null): array {
    $code = substr(bin2hex(random_bytes(16)), 0, 32);
    $password_hash = $senha ? password_hash($senha, PASSWORD_DEFAULT) : null;
    $has_view_only = _doc_links_has_flag($pdo, 'view_only');
    $has_force_wm = _doc_links_has_flag($pdo, 'force_watermark');
    if ($has_view_only || $has_force_wm) {
        $view_only = (int)($view_only ? 1 : 0);
        $force_watermark = (int)($force_watermark ? 1 : 0);
        $sql = "INSERT INTO documento_links (documento_id, code, password_hash, expires_at, max_downloads, downloads, created_by, created_at";
        $vals = [$documento_id, $code, $password_hash, $expira_em, $max_downloads, 0, $usuario_id];
        $ph = "VALUES (?,?,?,?,?,?,?,NOW()";
        if ($has_view_only) { $sql .= ", view_only"; $ph .= ",?"; $vals[] = $view_only; }
        if ($has_force_wm) { $sql .= ", force_watermark"; $ph .= ",?"; $vals[] = $force_watermark; }
        $sql .= ") " . $ph . ")";
        $ins = $pdo->prepare($sql);
        $ins->execute($vals);
    } else {
        $ins = $pdo->prepare("INSERT INTO documento_links (documento_id, code, password_hash, expires_at, max_downloads, downloads, created_by, created_at) VALUES (?,?,?,?,?,?,?,NOW())");
        $ins->execute([$documento_id, $code, $password_hash, $expira_em, $max_downloads, 0, $usuario_id]);
    }
    return ['ok' => true, 'code' => $code];
}

function validar_link_e_autorizar(PDO $pdo, string $code, ?string $senha = null): array {
    $sel = $pdo->prepare("SELECT * FROM documento_links WHERE code = ? LIMIT 1");
    $sel->execute([$code]);
    $link = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$link) { return ['ok' => false, 'error' => 'Link inválido']; }
    if (!empty($link['expires_at']) && strtotime($link['expires_at']) < time()) { return ['ok' => false, 'error' => 'Link expirado']; }
    if (!empty($link['max_downloads']) && (int)$link['downloads'] >= (int)$link['max_downloads']) { return ['ok' => false, 'error' => 'Limite de downloads atingido']; }
    if (!empty($link['password_hash'])) {
        if (!$senha || !password_verify($senha, $link['password_hash'])) {
            return ['ok' => false, 'error' => 'Senha inválida', 'requires_password' => true];
        }
    }
    return ['ok' => true, 'link' => $link];
}

function registrar_download_link(PDO $pdo, int $id): void {
    $upd = $pdo->prepare("UPDATE documento_links SET downloads = downloads + 1 WHERE id = ?");
    $upd->execute([$id]);
}

function share_set_view_only(PDO $pdo, string $code, bool $enable): void {
    // Atualiza view_only se a coluna existir
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM documento_links LIKE 'view_only'");
        $st->execute();
        if ($st->fetch()) {
            $upd = $pdo->prepare("UPDATE documento_links SET view_only = ? WHERE code = ?");
            $upd->execute([$enable ? 1 : 0, $code]);
        }
    } catch (Throwable $e) {}
}
