<?php
require_once __DIR__ . '/../core/init.php';
header('Content-Type: application/json');

$docId = isset($_GET['documento_id']) ? (int)$_GET['documento_id'] : 0;
$tipoId = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : 0;
if ($docId <= 0 && $tipoId <= 0) {
    echo json_encode(['erro' => 'Parâmetros inválidos']);
    exit();
}

try {
    if ($tipoId <= 0) {
        $st = $pdo->prepare('SELECT tipo_documento_id FROM documentos WHERE id = ? AND apagado_em IS NULL');
        $st->execute([$docId]);
        $tipoId = (int)$st->fetchColumn();
    }
    if ($tipoId <= 0) { throw new Exception('Tipo não encontrado'); }

    $sql = "SELECT mc.id, mc.rotulo,
                   dm.valor
            FROM metadado_campos mc
            LEFT JOIN documento_metadados dm
              ON dm.campo_id = mc.id AND dm.documento_id = :doc
            WHERE mc.tipo_documento_id = :tipo
            ORDER BY mc.ordem ASC, mc.rotulo ASC";
    $q = $pdo->prepare($sql);
    $q->execute([':doc' => $docId, ':tipo' => $tipoId]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    echo json_encode(['itens' => $rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
exit();

