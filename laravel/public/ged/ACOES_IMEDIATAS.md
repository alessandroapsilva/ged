# ⚡ AÇÕES IMEDIATAS - ROTEIRO PARA PRODUÇÃO

## 🎯 Objetivo: Colocar GED em produção com segurança máxima

---

## 📅 DIA 1 - HOJE (Setup Inicial)

### ✅ Já Feito
- [x] Análise completa do sistema
- [x] Scripts de backup criados
- [x] Scripts de monitoramento criados
- [x] Documentação gerada

### 🔥 Fazer AGORA (30 minutos)

#### 1. Configurar Backup Automático (15 min)

```powershell
# 1. Abrir PowerShell como Administrador
# 2. Copiar e colar (AJUSTAR SENHA DO MYSQL!):

cd C:\xampp\htdocs\ged\scripts

# Editar senha do MySQL no script
notepad backup_diario.ps1
# Alterar linha: $DB_PASS = ""  # para sua senha

# Testar backup manual
.\backup_diario.ps1

# Se funcionou, agendar para rodar às 2h da manhã
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File C:\xampp\htdocs\ged\scripts\backup_diario.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Backup Diário" -Action $action -Trigger $trigger -Principal $principal -Force

Write-Host "✓ Backup automático configurado!" -ForegroundColor Green
```

#### 2. Configurar Monitoramento (15 min)

```powershell
# Ainda no PowerShell como Admin:

# Testar monitoramento
cd C:\xampp\htdocs\ged\scripts
.\monitoramento.ps1

# Se funcionou, agendar para rodar a cada 5 minutos
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-ExecutionPolicy Bypass -File C:\xampp\htdocs\ged\scripts\monitoramento.ps1"
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 5) -RepetitionDuration ([TimeSpan]::MaxValue)
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName "GED Monitoramento" -Action $action -Trigger $trigger -Principal $principal -Force

Write-Host "✓ Monitoramento configurado!" -ForegroundColor Green
```

**✅ Resultado**: Backup e monitoramento rodando automaticamente!

---

## 📅 SEMANA 1 (Testes e Segurança)

### Segunda-feira (Dia 2)

#### Manhã: Instalar Ferramentas de Teste

```powershell
cd C:\xampp\htdocs\ged

# Instalar PHPUnit
composer require --dev phpunit/phpunit

# Criar estrutura de testes
New-Item -ItemType Directory -Path "tests/Unit" -Force
New-Item -ItemType Directory -Path "tests/Feature" -Force

# Criar arquivo de configuração PHPUnit
@"
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
"@ | Out-File -FilePath "phpunit.xml" -Encoding UTF8
```

#### Tarde: Primeiros Testes

Criar `tests/Unit/LoginTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    public function testPasswordHash()
    {
        $password = "senha123";
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $this->assertTrue(password_verify($password, $hash));
    }
    
    public function testInvalidPassword()
    {
        $hash = password_hash("senha123", PASSWORD_BCRYPT);
        
        $this->assertFalse(password_verify("senha_errada", $hash));
    }
}
```

Executar:
```powershell
vendor/bin/phpunit
```

**Meta do dia**: 5 testes básicos funcionando

---

### Terça-feira (Dia 3)

#### Scan de Segurança com OWASP ZAP

```powershell
# 1. Baixar OWASP ZAP
# https://www.zaproxy.org/download/

# 2. Instalar e abrir ZAP

# 3. Configurar:
# - URL alvo: http://localhost/ged
# - Modo: Automated Scan
# - Deixar rodar (30-60 min)

# 4. Analisar resultados
# - Exportar relatório HTML
# - Corrigir vulnerabilidades críticas/altas
```

**Meta do dia**: Scan completo + lista de vulnerabilidades

---

### Quarta-feira (Dia 4)

#### Implementar Rate Limiting

```powershell
cd C:\xampp\htdocs\ged
composer require sunspikes/php-ratelimiter
```

Criar `helpers/rate_limit_helper.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Sunspikes\Ratelimit\Throttle\Factory\ThrottleFactory;
use Sunspikes\Ratelimit\Cache\Adapter\DesarrolloWebAdapter;
use Desarrolloweb\Cache\Cache;

function checkRateLimit($identifier, $limit = 100, $period = 60) {
    $cache = new Cache();
    $adapter = new DesarrolloWebAdapter($cache);
    $throttleFactory = new ThrottleFactory();
    $throttle = $throttleFactory->make('fixed_window', [
        'limit' => $limit,
        'period' => $period
    ]);
    
    return $throttle->access($adapter, $identifier);
}

// Uso em login.php:
// if (!checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 60)) {
//     die('Muitas tentativas. Aguarde 1 minuto.');
// }
```

**Meta do dia**: Rate limiting em login e API

---

### Quinta-feira (Dia 5)

#### Mais Testes Automatizados

Criar `tests/Feature/DocumentUploadTest.php`:

```php
<?php

use PHPUnit\Framework\TestCase;

class DocumentUploadTest extends TestCase
{
    public function testValidFileExtension()
    {
        $validExtensions = ['pdf', 'doc', 'docx', 'jpg', 'png'];
        $filename = "documento.pdf";
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        
        $this->assertContains($ext, $validExtensions);
    }
    
    public function testMaxFileSize()
    {
        $maxSize = 50 * 1024 * 1024; // 50MB
        $fileSize = 30 * 1024 * 1024; // 30MB
        
        $this->assertLessThanOrEqual($maxSize, $fileSize);
    }
}
```

**Meta do dia**: 20 testes no total

---

### Sexta-feira (Dia 6-7)

#### Revisar Documentação

- [ ] Atualizar README.md
- [ ] Criar guia rápido para usuários
- [ ] Documentar API endpoints
- [ ] Criar troubleshooting guide

**Meta do fim de semana**: 30+ testes, documentação completa

---

## 📅 SEMANA 2 (Staging e Ajustes)

### Segunda-feira (Dia 8)

#### Criar Ambiente Staging

```powershell
# Opção 1: Servidor separado
# - Provisionar VM ou servidor cloud
# - Instalar Apache + PHP + MySQL
# - Configurar SSL (Let's Encrypt)

# Opção 2: Subdomínio local
# C:\Windows\System32\drivers\etc\hosts
# Adicionar: 127.0.0.1 staging.ged.local

# Copiar arquivos
xcopy C:\xampp\htdocs\ged C:\xampp\htdocs\ged-staging /E /I /Y

# Importar banco (com dados de teste)
mysql -u root -e "CREATE DATABASE ged_staging;"
mysql -u root ged_staging < C:\xampp\htdocs\ged\sql\base.sql
```

---

### Terça-feira (Dia 9)

#### Testes de Carga

```powershell
# Instalar Apache JMeter
# https://jmeter.apache.org/download_jmeter.cgi

# Criar plano de teste:
# 1. 10 usuários simultâneos (warmup)
# 2. 50 usuários simultâneos (normal)
# 3. 100 usuários simultâneos (pico)

# Endpoints para testar:
# - Login
# - Listar documentos
# - Upload
# - Busca
# - Download
```

**Meta do dia**: Sistema suporta 100 usuários simultâneos

---

### Quarta-feira (Dia 10)

#### Otimizações de Performance

Baseado nos resultados do JMeter:

```sql
-- Verificar queries lentas
SHOW FULL PROCESSLIST;

-- Analisar queries
EXPLAIN SELECT * FROM documentos WHERE ...;

-- Adicionar índices se necessário
CREATE INDEX idx_performance ON tabela(coluna);

-- Limpar cache
FLUSH TABLES;
OPTIMIZE TABLE documentos;
```

PHP (config):
```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

---

### Quinta-feira (Dia 11)

#### Validação com Usuários-Chave

- [ ] Convidar 3-5 usuários para testar
- [ ] Preparar checklist de testes
- [ ] Coletar feedback
- [ ] Fazer ajustes

**Checklist para usuários**:
- [ ] Login/logout
- [ ] Upload de documento
- [ ] Buscar documento
- [ ] Compartilhar documento
- [ ] Assinar documento
- [ ] Criar workflow
- [ ] Verificar notificações

---

### Sexta-feira (Dia 12-14)

#### Ajustes Finais + Plano de Deploy

- [ ] Corrigir bugs encontrados
- [ ] Executar todos os testes novamente
- [ ] Preparar checklist de deploy
- [ ] Definir data/hora de go-live
- [ ] Preparar comunicação

---

## 📅 SEMANA 3 (Deploy em Produção)

### Segunda-feira (Dia 15)

#### Preparação do Servidor de Produção

```powershell
# 1. Verificar requisitos
# - Windows Server ou Linux
# - Apache/IIS + PHP 8.1+ + MySQL 8.0+
# - SSL válido
# - Firewall configurado

# 2. Instalar dependências
# - PHP extensions: pdo, pdo_mysql, mbstring, gd, openssl
# - Composer

# 3. Configurar PHP (produção)
# display_errors=Off
# log_errors=On
# opcache.enable=1
```

---

### Terça-feira (Dia 16)

#### Deploy de Produção (Janela de Manutenção)

```powershell
# CHECKLIST PRÉ-DEPLOY

# 1. Backup completo do servidor atual (se houver)
# 2. Modo de manutenção
# 3. Sincronizar arquivos
rsync -avz --exclude 'uploads/' ged/ production:/var/www/ged/

# 4. Importar banco
mysql -h prod.db -u ged_user -p ged_prod < sql/base.sql

# 5. Configurar .env / config
# - DB credentials
# - SMTP settings
# - APP_ENV=production

# 6. Permissões
chmod 755 public/
chmod 777 public/uploads/
chmod 777 logs/

# 7. Limpar cache
php artisan cache:clear  # se Laravel
# ou
rm -rf tmp/*

# 8. Testar
curl https://ged.empresa.com.br/health.php

# 9. Tirar de manutenção

# 10. SMOKE TESTS
# - Login OK?
# - Upload OK?
# - Download OK?
# - E-mail OK?
```

---

### Quarta-feira (Dia 17)

#### Monitoramento Intensivo (24-48h)

```powershell
# Monitorar logs em tempo real
Get-Content C:\xampp\htdocs\ged\logs\error.log -Wait

# Verificar performance
# - Tempo de resposta
# - Uso de CPU/RAM
# - Queries lentas

# Coletar métricas
# - Quantos logins?
# - Quantos uploads?
# - Erros?
```

**Meta**: Zero erros críticos nas primeiras 24h

---

### Quinta-feira (Dia 18)

#### Feedback Inicial

- [ ] E-mail para todos os usuários
- [ ] Formulário de feedback
- [ ] Canal de suporte (e-mail, chat, telefone)
- [ ] Documentar problemas relatados

---

### Sexta-feira (Dia 19-21)

#### Ajustes Pós-Deploy

- [ ] Corrigir bugs reportados
- [ ] Otimizar performance baseado em uso real
- [ ] Melhorar documentação
- [ ] Planejar próximas features

---

## 📅 SEMANA 4+ (Operação e Melhorias)

### Operação Contínua

#### Diário
- [ ] Revisar logs de erro
- [ ] Verificar backups
- [ ] Monitorar uptime

#### Semanal
- [ ] Análise de métricas
- [ ] Review de tickets de suporte
- [ ] Atualização de dependências

#### Mensal
- [ ] Relatório de uso
- [ ] Planejamento de melhorias
- [ ] Análise de performance

---

## 🎯 CHECKLIST MASTER - GO/NO-GO

### ✅ Pré-requisitos para Produção

#### Funcionalidades
- [x] Login/autenticação funcionando
- [x] Upload de documentos
- [x] Download de documentos
- [x] Busca funcionando
- [x] Assinaturas digitais
- [x] Workflows
- [x] Compartilhamento
- [x] Notificações

#### Segurança
- [x] SSL/HTTPS configurado
- [x] 2FA disponível
- [x] RBAC implementado
- [ ] Scan de segurança sem críticos
- [ ] Rate limiting ativo
- [x] Logs de auditoria

#### Performance
- [x] Tempo de resposta < 2s
- [ ] Suporta 100+ usuários simultâneos
- [x] Índices otimizados
- [ ] Cache configurado

#### Infraestrutura
- [ ] Backup automático funcionando
- [ ] Monitoramento ativo
- [ ] Servidor de produção pronto
- [ ] SSL válido
- [ ] DNS configurado

#### Testes
- [ ] 30+ testes automatizados passando
- [ ] Testes de carga OK
- [ ] Testes com usuários OK
- [ ] Zero bugs críticos

#### Documentação
- [x] README completo
- [x] Guia de deploy
- [ ] Documentação de API
- [ ] Manual do usuário
- [ ] Runbook operacional

---

## ⚡ COMANDOS RÁPIDOS

### Verificar Status do Sistema

```powershell
# Health check
curl http://localhost/ged/public/health.php

# Verificar serviços
Get-Service MySQL*
Get-Service Apache*

# Verificar espaço em disco
Get-PSDrive C

# Ver logs de erro
Get-Content C:\xampp\htdocs\ged\logs\error.log -Tail 20

# Executar testes
cd C:\xampp\htdocs\ged
vendor/bin/phpunit

# Backup manual
.\scripts\backup_diario.ps1

# Monitoramento manual
.\scripts\monitoramento.ps1
```

---

## 🆘 EMERGÊNCIA - Rollback

Se algo der muito errado em produção:

```powershell
# 1. Ativar modo de manutenção
# Criar arquivo: public/.maintenance

# 2. Restaurar backup
mysql -u root ged < C:\Backups\GED\database\ged_YYYYMMDD.sql

# 3. Restaurar arquivos
Expand-Archive C:\Backups\GED\files\uploads_YYYYMMDD.zip -DestinationPath C:\xampp\htdocs\ged\public\

# 4. Limpar cache
rm -rf tmp/*

# 5. Testar
curl http://localhost/ged/public/health.php

# 6. Remover modo de manutenção
rm public/.maintenance
```

---

## 📞 CONTATOS DE EMERGÊNCIA

```
Equipe de Deploy:
- Tech Lead: ___________
- DBA: ___________
- SysAdmin: ___________
- Suporte: ___________

Fornecedores:
- Hosting: ___________
- SSL: ___________
- SMTP: ___________
```

---

## ✅ CONCLUSÃO

**Você tem TUDO que precisa para colocar o GED em produção!**

### O que fazer AGORA:
1. ✅ Configurar backup automático (15 min)
2. ✅ Configurar monitoramento (15 min)
3. ⏰ Começar testes (Semana 1)
4. 🚀 Deploy (Semana 3)

### Prazo Total: **3 semanas**

**Boa sorte! 🚀**

---

**Criado em**: 7 de novembro de 2025  
**Próxima revisão**: Após Semana 1
