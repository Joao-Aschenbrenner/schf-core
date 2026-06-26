# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-06-26

### Added

- Multi-organization architecture with Organization model, OrganizationService, OrganizationController, OrganizationPolicy
- Setup Wizard with 3-step flow (create organization → create admin → complete)
- Domain Events: OrganizationCreated, UserCreated, UserAssignedRole, OrganizationActivated
- Feature Flags system with 6 configurable flags (legacy_module, multi_organization, setup_wizard, auto_updates, desktop_app, developer_mode)
- FeatureFlagService singleton and `feature()` global helper
- ExpenseCategory model with organization relationship and default seeding

### Changed

- SetupWizardController now dispatches events on organization/admin creation
- OrganizationController now dispatches events on activate/deactivate
- AppServiceProvider registers FeatureFlagService as singleton
- composer.json autoload includes helpers.php

### Security

- 0 secrets/leaks maintained via gitleaks
- All sensitive Santa Casa data moved to legacy-data/ (local only)

### Infrastructure

- NSIS installer with port checking and registry management
- PowerShell install script with health checks
- Docker Compose with proper health dependencies

## [0.2.0] - 2026-06-13

### Added
- Tauri v2 desktop app integration
- Windows installer (NSIS + MSI)
- Configurable API URL (local, network, VPS)
- Backend health check screen
- Persistent session with expiry (12h)
- Zustand stores for config and auth state
- Connection status indicator in sidebar
- Settings/connection page
- Build scripts (PowerShell)
- Smoke test documentation
- Build documentation

### Changed
- API service uses Zustand config store (no more hardcoded URL)
- Auth service uses Zustand auth store (secure session management)
- Login page shows backend status and error messages
- Dashboard layout includes logout button and connection status
- Vite configured for Tauri (port 1420, HMR support)

### Security
- No passwords stored in plain text
- No secrets embedded in client
- Tokens not exposed in logs
- Session expiration enforced client-side
- Backend remains sole source of truth

## [0.1.0] - 2026-06-12

### Added

- Phase 0: Project scaffolding and directory structure
- Backend (Laravel) base structure with controllers, models, services, jobs, events, projectors, policies, rules, and support directories
- Frontend (React) base structure with components, pages, layouts, hooks, services, stores, types, and utils directories
- Infrastructure scaffolding with Docker, Nginx, MySQL, Redis, and backup configurations
- Legacy code directories for database scripts, programs, and raw data
- Documentation, scripts, storage, and backups directories
- Initial `.gitignore` for Laravel, React, Node, Docker, IDE, and OS files
