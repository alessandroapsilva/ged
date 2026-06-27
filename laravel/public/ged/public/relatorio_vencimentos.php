<?php
// public/relatorio_vencimentos.php
// Relatório de documentos por status de vencimento
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$filtro = $_GET['filtro'] ?? 'todos';
$pasta_id = isset($_GET['pasta_id']) ? (int)$_GET['pasta_id'] : null;

try {
    // Estatísticas gerais
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(data_vencimento) as com_vencimento,
            SUM(CASE WHEN data_vencimento < CURDATE() THEN 1 ELSE 0 END) as vencidos,
            SUM(CASE WHEN data_vencimento >= CURDATE() AND data_vencimento < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as a_vencer_30,
            SUM(CASE WHEN data_vencimento >= CURDATE() AND data_vencimento < DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as a_vencer_7
        FROM documentos 
        WHERE apagado_em IS NULL
    ")->fetch(PDO::FETCH_ASSOC);

    // Query base
    $where = ["d.apagado_em IS NULL"];
    $params = [];
    
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
    
    if ($pasta_id) {
        $where[] = "d.pasta_id = :pasta_id";
        $params[':pasta_id'] = $pasta_id;
    }

    $sql = "SELECT d.*, t.nome as tipo_nome, u.nome as usuario_nome
            FROM documentos d
            LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.data_vencimento ASC, d.titulo ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Erro: " . $e->getMessage());
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="far fa-calendar-check"></i> Relatório de Vencimentos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade.php">Painel</a></li>
                        <li class="breadcrumb-item active">Vencimentos</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Cards de estatísticas -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['com_vencimento'] ?></h3>
                            <p>Com Vencimento</p>
                        </div>
                        <div class="icon"><i class="far fa-calendar"></i></div>
                        <a href="?filtro=todos" class="small-box-footer">Ver todos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['vencidos'] ?></h3>
                            <p>Vencidos</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <a href="?filtro=vencidos" class="small-box-footer">Ver vencidos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['a_vencer_7'] ?></h3>
                            <p>A Vencer (7 dias)</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <a href="?filtro=a_vencer_7" class="small-box-footer">Ver urgentes <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= $stats['total'] - $stats['com_vencimento'] ?></h3>
                            <p>Sem Vencimento</p>
                        </div>
                        <div class="icon"><i class="fas fa-infinity"></i></div>
                        <a href="?filtro=sem_vencimento" class="small-box-footer">Ver permanentes <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            </div>

            <!-- Tabela de documentos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php
                        $titulo_filtro = [
                            'todos' => 'Todos os documentos com vencimento',
                            'vencidos' => 'Documentos vencidos',
                            'a_vencer_7' => 'Documentos a vencer (7 dias)',
                            'a_vencer_30' => 'Documentos a vencer (30 dias)',
                            'sem_vencimento' => 'Documentos sem vencimento (permanentes)'
                        ];
                        echo $titulo_filtro[$filtro] ?? 'Documentos';
                        ?>
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-success" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                        <a href="relatorio_vencimentos_csv.php?filtro=<?= htmlspecialchars($filtro) ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-download"></i> Exportar CSV
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($documentos)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="far fa-folder-open fa-3x mb-3"></i>
                            <p>Nenhum documento encontrado nesta categoria.</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Tipo</th>
                                    <th>Proprietário</th>
                                    <th>Criado</th>
                                    <th>Vencimento</th>
                                    <th>Status</th>
                                    <th class="text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documentos as $doc): ?>
                                    <?php
                                    $status_class = 'text-muted';
                                    $status_text = 'N/A';
                                    $status_icon = 'minus-circle';
                                    
                                    if (!empty($doc['data_vencimento'])) {
                                        $dataVenc = new DateTime($doc['data_vencimento']);
                                        $agora = new DateTime();
                                        $diff = $agora->diff($dataVenc);
                                        
                                        if ($diff->invert) {
                                            $status_class = 'text-danger';
                                            $status_text = 'Vencido';
                                            $status_icon = 'exclamation-triangle';
                                        } elseif ($diff->days <= 7) {
                                            $status_class = 'text-warning';
                                            $status_text = 'Urgente';
                                            $status_icon = 'clock';
                                        } elseif ($diff->days <= 30) {
                                            $status_class = 'text-info';
                                            $status_text = 'Próximo';
                                            $status_icon = 'hourglass-half';
                                        } else {
                                            $status_class = 'text-success';
                                            $status_text = 'OK';
                                            $status_icon = 'check-circle';
                                        }
                                        
                                        $textoVencimento = $diff->y > 0 ? "em " . $diff->y . " anos" : 
                                                          ($diff->m > 0 ? "em " . $diff->m . " meses" : 
                                                          "em " . $diff->days . " dias");
                                        if ($diff->invert) $textoVencimento = "Vencido";
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $doc['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($doc['titulo']) ?></strong>
                                            <?php if (!empty($doc['descricao'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($doc['descricao'], 0, 80, '…')) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($doc['tipo_nome']) ?></td>
                                        <td><?= htmlspecialchars($doc['usuario_nome']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($doc['data_upload'])) ?></td>
                                        <td>
                                            <?php if (!empty($doc['data_vencimento'])): ?>
                                                <?= date('d/m/Y', strtotime($doc['data_vencimento'])) ?>
                                                <br><small class="text-muted">(<?= $textoVencimento ?>)</small>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="<?= $status_class ?>">
                                                <i class="fas fa-<?= $status_icon ?>"></i> <?= $status_text ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <a href="documentos_ver.php?id=<?= $doc['id'] ?>" class="btn btn-xs btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="documentos_propriedades.php?id=<?= $doc['id'] ?>" class="btn btn-xs btn-secondary" title="Propriedades">
                                                <i class="fas fa-list-ul"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <?php if (!empty($documentos)): ?>
                    <div class="card-footer text-muted">
                        Total: <?= count($documentos) ?> documento(s)
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<style media="print">
    .main-header, .main-sidebar, .content-header, .card-tools, .btn, .main-footer { display: none !important; }
    .content-wrapper { margin: 0 !important; padding: 0 !important; }
</style>
