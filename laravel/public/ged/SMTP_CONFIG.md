# 📧 Guia de Configuração SMTP - ENFAS GED

## ✅ Checklist Rápido

- [x] Tabelas criadas (`emails_log`, `app_settings`)
- [x] Templates de email cadastrados
- [x] Função de envio implementada
- [x] Página de teste criada (`teste_smtp.php`)
- [ ] **SMTP configurado** ← FAZER AGORA
- [ ] **Teste de envio realizado**

---

## 🔧 1. CONFIGURAÇÃO SMTP

### Opção A: Gmail (Recomendado para Testes)

1. **Ativar "Acesso de apps menos seguros"** OU usar **Senha de App**:
   - Acesse: https://myaccount.google.com/apppasswords
   - Crie senha de app para "E-mail"

2. **Configurar no arquivo `.htaccess` ou `httpd.conf`**:
```apache
SetEnv GED_SMTP_HOST "smtp.gmail.com"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "seu-email@gmail.com"
SetEnv GED_SMTP_PASS "sua-senha-app-aqui"
SetEnv GED_SMTP_SECURE "tls"
SetEnv GED_MAIL_FROM "seu-email@gmail.com"
SetEnv GED_MAIL_FROM_NAME "ENFAS GED"
```

3. **OU configurar via interface web**:
   - Acesse: http://localhost/ged/public/teste_smtp.php
   - Preencha os campos (senha vai para variável de ambiente depois)

### Opção B: Servidor SMTP Próprio (ged.enfas.com.br)

```apache
SetEnv GED_SMTP_HOST "mail.ged.enfas.com.br"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "noreply@ged.enfas.com.br"
SetEnv GED_SMTP_PASS "SENHA_SEGURA_AQUI"
SetEnv GED_SMTP_SECURE "tls"
SetEnv GED_MAIL_FROM "noreply@ged.enfas.com.br"
SetEnv GED_MAIL_FROM_NAME "ENFAS GED"
```

### Opção C: Office 365 / Outlook

```apache
SetEnv GED_SMTP_HOST "smtp.office365.com"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "seu-email@outlook.com"
SetEnv GED_SMTP_PASS "sua-senha"
SetEnv GED_SMTP_SECURE "tls"
```

---

## 🧪 2. TESTAR ENVIO

### Via Interface Web (Mais Fácil):

1. Acesse: **http://localhost/ged/public/teste_smtp.php**
2. Configure SMTP (se ainda não configurou)
3. Digite seu e-mail no campo "E-mail de Destino"
4. Clique em **"Testar Envio"**
5. Verifique se recebeu o e-mail

### Via Código PHP (Avançado):

```php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/core/email.php';

$dados = [
    'usuario' => ['nome' => 'Teste'],
    'link' => 'http://localhost/ged',
    'expiracao' => '1 hora'
];

$sucesso = email_send_template($pdo, 'seu-email@teste.com', 'recuperar_senha', $dados);
echo $sucesso ? "✅ Enviado!" : "❌ Falhou!";
```

---

## 📋 3. TEMPLATES DISPONÍVEIS

| Slug | Nome | Uso |
|------|------|-----|
| `recuperar_senha` | Recuperação de Senha | Esqueci a senha |
| `usuario_criado` | Boas-vindas | Novo usuário |
| `compartilhar_link` | Compartilhamento | Compartilhar documento |
| `alerta_vencimento` | Alerta de Vencimento | Documentos vencendo |
| `senha_alterada` | Senha Alterada | Confirmação de troca |

### Variáveis de Template:

**recuperar_senha:**
```php
$dados = [
    'usuario' => ['nome' => 'João Silva'],
    'link' => 'http://..../redefinir_senha.php?token=abc123',
    'expiracao' => '1 hora'
];
```

**usuario_criado:**
```php
$dados = [
    'nome_usuario' => 'Maria Santos',
    'email' => 'maria@empresa.com',
    'senha_temporaria' => 'Temp@123'
];
```

---

## 🐛 4. TROUBLESHOOTING

### Erro: "SMTP connect() failed"

**Causa:** Credenciais inválidas ou porta bloqueada

**Solução:**
1. Verifique usuário/senha SMTP
2. Teste porta 587 (TLS) ou 465 (SSL)
3. Verifique firewall

### Erro: "Could not authenticate"

**Causa:** Senha incorreta ou "apps menos seguros" desativado

**Solução (Gmail):**
1. Use senha de app: https://myaccount.google.com/apppasswords
2. OU ative "Acesso de apps menos seguros"

### Erro: "Template não encontrado"

**Causa:** Template não existe ou está inativo

**Solução:**
```sql
-- Verificar templates
SELECT slug, ativo FROM email_templates;

-- Ativar template
UPDATE email_templates SET ativo = 1 WHERE slug = 'recuperar_senha';
```

### E-mails vão para SPAM

**Solução:**
1. Configure SPF/DKIM no domínio
2. Use domínio real (não localhost)
3. Evite palavras como "Grátis", "Urgente" no assunto

---

## 📊 5. MONITORAMENTO

### Ver Logs de Envio:

```sql
-- Últimos 20 envios
SELECT created_at, destinatario, status, erro 
FROM emails_log 
ORDER BY created_at DESC 
LIMIT 20;

-- Contar sucessos/falhas
SELECT status, COUNT(*) as total 
FROM emails_log 
GROUP BY status;
```

### Via Interface:
- Acesse: http://localhost/ged/public/teste_smtp.php
- Role até "Últimos 10 Envios"

---

## ✅ 6. CHECKLIST FINAL

Antes de colocar em produção:

- [ ] SMTP configurado via variáveis de ambiente
- [ ] Teste de envio bem-sucedido
- [ ] E-mail recebido na caixa de entrada (não SPAM)
- [ ] Templates personalizados com logo ENFAS
- [ ] Senha SMTP **NÃO está** no código-fonte
- [ ] Logs de envio funcionando
- [ ] Link "Esqueci a senha" testado
- [ ] E-mail de boas-vindas testado

---

## 🚀 PRÓXIMOS PASSOS

1. **Configure SMTP** usando uma das opções acima
2. **Teste envio** em http://localhost/ged/public/teste_smtp.php
3. **Personalize templates** se necessário
4. **Configure SPF/DKIM** no domínio para produção

---

**Documentação PHPMailer:** https://github.com/PHPMailer/PHPMailer  
**Gmail App Passwords:** https://myaccount.google.com/apppasswords
