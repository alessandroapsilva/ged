# 📚 SCRIPTS DE AUTOMAÇÃO - GED

## Scripts Disponíveis

### 1. **backup_diario.ps1** (Windows)
Script completo de backup automático do sistema GED.

**O que faz**:
- ✅ Backup completo do banco de dados MySQL
- ✅ Backup de todos os arquivos (uploads/)
- ✅ Backup dos logs do sistema
- ✅ Compactação automática
- ✅ Limpeza de backups antigos (retenção configurável)
- ✅ Verificação de integridade
- ✅ Log detalhado de todas operações

**Como usar**:

```powershell
# Executar manualmente (teste)
cd C:\xampp\htdocs\ged\scripts
.\backup_diario.ps1

# Agendar no Task Scheduler (diariamente às 2h da manhã)
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-File C:\xampp\htdocs\ged\scripts\backup_diario.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Backup Diário" -Action $action -Trigger $trigger -Principal $principal -Description "Backup automático do sistema GED"
```

**Configurações** (editar no arquivo):
- `$BACKUP_DIR`: Diretório onde os backups serão salvos
- `$DB_USER` / `$DB_PASS`: Credenciais do MySQL
- `$RETENCAO_DIAS`: Quantos dias manter backups (padrão: 30)

---

### 2. **backup_diario.sh** (Linux)
Versão Linux do script de backup.

**Como usar**:

```bash
# Dar permissão de execução
chmod +x /var/www/ged/scripts/backup_diario.sh

# Executar manualmente (teste)
./backup_diario.sh

# Agendar no crontab (diariamente às 2h)
crontab -e
# Adicionar linha:
0 2 * * * /var/www/ged/scripts/backup_diario.sh >> /var/log/ged_backup.log 2>&1
```

**Configurações** (editar no arquivo):
- `BACKUP_DIR`: Diretório de backups
- `DB_USER` / `DB_PASS` / `DB_HOST`: Credenciais do MySQL
- `RETENCAO_DIAS`: Dias de retenção

---

### 3. **monitoramento.ps1** (Windows)
Script de monitoramento contínuo do sistema GED.

**O que monitora**:
- ✅ Endpoint de saúde (health.php)
- ✅ Tempo de resposta da aplicação
- ✅ Conexão com banco de dados
- ✅ Espaço em disco disponível
- ✅ Uso de CPU
- ✅ Uso de memória RAM
- ✅ Status dos serviços (MySQL, Apache)
- ✅ Permissões do diretório uploads/
- ✅ Alertas automáticos quando limites são ultrapassados

**Como usar**:

```powershell
# Executar manualmente (teste)
cd C:\xampp\htdocs\ged\scripts
.\monitoramento.ps1

# Agendar no Task Scheduler (a cada 5 minutos)
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-File C:\xampp\htdocs\ged\scripts\monitoramento.ps1"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration ([TimeSpan]::MaxValue)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Monitoramento" -Action $action -Trigger $trigger -Principal $principal -Description "Monitoramento contínuo do sistema GED"
```

**Configurações** (editar no arquivo):
- `$GED_URL`: URL da aplicação
- `$MAX_RESPONSE_TIME`: Tempo máximo de resposta (ms)
- `$MIN_DISK_SPACE_GB`: Espaço mínimo em disco (GB)
- `$MAX_CPU_PERCENT`: % máximo de CPU
- `$MAX_MEMORY_PERCENT`: % máximo de memória
- `$SEND_EMAIL_ALERTS`: Ativar alertas por e-mail (true/false)

**Alertas por e-mail** (opcional):
```powershell
# Configurar no script:
$SEND_EMAIL_ALERTS = $true
$SMTP_SERVER = "smtp.suaempresa.com.br"
$EMAIL_FROM = "monitor@suaempresa.com.br"
$EMAIL_TO = "admin@suaempresa.com.br"
```

---

## 🚀 Configuração Rápida (Windows)

### 1. Backup Automático

```powershell
# 1. Editar configurações
notepad C:\xampp\htdocs\ged\scripts\backup_diario.ps1

# 2. Criar diretório de backups
New-Item -ItemType Directory -Path "C:\Backups\GED" -Force

# 3. Testar script
cd C:\xampp\htdocs\ged\scripts
.\backup_diario.ps1

# 4. Agendar (copiar e colar no PowerShell como Admin)
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File C:\xampp\htdocs\ged\scripts\backup_diario.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Backup Diário" -Action $action -Trigger $trigger -Principal $principal -Description "Backup automático do sistema GED" -Force
```

### 2. Monitoramento Automático

```powershell
# 1. Editar configurações
notepad C:\xampp\htdocs\ged\scripts\monitoramento.ps1

# 2. Testar script
cd C:\xampp\htdocs\ged\scripts
.\monitoramento.ps1

# 3. Agendar (copiar e colar no PowerShell como Admin)
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File C:\xampp\htdocs\ged\scripts\monitoramento.ps1"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration ([TimeSpan]::MaxValue)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Monitoramento" -Action $action -Trigger $trigger -Principal $principal -Description "Monitoramento contínuo do sistema GED" -Force
```

---

## 🐧 Configuração Rápida (Linux)

### 1. Backup Automático

```bash
# 1. Editar configurações
nano /var/www/ged/scripts/backup_diario.sh

# 2. Dar permissão
chmod +x /var/www/ged/scripts/backup_diario.sh

# 3. Testar
/var/www/ged/scripts/backup_diario.sh

# 4. Agendar no crontab
crontab -e
# Adicionar:
0 2 * * * /var/www/ged/scripts/backup_diario.sh >> /var/log/ged_backup.log 2>&1
```

---

## 📊 Verificando os Logs

### Windows

```powershell
# Logs de backup
Get-Content C:\Backups\GED\logs\backup_*.log -Tail 50

# Logs de monitoramento
Get-Content C:\xampp\htdocs\ged\logs\monitor_alerts.log -Tail 50

# Ver últimos backups
Get-ChildItem C:\Backups\GED\database -Filter "*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 10
```

### Linux

```bash
# Logs de backup
tail -f /var/log/ged_backup.log

# Ver últimos backups
ls -lht /var/backups/ged/database/ | head -10
```

---

## 🔧 Troubleshooting

### Erro: "Execution Policy"

```powershell
# Permitir execução de scripts PowerShell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Erro: "mysqldump não encontrado"

```powershell
# Adicionar MySQL ao PATH
$env:PATH += ";C:\xampp\mysql\bin"
# ou editar o script e usar caminho completo:
& "C:\xampp\mysql\bin\mysqldump.exe" -u root ged > backup.sql
```

### Erro: "Acesso negado ao diretório de backup"

```powershell
# Criar diretório com permissões corretas
New-Item -ItemType Directory -Path "C:\Backups\GED" -Force
icacls "C:\Backups\GED" /grant Everyone:(OI)(CI)F /T
```

---

## 📞 Suporte

Para problemas com os scripts:
1. Verificar logs em `C:\xampp\htdocs\ged\logs\`
2. Executar script manualmente para ver erros
3. Verificar permissões de arquivos/pastas
4. Confirmar que serviços MySQL e Apache estão rodando

---

**Última atualização**: 7 de novembro de 2025
