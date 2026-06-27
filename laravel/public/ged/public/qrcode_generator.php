<?php
// public/qrcode_generator.php

// 1) Caminho da biblioteca phpqrcode
$lib = __DIR__ . '/../libraries/phpqrcode/qrlib.php';
if (!file_exists($lib)) {
    header('Content-Type: image/png');
    $im = imagecreate(200, 36);
    imagecolorallocate($im, 255, 255, 255);
    $tc = imagecolorallocate($im, 200, 0, 0);
    imagestring($im, 3, 5, 10, 'phpqrcode ausente', $tc);
    imagepng($im);
    imagedestroy($im);
    exit;
}
require_once $lib;

// 2) Texto
$text = isset($_GET['text']) ? (string)$_GET['text'] : 'Sem dados';
if ($text === '') { $text = ' '; }

// 3) Emite cabeçalho e limpa buffers
if (function_exists('ob_get_length')) { while (ob_get_length()) { ob_end_clean(); } }
header('Content-Type: image/png');

// 4) Gera imagem
try {
    QRcode::png($text, false, QR_ECLEVEL_L, 4, 2);
} catch (Throwable $e) {
    $im = imagecreate(200, 36);
    imagecolorallocate($im, 255, 255, 255);
    $tc = imagecolorallocate($im, 200, 0, 0);
    imagestring($im, 3, 5, 10, 'Erro ao gerar QR', $tc);
    imagepng($im);
    imagedestroy($im);
}

