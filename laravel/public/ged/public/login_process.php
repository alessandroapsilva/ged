<?php
// public/login_process.php — autenticação por NOME DE USUÁRIO (com fallback por e-mail)
require_once '../core/init.php';
require_once '../helpers/auth_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limit simples por sessão: 5 tentativas em 10 minutos
    $now = time();
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        function($ts) use ($now) { return ($now - $ts) < 600; }
    );
    if (count($_SESSION['login_attempts']) >= 5) {
    header('Location: login?error=rate');
        exit();
    }

    $usernameOrEmail = isset($_POST['username']) ? trim($_POST['username']) : '';
    $senha = isset($_POST['senha']) ? (string)$_POST['senha'] : '';

    if ($usernameOrEmail === '' || $senha === '') {
    header('Location: login?error=1');
        exit();
    }

    // Descobre esquema disponível na base atual: 'usuarios' (GED) ou 'users' (schema alternativo)
    $user = null; $table = null;
    $hasUsuarios = true;
    try {
        $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'usuarios'");
        $q->execute();
        $hasUsuarios = ((int)$q->fetchColumn() > 0);
    } catch (Throwable $e) { $hasUsuarios = true; }

    if ($hasUsuarios) {
        // Verifica se coluna 'ativo' existe para filtrar
        $hasAtivo = false;
        try {
            $qc = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'ativo'");
            $qc->execute();
            $hasAtivo = ((int)$qc->fetchColumn() > 0);
        } catch (Throwable $e) {}
        $sql = "SELECT id, nome, senha, funcao_id" . ($hasAtivo ? ", ativo" : ", 1 AS ativo") . " FROM usuarios WHERE username = ?" . ($hasAtivo ? " AND ativo = 1" : "");
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $table = 'usuarios';
    } else {
        // Tenta tabela 'users'
        $hasUsers = false;
        try {
            $q2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'users'");
            $q2->execute();
            $hasUsers = ((int)$q2->fetchColumn() > 0);
        } catch (Throwable $e) {}
        if ($hasUsers) {
            $sql = "SELECT id, name AS nome, password_hash AS senha, NULL AS funcao_id, 1 AS ativo FROM users WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$usernameOrEmail]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $table = 'users';
        }
    }

    if ($user && isset($user['senha']) && password_verify($senha, (string)$user['senha'])) {
        // 2FA: somente se estiver usando tabela 'usuarios' com colunas de 2FA
        $twofa_enabled = 0; $twofa_secret = null;
        if ($table === 'usuarios') {
            try {
                $st2 = $pdo->prepare('SELECT twofa_enabled, twofa_secret FROM usuarios WHERE id = ?');
                $st2->execute([(int)$user['id']]);
                $r2 = $st2->fetch(PDO::FETCH_ASSOC) ?: [];
                $twofa_enabled = (int)($r2['twofa_enabled'] ?? 0);
                $twofa_secret = $r2['twofa_secret'] ?? null;
            } catch (Throwable $e) { /* coluna pode não existir */ }
        }

        if ($twofa_enabled === 1 && $twofa_secret) {
            // Etapa de verificação 2FA pendente
            $_SESSION['2fa_pending_user'] = (int)$user['id'];
            $_SESSION['2fa_pending_name'] = $user['nome'];
            $_SESSION['2fa_pending_funcao_id'] = isset($user['funcao_id']) ? (int)$user['funcao_id'] : null;
            $_SESSION['2fa_pending_until'] = time() + 300;
            header('Location: 2fa_verify.php');
            exit();
        }

        // Regera o ID da sessão por segurança
        session_regenerate_id(true);

        // Popula sessão básica
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['nome'];
        // Determina função
        $funcao = isset($user['funcao_id']) ? (int)$user['funcao_id'] : null;
        if (!$funcao && $table === 'users') {
            // Mapeia role textual para função
            try {
                $rs = $pdo->prepare('SELECT role FROM users WHERE id = ?');
                $rs->execute([(int)$user['id']]);
                $role = (string)$rs->fetchColumn();
                $funcao = ($role === 'Administrador') ? 1 : (($role === 'Diretor') ? 2 : 3);
            } catch (Throwable $e) { $funcao = 3; }
        }
        $_SESSION['user_funcao_id'] = $funcao ?: 3;

        // Carrega permissões da função (admin id 1 tem acesso total via chave mestra)
        $permissoes = [];
        if ((int)$user['id'] !== 1 && $funcao) {
            try {
                $perm_stmt = $pdo->prepare('SELECT p.chave FROM permissoes p JOIN funcao_permissao fp ON p.id = fp.permissao_id WHERE fp.funcao_id = ?');
                $perm_stmt->execute([$funcao]);
                $permissoes = $perm_stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            } catch (Throwable $e) { $permissoes = []; }
        }
        $_SESSION['user_permissions'] = $permissoes;
        if (function_exists('sincronizar_chaves_de_permissoes_na_sessao')) { sincronizar_chaves_de_permissoes_na_sessao(); } else { $_SESSION['permissoes'] = $permissoes; }

        // Limpa tentativas após sucesso
        $_SESSION['login_attempts'] = [];

    header('Location: documentos');
        exit();
    }

    // Falha: registra tentativa e retorna erro genérico
    $_SESSION['login_attempts'][] = $now;
    header('Location: login?error=1');
    exit();
}

// Acesso direto -> volta ao login
header('Location: login');
exit();
