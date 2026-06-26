# Changelog v1.1.0

All notable changes for v1.1.0

## [1.1.0] - 2026-06-26

### Added

#### Multi-Organization Architecture
- Organization model with `is_primary`, `is_active`, `settings` JSON, and User relationship
- OrganizationService for business logic (create, update, delete, activate, deactivate, setPrimary)
- OrganizationController with full CRUD + custom actions (activate/deactivate/setPrimary)
- OrganizationPolicy with granular permissions (view, create, update, delete, activate, deactivate, setPrimary)
- Organization routes: `apiResource` + `POST /organizations/{id}/activate` + `POST /organizations/{id}/deactivate` + `POST /organizations/{id}/set-primary`
- ExpenseCategory model with organization relationship
- Default expense categories seeding (Pessoal, Material, Serviços, Equipamentos, Outros)

#### Setup Wizard (Multi-Org Flow)
- SetupWizardController with 4 endpoints: `status`, `createOrganization`, `createAdmin`, `complete`
- SetupWizardPage with 3-step flow: create organization → create administrator → complete
- Initial organization created as `is_primary=true`, `is_active=true`
- Initial admin created as `is_master=true`, `is_system_admin=true` with `super_admin` role
- Master token generation for critical operations
- Default roles, permissions, and expense categories created on setup completion
- Step indicator showing Institution → Administrator → Completed flow

#### Feature Flags System
- `config/features.php` with 6 configurable flags
- FeatureFlagService singleton with typed methods for each flag
- `feature()` global helper function for easy access
- Feature flags: `legacy_module`, `multi_organization`, `setup_wizard`, `auto_updates`, `desktop_app`, `developer_mode`
- Flags configurable via `.env` variables
- All flags documented in installer (install.ps1) and .env.example

#### Domain Events
- OrganizationCreated event (dispatched on org creation via Setup Wizard and API)
- UserCreated event (dispatched on admin/user creation via Setup Wizard)
- UserAssignedRole event (dispatched when super_admin role is assigned)
- OrganizationActivated event (dispatched on activate/deactivate with `activated` boolean and `reason`)
- Events use Laravel's native event system with SerializesModels trait

#### Security & Code Quality
- Gitleaks: 0 leaks maintained
- No hardcoded credentials or sensitive data in public repository
- .gitignore enhanced to exclude sensitive patterns
- Feature flags evaluated lazily (not during console commands)

### Changed

#### Backend
- AppServiceProvider now registers FeatureFlagService as singleton
- AppServiceProvider configures `app.is_configured` based on Organization table existence
- composer.json autoload includes `helpers.php` for global functions

#### Frontend
- SetupWizardPage.tsx: React import fixed for React.Fragment usage
- API service structure maintains compatibility with multi-org architecture

#### Installer
- install.ps1 updated with all 6 feature flags
- .env.example updated with all 6 feature flags
- NSIS installer validated for port availability checking

### Security

- All sensitive Santa Casa data moved to `legacy-data/` (local only, not versioned)
- Public repository (schf-core) contains no real hospital data
- Private repository (schf-santacasa-migration) uses generic naming
- Gitleaks scanning in CI/CD pipeline
- Feature flags prevent accidental exposure of unused modules

### Infrastructure

- Docker-based development environment (backend, frontend, mysql, redis, nginx, queue)
- Docker Compose with health checks and proper dependencies
- Installation script (install.ps1) with Docker validation, port checking, directory creation, .env generation, build, migration, seeding
- NSIS installer for Windows desktop installation
- Backup infrastructure configured

## [1.0.0] - 2026-06-20

### Added
- Initial release as separate public/private repositories
- schf-core public repository (generic SCHF platform)
- schf-santacasa-migration private repository (Firebird migration module)
- v1.0.0 tags on both repositories