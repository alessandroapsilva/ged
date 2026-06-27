<?php
/**
 * Script de diagnóstico do eSign (CLI e Web)
 * Uso CLI: php scripts/diagnostico_esign.php ID_DO_DOCUMENTO
 * Uso Web: diagnostico_esign.php?id=ID_DO_DOCUMENTO
 */

$isCLI = (php_sapi_name() === 'cli');

// Entrada: ID do documento
if ($isCLI) {
    if (!isset($argc) || $argc < 2) {
        fwrite(STDERR, "Uso: php scripts/diagnostico_esign.php ID_DO_DOCUMENTO\nExemplo: php scripts/diagnostico_esign.php 123\n");
        exit(1);
    }
    $documento_id = (int)$argv[1];
} else {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><title>Diagnóstico eSign - Parâmetro ausente</title></head><body style="font-family:Segoe UI,Roboto,Arial,sans-serif;padding:24px;">';
        echo '<h2 style="margin:0 0 8px;">Diagnóstico do eSign</h2>';
        echo '<p>Informe o ID do documento via parâmetro <code>?id=</code>. Ex.: <code>diagnostico_esign.php?id=123</code></p>';
        echo '</body></html>';
        exit;
    }
    $documento_id = (int)$_GET['id'];
}

// Acumulador de relatório
$report = [
    'context' => [
        'when' => date('c'),
        'php_version' => PHP_VERSION,
        'sapi' => php_sapi_name(),
        'os' => PHP_OS_FAMILY,
        'documento_id' => $documento_id,
    ],
    'summary' => [ 'pass' => 0, 'warn' => 0, 'fail' => 0 ],
    'checks' => []
];

function add_check(&$report, $title, $status, $details = [], $tip = '') {
    $report['checks'][] = [
        'title' => $title,
        'status' => $status, // pass|warn|fail
        'details' => (array)$details,
        'tip' => $tip,
    ];
    if (isset($report['summary'][$status])) { $report['summary'][$status]++; }
}

// 1) core/init.php e ambiente base
$initPath = __DIR__ . '/../core/init.php';
if (file_exists($initPath)) {
    require_once $initPath;
    $msg = [
        'PROJECT_ROOT' => defined('PROJECT_ROOT') ? PROJECT_ROOT : '(não definido)',
    ];
    add_check($report, 'Carregamento do núcleo (core/init.php)', 'pass', $msg);
    // Proteção básica (se existir)
    if (!$isCLI) {
        $authPath = dirname($initPath, 1) . '/auth_check.php';
        if (file_exists($authPath)) {
            try { include_once $authPath; add_check($report, 'Proteção de acesso (auth_check.php)', 'pass'); }
            catch (Throwable $t) { add_check($report, 'Proteção de acesso (auth_check.php)', 'warn', [$t->getMessage()], 'Verifique sessão/permissões.'); }
        } else {
            add_check($report, 'Proteção de acesso (auth_check.php)', 'warn', ['Arquivo não encontrado'], 'Considere restringir o acesso a este script.');
        }
    }
} else {
    add_check($report, 'Carregamento do núcleo (core/init.php)', 'fail', ['Arquivo não encontrado'], 'Confirme o caminho e permissões.');
    finish_and_render($report, $isCLI);
    exit(1);
}

// 2) Conexão PDO
if (isset($pdo) && $pdo instanceof PDO) {
    add_check($report, 'Conexão com banco (PDO)', 'pass');
} else {
    add_check($report, 'Conexão com banco (PDO)', 'fail', ['Instância $pdo indisponível'], 'Cheque as credenciais no core/init.php.');
    finish_and_render($report, $isCLI);
    exit(1);
}

// 3) Documento no banco e arquivo físico
try {
    $stmt = $pdo->prepare("SELECT id, titulo, caminho_arquivo FROM documentos WHERE id = ? AND (apagado_em IS NULL OR apagado_em = '0000-00-00 00:00:00')");
    $stmt->execute([$documento_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($doc) {
        $det = ["ID {$doc['id']}", 'Título: ' . $doc['titulo'], 'Caminho: ' . $doc['caminho_arquivo']];
        $arquivo_fisico = (defined('PROJECT_ROOT') ? PROJECT_ROOT : __DIR__ . '/..') . '/public/' . $doc['caminho_arquivo'];
        if (file_exists($arquivo_fisico)) {
            $det[] = 'Arquivo físico: OK (' . $arquivo_fisico . ')';
            $det[] = 'Tamanho: ' . number_format(@filesize($arquivo_fisico) / 1024, 2) . ' KB';
            add_check($report, 'Documento encontrado', 'pass', $det);
        } else {
            $det[] = 'Arquivo físico NÃO encontrado: ' . $arquivo_fisico;
            add_check($report, 'Documento encontrado (arquivo ausente)', 'fail', $det, 'Republique o arquivo no caminho indicado.');
        }
    } else {
        add_check($report, 'Documento no banco', 'fail', ["Documento ID {$documento_id} não encontrado"], 'Use um ID válido.');
    }
} catch (Throwable $e) {
    add_check($report, 'Documento no banco', 'fail', [$e->getMessage()], 'Cheque se a tabela documentos existe e a consulta está correta.');
}

// 4) Classes principais
$adPath = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/core/assinatura_digital.php';
if (file_exists($adPath)) { require_once $adPath; }
if (class_exists('AssinaturaDigital')) {
    add_check($report, 'Classe AssinaturaDigital', 'pass');
} else { add_check($report, 'Classe AssinaturaDigital', 'fail', ['Arquivo/Classe ausente'], 'Confirme helpers e include_path.'); }

$psPath = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/helpers/pdf_signer.php';
if (file_exists($psPath)) { require_once $psPath; }
if (class_exists('PDFSigner')) {
    add_check($report, 'Helper PDFSigner', 'pass');
} else { add_check($report, 'Helper PDFSigner', 'fail', ['Arquivo/Classe ausente'], 'Garanta helpers/pdf_signer.php presente.'); }

// 5) Dependências de PDF (Composer/libraries)
if (file_exists((defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/vendor/autoload.php')) {
    require_once (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/vendor/autoload.php';
}
if (!class_exists('setasign\\Fpdi\\Fpdi')) {
    $libRoot = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/libraries';
    $candidates = [
        $libRoot . '/fpdf/fpdf.php',
        $libRoot . '/tcpdf/tcpdf.php',
        $libRoot . '/fpdi/src/autoload.php',
        $libRoot . '/setasign/fpdi/autoload.php',
        $libRoot . '/setasign/fpdi/src/autoload.php',
    ];
    foreach ($candidates as $file) { if (file_exists($file)) { @require_once $file; } }
}
if (class_exists('setasign\\Fpdi\\Fpdi')) {
    add_check($report, 'Biblioteca PDF (FPDI)', 'pass');
} else {
    add_check($report, 'Biblioteca PDF (FPDI)', 'warn', ['FPDI não encontrado'], 'Instale via Composer ou coloque em /libraries.');
}

// 6) QR Code library
$qrLibPaths = [
    (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/libraries/phpqrcode/qrlib.php',
    (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/public/libraries/phpqrcode/qrlib.php',
];
$qrLib = null;
foreach ($qrLibPaths as $path) { if (file_exists($path)) { $qrLib = $path; break; } }
if ($qrLib) {
    require_once $qrLib;
    $qrDet = ['phpqrcode: ' . str_replace((defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)), '', $qrLib)];
    // teste básico de geração em memória (base64)
    $qrBase64 = '';
    try {
        ob_start();
        QRcode::png('diagnostico-esign', null, QR_ECLEVEL_L, 3, 1);
        $png = ob_get_clean();
        if ($png) { $qrBase64 = 'data:image/png;base64,' . base64_encode($png); $qrDet[] = 'QR teste: OK'; }
    } catch (Throwable $t) { $qrDet[] = 'Erro QR: ' . $t->getMessage(); }
    add_check($report, 'Biblioteca QR Code (phpqrcode)', 'pass', $qrDet);
    $report['context']['qr_sample'] = $qrBase64;
} else {
    add_check($report, 'Biblioteca QR Code (phpqrcode)', 'fail', ['Não encontrada nos caminhos padrão'], 'Baixe e extraia em /libraries/phpqrcode/');
}

// 7) Pastas de escrita
$pastas = [
    (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/public/storage/uploads',
    (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__)) . '/public/storage/assinaturas'
];
foreach ($pastas as $pasta) {
    if (is_dir($pasta)) {
        if (is_writable($pasta)) add_check($report, 'Permissão pasta', 'pass', [$pasta . ' (gravável)']);
        else add_check($report, 'Permissão pasta', 'fail', [$pasta . ' (sem escrita)'], 'Ajuste permissões (Windows: controle total para IIS/Apache).');
    } else {
        $created = @mkdir($pasta, 0755, true);
        if ($created) add_check($report, 'Pasta criada', 'warn', [$pasta], 'Confirme permissões de escrita.');
        else add_check($report, 'Pasta inexistente', 'fail', [$pasta], 'Crie manualmente e permita escrita.');
    }
}

// 8) Config de upload e ambiente PHP
$tmp = sys_get_temp_dir();
$uploadInfo = [
    'file_uploads: ' . (ini_get('file_uploads') ? 'On' : 'Off'),
    'upload_max_filesize: ' . ini_get('upload_max_filesize'),
    'post_max_size: ' . ini_get('post_max_size'),
    'max_file_uploads: ' . ini_get('max_file_uploads'),
    'memory_limit: ' . ini_get('memory_limit'),
    'sys_get_temp_dir: ' . $tmp . (is_writable($tmp) ? ' (gravável)' : ' (sem escrita)'),
];
add_check($report, 'Ambiente de upload (php.ini)', 'pass', $uploadInfo, 'Aumente limites se necessário.');

// 9) Extensões PHP relevantes
$exts = [
    'openssl' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'gd' => extension_loaded('gd'),
];
foreach ($exts as $name => $ok) {
    add_check($report, 'Extensão PHP: ' . $name, $ok ? 'pass' : 'warn', [$ok ? 'Ativa' : 'Inativa'], $ok ? '' : 'Ative no php.ini se precisar.');
}

// 10) Banco: tabelas necessárias
try {
    $tables = [
        'documentos_assinaturas' => 'Registra assinaturas novas',
        'assinaturas' => 'Compatibilidade legado (opcional)',
        'log_sistema' => 'Auditoria de eventos (opcional)'
    ];
    foreach ($tables as $t => $desc) {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$t]);
        $exists = (bool)$st->fetchColumn();
        add_check($report, "Tabela: {$t}", $exists ? 'pass' : 'warn', [$desc, $exists ? 'Existe' : 'Não encontrada'], $exists ? '' : 'Crie conforme scripts SQL do projeto.');
    }
} catch (Throwable $t) {
    add_check($report, 'Verificação de tabelas', 'warn', [$t->getMessage()], 'Permissões no banco ou versão do MySQL.');
}

// 11) Teste de geração de verificador e URL de validação
try {
    $verificador = bin2hex(random_bytes(8));
    $hostBase = (isset($_SERVER['HTTP_HOST'])
        ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']
        : 'http://localhost');
    $basePath = '/ged';
    $verificationUrl = $hostBase . $basePath . '/esign/verificar?code=' . $verificador;
    add_check($report, 'Geração de verificador e URL', 'pass', ['verificador: ' . $verificador, 'url: ' . $verificationUrl]);
    $report['context']['verification_url_example'] = $verificationUrl;
} catch (Throwable $t) {
    add_check($report, 'Geração de verificador e URL', 'fail', [$t->getMessage()]);
}

// 12) Sanity: instanciar classe central (se existir)
if (class_exists('AssinaturaDigital')) {
    try {
        $assinatura = new AssinaturaDigital($pdo, 1);
        add_check($report, 'Instanciação AssinaturaDigital', 'pass');
    } catch (Throwable $t) {
        add_check($report, 'Instanciação AssinaturaDigital', 'warn', [$t->getMessage()], 'Cheque construtor/assinatura da classe.');
    }
}

// Renderização
finish_and_render($report, $isCLI);
exit;

// ===== Funções de renderização =====
function finish_and_render(array $report, bool $isCLI) {
    if ($isCLI) { render_cli($report); return; }
    render_html($report);
}

function render_cli(array $r) {
    echo "=== DIAGNÓSTICO DO ESIGN ===\n\n";
    echo 'Documento ID: ' . $r['context']['documento_id'] . "\n";
    echo 'Data/Hora: ' . $r['context']['when'] . "\n";
    echo 'PHP: ' . $r['context']['php_version'] . ' (' . $r['context']['sapi'] . ")\n\n";
    $i = 1; $total = count($r['checks']);
    foreach ($r['checks'] as $c) {
        $icon = $c['status'] === 'pass' ? '✓' : ($c['status'] === 'warn' ? '⚠' : '✗');
        echo "[{$i}/{$total}] {$c['title']} - {$icon}\n";
        foreach ($c['details'] as $d) echo '  • ' . $d . "\n";
        if (!empty($c['tip'])) echo '  dica: ' . $c['tip'] . "\n";
        echo "\n";
        $i++;
    }
    echo 'Resumo: PASS=' . $r['summary']['pass'] . ' WARN=' . $r['summary']['warn'] . ' FAIL=' . $r['summary']['fail'] . "\n";
    echo "\n=== FIM DO DIAGNÓSTICO ===\n";
}

function render_html(array $r) {
    $json = json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $qrImg = isset($r['context']['qr_sample']) && $r['context']['qr_sample'] ? '<img alt="QR" src="' . htmlspecialchars($r['context']['qr_sample']) . '" style="height:48px;width:48px;image-rendering:pixelated;border-radius:4px;" />' : '';
    $pass = (int)$r['summary']['pass']; $warn = (int)$r['summary']['warn']; $fail = (int)$r['summary']['fail'];
    echo '<!DOCTYPE html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Diagnóstico do eSign</title>';
    echo '<style>
    :root{--brand:#007bff;--ok:#28a745;--warn:#ffc107;--fail:#dc3545;--bg:#f4f6f9;--card:#fff;--text:#212529}
    *{box-sizing:border-box}body{margin:0;background:var(--bg);font:14px/1.45 \'Segoe UI\',Roboto,Arial,sans-serif;color:var(--text)}
    .container{max-width:1080px;margin:24px auto;padding:0 16px}
    .header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
    .badge{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;font-weight:600}
    .b-ok{background:#e7f7ec;color:var(--ok)}.b-warn{background:#fff8e6;color:#7a5d00}.b-fail{background:#ffe9ec;color:var(--fail)}
    .card{background:var(--card);border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 18px rgba(16,24,40,.06);overflow:hidden;margin:16px 0}
    .card h3{margin:0;padding:14px 16px;border-bottom:1px solid #eef2f7;background:linear-gradient(180deg,#fff, #f9fbff)}
    .list{padding:8px 0}
    .item{padding:12px 16px;border-top:1px dashed #eef2f7;display:flex;gap:14px;align-items:flex-start}
    .item:first-child{border-top:0}
    .dot{width:10px;height:10px;border-radius:50%}
    .ok{background:var(--ok)}.warn{background:var(--warn)}.fail{background:var(--fail)}
    .details{opacity:.9}
    .tip{margin-top:6px;font-size:12px;color:#475467}
    .toolbar{display:flex;gap:8px;flex-wrap:wrap}
    .btn{appearance:none;border:1px solid #d0d5dd;background:#fff;color:#344054;padding:8px 12px;border-radius:8px;cursor:pointer}
    .btn.primary{background:var(--brand);border-color:var(--brand);color:#fff}
    .summary{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-left:auto}
    .pill{padding:6px 10px;border-radius:999px;background:#eef2f7}
    .muted{color:#667085}
    .qr{margin-left:auto}
    </style></head><body>';
    echo '<div class="container">';
    echo '<div class="header">';
    echo '<h2 style="margin:0;">Diagnóstico do eSign</h2>';
    echo '<div class="summary">';
    echo '<span class="badge b-ok">✓ ' . $pass . ' OK</span>';
    echo '<span class="badge b-warn">⚠ ' . $warn . ' Avisos</span>';
    echo '<span class="badge b-fail">✗ ' . $fail . ' Falhas</span>';
    echo '</div>';
    echo '</div>';

    echo '<div class="card"><h3>Contexto</h3><div class="list">';
    echo '<div class="item"><div class="dot ok"></div><div class="details">';
    echo '<div><strong>Documento:</strong> ' . htmlspecialchars((string)$r['context']['documento_id']) . '</div>';
    echo '<div class="muted">PHP ' . htmlspecialchars($r['context']['php_version']) . ' • ' . htmlspecialchars($r['context']['sapi']) . ' • ' . htmlspecialchars($r['context']['os']) . ' • ' . htmlspecialchars($r['context']['when']) . '</div>';
    echo '</div><div class="qr">' . $qrImg . '</div></div>';
    echo '</div></div>';

    echo '<div class="card"><h3>Verificações</h3><div class="list">';
    foreach ($r['checks'] as $c) {
        $cls = $c['status'] === 'pass' ? 'ok' : ($c['status'] === 'warn' ? 'warn' : 'fail');
        echo '<div class="item">';
        echo '<div class="dot ' . $cls . '"></div>';
        echo '<div class="details">';
        echo '<div><strong>' . htmlspecialchars($c['title']) . '</strong></div>';
        foreach ($c['details'] as $d) echo '<div class="muted">• ' . htmlspecialchars((string)$d) . '</div>';
        if (!empty($c['tip'])) echo '<div class="tip">Dica: ' . htmlspecialchars($c['tip']) . '</div>';
        echo '</div></div>';
    }
    echo '</div></div>';

    echo '<div class="toolbar">';
    echo '<button class="btn" onclick="copyDiag()">Copiar resumo</button>';
    echo '<button class="btn" onclick="downloadJSON()">Baixar JSON</button>';
    if (!empty($r['context']['verification_url_example'])) {
        echo '<a class="btn primary" target="_blank" href="' . htmlspecialchars($r['context']['verification_url_example']) . '">Abrir URL de verificação (exemplo)</a>';
    }
        echo '</div>';

        echo '</div>';
        echo '<script>';
        echo 'const DATA = ' . $json . ';';
        echo <<<'JS'
function downloadJSON(){
    const blob = new Blob([JSON.stringify(DATA, null, 2)], {type: "application/json"});
    const url = URL.createObjectURL(blob); const a = document.createElement("a");
    a.href = url; a.download = "diagnostico_esign.json"; a.click(); setTimeout(()=>URL.revokeObjectURL(url), 1000);
}
function copyDiag(){
    const lines = [];
    lines.push("Diagnóstico do eSign");
    lines.push("Doc ID: " + DATA.context.documento_id);
    lines.push("PHP " + DATA.context.php_version + " • " + DATA.context.sapi + " • " + DATA.context.os);
    lines.push("PASS="+DATA.summary.pass+" WARN="+DATA.summary.warn+" FAIL="+DATA.summary.fail); lines.push("");
    DATA.checks.forEach(c=>{
        lines.push((c.status==='pass'?'[OK]':c.status==='warn'?'[WARN]':'[FAIL]')+" "+c.title);
        (c.details||[]).forEach(d=>lines.push("  • "+d));
        if(c.tip) lines.push("  dica: "+c.tip);
        lines.push("");
    });
    navigator.clipboard.writeText(lines.join("\\n")).then(()=>alert("Resumo copiado!"));
}
JS;
        echo '</script>';
    echo '</body></html>';
}
