# ========================================
# SCRIPT DE MONITORAMENTO - GED
# ========================================
# Descrição: Monitora saúde do sistema e envia alertas
# Uso: Agendar no Task Scheduler para rodar a cada 5 minutos
# Autor: Sistema GED
# Data: 2025-11-07
# ========================================

# Configurações
$GED_URL = "http://localhost/ged"
$HEALTH_ENDPOINT = "$GED_URL/public/health.php"
$LOG_DIR = "C:\xampp\htdocs\ged\logs"
$ALERT_LOG = "$LOG_DIR\monitor_alerts.log"

# Limites
$MAX_RESPONSE_TIME = 2000  # ms
$MIN_DISK_SPACE_GB = 10
$MAX_CPU_PERCENT = 80
$MAX_MEMORY_PERCENT = 85

# E-mail (configurar se quiser alertas por e-mail)
$SEND_EMAIL_ALERTS = $false
$SMTP_SERVER = "smtp.empresa.com.br"
$EMAIL_FROM = "monitor@empresa.com.br"
$EMAIL_TO = "admin@empresa.com.br"

# ========================================
# FUNÇÕES
# ========================================

function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    Add-Content -Path $ALERT_LOG -Value $logMessage
    
    # Colorir output
    switch ($Level) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        default { Write-Host $logMessage }
    }
}

function Send-Alert {
    param([string]$Subject, [string]$Body)
    
    Write-Log "ALERTA: $Subject" "ERROR"
    
    if ($SEND_EMAIL_ALERTS) {
        try {
            $emailParams = @{
                From = $EMAIL_FROM
                To = $EMAIL_TO
                Subject = "[GED ALERT] $Subject"
                Body = $Body
                SmtpServer = $SMTP_SERVER
                Port = 587
                UseSsl = $true
            }
            Send-MailMessage @emailParams
            Write-Log "E-mail de alerta enviado" "INFO"
        } catch {
            Write-Log "Erro ao enviar e-mail: $_" "ERROR"
        }
    }
}

function Test-HealthEndpoint {
    try {
        $stopwatch = [System.Diagnostics.Stopwatch]::StartNew()
        $response = Invoke-WebRequest -Uri $HEALTH_ENDPOINT -TimeoutSec 10 -UseBasicParsing
        $stopwatch.Stop()
        
        $responseTime = $stopwatch.ElapsedMilliseconds
        
        if ($response.StatusCode -eq 200) {
            $health = $response.Content | ConvertFrom-Json
            
            # Verificar response time
            if ($responseTime -gt $MAX_RESPONSE_TIME) {
                Send-Alert "Response Time Alto" "Tempo de resposta: $responseTime ms (limite: $MAX_RESPONSE_TIME ms)"
            } else {
                Write-Log "Health check OK - Response time: $responseTime ms" "SUCCESS"
            }
            
            # Verificar conexão com banco
            if ($health.database -eq "disconnected") {
                Send-Alert "Banco de Dados Offline" "Não foi possível conectar ao banco de dados MySQL"
            }
            
            return $true
        } else {
            Send-Alert "Endpoint Health Retornou Erro" "Status Code: $($response.StatusCode)"
            return $false
        }
    } catch {
        Send-Alert "Endpoint Health Inacessível" "Erro: $_"
        return $false
    }
}

function Test-DiskSpace {
    $drive = Get-PSDrive -Name C
    $freeSpaceGB = [math]::Round($drive.Free / 1GB, 2)
    
    Write-Log "Espaço livre em disco: $freeSpaceGB GB" "INFO"
    
    if ($freeSpaceGB -lt $MIN_DISK_SPACE_GB) {
        Send-Alert "Espaço em Disco Baixo" "Apenas $freeSpaceGB GB disponíveis (mínimo: $MIN_DISK_SPACE_GB GB)"
        return $false
    }
    
    return $true
}

function Test-CPUUsage {
    $cpu = Get-WmiObject Win32_Processor | Measure-Object -Property LoadPercentage -Average
    $cpuPercent = [math]::Round($cpu.Average, 2)
    
    Write-Log "Uso de CPU: $cpuPercent%" "INFO"
    
    if ($cpuPercent -gt $MAX_CPU_PERCENT) {
        Send-Alert "Uso de CPU Elevado" "CPU em $cpuPercent% (limite: $MAX_CPU_PERCENT%)"
        return $false
    }
    
    return $true
}

function Test-MemoryUsage {
    $os = Get-WmiObject Win32_OperatingSystem
    $totalMemory = $os.TotalVisibleMemorySize
    $freeMemory = $os.FreePhysicalMemory
    $usedPercent = [math]::Round((($totalMemory - $freeMemory) / $totalMemory) * 100, 2)
    
    Write-Log "Uso de memória: $usedPercent%" "INFO"
    
    if ($usedPercent -gt $MAX_MEMORY_PERCENT) {
        Send-Alert "Uso de Memória Elevado" "Memória em $usedPercent% (limite: $MAX_MEMORY_PERCENT%)"
        return $false
    }
    
    return $true
}

function Test-MySQLService {
    $service = Get-Service -Name "MySQL" -ErrorAction SilentlyContinue
    
    if ($null -eq $service) {
        # Tentar MySQL80 ou MariaDB
        $service = Get-Service -Name "MySQL80" -ErrorAction SilentlyContinue
        if ($null -eq $service) {
            $service = Get-Service -Name "MariaDB" -ErrorAction SilentlyContinue
        }
    }
    
    if ($null -eq $service) {
        Write-Log "Serviço MySQL não encontrado" "WARNING"
        return $true  # Não alertar se não encontrar (pode ser Linux)
    }
    
    if ($service.Status -ne "Running") {
        Send-Alert "Serviço MySQL Parado" "O serviço MySQL está com status: $($service.Status)"
        return $false
    }
    
    Write-Log "Serviço MySQL rodando normalmente" "SUCCESS"
    return $true
}

function Test-ApacheService {
    $service = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
    
    if ($null -eq $service) {
        Write-Log "Serviço Apache não encontrado" "WARNING"
        return $true
    }
    
    if ($service.Status -ne "Running") {
        Send-Alert "Serviço Apache Parado" "O serviço Apache está com status: $($service.Status)"
        return $false
    }
    
    Write-Log "Serviço Apache rodando normalmente" "SUCCESS"
    return $true
}

function Test-UploadDirectory {
    $uploadDir = "C:\xampp\htdocs\ged\public\uploads"
    
    if (-not (Test-Path $uploadDir)) {
        Send-Alert "Diretório Uploads Não Encontrado" "Caminho: $uploadDir"
        return $false
    }
    
    # Verificar permissões de escrita
    try {
        $testFile = "$uploadDir\.write_test"
        "test" | Out-File -FilePath $testFile -ErrorAction Stop
        Remove-Item $testFile -ErrorAction SilentlyContinue
        Write-Log "Permissões de escrita OK no diretório uploads" "SUCCESS"
        return $true
    } catch {
        Send-Alert "Sem Permissão de Escrita em Uploads" "Erro: $_"
        return $false
    }
}

# ========================================
# EXECUÇÃO DOS TESTES
# ========================================

Write-Host "`n=========================================" -ForegroundColor Cyan
Write-Host "  MONITORAMENTO GED - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Garantir que log dir existe
if (-not (Test-Path $LOG_DIR)) {
    New-Item -ItemType Directory -Path $LOG_DIR | Out-Null
}

$allTestsPassed = $true

# 1. Health Endpoint
Write-Host "[1/7] Testando endpoint de saúde..." -ForegroundColor White
if (-not (Test-HealthEndpoint)) { $allTestsPassed = $false }

# 2. Espaço em disco
Write-Host "`n[2/7] Verificando espaço em disco..." -ForegroundColor White
if (-not (Test-DiskSpace)) { $allTestsPassed = $false }

# 3. CPU
Write-Host "`n[3/7] Verificando uso de CPU..." -ForegroundColor White
if (-not (Test-CPUUsage)) { $allTestsPassed = $false }

# 4. Memória
Write-Host "`n[4/7] Verificando uso de memória..." -ForegroundColor White
if (-not (Test-MemoryUsage)) { $allTestsPassed = $false }

# 5. MySQL
Write-Host "`n[5/7] Verificando serviço MySQL..." -ForegroundColor White
if (-not (Test-MySQLService)) { $allTestsPassed = $false }

# 6. Apache
Write-Host "`n[6/7] Verificando serviço Apache..." -ForegroundColor White
if (-not (Test-ApacheService)) { $allTestsPassed = $false }

# 7. Diretório Uploads
Write-Host "`n[7/7] Verificando diretório de uploads..." -ForegroundColor White
if (-not (Test-UploadDirectory)) { $allTestsPassed = $false }

# ========================================
# RESULTADO FINAL
# ========================================

Write-Host "`n=========================================" -ForegroundColor Cyan

if ($allTestsPassed) {
    Write-Host "  ✓ TODOS OS TESTES PASSARAM" -ForegroundColor Green
    Write-Log "Monitoramento concluído - Sistema saudável" "SUCCESS"
} else {
    Write-Host "  ✗ ALGUNS TESTES FALHARAM - Verifique os logs" -ForegroundColor Red
    Write-Log "Monitoramento concluído - Sistema com problemas" "ERROR"
}

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Limpar logs antigos (> 30 dias)
Get-ChildItem $LOG_DIR -Filter "*.log" | Where-Object {
    $_.LastWriteTime -lt (Get-Date).AddDays(-30)
} | Remove-Item -Force
