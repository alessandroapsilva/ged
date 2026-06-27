<?php
include 'auth_check.php';
require_once 'classes/Document.php';

if (!isset($_GET['id'])) {
    die('ID do documento não fornecido.');
}

$documentId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $document = new Document();
    $document->addRating($documentId, $_SESSION['user']['id'], $_POST['rating'], $_POST['review'] ?? null);
}

header('Location: ver_processo.php?id=' . $documentId);
exit;
?>