<?php
// public/termos.php - Termos do Serviço (UTF-8)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-file-contract"></i> Termos do Serviço</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade">Painel</a></li>
                        <li class="breadcrumb-item active">Termos do Serviço</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="alert alert-info" role="alert">
                Estes termos regulam o uso do <strong><?= defined('BRAND_NAME') ? BRAND_NAME : 'GED' ?></strong>. Leia atentamente antes de utilizar o sistema.
            </div>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> 1. Aceitação</h3>
                </div>
                <div class="card-body">
                    <p>Ao acessar ou usar o sistema, você concorda com estes Termos do Serviço e com a Política de Privacidade.</p>
                </div>
            </div>

            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-check"></i> 2. Usuários e Acesso</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li>O acesso é pessoal, intransferível e protegido por credenciais individuais.</li>
                        <li>Você é responsável por manter a confidencialidade de suas credenciais.</li>
                    </ul>
                </div>
            </div>

            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bullseye"></i> 3. Uso Adequado</h3>
                </div>
                <div class="card-body">
                    <ul>
                        <li>É proibido o uso para fins ilícitos ou que infrinjam direitos de terceiros.</li>
                        <li>Respeite as políticas internas da organização e a legislação aplicável.</li>
                    </ul>
                </div>
            </div>

            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt"></i> 4. Segurança e Conformidade</h3>
                </div>
                <div class="card-body">
                    <p>Seguimos boas práticas de segurança. Você deve notificar incidentes e respeitar os controles de acesso.</p>
                </div>
            </div>

            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-check"></i> 5. Disponibilidade e Suporte</h3>
                </div>
                <div class="card-body">
                    <p>O serviço é fornecido "no estado em que se encontra"; níveis de serviço podem ser definidos contratualmente.</p>
                </div>
            </div>

            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-balance-scale"></i> 6. Propriedade Intelectual</h3>
                </div>
                <div class="card-body">
                    <p>O software e sua documentação são protegidos por direitos autorais e demais leis aplicáveis.</p>
                </div>
            </div>

            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-edit"></i> 7. Alterações dos Termos</h3>
                </div>
                <div class="card-body">
                    <p>Podemos atualizar estes termos. Mudanças relevantes serão comunicadas pelo sistema ou e-mail.</p>
                </div>
            </div>

            <div class="card card-light">
                <div class="card-body text-center">
                    <a href="privacidade" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="fas fa-user-shield"></i> Política de Privacidade
                    </a>
                    <a href="manual" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-book"></i> Manual do Usuário
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>
