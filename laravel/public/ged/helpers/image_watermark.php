<?php
// helpers/image_watermark.php - Aplica marca d'água em imagens (GD)

if (!function_exists('aplicar_marcadagua_imagem')) {
    /**
     * Aplica uma marca d'água textual repetida sobre a imagem de entrada e grava no output.
     * - Usa GD e fontes internas (sem TTF), padrão leve e compatível.
     * - opacity: 0.0 (invisível) a 1.0 (opaco)
     * Suporta: jpg, jpeg, png, gif, webp
     */
    function aplicar_marcadagua_imagem(string $input, string $output, string $texto, float $opacity = 0.15): bool {
        if (!file_exists($input)) return false;
        $info = @getimagesize($input);
        if (!$info) return false;
        $mime = $info['mime'] ?? '';

        // Carrega origem
        switch ($mime) {
            case 'image/jpeg': $im = @imagecreatefromjpeg($input); $outType = 'jpg'; break;
            case 'image/png': $im = @imagecreatefrompng($input); $outType = 'png'; break;
            case 'image/gif': $im = @imagecreatefromgif($input); $outType = 'gif'; break;
            case 'image/webp': if (function_exists('imagecreatefromwebp')) { $im = @imagecreatefromwebp($input); $outType = 'webp'; } else { return false; } break;
            default: return false;
        }
        if (!$im) return false;

        $w = imagesx($im); $h = imagesy($im);
        // Overlay transparente
        $overlay = imagecreatetruecolor($w, $h);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $trans = imagecolorallocatealpha($overlay, 0, 0, 0, 127); // full transparent
        imagefilledrectangle($overlay, 0, 0, $w, $h, $trans);

        // Cor da marca (cinza) com alpha baseado no opacity
        $alpha = 127 - max(0, min(127, (int)round(127 * $opacity))); // opacity 0.15 => alpha ~108
        $color = imagecolorallocatealpha($overlay, 120, 120, 120, $alpha);

        // Desenha padrão repetido
        $font = 3; // fonte interna
        $stepX = 240; $stepY = 160; // espaçamento do padrão
        $padX = 20; $padY = 20;
        $label = $texto;
        for ($y = $padY; $y < $h; $y += $stepY) {
            for ($x = $padX; $x < $w; $x += $stepX) {
                imagestring($overlay, $font, $x, $y, $label, $color);
            }
        }

        // Mescla overlay sobre a imagem original preservando alpha
        imagealphablending($im, true);
        imagecopy($im, $overlay, 0, 0, 0, 0, $w, $h);

        // Salva
        $ok = false;
        switch ($outType) {
            case 'jpg': $ok = @imagejpeg($im, $output, 90); break;
            case 'png': $ok = @imagepng($im, $output, 6); break;
            case 'gif': $ok = @imagegif($im, $output); break;
            case 'webp': if (function_exists('imagewebp')) { $ok = @imagewebp($im, $output, 90); } break;
        }

        imagedestroy($overlay);
        imagedestroy($im);
        return (bool)$ok;
    }
}
