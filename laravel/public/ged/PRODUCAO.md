# ✅ Checklist de Produção - ENFAS GED

## 📋 Status Atual
**Data de Preparação:** 8 de novembro de 2025  
**Versão:** 2.0 (Estilo eDok)

---

## 🔐 1. SEGURANÇA (CRÍTICO)

### 1.1 Configurações de Produção
- [ ] **Definir variável de ambiente:** `APP_ENV=production` ou `GED_ENV=production`
  - No Apache/XAMPP: editar `httpd.conf` ou criar `.htaccess` com `SetEnv APP_ENV production`
  - Isso desliga `display_errors` e ativa logs (já implementado em `core/init.php`)

### 1.2 Credenciais Sensíveis
- [ ] **Banco de Dados:**
  - [ ] Criar usuário MySQL específico (não usar `root`)
  - [ ] Definir senha forte via `GED_DB_PASS` (variável de ambiente)
  - [ ] Configurar permissões mínimas (SELECT, INSERT, UPDATE, DELETE no banco `ged`)
  
- [ ] **SMTP:**
  - [ ] Configurar `GED_SMTP_PASS` via variável de ambiente (NUNCA no código)
  - [ ] Validar credenciais SMTP funcionando (enviar email de teste)
  - [ ] Verificar se `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER` estão corretos

- [ ] **SSL/HTTPS:**
  - [ ] Obter certificado SSL (Let's Encrypt gratuito ou comercial)
  - [ ] Configurar Apache para HTTPS (porta 443)
  - [ ] Forçar redirecionamento HTTP → HTTPS
  - [ ] Ativar `GED_COOKIE_SECURE=1` para cookies seguros

### 1.3 Proteção Adicional
- [ ] **Rate Limiting:** ✅ Já implementado no login (5 tentativas/15min)
- [ ] **CSRF:** ✅ Já implementado
- [ ] **XSS:** ✅ Usando `htmlspecialchars()` em outputs
- [ ] **SQL Injection:** ✅ Prepared statements com PDO

---

## 🗄️ 2. BANCO DE DADOS

### 2.1 Migrações SQL Essenciais
```sql
-- Estrutura base
mysql -u root -p ged < sql/upgrade_2025-10-26.sql
mysql -u root -p ged < sql/rbac_2025-10-26.sql
mysql -u root -p ged < sql/username_login_2025-10-29.sql
mysql -u root -p ged < sql/20251102_2fa_usuarios.sql
mysql -u root -p ged < sql/20251030_create_email_templates.sql
mysql -u root -p ged < sql/tipos_documento_vencimentos_legais.sql
mysql -u root -p ged < sql/indices_performance_vencimentos.sql
mysql -u root -p ged < sql/logs_2025-10-26.sql
mysql -u root -p ged < sql/performance_optimization.sql
```

### 2.2 Usuário Administrador
- [ ] Criar/validar usuário admin com senha forte
- [ ] Ativar 2FA (Google Authenticator)

---

## 📧 3. E-MAIL E NOTIFICAÇÕES

### 3.1 Configuração SMTP
- [ ] **Configurar variáveis de ambiente SMTP** (ver `SMTP_CONFIG.md`)
  - Editar `.htaccess` ou `httpd.conf`
  - Definir: `GED_SMTP_HOST`, `GED_SMTP_PORT`, `GED_SMTP_USER`, `GED_SMTP_PASS`
  
- [ ] **Testar envio via interface:**
  - Acesse: http://localhost/ged/public/teste_smtp.php
  - Configure SMTP (se não usou variáveis de ambiente)
  - Envie e-mail de teste
  
- [ ] **OU testar via linha de comando:**
  ```bash
  cd c:\xampp\htdocs\ged\public
  php teste_smtp_rapido.php seu-email@exemplo.com
  ```

### 3.2 Templates de E-mail
- [x] Templates cadastrados no banco ✅
- [ ] Verificar conteúdo dos templates:
  ```sql
  SELECT slug, nome, ativo FROM email_templates;
  ```
- [ ] Personalizar templates com logo ENFAS (opcional)

### 3.3 Logs de Envio
- [x] Tabela `emails_log` criada ✅
- [ ] Monitorar envios:
  ```sql
  SELECT * FROM emails_log ORDER BY created_at DESC LIMIT 10;
  ```

---

## 🎨 4. INTERFACE

### 4.1 Login Modernizado
- [x] Design estilo eDok ✅
- [x] Checkbox "Lembrar-me" ✅
- [x] Link "Esqueceu a senha?" ✅
- [x] Rodapé com Política de Privacidade ✅
- [x] Logo destacada sem fundo ✅
- [x] Cores azuis (não laranja) ✅

### 4.2 Branding
- [ ] Logo oficial em `/public/assets/dist/img/`
- [ ] Cores em `core/branding.php`
- [ ] Favicon adicionado

---

## ⚙️ 5. FUNCIONALIDADES

### 5.1 Recursos Implementados
- [x] Login com username ou email ✅
- [x] 2FA estrutura pronta ✅
- [x] Dashboard com métricas ✅
- [x] Busca full-text em PDFs ✅
- [x] Filtros avançados ✅
- [x] Upload múltiplo ✅

### 5.2 Indexação
- [ ] Indexar documentos existentes via `/public/admin_indexacao.php`

---

## 🚀 6. SERVIDOR

### 6.1 Requisitos
- [ ] PHP >= 7.4
- [ ] MySQL/MariaDB >= 5.7
- [ ] Apache com mod_rewrite

### 6.2 Configurações PHP (php.ini)
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

### 6.3 Backup
- [ ] Configurar backup automático diário
- [ ] Testar restauração

---

## 📱 7. TESTES

### 7.1 Funcionalidades Críticas
- [ ] Login funciona
- [ ] Upload funciona
- [ ] Busca funciona
- [ ] Permissões aplicam corretamente

### 7.2 Navegadores
- [ ] Chrome
- [ ] Firefox  
- [ ] Edge

---

## 🔍 8. CONFORMIDADE LGPD

- [ ] Política de Privacidade completa
- [ ] Link visível no login
- [ ] Logs de auditoria (quem acessou o quê)
- [ ] Senhas hasheadas (bcrypt) ✅

---

## 🎯 9. DEPLOY FINAL

### 9.1 Pré-Deploy
- [ ] DNS configurado
- [ ] SSL ativo
- [ ] Variáveis de ambiente configuradas
- [ ] Permissões de arquivos corretas

### 9.2 Pós-Deploy
- [ ] Validar login
- [ ] Testar upload
- [ ] Verificar logs sem erros
- [ ] Monitorar primeiras 24h

---

## ✅ ITENS CRÍTICOS (MÍNIMO PARA PRODUÇÃO)

**✅ CONCLUÍDOS:**

1. ✅ **Interface:**
   - Login modernizado (estilo eDok)
   - Template de e-mail único aplicado
   - Logo ENFAS GED destacada
   - Cores padronizadas (azul #2563eb)

2. ✅ **E-mail:**
   - SMTP configurado e testado (250 OK)
   - 9 templates ativos
   - Logs funcionando

3. ✅ **Banco:**
   - Tabelas criadas (emails_log, app_settings)
   - Templates cadastrados

**⚠️ PENDENTES PARA PRODUÇÃO:**

4. 🔴 **Segurança:**
   - [ ] `APP_ENV=production`
   - [ ] HTTPS ativo (certificado SSL)
   - [ ] Senha admin forte
   - [ ] 2FA ativado para admin

5. 🔴 **Infraestrutura:**
   - [ ] Backup automático configurado
   - [ ] Cron de alertas agendado
   - [ ] Permissões de arquivos ajustadas

6. 🟡 **Recomendados:**
   - [ ] SPF/DKIM no domínio
   - [ ] Indexação de documentos existentes
   - [ ] Testes de aceitação completos

---

**📋 Ver checklist detalhado em: `RELEASE_NOTES_v2.0.0.md`**

---

**✅ Sistema pronto para produção quando todos itens críticos estiverem marcados!**
