<?php
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();

// Atalho seguro: direciona para o fluxo de configuração do 2FA
header('Location: 2fa_setup.php');
exit();

