SET NAMES utf8mb4;

-- Templates adicionais essenciais
INSERT INTO `email_templates` (`slug`, `nome`, `assunto`, `corpo_html`, `corpo_texto`, `variaveis_json`, `ativo`) VALUES
('senha_alterada', 'Senha Alterada', 'Sua senha foi alterada',
'<div style="font-family:sans-serif;color:#333;">
  <p>Olá {{usuario.nome|}},</p>
  <p>Informamos que sua senha no sistema foi alterada com sucesso em {{data|{{now}}}}.</p>
  <p>Se você não reconhece esta ação, contate o suporte imediatamente.</p>
  <p>Atenciosamente,<br>Equipe GED</p>
</div>',
'Olá {{usuario.nome|}},\nSua senha foi alterada em {{data|{{now}}}}.\nSe não foi você, contate o suporte.\nEquipe GED',
'{"usuario":{"nome":"Fulano"},"data":"2025-10-31 10:00"}', 1),

('usuario_criado', 'Boas-vindas (Usuário Criado)', 'Sua conta foi criada no GED',
'<div style="font-family:sans-serif;color:#333;">
  <p>Olá {{usuario.nome|}},</p>
  <p>Sua conta no GED foi criada. Use o usuário <strong>{{usuario.username|seu e-mail}}</strong> para acessar.</p>
  <p>Para definir sua senha inicial, acesse: <a href="{{link_redefinir|}}">Criar senha</a></p>
  <p>Bem-vindo(a)!</p>
</div>',
'Olá {{usuario.nome|}},\nSua conta foi criada. Usuário: {{usuario.username|seu e-mail}}.\nCrie sua senha: {{link_redefinir|}}\nBem-vindo(a)!',
'{"usuario":{"nome":"Fulano","username":"fulano"},"link_redefinir":"https://exemplo.local/redefinir/XYZ"}', 1),

('documento_assinado', 'Documento Assinado', 'Documento assinado: {{documento.titulo|Documento}}',
'<div style="font-family:sans-serif;color:#333;">
  <p>Olá {{destinatario.nome|}},</p>
  <p>O documento <strong>{{documento.titulo|Documento}}</strong> foi assinado por {{assinante.nome|}} em {{data|{{now}}}}.</p>
  <p>Visualize: <a href="{{link|}}">Abrir documento</a></p>
</div>',
'Olá {{destinatario.nome|}},\nO documento {{documento.titulo|Documento}} foi assinado por {{assinante.nome|}} em {{data|{{now}}}}.\nAbrir: {{link|}}',
'{"destinatario":{"nome":"Fulano"},"assinante":{"nome":"Ciclano"},"documento":{"titulo":"Contrato"},"link":"https://exemplo.local/doc/123"}', 1),

('lembrete_assinatura', 'Lembrete de Assinatura', 'Lembrete: assinatura pendente de {{documento.titulo|Documento}}',
'<div style="font-family:sans-serif;color:#333;">
  <p>Olá {{destinatario.nome|}},</p>
  <p>Este é um lembrete para assinar o documento <strong>{{documento.titulo|Documento}}</strong>.</p>
  <p>Prazo: {{prazo|em breve}}. Acesse: <a href="{{link|}}">Assinar agora</a></p>
</div>',
'Lembrete: assinar {{documento.titulo|Documento}}.\nPrazo: {{prazo|em breve}}.\nAssinar: {{link|}}',
'{"destinatario":{"nome":"Fulano"},"documento":{"titulo":"Contrato"},"prazo":"31/10/2025","link":"https://exemplo.local/assinar/XYZ"}', 1)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `assunto`=VALUES(`assunto`), `corpo_html`=VALUES(`corpo_html`), `corpo_texto`=VALUES(`corpo_texto`), `variaveis_json`=VALUES(`variaveis_json`), `ativo`=VALUES(`ativo`);
