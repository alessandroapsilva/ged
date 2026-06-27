# Deploy em Produção - GED

Este guia resume os passos para publicar o GED em produção com segurança e performance, inspirado no padrão eDok.

## 1. Planejamento
- Domínio/URL final definido (ex.: https://ged.suaempresa.com.br/)
- Certificado SSL válido (Let's Encrypt/ACME, IIS, ou provedor)
- Acesso ao servidor (Windows com Apache/IIS ou Linux com Apache/Nginx)
- Banco de dados MySQL/MariaDB com usuário dedicado e senha forte
- SMTP corporativo para envio de e-mails de compartilhamento/assinatura

## 2. Código e dependências
- Copie apenas a pasta `ged/` (subpasta `public/` será raiz pública do site)
- PHP ≥ 7.4 com extensões: pdo, pdo_mysql, mbstring, json, openssl, gd
- Composer (opcional) para otimizar autoloader: `composer dump-autoload -o`

## 3. Variáveis de ambiente
Defina as variáveis (via painel do servidor, vhost, `setx` no Windows PowerShell, etc.):
- `APP_ENV=production`
- `GED_ENV=production` (alternativa)
- `GED_VERSION` (opcional) e `GED_REVISION` (hash de build)
- `GED_COOKIE_SECURE=1` (se HTTPS)
- `GED_SHARE_WATERMARK=1` (ou 0, conforme política)

## 4. Webserver
### Apache (recomendado)
- Aponte o DocumentRoot para `.../ged/public`
- Habilite `mod_rewrite`, `mod_headers`, `mod_deflate`, `mod_expires`
- Use o `.htaccess` incluso (segurança básica, cache e compressão)

Exemplo de VirtualHost:
```
<VirtualHost *:80>
  ServerName ged.suaempresa.com.br
  Redirect / https://ged.suaempresa.com.br/
</VirtualHost>

<VirtualHost *:443>
  ServerName ged.suaempresa.com.br
  DocumentRoot "C:/inetpub/ged/public"  # ou /var/www/ged/public

  SSLEngine on
  SSLCertificateFile    "/caminho/cert.crt"
  SSLCertificateKeyFile "/caminho/cert.key"
  SSLCertificateChainFile "/caminho/chain.crt"

  <Directory "C:/inetpub/ged/public">
    AllowOverride All
    Require all granted
  </Directory>
</VirtualHost>
```

### IIS/Nginx
- Aponte a raiz pública para `ged/public`
- Replique cabeçalhos de segurança e cache do `.htaccess`

## 5. PHP e performance
- `display_errors=Off`, `log_errors=On`
- Ative `opcache` (PHP):
  - `opcache.enable=1`, `opcache.enable_cli=0`, `opcache.validate_timestamps=1` (ou 0 com deploys atômicos)
  - `opcache.memory_consumption=128`, `opcache.max_accelerated_files=10000`
- Tamanho de upload conforme necessidade: `upload_max_filesize`, `post_max_size`
- `max_execution_time`/`max_input_vars` adequados a lotes maiores

## 6. Banco de dados
- Importe o schema inicial (se necessário) e rode migrações/versões: `create_database.php`, `create_version.php`
- Crie usuário com permissões mínimas e troque senha padrão
- Faça backup agendado (mysqldump) e retenção conforme política

## 7. E-mail (SMTP)
- Configure credenciais SMTP na área administrativa ou arquivo de config do GED (se aplicável)
- Teste envio usando as telas de Compartilhar e Requisitar Assinatura

## 8. Tarefas agendadas (cron)
- Agende o script `cron_notifications.php` (ou equivalente de sua instalação) para processar notificações/vencimentos
  - Windows Task Scheduler:
    - Ação: `php.exe C:\xampp\htdocs\ged\cron_notifications.php`
    - Frequência: a cada 5 min (ou conforme carga)
  - Linux cron:
    - `*/5 * * * * /usr/bin/php /var/www/ged/cron_notifications.php > /dev/null 2>&1`

## 9. Saúde e monitoria
- Endpoint de saúde: `https://ged.seu-dominio/health.php` (retorna JSON com DB, extensões e permissões)
- Acompanhe logs de erros do PHP e do servidor web

## 10. Tema e branding
- Tema cinza (estilo eDok) ativado por padrão; o usuário pode alternar pela lâmpada
- Ajuste `config/branding.json` para `name`, `logo` e cores, se desejar

## 11. Segurança adicional
- Troque senhas iniciais, habilite MFA (se disponível)
- Restrinja acesso a IPs (se necessário)
- Faça pentest leve (SQLi/XSS básicos) e varredura de portas

## 12. Pós-deploy
- Testes de fluxo: login, criar pasta, upload, visualizar, compartilhar, requisitar assinatura, verificar QR e validação
- Verifique permissões de pastas (`uploads`, `tmp`, `thumbs`)
- Valide backups e alertas de monitoria

---
Qualquer ajuste específico do seu ambiente (Windows/Linux, Apache/IIS/Nginx) posso detalhar e entregar um arquivo de configuração pronto.
