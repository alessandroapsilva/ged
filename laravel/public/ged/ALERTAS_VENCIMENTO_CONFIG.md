# Configuração de Alertas Automáticos de Vencimento

## Script criado
- `public/cron_alertas_vencimento.php`

## O que faz
1. Verifica documentos a vencer nos próximos 7 dias
2. Verifica documentos vencidos nos últimos 7 dias
3. Envia notificações internas (tabela `workflow_notificacoes`)
4. Envia e-mails para os proprietários dos documentos (se configurado)
5. Gera log em `logs/alertas_vencimento.log`

## Configuração

### No Windows (Task Scheduler)
1. Abra o "Agendador de Tarefas" (Task Scheduler)
2. Criar Tarefa Básica > Nome: "GED Alertas Vencimento"
3. Gatilho: Diariamente às 08:00
4. Ação: Iniciar programa
   - Programa: `C:\xampp\php\php.exe`
   - Argumentos: `C:\xampp\htdocs\ged\public\cron_alertas_vencimento.php`
   - Iniciar em: `C:\xampp\htdocs\ged`

### No Linux (cron)
```bash
# Editar crontab
crontab -e

# Adicionar linha (executa diariamente às 8h)
0 8 * * * /usr/bin/php /var/www/html/ged/public/cron_alertas_vencimento.php >> /var/www/html/ged/logs/cron.log 2>&1
```

### Teste manual
```bash
# Windows
cd c:\xampp\htdocs\ged
c:\xampp\php\php.exe public\cron_alertas_vencimento.php

# Linux
cd /var/www/html/ged
php public/cron_alertas_vencimento.php
```

## Personalização

### Alterar prazo de alerta (padrão: 7 dias)
Edite `cron_alertas_vencimento.php` e ajuste:
- Linha ~30: `DATE_ADD(CURDATE(), INTERVAL 7 DAY)` → altere 7 para o prazo desejado
- Linha ~43: `DATE_SUB(CURDATE(), INTERVAL 7 DAY)` → idem

### Desabilitar e-mails (apenas notificações internas)
Comente ou remova os blocos:
```php
if (function_exists('enviar_email_sistema')) {
    // ...
}
```

### Alterar destinatários
Por padrão, envia para o proprietário (`usuario_id`). Para enviar para um grupo:
```php
// Buscar admins ou responsáveis
$stmt_admins = $pdo->query("SELECT email FROM usuarios WHERE role = 'admin'");
$admins = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);
foreach ($admins as $email_admin) {
    enviar_email_sistema($email_admin, ...);
}
```

## Log
- Arquivo: `logs/alertas_vencimento.log`
- Rotação: configure logrotate ou delete manualmente a cada 30 dias

## Monitoramento
Verifique se o cron está executando:
```bash
# Windows: Event Viewer > Task Scheduler
# Linux:
grep -i cron /var/log/syslog
tail -f logs/alertas_vencimento.log
```

---
**Criado em**: 29/10/2025
