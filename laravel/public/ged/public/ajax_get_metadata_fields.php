<?php
// public/ajax_get_metadata_fields.php
require_once '../core/init.php';

header('Content-Type: application/json');

$tipo_id = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : 0;

if ($tipo_id <= 0) {
    echo json_encode([]); // Retorna um array vazio se o ID for inválido
    exit();
}

try {
    // Busca os campos de metadados associados a este tipo de documento
    $sql = "SELECT identificador, rotulo, conteudo FROM metadado_campos WHERE tipo_documento_id = ? ORDER BY ordem ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tipo_id]);
    $campos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Retorna os campos como JSON
    echo json_encode($campos);

} catch (PDOException $e) {
    // Em caso de erro, retorna um array vazio e loga o erro (idealmente)
    // error_log($e->getMessage());
    echo json_encode([]);
}