SET NAMES utf8mb4;

-- Seeds de templates convertidos de templates/emails/*.php
-- Atualiza se já existir (com base no slug)

INSERT INTO `email_templates` (`slug`, `nome`, `assunto`, `corpo_html`, `corpo_texto`, `variaveis_json`, `ativo`) VALUES
('compartilhar_documento', 'Compartilhar Documento', 'Documento compartilhado: {{documento.titulo|Documento}}',
'<div style="font-family:sans-serif;color:#333;">
  <h3>eDok - Seus documentos, <strong>inteligentes.</strong></h3>
  <hr>
  <p>Olá!</p>
  <p>{{remetente.nome|Alguém}} acabou de compartilhar o documento <strong>"{{documento.titulo|Documento}}"</strong> com você.</p>
  <p>Para sua conveniência, o documento pode estar em anexo ou acessível pelo sistema.</p>
  <br>
  <p><small><strong>NOTA JURÍDICA:</strong> você é legalmente responsável pelo sigilo desta mensagem. NÃO COMPARTILHE-A. Se recebeu por engano, apague-a imediatamente.</small></p>
  <br>
  <p>Atenciosamente,</p>
  <p><strong>eDok</strong><br>Seus documentos, <strong>inteligentes.</strong></p>
</div>',
'Olá!\n{{remetente.nome|Alguém}} compartilhou o documento "{{documento.titulo|Documento}}" com você.\n\nNota: sigilo é sua responsabilidade.\n\nAtenciosamente, eDok',
'{"remetente":{"nome":"Fulano Admin"},"documento":{"titulo":"Contrato de Prestação de Serviços"}}',
1),

('novo_documento', 'Novo Documento', 'Novo documento cadastrado: {{documento.titulo|Documento}}',
'<div style="font-family:sans-serif;color:#333;">
  <p>Olá,</p>
  <p>Um novo documento foi cadastrado no sistema por <strong>{{usuario.nome|Usuário}}</strong>.</p>
  <ul>
    <li><strong>Título do Documento:</strong> {{documento.titulo|Documento}}</li>
    <li><strong>Data de Envio:</strong> {{data_envio|{{now}}}}</li>
  </ul>
  <p>Você pode acessá-lo no sistema.</p>
  <p style="text-align:center;margin:16px 0;">
    <a href="{{link|}}" style="background:#00c9a7;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;display:inline-block">Acessar o Sistema</a>
  </p>
  <p>Atenciosamente,<br>Equipe GED System</p>
</div>',
'Olá,\nUm novo documento foi cadastrado por {{usuario.nome|Usuário}}.\nTítulo: {{documento.titulo|Documento}}\nAcessar: {{link|}}\n\nEquipe GED System',
'{"usuario":{"nome":"Fulano"},"documento":{"titulo":"Relatório Financeiro"},"link":"https://exemplo.local/documentos"}',
1),

('recuperar_senha', 'Recuperação de Senha', 'Recupere sua senha',
'<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Recuperação de Senha</title>
<style>body{font-family:sans-serif;line-height:1.6;color:#333}.container{padding:20px;border:1px solid #ddd;border-radius:5px;max-width:600px;margin:20px auto}.button{background-color:#007bff;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block}</style>
</head><body><div class="container">
  <h2>Recuperação de Senha</h2>
  <p>Olá {{usuario.nome|}},</p>
  <p>Recebemos uma solicitação para redefinir sua senha no GED System. Se não foi você, por favor, ignore este e-mail.</p>
  <p>Para criar uma nova senha, clique no botão abaixo. Este link é válido por 1 hora.</p>
  <p style="text-align:center;margin:30px 0"><a href="{{link|}}" class="button">Redefinir Minha Senha</a></p>
  <p>Se o botão não funcionar, copie e cole o seguinte endereço no seu navegador:</p>
  <p>{{link|}}</p>
  <br><p>Obrigado,<br>GED System Prodea</p>
</div></body></html>',
'Olá {{usuario.nome|}},\nRecebemos uma solicitação de redefinição de senha.\nUse: {{link|}}\nSe não foi você, ignore.',
'{"usuario":{"nome":"Fulano"},"link":"https://exemplo.local/redefinir/XYZ"}',
1),

('requisitar_assinatura', 'Requisição de Assinatura', 'Requisição de assinatura: {{documento.titulo|Documento}}',
'<!DOCTYPE html><html lang="pt-br"><head><meta charset="UTF-8"><title>Requisição de Assinatura</title>
<style>body{font-family:sans-serif;line-height:1.6;color:#333}.container{padding:20px;border:1px solid #ddd;border-radius:5px;max-width:600px;margin:20px auto}.button{background-color:#007bff;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block}</style>
</head><body><div class="container">
  <h2>Requisição de Assinatura</h2>
  <p>Olá,</p>
  <p>Você foi solicitado(a) para assinar o documento "<strong>{{documento.titulo|Documento}}</strong>".</p>
  <p>Por favor, acesse o link abaixo para visualizar o documento e realizar a assinatura digital:</p>
  <p style="text-align:center;margin:30px 0"><a href="{{link|}}" class="button">Clique aqui para Assinar</a></p>
  <p>Se o botão não funcionar, copie e cole o seguinte endereço no seu navegador:</p>
  <p>{{link|}}</p>
  <br><p>Obrigado,<br>GED System Prodea</p>
</div></body></html>',
'Você foi solicitado(a) a assinar o documento {{documento.titulo|Documento}}.\nAcesse: {{link|}}\nObrigado, GED System Prodea',
'{"documento":{"titulo":"Contrato de Prestação de Serviços"},"link":"https://exemplo.local/assinar/XYZ"}',
1)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `assunto`=VALUES(`assunto`), `corpo_html`=VALUES(`corpo_html`), `corpo_texto`=VALUES(`corpo_texto`), `variaveis_json`=VALUES(`variaveis_json`), `ativo`=VALUES(`ativo`);
