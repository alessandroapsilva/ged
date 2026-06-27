<?php
// A função agora aceita um novo parâmetro $categoria
function registrar_log(PDO $pdo, ?int $usuario_id, string $acao, string $categoria = 'Atividade', ?int $documento_id = null, ?int $pasta_id = null) {
    try {
        $sql = "INSERT INTO logs (usuario_id, acao, categoria, documento_id, pasta_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        // Passa a categoria para a consulta
        $stmt->execute([$usuario_id, $acao, $categoria, $documento_id, $pasta_id]);
    } catch (PDOException $e) {
        error_log("ERRO AO REGISTRAR LOG: " . $e->getMessage());
    }
}