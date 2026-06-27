<?php
// public/relatorio_vencimentos_csv.php
// Exportação CSV do relatório de vencimentos
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$filtro = $_GET['filtro'] ?? 'todos';

try {
    $where = ["d.apagado_em IS NULL"];
    
    if ($filtro === 'vencidos') {
        $where[] = "d.data_vencimento < CURDATE()";
    } elseif ($filtro === 'a_vencer_7') {
        $where[] = "d.data_vencimento >= CURDATE() AND d.data_vencimento < DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($filtro === 'a_vencer_30') {
        $where[] = "d.data_vencimento >= CURDATE() AND d.data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($filtro === 'sem_vencimento') {
        $where[] = "d.data_vencimento IS NULL";
    } else {
        $where[] = "d.data_vencimento IS NOT NULL";
    }

    $sql = "SELECT 
                d.id,
                d.titulo,
                t.nome as tipo_nome,
                u.nome as usuario_nome,
                DATE_FORMAT(d.data_upload, '%d/%m/%Y') as data_criacao,
                DATE_FORMAT(d.data_vencimento, '%d/%m/%Y') as data_vencimento,
                CASE 
                    WHEN d.data_vencimento IS NULL THEN 'Sem vencimento'
                    WHEN d.data_vencimento < CURDATE() THEN 'Vencido'
                    WHEN d.data_vencimento < DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Urgente (7 dias)'
                    WHEN d.data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Próximo (30 dias)'
                    ELSE 'OK'
                END as status
            FROM documentos d
            LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.data_vencimento ASC, d.titulo ASC";
    
    $stmt = $pdo->query($sql);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cabeçalhos HTTP para download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="vencimentos_' . $filtro . '_' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // BOM UTF-8 para Excel reconhecer encoding
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');
    
    // Cabeçalho CSV
    fputcsv($output, ['ID', 'Título', 'Tipo', 'Proprietário', 'Data Criação', 'Data Vencimento', 'Status'], ';');
    
    // Dados
    foreach ($documentos as $doc) {
        fputcsv($output, [
            $doc['id'],
            $doc['titulo'],
            $doc['tipo_nome'],
            $doc['usuario_nome'],
            $doc['data_criacao'],
            $doc['data_vencimento'] ?? 'N/A',
            $doc['status']
        ], ';');
    }
    
    fclose($output);
    exit();

} catch (Exception $e) {
    die("Erro ao gerar CSV: " . $e->getMessage());
}
