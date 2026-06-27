<?php
// public/ajax_salvar_tema.php (VERSÃO SIMPLES, APENAS SESSÃO)
require_once '../core/init.php'; // init.php já inicia a sessão

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$tema = $data['tema'] ?? null;

if ($tema !== 'light' && $tema !== 'dark') {
    echo json_encode(['success' => false, 'message' => 'Tema inválido.']);
    exit();
}

// A única ação agora é salvar na sessão
$_SESSION['user_theme'] = $tema;

echo json_encode(['success' => true]);