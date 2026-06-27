<?php
require_once __DIR__ . '/../core/init.php';
require_once __DIR__ . '/../core/email.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
// Opcional: restrição simples — apenas usuários ID 1 ou quem tiver perfil admin (ajuste conforme seu sistema)
$allow = ($_SESSION['user_id'] == 1) || !empty($_SESSION['is_admin']);
if (!$allow) { http_response_code(403); echo 'Acesso negado'; exit; }

header('Content-Type: text/html; charset=UTF-8');

function ensure_table(PDO $pdo) {
  $pdo->exec("CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE,
    nome VARCHAR(150) NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    corpo TEXT NULL,
    corpo_html MEDIUMTEXT NULL,
    corpo_texto MEDIUMTEXT NULL,
    variaveis_json JSON NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS email_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    assunto VARCHAR(255),
    corpo_html MEDIUMTEXT,
    corpo_texto MEDIUMTEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS emails_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_slug VARCHAR(100),
    assunto VARCHAR(255),
    destinatario VARCHAR(255),
    remetente VARCHAR(255),
    status VARCHAR(20),
    erro TEXT NULL,
    payload_json JSON NULL,
    usuario_id INT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
    chave VARCHAR(100) PRIMARY KEY,
    valor TEXT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function upsert_template(PDO $pdo, string $slug, string $nome, string $assunto, string $html, string $text, array $vars): int {
  $stmt = $pdo->prepare('SELECT id FROM email_templates WHERE slug = ?');
  $stmt->execute([$slug]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
      $id = (int)$row['id'];
      $u = $pdo->prepare('UPDATE email_templates SET nome=?, assunto=?, corpo_html=?, corpo_texto=?, variaveis_json=?, ativo=1, updated_at=NOW() WHERE id=?');
      $u->execute([$nome, $assunto, $html, $text, json_encode($vars, JSON_UNESCAPED_UNICODE), $id]);
    } else {
      $i = $pdo->prepare('INSERT INTO email_templates (slug,nome,assunto,corpo_html,corpo_texto,variaveis_json,ativo) VALUES (?,?,?,?,?,?,1)');
      $i->execute([$slug,$nome,$assunto,$html,$text,json_encode($vars, JSON_UNESCAPED_UNICODE)]);
      $id = (int)$pdo->lastInsertId();
    }
    // versioning
    $v = $pdo->prepare('INSERT INTO email_template_versions (template_id,assunto,corpo_html,corpo_texto) VALUES (?,?,?,?)');
    $v->execute([$id,$assunto,$html,$text]);
    return $id;
}

try {
  ensure_table($pdo);

  $brand = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
  $primary = defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#6b7280';
  $baseUrl = rtrim((isset($_SERVER['REQUEST_SCHEME'])?$_SERVER['REQUEST_SCHEME']:((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on')?'https':'http')) . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL, '/');

  // Header/Footer padrão se não houver
  $hdr = app_setting_get($pdo, 'email_header_html','');
  $ftr = app_setting_get($pdo, 'email_footer_html','');
  if ($hdr === '' && $ftr === '') {
    $hdr = '<table role="presentation" width="100%" style="background:#f3f4f6"><tr><td align="center" style="padding:20px">'
         . '<table role="presentation" width="600" style="max-width:600px;background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.06)">'
         . '<tr><td style="padding:14px 18px;border-bottom:1px solid #eef1f5;font-family:Arial"><span style="display:inline-block;width:10px;height:10px;background:' . htmlspecialchars($primary,ENT_QUOTES) . ';border-radius:50%;vertical-align:middle;margin-right:8px"></span>'
         . '<span style="vertical-align:middle;font-weight:600;color:#1f2d3d">' . htmlspecialchars($brand,ENT_QUOTES) . '</span></td></tr><tr><td style="padding:18px 18px 6px;font-family:Arial">';
    $ftr = '</td></tr><tr><td style="padding:12px 18px;border-top:1px solid #eef1f5;color:#6c757d;font-family:Arial;font-size:12px">Este é um e-mail automático. Por favor, não responda.</td></tr></table></td></tr></table>';
    app_setting_set($pdo, 'email_header_html', $hdr);
    app_setting_set($pdo, 'email_footer_html', $ftr);
  }

  // Templates
  upsert_template(
    $pdo,
    'compartilhar_link',
    'Compartilhar documento (link)',
    'Documento: {{titulo|Documento}}',
    '<p>Você recebeu acesso ao documento <strong>{{titulo|Documento}}</strong>.</p><p><a href="{{url}}" style="background:#374151;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px;display:inline-block">Abrir documento</a></p><p>Validade: {{validade|Sem expiração}}</p><p>{{mensagem|}}</p>',
    "Documento: {{titulo|Documento}}\nAcesse: {{url}}\nValidade: {{validade|Sem expiração}}\n{{mensagem|}}",
    ['titulo','url','validade','mensagem']
  );

  upsert_template(
    $pdo,
    'compartilhar_documento',
    'Compartilhar documento (anexo)',
    'Documento: {{titulo|Documento}}',
    '<p>Segue o documento <strong>{{titulo|Documento}}</strong> em anexo, enviado por {{mensagem|um usuário}}.</p>',
    'Documento: {{titulo|Documento}}\nEnviado por: {{mensagem|}}',
    ['titulo','mensagem']
  );

  upsert_template(
    $pdo,
    'compartilhar_interno',
    'Compartilhamento interno',
    'Documento: {{titulo|Documento}}',
    '<p>Olá {{nome|}}, você recebeu acesso ao documento <strong>{{titulo|Documento}}</strong>.</p><p><a href="{{url}}" style="background:#374151;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px;display:inline-block">Abrir no GED</a></p><p>Validade: {{validade|Sem expiração}}</p><p>{{mensagem|}}</p>',
    'Olá {{nome|}}, acesse: {{url}}',
    ['nome','titulo','url','validade','mensagem']
  );

  upsert_template(
    $pdo,
    'requisitar_assinatura',
    'Requisição de Assinatura',
    'Assine: {{nome_documento|{{titulo|Documento}}}}',
    '<p>Você foi convidado a assinar o documento <strong>{{nome_documento|{{titulo|Documento}}}}</strong>.</p><p><a href="{{link_assinatura|{{url|}}}}" style="background:#374151;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px;display:inline-block">Assinar agora</a></p>',
    'Assinar: {{nome_documento|{{titulo|Documento}}}} - {{link_assinatura|{{url|}}}}',
    ['nome_documento','titulo','link_assinatura','url']
  );

  upsert_template(
    $pdo,
    'recuperar_senha',
    'Recuperação de senha',
    'Recuperar sua senha',
    '<p>Olá {{nome_usuario|}}, para redefinir sua senha clique no botão abaixo.</p><p><a href="{{link_redefinicao}}" style="background:#374151;color:#fff;text-decoration:none;padding:10px 14px;border-radius:6px;display:inline-block">Redefinir senha</a></p><p>O link expira em 1 hora.</p>',
    'Olá {{nome_usuario|}}, acesse: {{link_redefinicao}} (expira em 1 hora).',
    ['nome_usuario','link_redefinicao']
  );

  upsert_template(
    $pdo,
    'aviso_sistema',
    'Notificação do sistema',
    '{{assunto|Notificação}}',
    '<p>{{mensagem|}}</p>',
    '{{mensagem|}}',
    ['assunto','mensagem']
  );

  // Confirmação de assinatura (estilo eDok, com nossa marca)
  $primaryColor = $primary ?: '#6b7280';
  $btnColor = $primaryColor;
  $assin_html = '
  <table class="v1wrapper" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;margin:0;padding:0;width:100%">
    <tr><td align="center">
      <table class="v1content" width="100%" cellpadding="0" cellspacing="0" style="margin:0;padding:0;width:100%">
        <tr>
          <td class="v1header" style="padding:25px 0;text-align:center">
            <a href="' . htmlspecialchars($baseUrl, ENT_QUOTES) . '" style="color:#333;font-size:19px;font-weight:bold;text-decoration:none;display:inline-block">'
              . htmlspecialchars($brand, ENT_QUOTES) . ' - Seus documentos, <span class="v1primary" style="color:' . htmlspecialchars($primaryColor, ENT_QUOTES) . '">inteligentes</span>.' .
            '</a>
          </td>
        </tr>
        <tr>
          <td class="v1body" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;border-bottom:1px solid #f4f4f4;border-top:1px solid #f4f4f4;margin:0;padding:0;width:100%">
            <table class="v1inner-body" align="center" width="570" cellpadding="0" cellspacing="0" style="background:#fff;border:1px solid #ddd;border-radius:2px;box-shadow:0 2px 0 rgba(0,0,150,.025),2px 4px 0 rgba(0,0,150,.015);margin:0 auto;padding:0;width:570px">
              <tr>
                <td class="v1content-cell" style="max-width:100vw;padding:32px">
                  <h1 style="color:#333;font-size:18px;font-weight:bold;margin-top:0;text-align:left">Olá!</h1>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left">Abaixo estão os detalhes da sua assinatura eletrônica.</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Nome:</strong> {{nome|Não informado}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>CPF/CNPJ:</strong> {{cpf_cnpj|Não informado}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Qualificador:</strong> {{qualificacao|N/D}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Localização:</strong> {{localizacao|Não fornecida}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Assinatura:</strong> {{assinatura_info|Fornecida}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>IP (nome):</strong> {{ip_info|N/D}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Verificador:</strong> {{verificador|}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>Documento:</strong> {{documento|}}</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>ID (chave):</strong> {{documento_id|}} ({{chave|}})</p>

                  <table class="v1action" align="center" width="100%" cellpadding="0" cellspacing="0" style="margin:30px auto;padding:0;text-align:center;width:100%">
                    <tr><td align="center">
                      <table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align="center">
                        <table border="0" cellpadding="0" cellspacing="0"><tr><td>
                          <strong><a href="{{link_verificacao|}}" class="v1button v1button-primary" style="border-radius:4px;color:#fff;display:inline-block;overflow:hidden;text-decoration:none;background-color:' + htmlspecialchars($btnColor, ENT_QUOTES) + ';border:8px solid ' + htmlspecialchars($btnColor, ENT_QUOTES) + ';border-left-width:18px;border-right-width:18px" target="_blank" rel="noreferrer">Verificar Assinatura</a></strong>
                        </td></tr></table>
                      </td></tr></table>
                    </td></tr>
                  </table>

                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left"><strong>NOTA JURÍDICA:</strong> você é legalmente responsável pelo sigilo desta mensagem. Não compartilhe. Se recebeu por engano, apague imediatamente.</p>
                  <p style="color:#333;font-size:16px;line-height:1.5em;margin-top:0;text-align:left">Atenciosamente,<br><br><strong>' . htmlspecialchars($brand, ENT_QUOTES) . '<br>Seus documentos, <span class="v1primary" style="color:' . htmlspecialchars($primaryColor, ENT_QUOTES) . '">inteligentes</span>.</strong></p>

                  <table class="v1subcopy" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #ddd;margin-top:25px;padding-top:25px"><tr><td>
                    <p style="color:#333;line-height:1.5em;margin-top:0;text-align:left;font-size:14px">Se você estiver com problemas para clicar no botão "Verificar Assinatura", copie e cole este endereço no navegador:<br><br><span class="v1break-all" style="word-break:break-all"><a href="{{link_verificacao|}}" style="color:' + htmlspecialchars($primaryColor, ENT_QUOTES) + '" target="_blank" rel="noreferrer">{{link_verificacao|}}</a></span></p>
                  </td></tr></table>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            <table class="v1footer" align="center" width="570" cellpadding="0" cellspacing="0" style="margin:0 auto;padding:0;text-align:center;width:570px"><tr><td class="v1content-cell" align="center" style="max-width:100vw;padding:32px">
              <p style="line-height:1.5em;margin-top:0;color:#999;font-size:12px;text-align:center">© ' . date('Y') . ' ' . htmlspecialchars($brand, ENT_QUOTES) . '. Todos os direitos reservados.</p>
            </td></tr></table>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>';

  $assin_text = "Olá!\n\n" .
    "Detalhes da assinatura:\n" .
    "Nome: {{nome|N/D}}\n" .
    "CPF/CNPJ: {{cpf_cnpj|N/D}}\n" .
    "Qualificador: {{qualificacao|N/D}}\n" .
    "Localização: {{localizacao|N/D}}\n" .
    "Assinatura: {{assinatura_info|Fornecida}}\n" .
    "IP: {{ip_info|N/D}}\n" .
    "Verificador: {{verificador|}}\n" .
    "Documento: {{documento|}}\n" .
    "ID (chave): {{documento_id|}} ({{chave|}})\n\n" .
    "Verificar assinatura: {{link_verificacao|}}\n";

  upsert_template(
    $pdo,
    'assinatura_confirmada',
    'Confirmação de assinatura eletrônica',
    '[{{brand|' . addslashes($brand) . '}}] Confirmação de assinatura eletrônica',
    $assin_html,
    $assin_text,
    ['nome','cpf_cnpj','qualificacao','localizacao','assinatura_info','ip_info','verificador','documento','documento_id','chave','link_verificacao','brand']
  );

  echo '<div style="font-family:Arial;padding:20px">\n<h3>Templates de e-mail: configuração concluída</h3>\n<p>Os modelos foram criados/atualizados e ativados.</p>\n<p><a href="admin_email_templates.php">Gerenciar templates</a> · <a href="admin_email_template_test.php">Enviar teste</a></p>\n</div>';
} catch (Throwable $e) {
  http_response_code(500);
  echo '<pre>Erro ao configurar templates: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</pre>';
}
