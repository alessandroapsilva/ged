# ✅ Deploy Checklist - ENFAS GED v2.0.0

## 🎯 Status Atual: PRONTO PARA PRODUÇÃO

**Data:** 8 de novembro de 2025  
**Versão:** 2.0.0 (prod-2025.11.08)

---

## ✅ CONCLUÍDO (100% Pronto)

### 🎨 Interface & Design
- [x] Login modernizado estilo eDok
- [x] Logo ENFAS GED destacada (sem fundo cinza)
- [x] Cores azuis padronizadas (#2563eb, #3b82f6)
- [x] Checkbox "Lembrar-me" e "Esqueceu a senha?"
- [x] Footer com link de Política de Privacidade
- [x] Card login compacto (340px)
- [x] Responsividade mobile testada
- [x] Página recuperação de senha matching design

### 📧 Sistema de E-mail
- [x] Template único moderno aplicado
- [x] Gradient azul no header (#2563eb → #3b82f6)
- [x] Logo em branco no header do e-mail
- [x] Tipografia Inter (Google Fonts)
- [x] Rodapé com 3 links (Sistema, Privacidade, Suporte)
- [x] URLs dinâmicas (auto-detecta dev/prod)
- [x] Fallback de logo (onerror)
- [x] Responsivo mobile (logo 150px, padding reduzido)
- [x] Classes CSS adicionais (highlight-box, info-text)
- [x] 9 templates ativos e funcionando

### 🔧 Configuração SMTP
- [x] Servidor configurado (ged.enfas.com.br:465)
- [x] Autenticação funcionando (noreply@ged.enfas.com.br)
- [x] Teste realizado: 250 OK (mensagem enviada)
- [x] Variáveis de ambiente em .htaccess
- [x] Configurações em app_settings
- [x] Logs de envio auditáveis (emails_log)
- [x] Auto-correção porta/segurança (587→TLS, 465→SSL)
- [x] Envelope sender configurado

### 📊 Banco de Dados
- [x] Tabela emails_log criada
- [x] Tabela app_settings criada
- [x] 9 templates cadastrados
- [x] Configurações SMTP gravadas
- [x] URLs localhost → produção

### 🛠️ Ferramentas Admin
- [x] teste_smtp.php - Interface web
- [x] email_preview.php - Pré-visualização templates
- [x] teste_smtp_rapido.php - Script CLI
- [x] admin_email_template_test.php - Teste individual

### 📚 Documentação
- [x] SMTP_CONFIG.md - Guia completo
- [x] EMAIL_TEMPLATE.md - Doc do template
- [x] CONFIGURAR_EMAIL.md - Setup rápido
- [x] PRODUCAO.md - Checklist produção
- [x] RELEASE_NOTES_v2.0.0.md - Notas da versão

### ⚙️ Arquivos de Configuração
- [x] config/branding.json - Cores #2563eb/#3b82f6
- [x] config/version.json - v2.0.0 prod-2025.11.08
- [x] .htaccess - Variáveis SMTP configuradas
- [x] core/email.php - Template único aplicado

---

## ⚠️ PENDENTE (Antes de Deploy Produção)

### 🔴 CRÍTICO (Bloqueiam Deploy)

1. **HTTPS / SSL**
   ```bash
   # Obter certificado (Let's Encrypt grátis)
   certbot --apache -d ged.enfas.com.br
   
   # Ou certificado comercial
   # Configurar em Apache: SSLCertificateFile, SSLCertificateKeyFile
   ```
   - [ ] Certificado SSL instalado
   - [ ] Redirecionamento HTTP → HTTPS ativo
   - [ ] Cookie secure ativado

2. **Modo Produção**
   ```apache
   # Em .htaccess ou httpd.conf
   SetEnv APP_ENV "production"
   SetEnv GED_ENV "production"
   ```
   - [ ] APP_ENV=production definido
   - [ ] display_errors OFF (já está em core/init.php)
   - [ ] error_log funcionando

3. **Usuário Admin Seguro**
   ```sql
   -- Login no MySQL
   USE GED;
   
   -- Criar admin com senha forte
   UPDATE usuarios 
   SET senha = '$2y$10$[HASH_BCRYPT_AQUI]', 
       ativo = 1,
       tipo = 'admin'
   WHERE id = 1;
   
   -- Ou criar novo
   INSERT INTO usuarios (nome, email, senha, tipo, ativo) 
   VALUES ('Admin Sistema', 'admin@enfas.com.br', '[HASH]', 'admin', 1);
   ```
   - [ ] Senha forte (min 12 caracteres, maiúsc/minúsc/núm/especial)
   - [ ] E-mail válido configurado
   - [ ] Usuários de teste removidos

4. **Backup Automático**
   ```bash
   # Cron diário: 2h da manhã
   # crontab -e
   0 2 * * * mysqldump -u root -p[SENHA] GED > /backups/ged_$(date +\%Y\%m\%d).sql
   
   # Manter últimos 30 dias
   0 3 * * * find /backups -name "ged_*.sql" -mtime +30 -delete
   ```
   - [ ] Backup MySQL diário
   - [ ] Backup arquivos (storage/uploads) semanal
   - [ ] Teste de restauração realizado

---

## 🟡 RECOMENDADO (Alta Prioridade)

5. **SPF/DKIM no Domínio**
   ```dns
   ; Registro SPF (Adicionar no DNS)
   ged.enfas.com.br. IN TXT "v=spf1 mx a ip4:[IP_SERVIDOR] ~all"
   
   ; DKIM (solicitar ao provedor de e-mail)
   ```
   - [ ] SPF configurado
   - [ ] DKIM ativado
   - [ ] Teste anti-spam realizado

6. **Cron de Alertas**
   ```bash
   # Todo dia às 8h
   0 8 * * * php /var/www/ged/public/cron_notifications.php >> /var/log/ged_cron.log 2>&1
   ```
   - [ ] Cron agendado
   - [ ] Log funcionando
   - [ ] Teste manual executado

7. **Permissões de Arquivos**
   ```bash
   cd /var/www/ged
   
   # Arquivos sensíveis
   chmod 600 .htaccess
   chmod 600 config/*.json
   chmod 600 db_config.php
   
   # Diretórios públicos
   chmod 755 public
   
   # Diretórios graváveis
   chmod 775 storage uploads
   chown -R www-data:www-data storage uploads
   ```
   - [ ] Permissões ajustadas
   - [ ] Owner correto (www-data ou apache)

8. **2FA para Admin**
   ```sql
   -- Ativar 2FA para admin
   UPDATE usuarios SET dois_fatores_ativo = 1 WHERE tipo = 'admin';
   ```
   - [ ] Google Authenticator configurado
   - [ ] QR Code gerado e salvo
   - [ ] Teste de login com 2FA OK

---

## 🟢 OPCIONAL (Pode Fazer Depois)

9. **Indexação Documentos Existentes**
   ```bash
   php /var/www/ged/public/admin_indexacao.php
   ```

10. **Monitoramento**
    - [ ] Uptime monitor configurado
    - [ ] Alertas de erro via e-mail
    - [ ] Dashboard de métricas

11. **Otimizações**
    - [ ] Compressão Gzip ativa
    - [ ] Cache de assets (CSS/JS)
    - [ ] CDN para arquivos estáticos

---

## 📝 Comandos Úteis de Deploy

### Verificar Status Atual
```bash
# Ver configuração SMTP
php -r "echo getenv('GED_SMTP_HOST') ?: 'NÃO CONFIGURADO';"

# Ver modo (dev/prod)
php -r "echo getenv('APP_ENV') ?: 'development';"

# Testar e-mail
cd /var/www/ged/public
php teste_smtp_rapido.php seu-email@dominio.com

# Ver últimos logs
tail -f /var/log/apache2/error.log
tail -f /var/log/ged_cron.log
```

### Deploy Rápido
```bash
# 1. Fazer backup
mysqldump -u root -p GED > backup_pre_deploy.sql
tar -czf backup_arquivos.tar.gz storage uploads

# 2. Atualizar código (Git)
git pull origin main

# 3. Aplicar migrações (se houver novas)
mysql -u root -p GED < sql/nova_migracao.sql

# 4. Limpar cache (se houver)
rm -rf storage/cache/*

# 5. Reiniciar Apache
systemctl restart apache2
```

---

## ✅ Validação Pós-Deploy

### Testes Manuais
- [ ] Login funciona
- [ ] Upload de documento OK
- [ ] Busca retorna resultados
- [ ] Compartilhamento envia e-mail
- [ ] "Esqueci senha" envia e-mail
- [ ] Dashboard carrega métricas
- [ ] Permissões RBAC funcionando
- [ ] Logout funciona

### Testes Automatizados
```sql
-- Ver e-mails enviados nas últimas 24h
SELECT * FROM emails_log 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;

-- Taxa de sucesso
SELECT 
  status,
  COUNT(*) as total,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM emails_log), 2) as pct
FROM emails_log 
GROUP BY status;

-- Logins nas últimas 24h
SELECT COUNT(*) as total_logins
FROM logs 
WHERE acao LIKE '%Login%' 
  AND data_hora >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

### Métricas de Sucesso (Primeiras 48h)
- ✅ Taxa entrega e-mail > 95%
- ✅ Tempo resposta médio < 2s
- ✅ Zero erros críticos
- ✅ Usuários conseguem fazer login
- ✅ Documentos sendo criados/acessados

---

## 🚨 Rollback (Se Necessário)

```bash
# 1. Parar Apache
systemctl stop apache2

# 2. Restaurar backup
mysql -u root -p GED < backup_pre_deploy.sql
tar -xzf backup_arquivos.tar.gz

# 3. Reverter código (Git)
git reset --hard [COMMIT_ANTERIOR]

# 4. Reiniciar Apache
systemctl start apache2
```

---

## 📞 Contatos de Emergência

**Desenvolvedor:** [Nome/Telefone]  
**Suporte Infraestrutura:** [Nome/Telefone]  
**E-mail Sistema:** suporte@enfas.com.br  
**Documentação:** https://ged.enfas.com.br/docs

---

**Status Final:** ✅ **APROVADO PARA DEPLOY** (após completar itens CRÍTICOS)

**Assinatura:**  
_________________________  
Responsável Técnico

**Data:** ___/___/2025
