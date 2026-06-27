<?php
// public/documentos_teste.php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Inclui os mesmos templates da página de documentos
include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Página de Teste</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline">
                <div class="card-header">
                    <h3 class="card-title">Teste de Funcionalidade</h3>
                </div>
                <div class="card-body">
                    <p>Por favor, teste os botões do menu e do cabeçalho.</p>
                    <p>Clique no ícone do menu "hamburger" (as três barrinhas) e no seu nome de usuário no canto superior direito.</p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
// Inclui o mesmo rodapé
include '../templates/footer.php';
?>