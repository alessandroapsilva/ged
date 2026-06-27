<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Inicialização GED</h1>";

echo "<h2>1. Testando require do init.php...</h2>";
try {
    require_once '../core/init.php';
    echo "✅ init.php carregado com sucesso<br>";
} catch (Exception $e) {
    echo "❌ Erro ao carregar init.php: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>2. Verificando sessão...</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Usuário logado: ID = " . $_SESSION['user_id'] . "<br>";
    echo "Nome: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
} else {
    echo "❌ Usuário não está logado<br>";
}

echo "<h2>3. Verificando conexão com banco...</h2>";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Banco conectado. Total de usuários: " . $result['total'] . "<br>";
    } else {
        echo "❌ Variável \$pdo não está definida<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao consultar banco: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Verificando BASE_URL...</h2>";
if (defined('BASE_URL')) {
    echo "✅ BASE_URL definida: " . BASE_URL . "<br>";
} else {
    echo "❌ BASE_URL não está definida<br>";
}

echo "<h2>5. Links de teste:</h2>";
echo '<a href="index.php">Ir para index.php</a><br>';
echo '<a href="painel_produtividade_moderno.php">Ir para painel</a><br>';
echo '<a href="digitalizar_dynamsoft.php">Ir para digitalizar</a><br>';
echo '<a href="login.php">Ir para login</a><br>';

echo "<hr><h2>Todas as variáveis de sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
