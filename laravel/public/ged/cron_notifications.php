<?php
// Script para ser executado periodicamente (ex: a cada hora) via cron
// Exemplo: 0 * * * * php /caminho/para/cron_notifications.php

require_once 'config.php';
require_once 'classes/Notification.php';

$notification = new Notification();

// Verificar prazos vencendo
$notification->checkDeadlines();

echo "Verificação de notificações executada em " . date('Y-m-d H:i:s') . "\n";
?>