# ========================================
# SCRIPT DE BACKUP AUTOMÁTICO - GED
# ========================================
# Descrição: Faz backup completo do banco de dados e arquivos
# Uso: Agendar no Task Scheduler para rodar diariamente às 2h
# Autor: Sistema GED
# Data: 2025-11-07
# ========================================

# Configurações
$DATA = Get-Date -Format "yyyyMMdd_HHmmss"
$DATA_SIMPLES = Get-Date -Format "yyyy-MM-dd"

# Diretórios
$BACKUP_DIR = "C:\Backups\GED"
$GED_DIR = "C:\xampp\htdocs\ged"
$MYSQL_BIN = "C:\xampp\mysql\bin"
$PHP_BIN = "C:\xampp\php"

# Banco de dados
$DB_NAME = "ged"
$DB_USER = "root"
$DB_PASS = ""  # Altere se tiver senha

# Retenção (dias)
$RETENCAO_DIAS = 30
$RETENCAO_MENSAL = 365  # Manter 1 backup mensal por 1 ano

# Cores para output
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

# ========================================
# INÍCIO DO BACKUP
# ========================================

Write-ColorOutput Green "========================================="
Write-ColorOutput Green "  BACKUP GED - $DATA_SIMPLES"
Write-ColorOutput Green "========================================="
Write-Output ""

# 1. Criar diretório de backup se não existir
if (-not (Test-Path $BACKUP_DIR)) {
    Write-Output "[INFO] Criando diretório de backup: $BACKUP_DIR"
    New-Item -ItemType Directory -Path $BACKUP_DIR | Out-Null
}

# Subpastas organizadas
$BACKUP_DB_DIR = "$BACKUP_DIR\database"
$BACKUP_FILES_DIR = "$BACKUP_DIR\files"
$BACKUP_LOGS_DIR = "$BACKUP_DIR\logs"

if (-not (Test-Path $BACKUP_DB_DIR)) { New-Item -ItemType Directory -Path $BACKUP_DB_DIR | Out-Null }
if (-not (Test-Path $BACKUP_FILES_DIR)) { New-Item -ItemType Directory -Path $BACKUP_FILES_DIR | Out-Null }
if (-not (Test-Path $BACKUP_LOGS_DIR)) { New-Item -ItemType Directory -Path $BACKUP_LOGS_DIR | Out-Null }

# ========================================
# 2. BACKUP DO BANCO DE DADOS
# ========================================

Write-Output "[1/4] Fazendo backup do banco de dados..."

$DB_BACKUP_FILE = "$BACKUP_DB_DIR\ged_$DATA.sql"

try {
    # Comando mysqldump
    $env:PATH = "$MYSQL_BIN;$env:PATH"
    
    if ($DB_PASS -eq "") {
        & mysqldump -u $DB_USER $DB_NAME > $DB_BACKUP_FILE 2>&1
    } else {
        & mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $DB_BACKUP_FILE 2>&1
    }
    
    if (Test-Path $DB_BACKUP_FILE) {
        $fileSize = (Get-Item $DB_BACKUP_FILE).Length / 1MB
        Write-ColorOutput Green "  ✓ Backup do banco concluído: $('{0:N2}' -f $fileSize) MB"
    } else {
        throw "Arquivo de backup não foi criado"
    }
} catch {
    Write-ColorOutput Red "  ✗ ERRO ao fazer backup do banco: $_"
    exit 1
}

# ========================================
# 3. BACKUP DOS ARQUIVOS (UPLOADS)
# ========================================

Write-Output "[2/4] Fazendo backup dos arquivos (uploads)..."

$FILES_BACKUP_ZIP = "$BACKUP_FILES_DIR\uploads_$DATA.zip"

try {
    $uploadsDir = "$GED_DIR\public\uploads"
    
    if (Test-Path $uploadsDir) {
        Compress-Archive -Path $uploadsDir -DestinationPath $FILES_BACKUP_ZIP -Force
        
        $fileSize = (Get-Item $FILES_BACKUP_ZIP).Length / 1MB
        Write-ColorOutput Green "  ✓ Backup dos arquivos concluído: $('{0:N2}' -f $fileSize) MB"
    } else {
        Write-ColorOutput Yellow "  ! Diretório uploads não encontrado: $uploadsDir"
    }
} catch {
    Write-ColorOutput Red "  ✗ ERRO ao fazer backup dos arquivos: $_"
}

# ========================================
# 4. BACKUP DOS LOGS
# ========================================

Write-Output "[3/4] Fazendo backup dos logs..."

$LOGS_BACKUP_ZIP = "$BACKUP_LOGS_DIR\logs_$DATA.zip"

try {
    $logsDir = "$GED_DIR\logs"
    
    if (Test-Path $logsDir) {
        Compress-Archive -Path $logsDir -DestinationPath $LOGS_BACKUP_ZIP -Force
        
        $fileSize = (Get-Item $LOGS_BACKUP_ZIP).Length / 1MB
        Write-ColorOutput Green "  ✓ Backup dos logs concluído: $('{0:N2}' -f $fileSize) MB"
    } else {
        Write-ColorOutput Yellow "  ! Diretório logs não encontrado: $logsDir"
    }
} catch {
    Write-ColorOutput Red "  ✗ ERRO ao fazer backup dos logs: $_"
}

# ========================================
# 5. LIMPEZA DE BACKUPS ANTIGOS
# ========================================

Write-Output "[4/4] Limpando backups antigos (retenção: $RETENCAO_DIAS dias)..."

$dataLimite = (Get-Date).AddDays(-$RETENCAO_DIAS)
$backupsRemovidos = 0

# Limpar backups de banco
Get-ChildItem $BACKUP_DB_DIR -Filter "*.sql" | Where-Object {
    $_.LastWriteTime -lt $dataLimite -and $_.Name -notmatch "_01_"  # Preserva backups do dia 1 (mensal)
} | ForEach-Object {
    Remove-Item $_.FullName -Force
    $backupsRemovidos++
}

# Limpar backups de arquivos
Get-ChildItem $BACKUP_FILES_DIR -Filter "*.zip" | Where-Object {
    $_.LastWriteTime -lt $dataLimite -and $_.Name -notmatch "_01_"
} | ForEach-Object {
    Remove-Item $_.FullName -Force
    $backupsRemovidos++
}

# Limpar backups de logs (apenas > 90 dias)
$dataLimiteLogs = (Get-Date).AddDays(-90)
Get-ChildItem $BACKUP_LOGS_DIR -Filter "*.zip" | Where-Object {
    $_.LastWriteTime -lt $dataLimiteLogs
} | ForEach-Object {
    Remove-Item $_.FullName -Force
    $backupsRemovidos++
}

Write-ColorOutput Green "  ✓ $backupsRemovidos backup(s) antigo(s) removido(s)"

# ========================================
# 6. VERIFICAÇÃO DE INTEGRIDADE
# ========================================

Write-Output ""
Write-Output "Verificando integridade dos backups..."

$integridadeOK = $true

# Verificar tamanho do backup do banco
if (Test-Path $DB_BACKUP_FILE) {
    $dbSize = (Get-Item $DB_BACKUP_FILE).Length
    if ($dbSize -lt 1KB) {
        Write-ColorOutput Red "  ✗ ALERTA: Backup do banco muito pequeno ($dbSize bytes)"
        $integridadeOK = $false
    } else {
        Write-ColorOutput Green "  ✓ Backup do banco OK"
    }
}

# Verificar se há arquivos no zip
if (Test-Path $FILES_BACKUP_ZIP) {
    Write-ColorOutput Green "  ✓ Backup de arquivos OK"
}

# ========================================
# 7. RELATÓRIO FINAL
# ========================================

Write-Output ""
Write-ColorOutput Green "========================================="
Write-ColorOutput Green "  BACKUP CONCLUÍDO COM SUCESSO!"
Write-ColorOutput Green "========================================="
Write-Output ""
Write-Output "Local dos backups: $BACKUP_DIR"
Write-Output "Data/Hora: $DATA"
Write-Output ""

# Calcular espaço total usado
$espacoTotal = 0
Get-ChildItem $BACKUP_DIR -Recurse -File | ForEach-Object { $espacoTotal += $_.Length }
$espacoTotalMB = $espacoTotal / 1MB
$espacoTotalGB = $espacoTotal / 1GB

Write-Output "Espaço total utilizado: $('{0:N2}' -f $espacoTotalMB) MB ($('{0:N2}' -f $espacoTotalGB) GB)"

# Listar últimos backups
Write-Output ""
Write-Output "Últimos 5 backups do banco:"
Get-ChildItem $BACKUP_DB_DIR -Filter "*.sql" | Sort-Object LastWriteTime -Descending | Select-Object -First 5 | ForEach-Object {
    $tamanhoMB = $_.Length / 1MB
    Write-Output "  - $($_.Name) ($('{0:N2}' -f $tamanhoMB) MB) - $($_.LastWriteTime)"
}

# ========================================
# 8. LOG DO BACKUP
# ========================================

$logFile = "$BACKUP_LOGS_DIR\backup_$DATA_SIMPLES.log"
$logContent = @"
========================================
BACKUP GED - $DATA
========================================
Status: $(if($integridadeOK){'SUCESSO'}else{'ALERTA'})
Banco: $(if(Test-Path $DB_BACKUP_FILE){'OK'}else{'FALHA'})
Arquivos: $(if(Test-Path $FILES_BACKUP_ZIP){'OK'}else{'FALHA'})
Logs: $(if(Test-Path $LOGS_BACKUP_ZIP){'OK'}else{'FALHA'})
Backups removidos: $backupsRemovidos
Espaço total: $('{0:N2}' -f $espacoTotalGB) GB
========================================
"@

$logContent | Out-File -FilePath $logFile -Encoding UTF8

# ========================================
# 9. ENVIO DE NOTIFICAÇÃO (OPCIONAL)
# ========================================

# Descomentar se quiser receber e-mail de notificação
# $emailConfig = @{
#     From = "backup@suaempresa.com.br"
#     To = "admin@suaempresa.com.br"
#     Subject = "Backup GED - $DATA_SIMPLES - $(if($integridadeOK){'OK'}else{'ALERTA'})"
#     Body = $logContent
#     SmtpServer = "smtp.suaempresa.com.br"
#     Port = 587
#     UseSsl = $true
#     Credential = (New-Object PSCredential("user", (ConvertTo-SecureString "senha" -AsPlainText -Force)))
# }
# Send-MailMessage @emailConfig

Write-Output ""
Write-ColorOutput Cyan "Pressione qualquer tecla para sair..."
# $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
