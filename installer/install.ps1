<#
.SYNOPSIS
    SCHF Core - Instalador PowerShell
.DESCRIPTION
    Instala o SCHF (Sistema de Controle Hospitalar e Financeiro) via Docker Compose
.NOTES
    Requer: Docker Desktop, PowerShell 5.1+
#>

param(
    [string]$InstallPath = "C:\SCHF",
    [string]$DockerComposeFile = "docker-compose.yml",
    [switch]$SkipDockerCheck,
    [switch]$SkipBuild,
    [switch]$Force
)

$ErrorActionPreference = "Stop"

function Write-Header {
    param([string]$Message)
    Write-Host "`n=== $Message ===" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Write-ErrorMsg {
    param([string]$Message)
    Write-Host "[ERRO] $Message" -ForegroundColor Red
}

function Write-WarningMsg {
    param([string]$Message)
    Write-Host "[AVISO] $Message" -ForegroundColor Yellow
}

function Check-Docker {
    Write-Header "Verificando Docker"
    try {
        $version = docker --version
        Write-Success "Docker encontrado: $version"
        $composeVersion = docker compose version
        Write-Success "Docker Compose encontrado: $composeVersion"
        return $true
    } catch {
        Write-ErrorMsg "Docker não encontrado. Instale o Docker Desktop primeiro."
        return $false
    }
}

function Check-Ports {
    Write-Header "Verificando portas necessárias"
    $ports = @(9080, 13306, 6379)
    $conflicts = @()
    foreach ($port in $ports) {
        $listener = [System.Net.NetworkInformation.IPGlobalProperties]::GetIPGlobalProperties().GetActiveTcpListeners()
        if ($listener.Port -contains $port) {
            $conflicts += $port
        }
    }
    if ($conflicts.Count -gt 0) {
        Write-WarningMsg "Portas em uso: $($conflicts -join ', ')"
        if (-not $Force) {
            $response = Read-Host "Continuar mesmo assim? (s/N)"
            if ($response -ne 's') { exit 1 }
        }
    } else {
        Write-Success "Portas livres"
    }
}

function Create-Directories {
    Write-Header "Criando diretórios"
    $dirs = @(
        "backend/storage/app/backups",
        "backend/storage/logs",
        "backend/storage/framework/cache",
        "backend/storage/framework/sessions",
        "backend/storage/framework/views",
        "backend/bootstrap/cache",
        "mysql/data",
        "redis/data",
        "backups"
    )
    foreach ($dir in $dirs) {
        $fullPath = Join-Path $InstallPath $dir
        if (-not (Test-Path $fullPath)) {
            New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
            Write-Success "Criado: $dir"
        }
    }
}

function Generate-EnvFile {
    Write-Header "Gerando arquivo .env"
    $envPath = Join-Path $InstallPath "backend\.env"
    if (Test-Path $envPath -and -not $Force) {
        Write-WarningMsg "Arquivo .env já existe. Use -Force para sobrescrever."
        return
    }

    $appKey = "base64:" + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
    $dbPassword = -join ((1..32) | ForEach-Object { ("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*")[(Get-Random -Maximum 62)] })
    $redisPassword = -join ((1..32) | ForEach-Object { ("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*")[(Get-Random -Maximum 62)] })
    $mysqlRootPassword = -join ((1..32) | ForEach-Object { ("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*")[(Get-Random -Maximum 62)] })

    $envContent = @"
APP_NAME=SCHF
APP_ENV=production
APP_KEY=$appKey
APP_DEBUG=false
APP_URL=http://localhost:9080

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=schf
DB_USERNAME=schf
DB_PASSWORD=$dbPassword

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost

REDIS_HOST=redis
REDIS_PASSWORD=$redisPassword
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:9080

FRONTEND_URL=http://localhost:9080

ACTIVITYLOG_ENABLED=true

FEATURE_LEGACY_MODULE=false
FEATURE_MULTI_ORGANIZATION=true
FEATURE_SETUP_WIZARD=true
FEATURE_AUTO_UPDATES=false

APP_UPDATE_REPO=ORG/schf-core

MYSQL_ROOT_PASSWORD=$mysqlRootPassword
"@

    Set-Content -Path $envPath -Value $envContent -Encoding UTF8
    Write-Success "Arquivo .env gerado com senhas aleatórias"
}

function Build-Containers {
    if ($SkipBuild) { return }
    Write-Header "Construindo containers Docker"
    try {
        docker compose -f (Join-Path $InstallPath $DockerComposeFile) build --no-cache
        Write-Success "Containers construídos"
    } catch {
        Write-ErrorMsg "Falha ao construir containers: $_"
        exit 1
    }
}

function Start-Containers {
    Write-Header "Iniciando containers"
    try {
        docker compose -f (Join-Path $InstallPath $DockerComposeFile) up -d
        Write-Success "Containers iniciados"
    } catch {
        Write-ErrorMsg "Falha ao iniciar containers: $_"
        exit 1
    }
}

function Wait-For-Health {
    Write-Header "Aguardando saúde dos serviços"
    $maxAttempts = 60
    $attempt = 0
    while ($attempt -lt $maxAttempts) {
        try {
            $response = Invoke-RestMethod -Uri "http://localhost:9080/api/health" -TimeoutSec 5 -ErrorAction Stop
            if ($response.status -eq 'ok') {
                Write-Success "Backend saudável"
                return $true
            }
        } catch {}
        $attempt++
        Write-Host "Aguardando... ($attempt/$maxAttempts)" -NoNewline
        Start-Sleep -Seconds 2
        Write-Host "`r" -NoNewline
    }
    Write-ErrorMsg "Timeout aguardando saúde dos serviços"
    return $false
}

function Run-Migrations {
    Write-Header "Executando migrations"
    try {
        docker exec schf-backend php artisan migrate --force
        Write-Success "Migrations executadas"
    } catch {
        Write-ErrorMsg "Falha nas migrations: $_"
        return $false
    }
    return $true
}

function Run-Seeders {
    Write-Header "Executando seeders"
    try {
        docker exec schf-backend php artisan db:seed --force
        Write-Success "Seeders executados"
    } catch {
        Write-ErrorMsg "Falha nos seeders: $_"
        return $false
    }
    return $true
}

function Show-Summary {
    Write-Header "Instalação Concluída"
    Write-Host "`nSCHF Core instalado com sucesso!" -ForegroundColor Green
    Write-Host "`nAcesso:" -ForegroundColor Cyan
    Write-Host "  Frontend:  http://localhost:9080"
    Write-Host "  API:       http://localhost:9080/api"
    Write-Host "  Health:    http://localhost:9080/api/health"
    Write-Host "`nPrimeiro acesso:" -ForegroundColor Cyan
    Write-Host "  1. Acesse http://localhost:9080"
    Write-Host "  2. Será redirecionado para /setup-wizard"
    Write-Host "  3. Configure a instituição e o administrador master"
    Write-Host "`nCredenciais do banco (salve em local seguro):" -ForegroundColor Yellow
    $envContent = Get-Content (Join-Path $InstallPath "backend\.env") -Raw
    $dbPass = ($envContent -split "`n" | Where-Object { $_ -like "DB_PASSWORD=*" }) -replace "DB_PASSWORD=", ""
    Write-Host "  Database: schf"
    Write-Host "  User:     schf"
    Write-Host "  Password: $dbPass"
    Write-Host "`nPróximos passos:" -ForegroundColor Cyan
    Write-Host "  - Configure backup automático em /admin/backups"
    Write-Host "  - Verifique atualizações em /admin/updates"
    Write-Host "  - Consulte documentação em docs/"
}

# Main
Write-Host "SCHF Core Installer" -ForegroundColor Cyan
Write-Host "=====================" -ForegroundColor Cyan

if (-not $SkipDockerCheck) {
    if (-not (Check-Docker)) { exit 1 }
}

Check-Ports

if (-not (Test-Path (Join-Path $PSScriptRoot "..\backend\artisan"))) {
    Write-ErrorMsg "Execute este script a partir da raiz do projeto SCHF Core"
    exit 1
}

$InstallPath = Resolve-Path $InstallPath
Write-Host "Diretório de instalação: $InstallPath"

Create-Directories
Generate-EnvFile
Build-Containers
Start-Containers

if (Wait-For-Health) {
    if (Run-Migrations -and Run-Seeders) {
        Show-Summary
    } else {
        Write-ErrorMsg "Falha na configuração do banco de dados"
        exit 1
    }
} else {
    Write-ErrorMsg "Serviços não ficaram saudáveis a tempo"
    exit 1
}