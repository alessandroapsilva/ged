<?php
$pdo = new PDO('mysql:host=localhost;dbname=ged;charset=utf8mb4','root','');
$queries = [
    "hoje" => "SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND DATE(admitido_em)=CURDATE()",
    "ontem" => "SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND DATE(admitido_em)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)",
    "7d" => "SELECT COUNT(*) FROM ingest_arquivos WHERE status='admitido' AND admitido_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
    "corrigidos" => "SELECT COUNT(*) FROM ingest_arquivos WHERE status='corrigido'",
    "a_corrigir" => "SELECT COUNT(*) FROM ingest_arquivos WHERE status='corrigir'",
    "total" => "SELECT COUNT(*) FROM ingest_arquivos",
];
foreach ($queries as $k => $sql) {
    $v = (int)$pdo->query($sql)->fetchColumn();
    echo $k,": ", $v, "\n";
}
$itens = $pdo->query("SELECT * FROM ingest_arquivos ORDER BY id DESC LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
echo "itens_ok: ".(is_array($itens) ? count($itens) : 0)."\n";
