<?php
$pdo = new PDO('mysql:host=localhost;dbname=ged;charset=utf8mb4','root','');
$cols = $pdo->query("SHOW COLUMNS FROM email_templates")->fetchAll(PDO::FETCH_COLUMN, 0);
$hasHtml = in_array('corpo_html', $cols, true);
$hasTexto = in_array('corpo_texto', $cols, true);
$hasCorpo = in_array('corpo', $cols, true);

echo "email_templates columns: ".implode(',', $cols)."\n";

$templates = [
  'senha_alterada' => [
    'nome' => 'Senha Alterada',
    'assunto' => 'Sua senha foi alterada',
    'corpo_html' => '<div style="font-family:sans-serif;color:#333;"><p>Olá {{usuario.nome|}},</p><p>Informamos que sua senha no sistema foi alterada com sucesso em {{data|{{now}}}}.</p><p>Se você não reconhece esta ação, contate o suporte imediatamente.</p><p>Atenciosamente,<br>Equipe GED</p></div>',
    'corpo_texto' => 'Olá {{usuario.nome|}},\nSua senha foi alterada em {{data|{{now}}}}.\nSe não foi você, contate o suporte.\nEquipe GED',
    'variaveis_json' => json_encode(['usuario'=>['nome'=>'Fulano'],'data'=>'2025-10-31 10:00'], JSON_UNESCAPED_UNICODE)
  ],
  'usuario_criado' => [
    'nome' => 'Boas-vindas (Usuário Criado)',
    'assunto' => 'Sua conta foi criada no GED',
    'corpo_html' => '<div style="font-family:sans-serif;color:#333;"><p>Olá {{usuario.nome|}},</p><p>Sua conta no GED foi criada. Use o usuário <strong>{{usuario.username|seu e-mail}}</strong> para acessar.</p><p>Para definir sua senha inicial, acesse: <a href="{{link_redefinir|}}">Criar senha</a></p><p>Bem-vindo(a)!</p></div>',
    'corpo_texto' => 'Olá {{usuario.nome|}},\nSua conta foi criada. Usuário: {{usuario.username|seu e-mail}}.\nCrie sua senha: {{link_redefinir|}}\nBem-vindo(a)!',
    'variaveis_json' => json_encode(['usuario'=>['nome'=>'Fulano','username'=>'fulano'],'link_redefinir'=>'https://exemplo.local/redefinir/XYZ'], JSON_UNESCAPED_UNICODE)
  ],
  'documento_assinado' => [
    'nome' => 'Documento Assinado',
    'assunto' => 'Documento assinado: {{documento.titulo|Documento}}',
    'corpo_html' => '<div style="font-family:sans-serif;color:#333;"><p>Olá {{destinatario.nome|}},</p><p>O documento <strong>{{documento.titulo|Documento}}</strong> foi assinado por {{assinante.nome|}} em {{data|{{now}}}}.</p><p>Visualize: <a href="{{link|}}">Abrir documento</a></p></div>',
    'corpo_texto' => 'Olá {{destinatario.nome|}},\nO documento {{documento.titulo|Documento}} foi assinado por {{assinante.nome|}} em {{data|{{now}}}}.\nAbrir: {{link|}}',
    'variaveis_json' => json_encode(['destinatario'=>['nome'=>'Fulano'],'assinante'=>['nome'=>'Ciclano'],'documento'=>['titulo'=>'Contrato'],'link'=>'https://exemplo.local/doc/123'], JSON_UNESCAPED_UNICODE)
  ],
  'lembrete_assinatura' => [
    'nome' => 'Lembrete de Assinatura',
    'assunto' => 'Lembrete: assinatura pendente de {{documento.titulo|Documento}}',
    'corpo_html' => '<div style="font-family:sans-serif;color:#333;"><p>Olá {{destinatario.nome|}},</p><p>Este é um lembrete para assinar o documento <strong>{{documento.titulo|Documento}}</strong>.</p><p>Prazo: {{prazo|em breve}}. Acesse: <a href="{{link|}}">Assinar agora</a></p></div>',
    'corpo_texto' => 'Lembrete: assinar {{documento.titulo|Documento}}.\nPrazo: {{prazo|em breve}}.\nAssinar: {{link|}}',
    'variaveis_json' => json_encode(['destinatario'=>['nome'=>'Fulano'],'documento'=>['titulo'=>'Contrato'],'prazo'=>'31/10/2025','link'=>'https://exemplo.local/assinar/XYZ'], JSON_UNESCAPED_UNICODE)
  ],
];

foreach ($templates as $slug => $tpl) {
  if ($hasHtml || $hasTexto) {
    $sql = "INSERT INTO email_templates (slug, nome, assunto" .
           ($hasHtml ? ", corpo_html" : "") .
           ($hasTexto ? ", corpo_texto" : "") .
           ", variaveis_json, ativo) VALUES (:slug,:nome,:assunto" .
           ($hasHtml ? ", :corpo_html" : "") .
           ($hasTexto ? ", :corpo_texto" : "") .
           ", :variaveis_json, 1)
           ON DUPLICATE KEY UPDATE nome=VALUES(nome), assunto=VALUES(assunto)" .
           ($hasHtml ? ", corpo_html=VALUES(corpo_html)" : "") .
           ($hasTexto ? ", corpo_texto=VALUES(corpo_texto)" : "") .
           ", variaveis_json=VALUES(variaveis_json), ativo=VALUES(ativo)";
    $st = $pdo->prepare($sql);
    $st->bindValue(':slug', $slug);
    $st->bindValue(':nome', $tpl['nome']);
    $st->bindValue(':assunto', $tpl['assunto']);
    if ($hasHtml) $st->bindValue(':corpo_html', $tpl['corpo_html']);
    if ($hasTexto) $st->bindValue(':corpo_texto', $tpl['corpo_texto']);
    $st->bindValue(':variaveis_json', $tpl['variaveis_json']);
    $st->execute();
    echo "OK seed ".$slug."\n";
  } elseif ($hasCorpo) {
    $sql = "INSERT INTO email_templates (slug, nome, assunto, corpo, ativo) VALUES (:slug,:nome,:assunto,:corpo,1)
            ON DUPLICATE KEY UPDATE nome=VALUES(nome), assunto=VALUES(assunto), corpo=VALUES(corpo), ativo=VALUES(ativo)";
    $st = $pdo->prepare($sql);
    $st->bindValue(':slug', $slug);
    $st->bindValue(':nome', $tpl['nome']);
    $st->bindValue(':assunto', $tpl['assunto']);
    $st->bindValue(':corpo', $tpl['corpo_texto']); // fallback texto como corpo
    $st->execute();
    echo "OK seed (corpo) ".$slug."\n";
  } else {
    echo "WARN: tabela email_templates não tem colunas esperadas.\n";
  }
}
