<?php
require_once '../core/init.php';
require_once '../helpers/log_helper.php';
require_once '../helpers/auth_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Rate limit simples por sessão: 5 tentativas a cada 10 minutos
    $now = time();
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? [];
    // Remove tentativas antigas
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($ts) use ($now){ return ($now - $ts) < 600; });
    if (count($_SESSION['login_attempts']) >= 5) {
        header("Location: login.php?erro=rate");
        exit();
    }

    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    $sql_user = "SELECT id, nome, senha, funcao_id FROM usuarios WHERE email = ? AND ativo = 1";
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->execute([$email]);
    $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Sucesso no login! Agora, busca as permissões da função do usuário.
        $sql_perms = "SELECT p.chave
                      FROM permissoes p
                      JOIN funcao_permissao fp ON p.id = fp.permissao_id
                      WHERE fp.funcao_id = ?";
        $stmt_perms = $pdo->prepare($sql_perms);
        $stmt_perms->execute([$usuario['funcao_id']]);
        
        $permissoes_chaves = $stmt_perms->fetchAll(PDO::FETCH_COLUMN);

        // Armazena tudo na sessão
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nome'];
        $_SESSION['funcao_id'] = $usuario['funcao_id'];
        $_SESSION['permissoes'] = $permissoes_chaves; // chaves de permissões
        // Mantém compatibilidade entre módulos
        if (!isset($_SESSION['user_permissions'])) {
            $_SESSION['user_permissions'] = $permissoes_chaves;
        }
        if (function_exists('sincronizar_chaves_de_permissoes_na_sessao')) {
            sincronizar_chaves_de_permissoes_na_sessao();
        }

        registrar_log($pdo, $usuario['id'], 'Login bem-sucedido.');
    // Zera tentativas
    $_SESSION['login_attempts'] = [];

        header("Location: documentos_listar.php");
        exit();
    } else {
        // Registra falha
        $_SESSION['login_attempts'][] = $now;
        header("Location: login.php?erro=1");
        exit();
    }
}
?>