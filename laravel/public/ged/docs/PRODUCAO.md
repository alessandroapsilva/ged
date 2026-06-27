# Guia de Produção – GED

Este guia resume os passos para publicar o GED com segurança e boa performance.

## 1) Ambiente
- APP_ENV/GED_ENV: defina `production` (desliga erros em tela, ativa headers)  
  Apache: `SetEnv APP_ENV "production"`
- BASE_URL: caminho do subdiretório (ex.: `/ged`)  
  Apache: `SetEnv GED_BASE_URL "/ged"`

## 2) Banco de Dados
- Variáveis: `GED_DB_HOST`, `GED_DB_NAME`, `GED_DB_USER`, `GED_DB_PASS`
- Migrações essenciais (em `ged/sql/`):
  - `20251030_create_app_settings.sql`
  - `20251030_create_email_templates.sql`
  - `20251030_create_ingest.sql`
  - opcionais: `workflow.sql`, `assinaturas*.sql`, `versioning_*.sql`, `ocr.sql`, `sharing_*.sql`

## 3) Web Server (Apache)
- Aponte o VirtualHost para `.../ged/public`
- Habilite `.htaccess`: `AllowOverride All`
- HTTPS recomendado. HSTS já previsto em `public/.htaccess` (ativo só em HTTPS)

Exemplo: `apache/ged-vhost.example.conf` (copie e ajuste para o `httpd-vhosts.conf`).

## 4) Dependências PHP
- Extensões: `pdo_mysql`, `mbstring`, `json`, `openssl`, `gd`
- Composer (opcional em prod): `composer install --no-dev --optimize-autoloader`

## 5) Diretórios e Permissões
- Graváveis: `public/uploads`, `public/storage/uploads`, `storage`, `storage/arquivados`
- Proteção: `.htaccess` já incluso em `storage/` e `storage/arquivados/` (deny all)

## 6) SMTP/E-mails
- Defina: `GED_SMTP_HOST`, `GED_SMTP_PORT` (587 TLS/465 SSL), `GED_SMTP_USER`, `GED_SMTP_PASS`, `GED_SMTP_SECURE`, `GED_MAIL_FROM`, `GED_MAIL_FROM_NAME`
- Teste pelo painel (Admin > E-mails) e monitore logs

## 7) Limites de Upload
- Ajuste `php.ini`: `upload_max_filesize`, `post_max_size`, `memory_limit` conforme volume
- Tela Admin > Checklist de Produção mostra e explica onde alterar

## 8) Tarefas Agendadas (Windows)
- Backup diário: `scripts/backup_diario.ps1` (Task Scheduler – 02:00)
- Monitoramento 5/5 min: `scripts/monitoramento.ps1`
- Reindex (busca por conteúdo): `php .../ged/scripts/cron_reindex.php`

## 9) Healthcheck e Checklist
- Health: `http(s)://host/ged/public/health.php` → `{"status":"ok"}`
- Admin > Checklist de Produção: valida pastas, limites e SMTP; cria `.htaccess` se faltar

## 10) Limpeza de Placeholders
- Páginas antes vazias agora implementadas: `2fa_enable.php`, `assinaturas_relatorio.php`, `compartilhar_usuario_revogar.php`, `notificacoes.php`.
- CSS placeholder preenchido: `assets/dist/css/header_premium.css`.

## 11) Dicas de Segurança
- Use HTTPS com TLS atual (1.2+)
- Troque a senha padrão do MySQL e configure perfis de acesso mínimos
- Rotacione senhas SMTP (senha de app quando possível)
- Faça backup offsite periódico (não só no mesmo disco)

## 12) Problemas Comuns
- `.htaccess` sem efeito: verifique `AllowOverride All` no VirtualHost
- E-mail falhando: confirme `GED_SMTP_PASS` e compatibilidade `port`/`secure`
- Upload falhando: permissões de `public/storage/uploads` e limites do `php.ini`

