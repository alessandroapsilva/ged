<?php
// helpers/ingest_helper.php - Utilidades para o módulo Ingest

if (!function_exists('ingest_status_badge')) {
    function ingest_status_badge(string $status): string {
        $map = [
            'pendente' => ['secondary', 'Pendente'],
            'corrigir' => ['warning', 'A Corrigir'],
            'corrigido' => ['info', 'Corrigido'],
            'admitido' => ['success', 'Admitido'],
            'erro' => ['danger', 'Erro'],
        ];
        [$cls, $rotulo] = $map[$status] ?? ['secondary', ucfirst($status)];
        return '<span class="badge badge-' . $cls . '">' . $rotulo . '</span>';
    }
}

if (!function_exists('ingest_validar_nome')) {
    // Regra simples: nome deve começar com 2 letras maiúsculas + '_' (ex.: SP_contrato.pdf)
    function ingest_validar_nome(string $nomeArquivo): array {
        $base = pathinfo($nomeArquivo, PATHINFO_FILENAME);
        if (!preg_match('/^[A-Z]{2}_.+$/', $base)) {
            return ['ok' => false, 'falha' => 'Sem Etiquetas Identificáveis'];
        }
        // Exemplo de múltiplas etiquetas: mais de um '_' antes do nome real
        $parts = explode('_', $base);
        if (count($parts) > 3) {
            return ['ok' => false, 'falha' => 'Múltiplas Etiquetas'];
        }
        return ['ok' => true, 'falha' => null];
    }
}

if (!function_exists('ingest_importar_arquivo')) {
    function ingest_importar_arquivo(PDO $pdo, string $caminhoOrigem, string $nomeOriginal = null, string $origem = 'LOCAL', ?int $usuarioId = null): int {
        $nomeOriginal = $nomeOriginal ?: basename($caminhoOrigem);
        if (!file_exists($caminhoOrigem)) {
            throw new RuntimeException('Arquivo inexistente: ' . $caminhoOrigem);
        }
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            throw new RuntimeException('Apenas PDF é permitido no Ingest.');
        }

        $destRel = 'storage/ingest/' . uniqid('ing_', true) . '.pdf';
        $destAbs = PROJECT_ROOT . '/public/' . $destRel;
        $dir = dirname($destAbs);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Falha ao criar diretório de ingest.');
        }
        if (!@copy($caminhoOrigem, $destAbs)) {
            throw new RuntimeException('Falha ao copiar arquivo para ingest.');
        }

        $tamanho = filesize($destAbs) ?: null;
        $valid = ingest_validar_nome($nomeOriginal);
        $status = $valid['ok'] ? 'pendente' : 'corrigir';
        $falha = $valid['falha'];

        $st = $pdo->prepare('INSERT INTO ingest_arquivos (nome_original, caminho_relativo, origem, tamanho_bytes, status, falha_motivo, usuario_id) VALUES (?,?,?,?,?,?,?)');
        $st->execute([$nomeOriginal, $destRel, $origem, $tamanho, $status, $falha, $usuarioId]);
        return (int)$pdo->lastInsertId();
    }
}

?>
