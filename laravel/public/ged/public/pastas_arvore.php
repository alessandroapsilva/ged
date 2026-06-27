<?php
// public/pastas_arvore.php
require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

function get_pasta_tree($pdo, $pasta_pai_id = null) {
    $sql = "SELECT id, nome FROM pastas WHERE pasta_pai_id <=> :pasta_pai_id AND apagado_em IS NULL ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':pasta_pai_id' => $pasta_pai_id]);
    $pastas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tree = [];
    foreach ($pastas as $pasta) {
        $subpastas = get_pasta_tree($pdo, $pasta['id']);
        $node = [
            'id' => $pasta['id'],
            'nome' => $pasta['nome'],
            'subpastas' => $subpastas
        ];
        $tree[] = $node;
    }
    return $tree;
}

$arvore_de_pastas = get_pasta_tree($pdo);
echo json_encode($arvore_de_pastas);
?>