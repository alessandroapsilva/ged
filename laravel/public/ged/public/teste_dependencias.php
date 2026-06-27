<?php
// Testa Tesseract OCR
try {
    $tesseract = new thiagoalessio\TesseractOCR\TesseractOCR();
    echo "Tesseract OCR está instalado corretamente\n";
} catch (Exception $e) {
    echo "Erro no Tesseract OCR: " . $e->getMessage() . "\n";
}

// Testa ImageMagick
if (extension_loaded('imagick')) {
    echo "ImageMagick está instalado corretamente\n";
    echo "Versão do ImageMagick: " . Imagick::getVersion()['versionString'] . "\n";
} else {
    echo "ImageMagick não está instalado\n";
}

// Testa OpenSSL
if (extension_loaded('openssl')) {
    echo "OpenSSL está instalado corretamente\n";
    echo "Versão do OpenSSL: " . OPENSSL_VERSION_TEXT . "\n";
} else {
    echo "OpenSSL não está instalado\n";
}
?>