<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: documentos.php'); exit(); }
if (function_exists('require_csrf_or_abort')) { require_csrf_or_abort(); }

$pasta_pai_id = isset($_POST['pasta_pai_id']) ? (int)$_POST['pasta_pai_id'] : null;
$nomes_raw = (string)($_POST['nomes'] ?? '');
$nomes = array_filter(array_map('trim', preg_split('/\r?\n/', $nomes_raw)));

if (empty($nomes)) { $_SESSION['flash_message']=['type'=>'aviso','text'=>'Informe ao menos um nome de pasta.']; header('Location: pastas_criar_lote.php?pasta_pai_id='.(int)$pasta_pai_id); exit(); }

// Descobre colunas opcionais
$cols = [];
try { foreach($pdo->query("SHOW COLUMNS FROM pastas")->fetchAll(PDO::FETCH_COLUMN,0) as $c){ $cols[strtolower($c)] = true; } } catch (Throwable $e) {}

$ok=0; $fail=[];
foreach ($nomes as $nome) {
    try {
        $fields = ['nome']; $vals = [$nome]; $ph=['?'];
        if ($pasta_pai_id !== null && isset($cols['pasta_pai_id'])) { $fields[]='pasta_pai_id'; $vals[]=$pasta_pai_id; $ph[]='?'; }
        if (isset($cols['criado_em'])) { $fields[]='criado_em'; $ph[]='NOW()'; }
        $sql = 'INSERT INTO pastas ('.implode(',',$fields).') VALUES ('.implode(',',$ph).')';
        $stmt = $pdo->prepare($sql);
        // bind só nos ? reais
        $bindVals = [];
        foreach ($vals as $v) { $bindVals[] = $v; }
        $stmt->execute($bindVals);
        $ok++;
    } catch (Throwable $e) {
        $fail[] = $nome;
    }
}

$_SESSION['flash_message'] = [
  'type' => (count($fail)===0 ? 'sucesso':'aviso'),
  'text' => ($ok.' pastas criadas.').(count($fail)? ' Falhou: '.implode(', ',$fail):'')
];

header('Location: documentos.php'.($pasta_pai_id? ('?pasta_id='.$pasta_pai_id):''));
exit();

