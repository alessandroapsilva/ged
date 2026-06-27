<?php
require_once '../../core/init.php';
// Inclui o autoloader do Composer para usar a biblioteca PDFParser
require_once PROJECT_ROOT . '/vendor/autoload.php'; 

if (!isset($_SESSION['user_id'])) { 
    die("Acesso negado. Você precisa estar logado como administrador.");
}

echo "<h1>Iniciando atualização de documentos...</h1>";
set_time_limit(300); // Aumenta o limite de tempo para 5 minutos

try {
    $pdo->beginTransaction();
    
    $parser = new \Smalot\PdfParser\Parser();
    
    // 1. Busca todos os documentos que ainda não foram processados
    $stmt = $pdo->query("SELECT id, caminho_arquivo FROM documentos WHERE hash_arquivo IS NULL OR quantidade_paginas IS NULL");
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($documentos)) {
        echo "<p>Nenhum documento para atualizar. Tudo já está em dia!</p>";
        exit;
    }

    echo "<p>Encontrados " . count($documentos) . " documentos para processar...</p><ul>";

    // 2. Prepara as consultas de atualização
    $update_sql = "UPDATE documentos SET hash_arquivo = ?, quantidade_paginas = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);

    foreach ($documentos as $doc) {
        $caminho_servidor = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];

        if (file_exists($caminho_servidor)) {
            try {
                // Calcula o hash
                $hash = hash_file('sha256', $caminho_servidor);
                
                // Conta as páginas
                $pdf = $parser->parseFile($caminho_servidor);
                $paginas = count($pdf->getPages());

                // Atualiza o banco
                $update_stmt->execute([$hash, $paginas, $doc['id']]);
                
                echo "<li style='color: green;'>Documento ID {$doc['id']} atualizado com sucesso (Hash: " . substr($hash, 0, 10) . "..., Páginas: {$paginas}).</li>";
            } catch (Exception $e) {
                echo "<li style='color: red;'>Erro ao processar Documento ID {$doc['id']}: " . $e->getMessage() . "</li>";
            }
        } else {
            echo "<li style='color: orange;'>Arquivo para o Documento ID {$doc['id']} não encontrado no caminho: {$caminho_servidor}</li>";
        }
    }

    $pdo->commit();
    echo "</ul><h2 style='color: green;'>Processo concluído!</h2>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro fatal de banco de dados: " . $e->getMessage());
}
?>