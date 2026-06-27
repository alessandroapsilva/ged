<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/ingest_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$f = $_GET['f'] ?? '';
$where = '1=1';
switch ($f) {
    case 'admitidos_hoje':
        $where = "status='admitido' AND DATE(admitido_em)=CURDATE()"; break;
    case 'admitidos_ontem':
        $where = "status='admitido' AND DATE(admitido_em)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; break;
    case 'admitidos_7d':
        $where = "status='admitido' AND admitido_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    case 'corrigidos':
        $where = "status='corrigido'"; break;
    case 'a_corrigir':
        $where = "status='corrigir'"; break;
    default:
        $where = '1=1';
}
$sql = "SELECT * FROM ingest_arquivos WHERE $where ORDER BY id DESC LIMIT 200";
$itens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <?php include_once '../templates/partials/notifications.php'; ?>
    <section class="content-header"><div class="container-fluid"><h1>eDok Ingest - Listagem</h1></div></section>
    <section class="content"><div class="container-fluid">
        <div class="card card-dark card-outline">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Resultados</h3>
                <a href="ingest.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th style="width:60px;">Nº</th>
                            <th>Arquivo Original</th>
                            <th>Falha</th>
                            <th>Origem</th>
                            <th class="text-right">Tamanho</th>
                            <th>Data</th>
                            <th class="text-right">Ações</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($itens)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum item encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($itens as $arq): ?>
                            <tr>
                                <td><?= (int)$arq['id'] ?></td>
                                <td>
                                    <i class="far fa-file-pdf text-danger mr-1"></i>
                                    <a href="<?= '../' . htmlspecialchars($arq['caminho_relativo']) ?>" target="_blank"><?= htmlspecialchars($arq['nome_original']) ?></a>
                                    <div class="small mt-1">Status: <?= ingest_status_badge($arq['status']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($arq['falha_motivo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($arq['origem'] ?? '') ?></td>
                                <td class="text-right"><?php if ($arq['tamanho_bytes']) echo number_format($arq['tamanho_bytes']/1024, 2, ',', '.') . ' KB'; ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($arq['criado_em'])) ?></td>
                                <td class="text-right">
                                    <div class="btn-group btn-group-sm">
                                        <a class="btn btn-outline-secondary" href="<?= '../' . htmlspecialchars($arq['caminho_relativo']) ?>" target="_blank" title="Visualizar"><i class="fas fa-eye"></i></a>
                                        <?php if ($arq['status'] === 'pendente' || $arq['status'] === 'corrigido'): ?>
                                            <a class="btn btn-outline-success" href="ingest_admitir.php?id=<?= (int)$arq['id'] ?>" title="Admitir"><i class="fas fa-cloud-upload-alt"></i></a>
                                        <?php endif; ?>
                                        <?php if ($arq['status'] === 'corrigir'): ?>
                                            <a class="btn btn-outline-warning" href="ingest_revalidar.php?id=<?= (int)$arq['id'] ?>" title="Revalidar"><i class="fas fa-redo"></i></a>
                                        <?php endif; ?>
                                        <a class="btn btn-outline-danger" href="ingest_apagar.php?id=<?= (int)$arq['id'] ?>" title="Remover"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div></section>
</div>

<?php require_once '../templates/footer.php'; ?>
