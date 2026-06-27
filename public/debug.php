<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug GED</title>";
echo "<style>body{font-family:Arial;padding:20px;} .ok{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";
echo "</head><body><h1>🔍 Debug GED Sistema</h1>";

// Teste 1: PHP Info Básico
echo "<h2>1. PHP Funcionando</h2>";
echo "<p class='ok'>✅ PHP Version: " . phpversion() . "</p>";

// Teste 2: Arquivo init.php
echo "<h2>2. Carregando init.php</h2>";
try {
    require_once '../core/init.php';
    echo "<p class='ok'>✅ init.php carregado</p>";
} catch (Throwable $e) {
    echo "<p class='error'>❌ ERRO no init.php: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    die();
}

// Teste 3: Sessão
echo "<h2>3. Sessão</h2>";
if (isset($_SESSION)) {
    echo "<p class='ok'>✅ Sessão iniciada</p>";
    if (isset($_SESSION['user_id'])) {
        echo "<p class='ok'>✅ Usuário logado: " . htmlspecialchars($_SESSION['user_name'] ?? 'N/A') . " (ID: " . $_SESSION['user_id'] . ")</p>";
    } else {
        echo "<p class='error'>⚠️ Usuário não logado</p>";
    }
} else {
    echo "<p class='error'>❌ Sessão não iniciada</p>";
}

// Teste 4: Banco de Dados
echo "<h2>4. Banco de Dados</h2>";
try {
    if (isset($pdo)) {
        echo "<p class='ok'>✅ Conexão PDO existe</p>";
        $test = $pdo->query("SELECT 1 as test")->fetch();
        echo "<p class='ok'>✅ Query funciona</p>";
        
        $userCount = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        echo "<p class='ok'>✅ Total de usuários: " . $userCount . "</p>";
    } else {
        echo "<p class='error'>❌ PDO não está definido</p>";
    }
} catch (Throwable $e) {
    echo "<p class='error'>❌ Erro no banco: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Teste 5: ProfessionalLayout
echo "<h2>5. ProfessionalLayout.php</h2>";
try {
    require_once '../helpers/ProfessionalLayout.php';
    echo "<p class='ok'>✅ ProfessionalLayout.php carregado</p>";
    
    $test_layout = new ProfessionalLayout('Teste');
    echo "<p class='ok'>✅ Classe ProfessionalLayout instanciada</p>";
} catch (Throwable $e) {
    echo "<p class='error'>❌ ERRO no ProfessionalLayout: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Teste 6: Arquivos CSS
echo "<h2>6. Arquivos CSS</h2>";
$css_file = '../public/css/professional.css';
if (file_exists($css_file)) {
    echo "<p class='ok'>✅ professional.css existe (" . filesize($css_file) . " bytes)</p>";
} else {
    echo "<p class='error'>❌ professional.css NÃO encontrado em: " . realpath('../public/css/') . "</p>";
}

// Teste 7: BASE_URL
echo "<h2>7. Constantes</h2>";
echo "<p class='ok'>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NÃO DEFINIDO') . "</p>";
echo "<p class='ok'>PROJECT_ROOT: " . (defined('PROJECT_ROOT') ? PROJECT_ROOT : 'NÃO DEFINIDO') . "</p>";

// Teste 8: Links
echo "<h2>8. Links de Navegação</h2>";
echo "<ul>";
echo "<li><a href='index.php'>index.php</a></li>";
echo "<li><a href='login.php'>login.php</a></li>";
echo "<li><a href='painel_produtividade_moderno.php'>painel_produtividade_moderno.php</a></li>";
echo "<li><a href='documentos.php'>documentos.php</a></li>";
echo "</ul>";

echo "<hr><h2>Informações do Servidor</h2>";
echo "<pre>";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "</pre>";

echo "</body></html>";
?>
