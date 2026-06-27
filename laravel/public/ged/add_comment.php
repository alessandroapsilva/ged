<?php
include 'auth_check.php';
require_once 'classes/Document.php';

if (!isset($_GET['id'])) {
    die('ID do documento não fornecido.');
}

$documentId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $document = new Document();
    $document->addComment($documentId, $_SESSION['user']['id'], $_POST['comment']);
}

header('Location: ver_processo.php?id=' . $documentId);
exit;
?>