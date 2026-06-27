<?php
// helpers/auth_helper.php (compatível com diferentes estruturas de sessão)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Normaliza o array de permissões da sessão para uma chave única
 */
function __ged_get_session_permissions(): array {
    // Compatibilidade: alguns módulos usam 'permissoes', outros 'user_permissions'
    if (isset($_SESSION['permissoes']) && is_array($_SESSION['permissoes'])) {
        return $_SESSION['permissoes'];
    }
    if (isset($_SESSION['user_permissions']) && is_array($_SESSION['user_permissions'])) {
        return $_SESSION['user_permissions'];
    }
    return [];
}

/**
 * Verifica se o usuário autenticado possui determinada permissão
 */
function usuario_tem_permissao(string $chave_permissao): bool {
    // Chave mestra para o admin (ID 1) sempre ter acesso a tudo
    if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === 1) {
        return true;
    }

    $perms = __ged_get_session_permissions();
    return in_array($chave_permissao, $perms, true);
}

// Alias em inglês para chamadas legadas
function has_permission(string $key): bool { return usuario_tem_permissao($key); }

/**
 * Garante que a sessão contenha as duas chaves para compatibilidade
 */
function sincronizar_chaves_de_permissoes_na_sessao(): void {
    $perms = __ged_get_session_permissions();
    $_SESSION['permissoes'] = $perms;
    $_SESSION['user_permissions'] = $perms;
}

/**
 * Exige que o usuário esteja autenticado
 */
function require_auth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Exige determinada permissão; caso contrário redireciona para acesso_negado
 */
function require_permission(string $perm): void {
    if (!usuario_tem_permissao($perm)) {
        if (php_sapi_name() !== 'cli') {
            header('Location: acesso_negado.php');
        }
        exit('Acesso negado.');
    }
}
