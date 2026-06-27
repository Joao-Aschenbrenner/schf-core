<#
.SYNOPSIS
    SCHF Core - Update Script
.DESCRIPTION
    Updates an existing SCHF Core installation to a new version
.PARAMETER Version
    Target version to update to
.PARAMETER InstallPath
    Path to existing installation
.PARAMETER SkipBackup
    Skip pre-update backup
.EXAMPLE
    .\update.ps1 -Version "1.2.0" -InstallPath "C:\SCHF"
#>

param(
    [Parameter(Mandatory=$true)]
    [string]$Version,

    [string]$InstallPath = "C:\SCHF",
    [switch]$SkipBackup,
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

function Get-CurrentVersion {
    $envPath = Join-Path $InstallPath "backend\.env"
    if (Test-Path $envPath) {
        $envContent = Get-Content $envPath -Raw
        $versionLine = $envContent -split "`n" | Where-Object { $_ -like "APP_VERSION=*" }
        if ($versionLine) {
            return ($versionLine -replace "APP_VERSION=", "").Trim()
        }
    }
    return "0.0.0"
}

function Backup-Database {
    if ($SkipBackup) {
        Write-WarningMsg "Backup pulado pelo usuário"
        return
    }

    Write-Header "Criando backup pré-atualização"
    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
    $backupDir = Join-Path $InstallPath "backups"
    $backupFile = Join-Path $backupDir "pre_update_$timestamp.sql"

    if (-not (Test-Path $backupDir)) {
        New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
    }

    try {
        $dbPassword = ($envContent -split "`n" | Where-Object { $_ -like "DB_PASSWORD=*" }) -replace "DB_PASSWORD=", ""
        docker exec schf-mysql mysqldump -u root -p"$dbPassword" schf > $backupFile 2>$null
        Write-Success "Backup criado: $backupFile"
    } catch {
        Write-WarningMsg "Backup falhou: $_"
        if (-not $Force) {
            $response = Read-Host "Continuar sem backup? (s/N)"
            if ($response -ne 's') { exit 1 }
        }
    }
}

function Pull-NewImages {
    Write-Header "Baixando novas imagens Docker"
    try {
        $composePath = Join-Path $InstallPath "docker-compose.yml"
        $compose = Get-Content $composePath -Raw

        $images = @("backend", "frontend", "nginx", "queue")
        foreach ($img in $images) {
            $compose = $compose -replace "(image: .*schf-{$img}:)[^\s]+", "`$1$Version"
        }

        Set-Content -Path $composePath -Value $compose -Encoding UTF8
        Write-Success "docker-compose.yml atualizado"

        docker compose -f $composePath pull
        Write-Success "Imagens baixadas"
    } catch {
        Write-ErrorMsg "Falha ao baixar imagens: $_"
        exit 1
    }
}

function Stop-Containers {
    Write-Header "Parando containers"
    try {
        docker compose -f (Join-Path $InstallPath "docker-compose.yml") down
        Write-Success "Containers parados"
    } catch {
        Write-WarningMsg "Erro ao parar containers: $_"
    }
}

function Start-Containers {
    Write-Header "Iniciando containers"
    try {
        docker compose -f (Join-Path $InstallPath "docker-compose.yml") up -d
        Write-Success "Containers iniciados"
    } catch {
        Write-ErrorMsg "Falha ao iniciar containers: $_"
        exit 1
    }
}

function Wait-ForHealth {
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
        return $true
    } catch {
        Write-ErrorMsg "Falha nas migrations: $_"
        return $false
    }
}

function Clear-Cache {
    Write-Header "Limpando cache"
    try {
        docker exec schf-backend php artisan config:clear
        docker exec schf-backend php artisan route:clear
        docker exec schf-backend php artisan view:clear
        docker exec schf-backend php artisan cache:clear
        Write-Success "Cache limpo"
    } catch {
        Write-WarningMsg "Erro ao limpar cache: $_"
    }
}

function Show-Summary {
    param([string]$OldVersion, [string]$NewVersion, [double]$Duration)

    Write-Header "Atualização Concluída"
    Write-Host "`nSCHF Core atualizado com sucesso!" -ForegroundColor Green
    Write-Host "`nVersão:" -ForegroundColor Cyan
    Write-Host "  Anterior:  $OldVersion"
    Write-Host "  Atual:     $NewVersion"
    Write-Host "`nDuração: $Duration segundos" -ForegroundColor Cyan
    Write-Host "`nAcesso: http://localhost:9080" -ForegroundColor Cyan
}

# Main
Write-Host "SCHF Core Update" -ForegroundColor Cyan
Write-Host "================" -ForegroundColor Cyan

$startTime = Get-Date

if (-not (Test-Path (Join-Path $InstallPath "backend\artisan"))) {
    Write-ErrorMsg "Instalação não encontrada em $InstallPath"
    exit 1
}

$currentVersion = Get-CurrentVersion
Write-Host "Versão atual: $currentVersion" -ForegroundColor Yellow
Write-Host "Versão alvo:  $Version" -ForegroundColor Yellow

$envContent = Get-Content (Join-Path $InstallPath "backend\.env") -Raw

Backup-Database
Stop-Containers
Pull-NewImages
Start-Containers

if (Wait-ForHealth) {
    if (Run-Migrations) {
        Clear-Cache
        $duration = ((Get-Date) - $startTime).TotalSeconds
        Show-Summary -OldVersion $currentVersion -NewVersion $Version -Duration $duration
    } else {
        Write-ErrorMsg "Falha nas migrations"
        exit 1
    }
} else {
    Write-ErrorMsg "Serviços não ficaram saudáveis"
    exit 1
}