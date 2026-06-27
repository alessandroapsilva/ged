<?php
require_once '../../core/init.php';
// Inclui o autoloader do Composer para usar a biblioteca PDFParser
require_once PROJECT_ROOT . '/vendor/autoload.php'; 

if (!isset($_SESSION['user_id'])) { 
    die("Acesso negado. Você precisa estar logado como administrador.");
}

echo "<h1>Iniciando migração de documentos (v2)...</h1>";
set_time_limit(300); // Aumenta o limite de tempo para 5 minutos

try {
    $pdo->beginTransaction();
    
    $parser = new \Smalot\PdfParser\Parser();
    
    // 1. Busca todos os documentos que precisam de atualização
    $stmt = $pdo->query("
        SELECT d.id, d.caminho_arquivo, d.tipo_documento_id, d.data_upload,
               t.vencimento_prazo, t.vencimento_unidade
        FROM documentos d
        LEFT JOIN tipos_documento t ON d.tipo_documento_id = t.id
        WHERE d.hash_arquivo IS NULL OR d.quantidade_paginas IS NULL OR d.data_vencimento IS NULL
    ");
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($documentos)) {
        echo "<p>Nenhum documento para atualizar. Tudo já está em dia!</p>";
        exit;
    }

    echo "<p>Encontrados " . count($documentos) . " documentos para processar...</p><ul>";

    // 2. Prepara as consultas de atualização
    $update_sql = "UPDATE documentos SET hash_arquivo = ?, quantidade_paginas = ?, data_vencimento = ? WHERE id = ?";
    $update_stmt = $pdo->prepare($update_sql);

    foreach ($documentos as $doc) {
        $caminho_servidor = PROJECT_ROOT . '/public/' . $doc['caminho_arquivo'];

        if (file_exists($caminho_servidor)) {
            try {
                $hash = hash_file('sha256', $caminho_servidor);
                $pdf = $parser->parseFile($caminho_servidor);
                $paginas = count($pdf->getPages());

                // Calcula data de vencimento (se aplicável)
                $data_vencimento = null;
                if (!empty($doc['vencimento_prazo']) && !empty($doc['vencimento_unidade'])) {
                    $prazo = (int)$doc['vencimento_prazo'];
                    $unidade = rtrim($doc['vencimento_unidade'], 's'); // Anos -> Ano
                    // Calcula a partir da data de upload do documento
                    $data_upload_base = strtotime($doc['data_upload']); 
                    $data_vencimento = date('Y-m-d', strtotime("+{$prazo} {$unidade}", $data_upload_base));
                }
                
                $update_stmt->execute([$hash, $paginas, $data_vencimento, $doc['id']]);
                echo "<li style='color: green;'>Documento ID {$doc['id']} atualizado com sucesso (Páginas: {$paginas}, Vencimento: {$data_vencimento}).</li>";

            } catch (Exception $e) {
                echo "<li style='color: red;'>Erro ao processar Documento ID {$doc['id']}: " . $e->getMessage() . "</li>";
            }
        } else {
            echo "<li style='color: orange;'>Arquivo para o Documento ID {$doc['id']} não encontrado.</li>";
        }
    }

    $pdo->commit();
    echo "</ul><h2 style='color: green;'>Processo concluído!</h2>";

} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erro fatal de banco de dados: " . $e->getMessage());
}
?>