<?php
/**
 * Script de Teste Rápido SMTP - ENFAS GED
 * 
 * Uso: php teste_smtp_rapido.php seu-email@exemplo.com
 */

if (php_sapi_name() !== 'cli') {
    die("Este script deve ser executado via linha de comando.\n");
}

// Permite executar sem argumentos apenas para exibir a configuração
$email_destino = null;
if ($argc >= 2) {
    $email_destino = $argv[1];
    if (!filter_var($email_destino, FILTER_VALIDATE_EMAIL)) {
        echo "❌ E-mail inválido: {$email_destino}\n";
        exit(1);
    }
}

echo "🚀 ENFAS GED - Teste Rápido SMTP\n";
echo "================================\n\n";

// Carrega o sistema
require_once __DIR__ . '/../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';

// Mostra configuração atual
echo "📋 Configuração SMTP:\n";
echo "   Host: " . (getenv('GED_SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : 'NÃO CONFIGURADO')) . "\n";
echo "   Port: " . (getenv('GED_SMTP_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : '587')) . "\n";
echo "   User: " . (getenv('GED_SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : 'NÃO CONFIGURADO')) . "\n";
echo "   From: " . (getenv('GED_MAIL_FROM') ?: (defined('MAIL_FROM') ? MAIL_FROM : 'NÃO CONFIGURADO')) . "\n";
echo "   Pass: " . (getenv('GED_SMTP_PASS') ? '***configurada***' : (defined('SMTP_PASS') && SMTP_PASS ? '***configurada***' : 'NÃO CONFIGURADA')) . "\n\n";

// Verifica se SMTP está configurado
$smtp_host = getenv('GED_SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : '');
if (empty($smtp_host)) {
    echo "⚠️  SMTP não configurado!\n\n";
    echo "Configure as variáveis de ambiente ou edite config.php:\n";
    echo "   SetEnv GED_SMTP_HOST \"smtp.gmail.com\"\n";
    echo "   SetEnv GED_SMTP_USER \"seu-email@gmail.com\"\n";
    echo "   SetEnv GED_SMTP_PASS \"sua-senha\"\n\n";
    exit(1);
}

// Dados para o e-mail de teste
$dados = [
    'usuario' => [
        'nome' => 'Teste SMTP'
    ],
    'link' => 'http://localhost/ged/public/login.php',
    'expiracao' => '1 hora'
];

if ($email_destino === null) {
    echo "ℹ️  Execução somente informativa. Para enviar um teste, rode:\n";
    echo "    php teste_smtp_rapido.php seu-email@exemplo.com\n";
    exit(0);
}

echo "📧 Enviando e-mail de teste para: {$email_destino}\n";
echo "   Template: recuperar_senha\n\n";

try {
    $sucesso = email_send_template($pdo, $email_destino, 'recuperar_senha', $dados, ['smtp_debug' => 2]);
    
    if ($sucesso) {
        echo "\n✅ E-MAIL ENVIADO COM SUCESSO!\n\n";
        echo "Verifique sua caixa de entrada (e SPAM).\n";
        exit(0);
    } else {
        echo "\n❌ FALHA AO ENVIAR E-MAIL!\n\n";
        echo "Verifique:\n";
        echo "1. Credenciais SMTP corretas\n";
        echo "2. Porta e segurança (TLS/SSL)\n";
        echo "3. Firewall bloqueando conexão\n";
        echo "4. Logs do Apache/PHP\n\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
