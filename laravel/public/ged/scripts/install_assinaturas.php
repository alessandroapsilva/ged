#!/usr/bin/env php
<?php
/**
 * Script de instalação do módulo de assinaturas
 * Uso: php scripts/install_assinaturas.php
 */

echo "=== ENFAS GED - Instalador do Módulo de Assinaturas ===\n\n";

// 1. Verificar ambiente
echo "[1/5] Verificando ambiente PHP...\n";

$required_extensions = ['pdo', 'pdo_mysql', 'openssl', 'gd'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
        echo "  ✗ Extensão {$ext} NÃO encontrada\n";
    } else {
        echo "  ✓ Extensão {$ext} OK\n";
    }
}

if (!empty($missing_extensions)) {
    echo "\n⚠ ATENÇÃO: Extensões faltando. Habilite no php.ini e reinicie o Apache.\n";
    exit(1);
}

// 2. Verificar dependências (Composer e fallback libraries/)
echo "\n[2/5] Verificando dependências de PDF...\n";

$composer_json = __DIR__ . '/../composer.json';
$vendor_dir = __DIR__ . '/../vendor';
$libs_dir = __DIR__ . '/../libraries';

if (!file_exists($composer_json)) {
    echo "  ⚠ composer.json não encontrado (Composer opcional se usar libraries/)\n";
}

$fpdi_vendor = file_exists($vendor_dir . '/setasign/fpdi/src/autoload.php');
$tcpdf_vendor = file_exists($vendor_dir . '/tecnickcom/tcpdf/tcpdf.php');

if (is_dir($vendor_dir)) {
    if ($fpdi_vendor && $tcpdf_vendor) {
        echo "  ✓ FPDI e TCPDF via Composer\n";
    } else {
        echo "  ⚠ Dependências PDF via Composer incompletas. Execute: composer install\n";
    }
} else {
    echo "  ⚠ Pasta vendor/ não encontrada (ok se usar libraries/)\n";
}

// Fallback: checa em libraries/
$fpdf_lib = file_exists($libs_dir . '/fpdf/fpdf.php');
$tcpdf_lib = file_exists($libs_dir . '/tcpdf/tcpdf.php');
$fpdi_lib  = file_exists($libs_dir . '/fpdi/src/autoload.php')
          || file_exists($libs_dir . '/setasign/fpdi/autoload.php')
          || file_exists($libs_dir . '/setasign/fpdi/src/autoload.php');

if ($fpdf_lib || $tcpdf_lib) {
    echo "  ✓ FPDF/TCPDF detectado em libraries/\n";
} else {
    echo "  ⚠ FPDF/TCPDF não encontrado em libraries/\n";
}
if ($fpdi_lib) {
    echo "  ✓ FPDI detectado em libraries/\n";
} else {
    echo "  ⚠ FPDI não encontrado em libraries/\n";
}

// 3. Criar estrutura de pastas
echo "\n[3/5] Criando estrutura de pastas...\n";

$dirs_to_create = [
    __DIR__ . '/../public/storage/assinaturas',
    __DIR__ . '/../docs'
];

foreach ($dirs_to_create as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "  ✓ Criado: {$dir}\n";
        } else {
            echo "  ✗ Erro ao criar: {$dir}\n";
        }
    } else {
        echo "  ✓ Já existe: {$dir}\n";
    }
}

// 4. Verificar conexão com banco
echo "\n[4/5] Verificando conexão com banco de dados...\n";

$db_config = __DIR__ . '/../db_config.php';
if (file_exists($db_config)) {
    require_once $db_config;
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "  ✓ Conexão OK com banco '{$GLOBALS['DB_NAME']}'\n";
        
        // Verificar se tabela documentos_assinaturas existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'documentos_assinaturas'");
        if ($stmt->rowCount() > 0) {
            echo "  ✓ Tabela 'documentos_assinaturas' existe\n";
        } else {
            echo "  ⚠ Tabela 'documentos_assinaturas' não existe. Execute: mysql -u root ged < sql/assinaturas_migration.sql\n";
        }
        // Verificar se tabela log_sistema existe
        $stmtLog = $pdo->query("SHOW TABLES LIKE 'log_sistema'");
        if ($stmtLog->rowCount() > 0) {
            echo "  ✓ Tabela 'log_sistema' existe\n";
        } else {
            echo "  ⚠ Tabela 'log_sistema' não existe. Opcional, mas recomendado. Execute: mysql -u root ged < sql/log_sistema.sql\n";
        }
        
    } catch (PDOException $e) {
        echo "  ✗ Erro de conexão: " . $e->getMessage() . "\n";
    }
} else {
    echo "  ⚠ db_config.php não encontrado\n";
}

// 5. Verificar arquivos do módulo
echo "\n[5/5] Verificando arquivos do módulo...\n";

$required_core_files = [
    'helpers/pdf_signer.php',
    'core/assinatura_digital.php',
    'public/esign/index.php',
    'public/esign/assinar_process.php',
    'public/esign/assinar_simples_process.php',
    'public/esign/verificar.php',
    'public/qrcode_generator.php',
    'sql/assinaturas_migration.sql',
    'docs/ASSINATURAS.md'
];

$missing_files = [];
foreach ($required_core_files as $file) {
    $full_path = __DIR__ . '/../' . $file;
    if (file_exists($full_path)) {
        echo "  ✓ {$file}\n";
    } else {
        echo "  ✗ {$file} NÃO ENCONTRADO\n";
        $missing_files[] = $file;
    }
}

// Arquivos legados opcionais (avisar apenas)
$optional_legacy = [
    'public/assinaturas_assinar.php',
    'public/assinaturas_assinar_process.php',
    'public/assinaturas_verificar.php',
    'public/assinaturas_minhas.php',
];
foreach ($optional_legacy as $file) {
    $full_path = __DIR__ . '/../' . $file;
    if (file_exists($full_path)) {
        echo "  ○ (legado) {$file} presente\n";
    } else {
        echo "  ○ (legado) {$file} ausente (ok)\n";
    }
}

// Resumo
echo "\n=== RESUMO ===\n";

// Verifica biblioteca do QR Code
$qr_lib_paths = [
    __DIR__ . '/../libraries/phpqrcode/qrlib.php',
    __DIR__ . '/../public/libraries/phpqrcode/qrlib.php'
];

$qr_lib_found = false;
foreach ($qr_lib_paths as $qr_lib) {
    if (file_exists($qr_lib)) {
        $qr_lib_found = true;
        break;
    }
}

if (!$qr_lib_found) {
    echo "\n⚠ Biblioteca de QRCode não encontrada.\n";
    echo "  Baixe em: https://sourceforge.net/projects/phpqrcode/\n";
    echo "  Extraia em: libraries/phpqrcode/ (na raiz do projeto)\n";
}

if (empty($missing_extensions) && empty($missing_files)) {
    echo "✓ Instalação completa! O módulo de assinaturas está pronto para uso.\n";
    echo "\nPróximos passos:\n";
    echo "1. Certifique-se que o schema foi importado: mysql -u root ged < sql/assinaturas_migration.sql\n";
    echo "2. Instale as dependências: composer install\n";
    echo "3. Assinaturas centralizadas (eSign): http://localhost/ged/public/esign/index.php\n";
    echo "4. Minhas assinaturas: http://localhost/ged/public/assinaturas_minhas.php\n";
    echo "\nDocumentação: docs/ASSINATURAS.md\n";
} else {
    echo "⚠ Instalação INCOMPLETA. Resolva os problemas acima.\n";
    if (!empty($missing_extensions)) {
        echo "\nExtensões faltando: " . implode(', ', $missing_extensions) . "\n";
    }
    if (!empty($missing_files)) {
        echo "\nArquivos faltando: " . implode(', ', $missing_files) . "\n";
    }
}

echo "\n";
