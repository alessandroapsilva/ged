<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
require_once '../core/init.php';
require_once '../db_config.php';
require_once '../helpers/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nome = trim($_POST['nome']);
    $usernameInput = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $funcao_id = (int)$_POST['funcao_id'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (empty($nome) || empty($email) || empty($senha) || empty($funcao_id)) {
        header('Location: usuarios_adicionar.php?erro=campos_vazios');
        exit();
    }

    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Detecta se a coluna username existe
    $hasUsername = false;
    try {
        $col = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
        $hasUsername = (bool)$col->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $hasUsername = false; }

    // Função de sanitização para username
    $sanitize = function (string $s): string {
        $s = mb_strtolower($s, 'UTF-8');
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
        $s = preg_replace('/[^a-z0-9._-]+/', '', $s);
        return trim($s);
    };

    try {
        if ($hasUsername) {
            // Username é OBRIGATÓRIO: sanitiza e verifica unicidade
            $username = $sanitize($usernameInput);
            if ($username === '') {
                header('Location: usuarios_adicionar.php?erro=username_vazio');
                exit();
            }

            $check = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetchColumn() > 0) {
                header('Location: usuarios_adicionar.php?erro=username_duplicado');
                exit();
            }

            $sql = "INSERT INTO usuarios (nome, username, email, senha, funcao_id, ativo) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $username, $email, $senha_hash, $funcao_id, $ativo]);
            $novo_usuario_id = $pdo->lastInsertId();
        } else {
            // Sem suporte a username: fluxo antigo
            $sql = "INSERT INTO usuarios (nome, email, senha, funcao_id, ativo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $senha_hash, $funcao_id, $ativo]);
            $novo_usuario_id = $pdo->lastInsertId();
        }

        $acao_log = "Criou o usuário '{$nome}' (ID: {$novo_usuario_id}).";

        registrar_log($pdo, $_SESSION['user_id'], $acao_log);
        
        header('Location: usuarios_listar.php?sucesso=usuario_criado');
        exit();

    } catch (PDOException $e) {
        // Redireciona com um erro genérico. O erro real pode ser visto nos logs do servidor.
        header('Location: usuarios_adicionar.php?erro=db_error');
        exit();
    }
} else {
    header('Location: usuarios_listar.php');
    exit();
}
?>