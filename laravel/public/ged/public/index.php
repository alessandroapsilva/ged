<?php
// public/index.php

// 1. INICIALIZA O SISTEMA (PARA GARANTIR QUE O USUÁRIO ESTÁ LOGADO)
require_once '../core/init.php';

// 2. REDIRECIONA IMEDIATAMENTE PARA A LISTA DE DOCUMENTOS
// O init.php já fez a verificação de segurança. Agora apenas mandamos
// o usuário para a página correta.
header('Location: painel_produtividade.php');
exit();

// Não há mais nada neste arquivo! Ele é apenas uma ponte.
?>