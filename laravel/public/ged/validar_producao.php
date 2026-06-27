#!/usr/bin/env php
<?php
/**
 * Script de Validação Final - ENFAS GED v2.0.0
 * Verifica se o sistema está pronto para produção
 * 
 * Uso: php validar_producao.php
 */

$verde = "\033[32m";
$vermelho = "\033[31m";
$amarelo = "\033[33m";
$azul = "\033[34m";
$reset = "\033[0m";

function check($condicao, $mensagem, $critico = false) {
    global $verde, $vermelho, $amarelo, $reset;
    $status = $condicao ? "{$verde}✓{$reset}" : ($critico ? "{$vermelho}✗{$reset}" : "{$amarelo}⚠{$reset}");
    $nivel = $critico ? "CRÍTICO" : "RECOMENDADO";
    echo sprintf("%s %s - %s\n", $status, $mensagem, $condicao ? "OK" : "{$nivel}");
    return $condicao;
}

echo "{$azul}═══════════════════════════════════════════════════════════════{$reset}\n";
echo "{$azul}  ENFAS GED v2.0.0 - Validação para Produção{$reset}\n";
echo "{$azul}═══════════════════════════════════════════════════════════════{$reset}\n\n";

define('PROJECT_ROOT', __DIR__);
require_once __DIR__ . '/core/init.php';
require_once __DIR__ . '/core/email.php';

$errosCriticos = 0;
$avisos = 0;

// 1. ARQUIVOS E ESTRUTURA
echo "{$azul}[1] Arquivos e Estrutura{$reset}\n";
if (!check(file_exists(PROJECT_ROOT . '/config/branding.json'), "config/branding.json existe", true)) $errosCriticos++;
if (!check(file_exists(PROJECT_ROOT . '/config/version.json'), "config/version.json existe")) $avisos++;
if (!check(file_exists(PROJECT_ROOT . '/core/email.php'), "core/email.php existe", true)) $errosCriticos++;
if (!check(file_exists(PROJECT_ROOT . '/.htaccess'), ".htaccess configurado", true)) $errosCriticos++;
if (!check(is_dir(PROJECT_ROOT . '/storage') && is_writable(PROJECT_ROOT . '/storage'), "Diretório storage gravável", true)) $errosCriticos++;
if (!check(is_dir(PROJECT_ROOT . '/uploads') && is_writable(PROJECT_ROOT . '/uploads'), "Diretório uploads gravável", true)) $errosCriticos++;
echo "\n";

// 2. BANCO DE DADOS
echo "{$azul}[2] Banco de Dados{$reset}\n";
try {
    $tabelas = $pdo->query("SHOW TABLES LIKE '%email%'")->fetchAll(PDO::FETCH_COLUMN);
    if (!check(in_array('emails_log', $tabelas) || in_array('emails_log', array_map('strtolower', $tabelas)), "Tabela emails_log criada", true)) $errosCriticos++;
    if (!check(in_array('email_templates', $tabelas) || in_array('email_templates', array_map('strtolower', $tabelas)), "Tabela email_templates criada", true)) $errosCriticos++;
    
    $templates = $pdo->query("SELECT COUNT(*) FROM email_templates WHERE ativo = 1")->fetchColumn();
    if (!check($templates >= 9, "Templates ativos ($templates/9)", true)) $errosCriticos++;
    
    $settings = $pdo->query("SELECT COUNT(*) FROM app_settings WHERE chave LIKE 'smtp_%'")->fetchColumn();
    if (!check($settings >= 4, "Configurações SMTP ($settings)", true)) $errosCriticos++;
    
    $adminCount = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'admin' AND ativo = 1")->fetchColumn();
    if (!check($adminCount > 0, "Usuário admin ativo ($adminCount)", true)) $errosCriticos++;
} catch (Exception $e) {
    echo "{$vermelho}✗ Erro ao conectar banco: {$e->getMessage()}{$reset}\n";
    $errosCriticos += 5;
}
echo "\n";

// 3. CONFIGURAÇÃO SMTP
echo "{$azul}[3] Configuração SMTP{$reset}\n";
$smtpHost = getenv('GED_SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : app_setting_get($pdo, 'smtp_host', ''));
$smtpUser = getenv('GED_SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : app_setting_get($pdo, 'smtp_user', ''));
$smtpPass = getenv('GED_SMTP_PASS') ?: (defined('SMTP_PASS') ? SMTP_PASS : app_setting_get($pdo, 'smtp_pass', ''));
$smtpPort = getenv('GED_SMTP_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : app_setting_get($pdo, 'smtp_port', ''));

if (!check(!empty($smtpHost), "SMTP Host configurado", true)) $errosCriticos++;
if (!check(!empty($smtpUser), "SMTP User configurado", true)) $errosCriticos++;
if (!check(!empty($smtpPass), "SMTP Pass configurada", true)) $errosCriticos++;
if (!check(in_array($smtpPort, [25, 465, 587, 2525]), "SMTP Port válida ($smtpPort)")) $avisos++;

// Verificar último envio
try {
    $ultimoEnvio = $pdo->query("SELECT status, created_at FROM emails_log ORDER BY id DESC LIMIT 1")->fetch();
    if ($ultimoEnvio) {
        $sucesso = $ultimoEnvio['status'] === 'sucesso';
        if (!check($sucesso, "Último e-mail enviado com sucesso", true)) $errosCriticos++;
        echo "  → Último envio: {$ultimoEnvio['created_at']} ({$ultimoEnvio['status']})\n";
    } else {
        echo "{$amarelo}  ⚠ Nenhum e-mail enviado ainda (execute teste){$reset}\n";
        $avisos++;
    }
} catch (Exception $e) {
    echo "{$amarelo}  ⚠ Não foi possível verificar logs de e-mail{$reset}\n";
    $avisos++;
}
echo "\n";

// 4. SEGURANÇA
echo "{$azul}[4] Segurança{$reset}\n";
$appEnv = getenv('APP_ENV') ?: getenv('GED_ENV') ?: 'development';
if (!check($appEnv === 'production', "APP_ENV=production", true)) {
    echo "  → Atual: {$appEnv}\n";
    $errosCriticos++;
}
if (!check(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', "HTTPS ativo")) {
    echo "  → Será obrigatório em produção\n";
    $avisos++;
}
if (!check(defined('CSRF_PROTECTION'), "CSRF Protection implementado")) $avisos++;
if (!check(ini_get('display_errors') == 0 || $appEnv === 'production', "display_errors OFF")) $avisos++;
echo "\n";

// 5. TEMPLATE DE E-MAIL
echo "{$azul}[5] Template de E-mail{$reset}\n";
try {
    $preview = email_preview_template($pdo, 'alerta_vencimento', [
        'nome' => 'Teste',
        'documento' => ['titulo' => 'Contrato', 'vencimento' => '31/12/2025', 'link' => '#'],
        'dias' => '7'
    ]);
    $temGradiente = strpos($preview['html'], 'linear-gradient') !== false;
    $temLogo = strpos($preview['html'], 'logo_enfasged') !== false || strpos($preview['html'], 'email-logo') !== false;
    $temInter = strpos($preview['html'], 'Inter') !== false;
    $temRodape = strpos($preview['html'], 'email-footer') !== false;
    
    if (!check($temGradiente, "Template com gradiente azul", true)) $errosCriticos++;
    if (!check($temLogo, "Template com logo ENFAS")) $avisos++;
    if (!check($temInter, "Template com fonte Inter")) $avisos++;
    if (!check($temRodape, "Template com rodapé completo")) $avisos++;
} catch (Exception $e) {
    echo "{$vermelho}✗ Erro ao renderizar template: {$e->getMessage()}{$reset}\n";
    $errosCriticos++;
}
echo "\n";

// 6. BRANDING
echo "{$azul}[6] Branding{$reset}\n";
$brandName = defined('BRAND_NAME') ? BRAND_NAME : 'N/A';
$primaryColor = defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : 'N/A';
if (!check($brandName === 'ENFAS GED', "Nome da marca correto ($brandName)")) $avisos++;
if (!check($primaryColor === '#2563eb', "Cor primária azul ($primaryColor)")) $avisos++;
echo "\n";

// RESUMO FINAL
echo "{$azul}═══════════════════════════════════════════════════════════════{$reset}\n";
echo "{$azul}  RESUMO DA VALIDAÇÃO{$reset}\n";
echo "{$azul}═══════════════════════════════════════════════════════════════{$reset}\n\n";

if ($errosCriticos === 0 && $avisos === 0) {
    echo "{$verde}✓ SISTEMA 100% PRONTO PARA PRODUÇÃO!{$reset}\n\n";
    echo "Todos os requisitos críticos e recomendados foram atendidos.\n";
    echo "Você pode fazer o deploy com confiança.\n\n";
    exit(0);
} elseif ($errosCriticos === 0) {
    echo "{$amarelo}⚠ SISTEMA PRONTO (com {$avisos} avisos){$reset}\n\n";
    echo "Requisitos críticos OK, mas há {$avisos} recomendação(ões) pendente(s).\n";
    echo "Sistema funcional, mas considere resolver os avisos antes do deploy.\n\n";
    exit(0);
} else {
    echo "{$vermelho}✗ SISTEMA NÃO PRONTO ({$errosCriticos} erros críticos){$reset}\n\n";
    echo "Há {$errosCriticos} problema(s) crítico(s) que BLOQUEIAM o deploy.\n";
    echo "Resolva os itens marcados como CRÍTICO antes de prosseguir.\n\n";
    
    echo "{$amarelo}Próximos passos:{$reset}\n";
    if ($appEnv !== 'production') {
        echo "1. Definir APP_ENV=production no .htaccess\n";
    }
    if (empty($smtpHost)) {
        echo "2. Configurar SMTP (GED_SMTP_HOST, etc)\n";
    }
    echo "3. Consultar DEPLOY_CHECKLIST.md para detalhes\n\n";
    
    exit(1);
}
