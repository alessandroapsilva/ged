<?php
require_once '../core/init.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit('Acesso negado');
}

try {
    $termo = isset($_GET['q']) ? $_GET['q'] : '';
    
    $sql = "SELECT id, nome as text FROM usuarios 
            WHERE status = 'ativo' 
            AND (nome LIKE ? OR email LIKE ?) 
            ORDER BY nome 
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $termo = "%$termo%";
    $stmt->execute([$termo, $termo]);
    
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($usuarios);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}