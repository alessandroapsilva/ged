<?php
// templates/partials/notifications.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    $type = $msg['type'] ?? 'info';
    $map = [
        'sucesso' => 'success',
        'erro' => 'danger',
        'aviso' => 'warning',
        'info' => 'info',
    ];
    $cls = $map[$type] ?? 'info';
    $text = $msg['text'] ?? '';
    echo '<div class="alert alert-' . htmlspecialchars($cls) . ' alert-dismissible fade show" role="alert">'
        . htmlspecialchars($text)
        . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        . '</div>';
}
?>
<?php
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // Limpa a mensagem para não mostrar de novo

    $alert_class = 'info';
    $icon_class = 'fa-info-circle';
    $title = 'Aviso';

    switch ($message['type']) {
        case 'sucesso':
            $alert_class = 'success';
            $icon_class = 'fa-check';
            $title = 'Sucesso';
            break;
        case 'erro':
            $alert_class = 'danger';
            $icon_class = 'fa-ban';
            $title = 'Erro';
            break;
        case 'alerta':
            $alert_class = 'warning';
            $icon_class = 'fa-exclamation-triangle';
            $title = 'Alerta';
            break;
    }
?>
    <section class="content">
        <div class="container-fluid">
            <div class="alert alert-<?= $alert_class ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas <?= $icon_class ?>"></i> <?= $title ?></h5>
                <?= htmlspecialchars($message['text']) ?>
            </div>
        </div>
    </section>
<?php 
} 
?>