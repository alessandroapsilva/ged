<?php
include 'auth_check.php';
require_once 'classes/Document.php';

if (!isset($_GET['id'])) {
    die('ID do documento não fornecido.');
}

$documentId = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_reason'])) {
    $document = new Document();
    $document->createVersion($documentId, $_SESSION['user']['id'], $_POST['change_reason']);
}

header('Location: ver_processo.php?id=' . $documentId);
exit;
?>