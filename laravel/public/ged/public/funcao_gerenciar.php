<?php
require_once '../core/init.php';
// session_start(); // Removido, pois init.php já faz isso
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// --- PARTE 1: LÓGICA PARA SALVAR (PROCESSAR O POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome_funcao = trim($_POST['nome_funcao']);
    $chave = trim($_POST['chave']);
    $descricao = trim($_POST['descricao']);
    $nivel = (int)$_POST['nivel'];
    $permissoes = $_POST['permissoes'] ?? [];

    if (empty($nome_funcao) || empty($chave)) { die('Erro: Dados inválidos.'); }
    if ($id === false) { $id = null; } // Garante que o ID seja nulo se não for um INT válido

    $pdo->beginTransaction();
    try {
        if ($id) { // Se tem ID, é uma ATUALIZAÇÃO
            $sql = "UPDATE funcoes SET nome_funcao = ?, chave = ?, descricao = ?, nivel = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome_funcao, $chave, $descricao, $nivel, $id]);
        } else { // Se não tem ID, é uma INSERÇÃO
            $sql = "INSERT INTO funcoes (nome_funcao, chave, descricao, nivel) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome_funcao, $chave, $descricao, $nivel]);
            $id = $pdo->lastInsertId();
        }

        // Sincroniza as permissões
        $stmt_delete = $pdo->prepare("DELETE FROM funcao_permissao WHERE funcao_id = ?");
        $stmt_delete->execute([$id]);

        if (!empty($permissoes)) {
            $sql_insert = "INSERT INTO funcao_permissao (funcao_id, permissao_id) VALUES (?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            foreach ($permissoes as $permissao_id) {
                $stmt_insert->execute([$id, $permissao_id]);
            }
        }
        $pdo->commit();
        header('Location: funcoes_listar.php?sucesso=salvo');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        die('Erro ao salvar as alterações: ' . $e->getMessage());
    }
}

// --- PARTE 2: LÓGICA PARA EXIBIR O FORMULÁRIO (GET) ---
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$funcao = null;
$page_title = "Adicionar Nova Função";
$permissoes_atuais_ids = [];

if ($id) {
    $stmt_funcao = $pdo->prepare("SELECT * FROM funcoes WHERE id = ?");
    $stmt_funcao->execute([$id]);
    $funcao = $stmt_funcao->fetch(PDO::FETCH_ASSOC);
    if (!$funcao) { header('Location: funcoes_listar.php'); exit(); }
    
    $page_title = "Editar Função: " . htmlspecialchars($funcao['nome_funcao']);

    $stmt_permissoes_atuais = $pdo->prepare("SELECT permissao_id FROM funcao_permissao WHERE funcao_id = ?");
    $stmt_permissoes_atuais->execute([$id]);
    $permissoes_atuais_ids = $stmt_permissoes_atuais->fetchAll(PDO::FETCH_COLUMN);
}

$todas_permissoes = $pdo->query("SELECT * FROM permissoes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><h1><?= $page_title ?></h1></div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header"><h3 class="card-title">Dados da Função</h3></div>
                <form action="" method="post">
                    <input type="hidden" name="id" value="<?= $funcao['id'] ?? '' ?>">
                    <div class="card-body">
                        <div class="form-group"><label>Nome da Função</label><input type="text" class="form-control" name="nome_funcao" value="<?= htmlspecialchars($funcao['nome_funcao'] ?? '') ?>" required></div>
                        <div class="form-group"><label>Chave</label><input type="text" class="form-control" name="chave" value="<?= htmlspecialchars($funcao['chave'] ?? '') ?>" required></div>
                        <div class="form-group"><label>Descrição</label><input type="text" class="form-control" name="descricao" value="<?= htmlspecialchars($funcao['descricao'] ?? '') ?>"></div>
                        <div class="form-group"><label>Nível</label><input type="number" class="form-control" name="nivel" value="<?= $funcao['nivel'] ?? 0 ?>" required></div>
                        <div class="form-group">
                            <label>Permissões</label>
                            <select name="permissoes[]" class="form-control" multiple style="height: 250px;">
                                <?php foreach ($todas_permissoes as $permissao): ?>
                                    <option value="<?= $permissao['id'] ?>" <?= in_array($permissao['id'], $permissoes_atuais_ids) ? 'selected' : '' ?>><?= htmlspecialchars($permissao['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="funcoes_listar.php" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>