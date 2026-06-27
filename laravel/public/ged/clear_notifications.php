<?php
session_start();
include 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['notifications']);
    header('Location: index.php');
    exit;
}
?>