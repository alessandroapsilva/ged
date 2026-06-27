<?php
// public/pastas_propriedades.php (VERSÃO FUNCIONAL)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$pasta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pasta_id <= 0) {
    header('Location: documentos.php?erro=invalido');
    exit();
}

try {
    $sql = "SELECT * FROM pastas WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pasta_id]);
    $pasta = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pasta) {
        header('Location: documentos.php?erro=nao_encontrado');
        exit();
    }
} catch (PDOException $e) {
    die("Erro ao buscar dados da pasta: " . $e->getMessage());
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-folder-open text-warning mr-2"></i> <?= htmlspecialchars($pasta['nome']); ?></h1>
                    <p class="text-muted">Propriedades da pasta</p>
                </div>
                <a href="documentos.php?pasta_id=<?= $pasta['pasta_pai_id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header"><h3 class="card-title">Informações Gerais</h3></div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">ID da Pasta</dt>
                        <dd class="col-sm-9"><?= $pasta['id']; ?></dd>

                        <dt class="col-sm-3">Nome</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($pasta['nome']); ?></dd>
                        
                        <dt class="col-sm-3">Criada em</dt>
                        <dd class="col-sm-9"><?= date('d/m/Y H:i', strtotime($pasta['data_criacao'])); ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>