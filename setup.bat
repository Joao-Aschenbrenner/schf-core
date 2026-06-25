@echo off
title SCHF - Setup Rapido
color 0B
chcp 65001 >nul

echo ================================================
echo    SCHF
echo    Assistente Rapido de Configuracao
echo ================================================
echo.
echo Este script configura o Docker e a rede para
echo o modo servidor do SCHF.
echo.
echo Recomendado: Execute como Administrador.
echo.
pause

:: Check if running as admin
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo [!] AVISO: Nao esta executando como Administrador.
    echo     Algumas configuracoes podem falhar.
    echo.
    pause
)

:: Step 1: Check Docker
cls
echo [1/4] Verificando Docker...
where docker >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] Docker nao encontrado.
    echo.
    echo Para instalar o Docker Desktop, acesse:
    echo https://www.docker.com/products/docker-desktop/
    echo.
    echo Apos instalar, execute este script novamente.
    pause
    exit /b 1
)
echo [OK] Docker instalado.

docker info >nul 2>&1
if %errorlevel% neq 0 (
    echo [!] Docker Desktop nao esta rodando.
    echo     Inicie o Docker Desktop e aguarde...
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    echo     Aguardando 30 segundos...
    timeout /t 30 /nobreak >nul
)

:: Step 2: Start containers
cls
echo [2/4] Iniciando containers...
set DOCKER_DIR=%APPDATA%\SCHF\docker

if not exist "%DOCKER_DIR%\docker-compose.yml" (
    echo [!] Configuracao Docker nao encontrada em %DOCKER_DIR%
    echo     Execute o instalador primeiro.
    pause
    exit /b 1
)

docker-compose -f "%DOCKER_DIR%\docker-compose.yml" up -d
if %errorlevel% equ 0 (
    echo [OK] Containers iniciados com sucesso!
) else (
    echo [FAIL] Erro ao iniciar containers.
    pause
    exit /b 1
)

:: Step 3: Firewall
cls
echo [3/4] Configurando firewall...
netsh advfirewall firewall add rule name="schf-backend" dir=in action=allow protocol=TCP localport=9080 >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Porta 9080 liberada no firewall.
) else (
    echo [!] Nao foi possivel configurar o firewall (necessario Administrador).
)

:: Step 4: Test connection
cls
echo [4/4] Testando conexao...
echo.
echo Aguardando backend iniciar...
timeout /t 10 /nobreak >nul

powershell -Command "try { $r = Invoke-WebRequest -Uri 'http://localhost:9080/api/health' -UseBasicParsing -TimeoutSec 5; if ($r.StatusCode -eq 200) { Write-Host '[OK] Backend online!' -ForegroundColor Green } else { Write-Host '[FAIL] Backend respondeu com codigo '$r.StatusCode -ForegroundColor Red } } catch { Write-Host '[FAIL] Backend nao respondeu' -ForegroundColor Red }"

echo.
echo ================================================
echo    Configuracao concluida!
echo.
echo    API rodando em: http://localhost:9080/api
echo    Acesso local:   http://localhost:9080
echo    Acesso rede:    http://SEU_IP:9080
echo ================================================
echo.
echo Para clientes na rede, copie o executavel
echo e execute - ele encontrara o servidor
echo automaticamente via descoberta LAN.
echo.
pause


