<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';

// Requer estar logado e poder compartilhar (usa mesma permissão do fluxo)
require_auth();
if (function_exists('require_permission')) { require_permission('document.share'); }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$q = trim($_GET['q'] ?? '');
$limit = 15;
$items = [];
try {
    if ($q === '') {
        $stmt = $pdo->prepare("SELECT destinatario, COUNT(*) c FROM emails_log GROUP BY destinatario ORDER BY c DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT destinatario, COUNT(*) c FROM emails_log WHERE destinatario LIKE ? GROUP BY destinatario ORDER BY c DESC LIMIT ?");
        $stmt->bindValue(1, $q . '%', PDO::PARAM_STR);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (filter_var($row['destinatario'], FILTER_VALIDATE_EMAIL)) {
            $items[] = $row['destinatario'];
        }
    }
} catch (Throwable $e) {}

echo json_encode(['suggestions' => $items], JSON_UNESCAPED_UNICODE);
