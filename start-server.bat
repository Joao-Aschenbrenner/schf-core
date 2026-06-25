@echo off
title SCHF - Iniciar Servidor
color 0B

echo ================================================
echo    SCHF
echo    Iniciar Servidor
echo ================================================
echo.

:: Check Docker
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERRO] Docker nao encontrado.
    echo Instale o Docker Desktop: https://www.docker.com/products/docker-desktop/
    pause
    exit /b 1
)

:: Check if Docker is running
docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo Iniciando Docker Desktop...
    start "" "Docker Desktop.exe"
    echo Aguardando 20 segundos...
    timeout /t 20 /nobreak >nul
)

:: Start containers
set DOCKER_DIR=%APPDATA%\SCHF\docker
if not exist "%DOCKER_DIR%\docker-compose.yml" (
    echo [ERRO] Configuracao Docker nao encontrada.
    echo Execute setup.bat primeiro.
    pause
    exit /b 1
)

echo Iniciando containers...
docker-compose -f "%DOCKER_DIR%\docker-compose.yml" up -d
if %errorlevel% equ 0 (
    echo.
    echo [OK] Servidor rodando em:
    echo     http://localhost:9080/api
    echo.
    echo Para testar: curl http://localhost:9080/api/health
) else (
    echo [ERRO] Falha ao iniciar containers.
)

echo.
echo Pressione qualquer tecla para abrir o app...
pause >nul

:: Launch the app
start "" "%LOCALAPPDATA%\SCHF\schf-core.exe"


