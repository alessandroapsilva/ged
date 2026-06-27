<?php
require_once '../core/init.php';

// Proteção da página
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Adicionar Novo Tipo de Documento</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="tipos_listar.php">Tipos de Documentos</a></li>
                        <li class="breadcrumb-item active">Novo</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Dados do Novo Tipo</h3>
                        </div>
                        <form action="tipos_salvar.php" method="post">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nome">Nome do Tipo</label>
                                    <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex: Contrato Social" required>
                                </div>
                                <div class="form-group">
                                    <label for="codigo_tipo">Código (2 a 4 letras maiúsculas)</label>
                                    <input type="text" class="form-control" id="codigo_tipo" name="codigo_tipo" placeholder="Ex: CS" required maxlength="4" minlength="2" pattern="[A-Z]{2,4}">
                                    <small class="form-text text-muted">Use apenas letras maiúsculas, sem números ou símbolos.</small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Salvar</button>
                                <a href="tipos_listar.php" class="btn btn-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
include '../templates/footer.php';
?>