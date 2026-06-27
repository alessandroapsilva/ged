<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Incluímos nossos templates para manter o layout
include '../templates/header.php';
include '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <h1>Acesso Negado</h1>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="error-page" style="margin-top: 100px;">
                <h2 class="headline text-danger" style="font-size: 80px;"><i class="fas fa-ban"></i></h2>

                <div class="error-content">
                    <h3><i class="fas fa-exclamation-triangle text-danger"></i> Oops! Acesso não autorizado.</h3>
                    <p>
                        Você não tem permissão para visualizar esta página.
                        Por favor, entre em contato com o administrador do sistema se você acredita que isso é um erro.
                    </p>
                    <button onclick="history.back()" class="btn btn-primary">Voltar para a página anterior</button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>