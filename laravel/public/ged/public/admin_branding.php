<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_once PROJECT_ROOT . '/helpers/csrf_helper.php';

require_auth();
if (!(usuario_tem_permissao('admin.branding') || usuario_tem_permissao('admin.access'))) {
    header('Location: acesso_negado.php');
    exit();
}

// Carrega config atual
$brandingFile = PROJECT_ROOT . '/config/branding.json';
$branding = [];
if (is_file($brandingFile)) {
    try { $branding = json_decode(file_get_contents($brandingFile), true) ?: []; } catch (Throwable $e) {}
}
$name = $branding['name'] ?? BRAND_NAME;
$logo = $branding['logo'] ?? BRAND_LOGO;
$primary = $branding['primary_color'] ?? BRAND_PRIMARY_COLOR;
$accent = $branding['accent_color'] ?? BRAND_ACCENT_COLOR;

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>Branding</h1></div></div></div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <?php if (file_exists(__DIR__ . '/../templates/partials/notifications.php')) { include_once __DIR__ . '/../templates/partials/notifications.php'; } ?>
            <div class="card card-dark card-outline">
                <div class="card-body">
                    <form action="admin_branding_save.php" method="post" enctype="multipart/form-data">
                        <?= csrf_input(); ?>
                        <div class="form-group">
                            <label>Nome do Sistema</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Cor Primária</label>
                                <input type="color" class="form-control" name="primary_color" value="<?= htmlspecialchars($primary) ?>">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Cor de Acento</label>
                                <input type="color" class="form-control" name="accent_color" value="<?= htmlspecialchars($accent) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Logo (PNG/JPG/WEBP, até 2MB)</label>
                            <div class="mb-2"><img src="<?= htmlspecialchars($logo) ?>" alt="Logo atual" style="max-height:60px"></div>
                            <input type="file" class="form-control-file" name="logo">
                            <small class="form-text text-muted">Se não enviar, a logo atual será mantida.</small>
                        </div>
                        <div class="text-right">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
<?php require_once '../templates/footer.php'; ?>
