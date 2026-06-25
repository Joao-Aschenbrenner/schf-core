# Desenvolvimento desktop SCHF
# Inicia Tauri em modo dev com hot reload

$ErrorActionPreference = "Stop"

Write-Host "=== SCHF â€” Dev Desktop ===" -ForegroundColor Cyan

# Verificar se backend esta rodando
Write-Host "Verificando backend..." -ForegroundColor Yellow

try {
    $response = Invoke-WebRequest -Uri "http://localhost:8080/api/health" -TimeoutSec 3 -ErrorAction Stop
    Write-Host "Backend: Online" -ForegroundColor Green
} catch {
    Write-Host "Backend: Offline" -ForegroundColor Red
    Write-Host ""
    Write-Host "Deseja iniciar o backend via Docker? (S/n)" -ForegroundColor Yellow
    $answer = Read-Host
    if ($answer -ne 'n') {
        Set-Location -LiteralPath "$PSScriptRoot\.."
        docker-compose up -d
        Write-Host "Aguardando backend inicializar..." -ForegroundColor Yellow
        Start-Sleep -Seconds 10
    }
}

# Iniciar Tauri dev
Write-Host "Iniciando Tauri dev..." -ForegroundColor Yellow
Set-Location -LiteralPath "$PSScriptRoot\..\frontend"
npm run tauri:dev

