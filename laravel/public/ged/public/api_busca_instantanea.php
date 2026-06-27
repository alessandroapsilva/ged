<?php
/**
 * API de Busca Instantânea
 * Retorna resultados em JSON para autocomplete e busca em tempo real
 */

require_once '../core/init.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$query = $_GET['q'] ?? '';
$isInstant = isset($_GET['instant']) && $_GET['instant'] == '1';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit();
}

$results = [];
$searchTerm = '%' . $query . '%';

try {
    // Busca em documentos
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.titulo,
            d.descricao,
            t.nome as tipo_nome,
            'documento' as tipo
        FROM documentos d
        LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
        WHERE d.apagado_em IS NULL
        AND (d.titulo LIKE ? OR d.descricao LIKE ?)
        ORDER BY d.data_upload DESC
        LIMIT 10
    ");
    $stmt->execute([$searchTerm, $searchTerm]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($documentos as $doc) {
        $results[] = [
            'title' => $doc['titulo'],
            'description' => substr($doc['descricao'] ?: 'Sem descrição', 0, 100),
            'url' => 'documentos_ver.php?id=' . $doc['id'],
            'icon' => 'fas fa-file-alt',
            'badge' => $doc['tipo_nome'],
            'badgeType' => 'primary'
        ];
    }

    // Busca em pastas
    if (!$isInstant || count($results) < 5) {
        $stmt = $pdo->prepare("
            SELECT id, nome
            FROM pastas
            WHERE apagado_em IS NULL
            AND nome LIKE ?
            ORDER BY nome ASC
            LIMIT 5
        ");
        $stmt->execute([$searchTerm]);
        $pastas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pastas as $pasta) {
            $results[] = [
                'title' => $pasta['nome'],
                'description' => 'Pasta',
                'url' => 'documentos.php?pasta_id=' . $pasta['id'],
                'icon' => 'fas fa-folder',
                'badge' => 'Pasta',
                'badgeType' => 'success'
            ];
        }
    }

    // Busca em usuários (apenas admin)
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Administrador') {
        if (!$isInstant || count($results) < 8) {
            $stmt = $pdo->prepare("
                SELECT id, nome, email
                FROM usuarios
                WHERE nome LIKE ? OR email LIKE ?
                ORDER BY nome ASC
                LIMIT 3
            ");
            $stmt->execute([$searchTerm, $searchTerm]);
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($usuarios as $user) {
                $results[] = [
                    'title' => $user['nome'],
                    'description' => $user['email'],
                    'url' => 'usuarios_editar.php?id=' . $user['id'],
                    'icon' => 'fas fa-user',
                    'badge' => 'Usuário',
                    'badgeType' => 'info'
                ];
            }
        }
    }

} catch (PDOException $e) {
    error_log("Erro na busca: " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar']);
    exit();
}

echo json_encode($results);
