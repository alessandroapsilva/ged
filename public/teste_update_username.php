<?php
// TESTE TEMPORÁRIO - Deletar depois
require_once '../db_config.php';

$id = 6; // Alessandro
$novo_username = 'alessandro.silva'; // VALOR DIFERENTE DO ATUAL

try {
    // Mostra o username atual
    $stmt = $pdo->prepare("SELECT id, nome, username FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $antes = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>ANTES:</h3>";
    echo "<pre>";
    print_r($antes);
    echo "</pre>";

    // Verifica se já existe outro usuário com esse username
    $check = $pdo->prepare("SELECT id, username FROM usuarios WHERE username = ? AND id != ?");
    $check->execute([$novo_username, $id]);
    $conflito = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($conflito) {
        echo "<h2 style='color:orange'>⚠️ CONFLITO: Usuário ID {$conflito['id']} já usa '{$conflito['username']}'</h2>";
    } else {
        echo "<h3 style='color:green'>✅ Nenhum conflito - pode atualizar</h3>";
    }

    // Tenta atualizar COM DEBUG
    $sql = "UPDATE usuarios SET username = ? WHERE id = ?";
    echo "<h3>SQL:</h3><pre>$sql</pre>";
    echo "<h3>Parâmetros:</h3><pre>username: '{$novo_username}', id: {$id}</pre>";
    
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([$novo_username, $id]);
    
    echo "<h3>Execute retornou: " . ($resultado ? 'TRUE' : 'FALSE') . "</h3>";
    echo "<h3>Linhas afetadas (rowCount): " . $stmt->rowCount() . "</h3>";
    
    // Pega erros se houver
    $errorInfo = $stmt->errorInfo();
    if ($errorInfo[0] !== '00000') {
        echo "<h3 style='color:red'>ERRO PDO:</h3><pre>";
        print_r($errorInfo);
        echo "</pre>";
    }

    // Mostra o resultado
    $stmt = $pdo->prepare("SELECT id, nome, username FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $depois = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>DEPOIS:</h3>";
    echo "<pre>";
    print_r($depois);
    echo "</pre>";

    if ($antes['username'] !== $depois['username']) {
        echo "<h2 style='color:green'>✅ UPDATE FUNCIONOU!</h2>";
    } else {
        echo "<h2 style='color:red'>❌ UPDATE NÃO FUNCIONOU - valor não mudou</h2>";
        echo "<p>Possíveis causas:</p>";
        echo "<ul>";
        echo "<li>WHERE id = {$id} não encontrou nenhum registro</li>";
        echo "<li>Valor já é '{$novo_username}' (mesmo sendo diferente visualmente)</li>";
        echo "<li>Há caracteres invisíveis/encoding diferente</li>";
        echo "</ul>";
    }

} catch (PDOException $e) {
    echo "<h2 style='color:red'>ERRO: " . $e->getMessage() . "</h2>";
    echo "<pre>";
    print_r($e);
    echo "</pre>";
}
?>
