; SCHF Core - NSIS Installer
; Compile with: makensis installer.nsi

!include "MUI2.nsh"
!include "LogicLib.nsh"
!include "nsProcess.nsh"

Name "SCHF Core"
OutFile "SCHF-Core-Setup.exe"
InstallDir "$PROGRAMFILES\SCHF Core"
InstallDirRegKey HKLM "Software\SCHF Core" "InstallDir"
RequestExecutionLevel admin
Icon "..\frontend\src-tauri\icons\icon.ico"

!define MUI_ABORTWARNING
!define MUI_ICON "..\frontend\src-tauri\icons\icon.ico"
!define MUI_UNICON "..\frontend\src-tauri\icons\icon.ico"

!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_WELCOME
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_UNPAGE_FINISH

!insertmacro MUI_LANGUAGE "Portuguese"

Var DockerRunning
Var PortsAvailable
Var PreviousInstall

Section "Main" SecMain
    SetOutPath $INSTDIR

    ; Copy application files
    File /r "..\backend\*"
    File /r "..\frontend\*"
    File /r "..\infra\*"
    File /r "..\scripts\*"
    File /r "..\docs\*"
    File "..\docker-compose.yml"
    File "..\Dockerfile.k6"
    File "..\.gitleaks.toml"
    File "..\.hadolint.yaml"
    File "..\CHANGELOG.md"
    File "..\README.md"

    ; Create necessary directories
    CreateDirectory "$INSTDIR\backend\storage\app\backups"
    CreateDirectory "$INSTDIR\backend\storage\logs"
    CreateDirectory "$INSTDIR\backend\storage\framework\cache"
    CreateDirectory "$INSTDIR\backend\storage\framework\sessions"
    CreateDirectory "$INSTDIR\backend\storage\framework\views"
    CreateDirectory "$INSTDIR\backend\bootstrap\cache"
    CreateDirectory "$INSTDIR\mysql\data"
    CreateDirectory "$INSTDIR\redis\data"
    CreateDirectory "$INSTDIR\backups"

    ; Write registry for uninstall
    WriteRegStr HKLM "Software\SCHF Core" "InstallDir" $INSTDIR
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "DisplayName" "SCHF Core"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "UninstallString" "$INSTDIR\uninstall.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "DisplayVersion" "1.0.0"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "Publisher" "SCHF Team"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "NoModify" "1"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "NoRepair" "1"

    ; Create uninstaller
    WriteUninstaller "$INSTDIR\uninstall.exe"

    ; Create shortcuts
    CreateDirectory "$SMPROGRAMS\SCHF Core"
    CreateShortCut "$SMPROGRAMS\SCHF Core\SCHF Core.lnk" "$INSTDIR\backend\artisan" "" "" "Iniciar SCHF Core"
    CreateShortCut "$SMPROGRAMS\SCHF Core\Desinstalar.lnk" "$INSTDIR\uninstall.exe"

    ; Generate .env file
    ExecWait '"$INSTDIR\installer\install.ps1" -InstallPath "$INSTDIR" -Force'
SectionEnd

Section "Docker" SecDocker
    ; Check if Docker is installed
    ReadRegStr $0 HKLM "Software\Docker Inc.\Docker" "InstallPath"
    StrCmp $0 "" DockerNotFound DockerFound
    DockerFound:
    StrCpy $DockerRunning 1
    Goto DockerCheckDone
    DockerNotFound:
    MessageBox MB_ICONWARNING "Docker Desktop não encontrado. Instale o Docker Desktop antes de usar o SCHF Core."
    StrCpy $DockerRunning 0
    DockerCheckDone:
SectionEnd

Function .onInit
    ; Check for previous installation
    ReadRegStr $PreviousInstall HKLM "Software\SCHF Core" "InstallDir"
    StrCmp $PreviousInstall "" NoPreviousInstall
    MessageBox MB_YESNO|MB_ICONQUESTION "Uma instalação anterior foi detectada em:$PreviousInstall$\n$\nDeseja sobrescrever?" IDYES NoPreviousInstall
    Abort
    NoPreviousInstall:
FunctionEnd

Function .onInstSuccess
    ; Check ports
    ${For} $R0 9080 13306
        ${If} ${IsPortOpen} $R0
            StrCpy $PortsAvailable 0
            ${Break}
        ${Else}
            StrCpy $PortsAvailable 1
        ${EndIf}
    ${Next}
    ${If} $PortsAvailable == 0
        MessageBox MB_ICONWARNING "Portas 9080 e/ou 13306 estão em uso. Verifique antes de iniciar."
    ${EndIf}
FunctionEnd

Section "Uninstall"
    ; Stop containers if running
    ExecWait 'docker compose -f "$INSTDIR\docker-compose.yml" down -v'

    ; Remove files
    RMDir /r "$INSTDIR"

    ; Remove registry
    DeleteRegKey HKLM "Software\SCHF Core"
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore"

    ; Remove shortcuts
    Delete "$SMPROGRAMS\SCHF Core\SCHF Core.lnk"
    Delete "$SMPROGRAMS\SCHF Core\Desinstalar.lnk"
    RMDir "$SMPROGRAMS\SCHF Core"
SectionEnd

; Helper macro to check if port is open
!macro IsPortOpen PORT
    System::Call 'ws2_32::socket(i 2, i 1, i 6)i.r0'
    StrCmp $0 0 0 +2
    System::Call 'ws2_32::bind(i r0, t "sockaddr_in" i 2 i 0 i ${PORT})i.r1'
    System::Call 'ws2_32::closesocket(i r0)'
    StrCmp $1 0 1 0
!macroend