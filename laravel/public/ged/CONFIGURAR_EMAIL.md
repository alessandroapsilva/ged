# 🚀 Como Configurar E-mail AGORA

## ⚡ Configuração Rápida (5 minutos)

### 1️⃣ Editar Arquivo de Configuração

Abra o arquivo `c:\xampp\htdocs\ged\.htaccess` (crie se não existir) ou copie de `.htaccess.exemplo`:

```apache
# SMTP Gmail (RECOMENDADO PARA TESTES)
SetEnv GED_SMTP_HOST "smtp.gmail.com"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "seu-email@gmail.com"
SetEnv GED_SMTP_PASS "sua-senha-app"
SetEnv GED_SMTP_SECURE "tls"
SetEnv GED_MAIL_FROM "seu-email@gmail.com"
SetEnv GED_MAIL_FROM_NAME "ENFAS GED"
```

**⚠️ IMPORTANTE:** Para Gmail, use **Senha de App**:
1. Acesse: https://myaccount.google.com/apppasswords
2. Crie senha de app para "E-mail"
3. Use essa senha no `GED_SMTP_PASS`

### 2️⃣ Reiniciar Apache

```powershell
# No XAMPP Control Panel, clique em "Stop" e depois "Start" no Apache
```

OU via terminal:
```powershell
c:\xampp\apache\bin\httpd.exe -k restart
```

### 3️⃣ Testar Envio

**Opção A - Via Interface Web (Mais Fácil):**
1. Acesse: http://localhost/ged/public/teste_smtp.php
2. Digite seu e-mail
3. Clique em "Testar Envio"

**Opção B - Via Terminal:**
```powershell
cd c:\xampp\htdocs\ged\public
php teste_smtp_rapido.php seu-email@exemplo.com
```

### 4️⃣ Verificar E-mail

- ✅ Verifique sua caixa de entrada
- ⚠️ Se não aparecer, verifique SPAM
- ❌ Se não chegou, veja **Solução de Problemas** abaixo

---

## 🔧 Outras Opções de SMTP

### Office 365 / Outlook

```apache
SetEnv GED_SMTP_HOST "smtp.office365.com"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "seu-email@outlook.com"
SetEnv GED_SMTP_PASS "sua-senha"
SetEnv GED_SMTP_SECURE "tls"
```

### Servidor SMTP Próprio (ENFAS)

```apache
SetEnv GED_SMTP_HOST "mail.ged.enfas.com.br"
SetEnv GED_SMTP_PORT "587"
SetEnv GED_SMTP_USER "noreply@ged.enfas.com.br"
SetEnv GED_SMTP_PASS "SENHA_SEGURA"
SetEnv GED_SMTP_SECURE "tls"
```

---

## 🐛 Solução de Problemas

### ❌ "SMTP connect() failed"

**Problema:** Não consegue conectar ao servidor SMTP

**Solução:**
1. Verifique se o host está correto (`smtp.gmail.com`)
2. Tente porta `465` com `ssl` em vez de `587` com `tls`
3. Verifique firewall do Windows

### ❌ "Could not authenticate"

**Problema:** Senha incorreta

**Solução Gmail:**
1. Use **Senha de App**: https://myaccount.google.com/apppasswords
2. NÃO use a senha normal da conta

**Solução Office365:**
- Ative autenticação SMTP nas configurações da conta

### ❌ E-mail não chega

**Problema:** Envio bem-sucedido mas não recebe

**Solução:**
1. Verifique pasta SPAM/Lixo Eletrônico
2. Aguarde alguns minutos (pode demorar)
3. Verifique logs:
   ```sql
   SELECT * FROM emails_log ORDER BY created_at DESC LIMIT 5;
   ```

### ❌ "Template não encontrado"

**Problema:** Template de e-mail não existe no banco

**Solução:**
```powershell
# Aplicar migração de templates
cd c:\xampp\htdocs\ged
Get-Content sql\20251030_create_email_templates.sql | c:\xampp\mysql\bin\mysql.exe -u root GED
```

---

## 📋 Verificar Status

### Ver Configuração Atual:
```powershell
cd c:\xampp\htdocs\ged\public
php teste_smtp_rapido.php
# (sem passar e-mail - só mostra config)
```

### Ver Templates Cadastrados:
```sql
USE GED;
SELECT slug, nome, ativo FROM email_templates;
```

### Ver Logs de Envio:
```sql
USE GED;
SELECT created_at, destinatario, status, erro 
FROM emails_log 
ORDER BY created_at DESC 
LIMIT 10;
```

---

## ✅ Tudo Funcionando?

Se o e-mail de teste chegou:
- ✅ SMTP configurado corretamente
- ✅ Templates funcionando
- ✅ Sistema pronto para enviar notificações
- ✅ "Esqueci a senha" funcionando
- ✅ Boas-vindas de usuários funcionando

---

## 📚 Documentação Completa

- `SMTP_CONFIG.md` - Guia detalhado de configuração
- `PRODUCAO.md` - Checklist completo de produção
- Página de teste: http://localhost/ged/public/teste_smtp.php
