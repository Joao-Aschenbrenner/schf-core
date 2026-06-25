# Build do executavel desktop SCHF
# Requer: Node.js 20+, Rust (stable), Visual Studio C++ Build Tools

param(
    [string]$OutputDir = ".\release",
    [string]$Version = "0.1.0"
)

$ErrorActionPreference = "Stop"

Write-Host "=== SCHF â€” Build Desktop v$Version ===" -ForegroundColor Cyan

# Verificar prereqs
Write-Host "[1/5] Verificando prerequisitos..." -ForegroundColor Yellow

$nodeVersion = node --version 2>$null
if (-not $nodeVersion) {
    Write-Error "Node.js nao encontrado. Instale em https://nodejs.org/"
    exit 1
}
Write-Host "  Node.js: $nodeVersion" -ForegroundColor Green

$rustVersion = rustc --version 2>$null
if (-not $rustVersion) {
    Write-Error "Rust nao encontrado. Instale em https://rustup.rs/"
    exit 1
}
Write-Host "  Rust: $rustVersion" -ForegroundColor Green

$cargoVersion = cargo --version 2>$null
Write-Host "  Cargo: $cargoVersion" -ForegroundColor Green

# Instalar dependencias
Write-Host "[2/5] Instalando dependencias npm..." -ForegroundColor Yellow
Set-Location -LiteralPath "$PSScriptRoot\..\frontend"
npm install
if (-not $?) {
    Write-Error "Falha ao instalar dependencias npm"
    exit 1
}

# Gerar build do frontend
Write-Host "[3/5] Build do frontend (Vite)..." -ForegroundColor Yellow
npm run build
if (-not $?) {
    Write-Error "Falha no build do frontend"
    exit 1
}

# Build Tauri
Write-Host "[4/5] Build Tauri (Rust + instalador)..." -ForegroundColor Yellow
npm run tauri:build
if (-not $?) {
    Write-Error "Falha no build Tauri"
    exit 1
}

# Copiar artefatos
Write-Host "[5/5] Copiando artefatos..." -ForegroundColor Yellow

$bundleDir = "$PSScriptRoot\..\frontend\src-tauri\target\release\bundle"
if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$exe = Get-ChildItem -Path "$bundleDir\nsis\*.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($exe) {
    Copy-Item $exe.FullName -Destination $OutputDir -Force
    Write-Host "  NSIS: $($exe.Name) -> $OutputDir" -ForegroundColor Green
}

$msi = Get-ChildItem -Path "$bundleDir\msi\*.msi" -ErrorAction SilentlyContinue | Select-Object -First 1
if ($msi) {
    Copy-Item $msi.FullName -Destination $OutputDir -Force
    Write-Host "  MSI: $($msi.Name) -> $OutputDir" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== Build concluido com sucesso! ===" -ForegroundColor Cyan
Write-Host "Arquivos em: $OutputDir" -ForegroundColor White
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor White
Write-Host "  1. Teste o instalador em uma maquina limpa" -ForegroundColor White
Write-Host "  2. Verifique se o app abre e conecta ao backend" -ForegroundColor White
Write-Host "  3. Execute os smoke tests em docs/SMOKE_TESTS.md" -ForegroundColor White

