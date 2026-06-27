<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Gera ou retorna o token CSRF atual da sessão
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gera o HTML do campo hidden com o token CSRF
 */
function csrf_input(): string {
    $token = csrf_token();
    return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida o token CSRF recebido em requisições POST/PUT/DELETE
 */
function csrf_validate(): bool {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') { return true; }
    $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token) { return false; }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Exige CSRF válido; caso inválido, redireciona para acesso_negado com erro 403
 */
function require_csrf_or_abort(): void {
    if (!csrf_validate()) {
        http_response_code(403);
        if (php_sapi_name() !== 'cli') {
            header('Location: acesso_negado.php?err=csrf');
        }
        exit('CSRF inválido.');
    }
}
