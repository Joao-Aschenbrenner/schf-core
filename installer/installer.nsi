; SCHF Core - NSIS Installer v1.2.0
; Compile with: makensis installer.nsi
; Supports: /S (silent), /D=DIR (install dir), /UPDATE, /REPAIR

!include "MUI2.nsh"
!include "LogicLib.nsh"
!include "nsProcess.nsh"
!include "FileFunc.nsh"
!include "WordFunc.nsh"

Name "SCHF Core"
OutFile "SCHF-Setup.exe"
InstallDir "$PROGRAMFILES\SCHF Core"
InstallDirRegKey HKLM "Software\SCHF Core" "InstallDir"
RequestExecutionLevel admin
Icon "..\frontend\src-tauri\icons\icon.ico"

!define VERSION "1.2.0"
!define MUI_ABORTWARNING
!define MUI_ICON "..\frontend\src-tauri\icons\icon.ico"
!define MUI_UNICON "..\frontend\src-tauri\icons\icon.ico"

Var DockerRunning
Var PortsAvailable
Var PreviousInstall
Var PreviousVersion
Var IsUpdate
Var IsRepair

; --- Pages ---
!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_LICENSE "..\LICENSE"
!insertmacro MUI_PAGE_COMPONENTS
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH

!insertmacro MUI_UNPAGE_WELCOME
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_UNPAGE_FINISH

!insertmacro MUI_LANGUAGE "Portuguese"

; --- Macros ---
!macro IsPortOpen PORT
    System::Call 'ws2_32::socket(i 2, i 1, i 6)i.r0'
    StrCmp $0 0 0 +2
    System::Call 'ws2_32::bind(i r0, t "sockaddr_in" i 2 i 0 i ${PORT})i.r1'
    System::Call 'ws2_32::closesocket(i r0)'
    StrCmp $1 0 1 0
!macroend

; --- Functions ---
Function .onInit
    ; Parse command line arguments
    ${GetParameters} $0

    ${GetOptions} $0 "/S" $1
    IfErrors +2
    SetSilent silent

    ${GetOptions} $0 "/UPDATE" $1
    IfErrors +2
    StrCpy $IsUpdate 1

    ${GetOptions} $0 "/REPAIR" $1
    IfErrors +2
    StrCpy $IsRepair 1

    ; Check for previous installation
    ReadRegStr $PreviousInstall HKLM "Software\SCHF Core" "InstallDir"
    ReadRegStr $PreviousVersion HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "DisplayVersion"

    StrCmp $PreviousInstall "" NoPreviousInstall

    ; Previous installation found
    StrCpy $IsRepair 1
    StrCmp $IsUpdate 1 HandleUpdate
    StrCmp $IsRepair 1 HandleRepair

    MessageBox MB_YESNO|MB_ICONQUESTION \
        "Uma instalação anterior foi detectada em:$\n$PreviousInstall$\n$\nVersão: $PreviousVersion$\n$\nDeseja atualizar para a versão ${VERSION}?" \
        IDYES HandleUpdate
    Abort

    HandleUpdate:
        StrCpy $IsUpdate 1
        StrCpy $INSTDIR $PreviousInstall
        Goto DoneInit

    HandleRepair:
        StrCpy $IsRepair 1
        StrCpy $INSTDIR $PreviousInstall
        Goto DoneInit

    NoPreviousInstall:
        StrCpy $IsUpdate 0
        StrCpy $IsRepair 0

    DoneInit:
FunctionEnd

Function .onInstSuccess
    ; Check ports
    StrCpy $PortsAvailable 1

    ${For} $R0 9080 13306
        ${If} ${IsPortOpen} $R0
            StrCpy $PortsAvailable 0
            ${Break}
        ${EndIf}
    ${Next}

    ${If} $PortsAvailable == 0
        ${If} ${Silent}
            ; In silent mode, just log warning
            DetailPrint "AVISO: Portas 9080 e/ou 13306 podem estar em uso"
        ${Else}
            MessageBox MB_ICONWARNING "Portas 9080 e/ou 13306 estão em uso. Verifique antes de iniciar."
        ${EndIf}
    ${EndIf}

    ; Start Docker containers
    ${If} $IsUpdate == 0
        DetailPrint "Iniciando containers Docker..."
        nsExec::ExecToLog 'docker compose -f "$INSTDIR\docker-compose.yml" up -d'
    ${EndIf}
FunctionEnd

; --- Sections ---
Section "Core Files" SecCore
    SectionIn RO

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

    ; Write registry
    WriteRegStr HKLM "Software\SCHF Core" "InstallDir" $INSTDIR
    WriteRegStr HKLM "Software\SCHF Core" "Version" "${VERSION}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "DisplayName" "SCHF Core"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "UninstallString" "$INSTDIR\uninstall.exe"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "DisplayVersion" "${VERSION}"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "Publisher" "SCHF Team"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "InstallLocation" "$INSTDIR"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "NoModify" "1"
    WriteRegStr HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "NoRepair" "1"

    ; Estimate size
    ${GetSize} "$INSTDIR" "/S=0K" $0 $1 $2
    IntFmt $0 "0x%08X" $0
    WriteRegDWORD HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore" "EstimatedSize" "$0"

    ; Create uninstaller
    WriteUninstaller "$INSTDIR\uninstall.exe"
SectionEnd

Section "Shortcuts" SecShortcuts
    CreateDirectory "$SMPROGRAMS\SCHF Core"
    CreateShortCut "$SMPROGRAMS\SCHF Core\SCHF Core.lnk" "http://localhost:9080" "" "" "Abrir SCHF Core"
    CreateShortCut "$SMPROGRAMS\SCHF Core\Documentação.lnk" "$INSTDIR\docs" "" "" "Abrir Documentação"
    CreateShortCut "$SMPROGRAMS\SCHF Core\Desinstalar.lnk" "$INSTDIR\uninstall.exe"

    CreateShortCut "$DESKTOP\SCHF Core.lnk" "http://localhost:9080" "" "" "Abrir SCHF Core"
SectionEnd

Section "Configuration" SecConfig
    ${If} $IsUpdate == 0
        DetailPrint "Gerando arquivo de configuração..."
        nsExec::ExecToLog '"$INSTDIR\installer\install.ps1" -InstallPath "$INSTDIR" -Force'
    ${Else}
        DetailPrint "Mantendo configuração existente..."
    ${EndIf}
SectionEnd

Section "Docker Check" SecDocker
    ReadRegStr $0 HKLM "Software\Docker Inc.\Docker" "InstallPath"
    StrCmp $0 "" DockerNotFound DockerFound

    DockerFound:
        DetailPrint "Docker Desktop encontrado"
        StrCpy $DockerRunning 1
        Goto DockerCheckDone

    DockerNotFound:
        ${If} ${Silent}
            DetailPrint "AVISO: Docker Desktop não encontrado"
        ${Else}
            MessageBox MB_ICONWARNING "Docker Desktop não encontrado.$\n$\nInstale o Docker Desktop antes de usar o SCHF Core."
        ${EndIf}
        StrCpy $DockerRunning 0

    DockerCheckDone:
SectionEnd

Section "Uninstall"
    ; Stop containers
    DetailPrint "Parando containers Docker..."
    nsExec::ExecToLog 'docker compose -f "$INSTDIR\docker-compose.yml" down -v'

    ; Remove application files
    RMDir /r "$INSTDIR"

    ; Remove registry
    DeleteRegKey HKLM "Software\SCHF Core"
    DeleteRegKey HKLM "Software\Microsoft\Windows\CurrentVersion\Uninstall\SCHFCore"

    ; Remove shortcuts
    Delete "$SMPROGRAMS\SCHF Core\SCHF Core.lnk"
    Delete "$SMPROGRAMS\SCHF Core\Documentação.lnk"
    Delete "$SMPROGRAMS\SCHF Core\Desinstalar.lnk"
    RMDir "$SMPROGRAMS\SCHF Core"

    Delete "$DESKTOP\SCHF Core.lnk"
SectionEnd

; --- Component Descriptions ---
!insertmacro MUI_FUNCTION_DESCRIPTION_BEGIN
    !insertmacro MUI_DESCRIPTION_TEXT ${SecCore} "Arquivos principais do SCHF Core"
    !insertmacro MUI_DESCRIPTION_TEXT ${SecShortcuts} "Atalhos no Menu Iniciar e Área de Trabalho"
    !insertmacro MUI_DESCRIPTION_TEXT ${SecConfig} "Configuração inicial (.env, Docker)"
    !insertmacro MUI_DESCRIPTION_TEXT ${SecDocker} "Verificação do Docker Desktop"
!insertmacro MUI_FUNCTION_DESCRIPTION_END