<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pasta_id = $_POST['pasta_id'];
    $novo_nome = trim($_POST['novo_nome']);
    $pasta_pai_id_atual = $_POST['pasta_pai_id_atual'];
    if (!empty($pasta_id) && !empty($novo_nome)) {
        try {
            $sql = "UPDATE pastas SET nome = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$novo_nome, $pasta_id]);
        } catch (PDOException $e) {}
    }
    $redirect_url = "documentos_listar.php";
    if (!empty($pasta_pai_id_atual)) { $redirect_url .= "?pasta_id=" . $pasta_pai_id_atual; }
    header("Location: " . $redirect_url);
    exit();
}
?>