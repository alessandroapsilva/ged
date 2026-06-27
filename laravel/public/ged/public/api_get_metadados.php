<?php
// public/api_get_metadados.php

// 1. INICIALIZA O SISTEMA E A CONEXÃO COM O BANCO
require_once '../core/init.php';

// Define o cabeçalho da resposta como JSON, a "linguagem" do JavaScript
header('Content-Type: application/json');

// 2. PEGA O ID DO TIPO DE DOCUMENTO DA REQUISIÇÃO
$tipo_id = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : 0;

if ($tipo_id === 0) {
    // Se nenhum ID foi enviado, retorna um erro em JSON
    echo json_encode(['erro' => 'ID do tipo de documento não fornecido.']);
    exit();
}

// 3. BUSCA NO BANCO DE DADOS TODOS OS CAMPOS LIGADOS A ESTE TIPO
$sql = "SELECT 
            mc.id,
            mc.nome_campo,
            mc.rotulo,
            mc.tipo_campo,
            mc.largura,
            mc.mascara,
            mc.opcoes_lista,
            mc.obrigatorio
        FROM metadado_campos mc
        JOIN tipo_documento_metadados tdm ON mc.id = tdm.metadado_campo_id
        WHERE tdm.tipo_documento_id = ?
        ORDER BY tdm.ordem ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$tipo_id]);
$campos = $stmt->fetchAll();

// 4. RETORNA OS CAMPOS ENCONTRADOS EM FORMATO JSON
echo json_encode($campos);
exit();
?>