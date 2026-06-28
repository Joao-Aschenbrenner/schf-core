# Migration Bundle Import

SCHF Core imports only the universal Migration Bundle format defined in `schf-sdk`.

## Boundaries

- Core never reads a legacy database.
- Core never knows a migration plugin or ERP-specific connector.
- Core only validates, previews, and imports normalized Bundle files.
- SCHF Migration generates the Bundle and never writes directly to the Core database.

## Feature Flag

Import is disabled by default.

Enable only during controlled migration windows:

```env
FEATURE_MIGRATION_IMPORT=true
```

## Admin Routes

All routes require master admin authentication.

| Method | Route | Purpose |
|--------|-------|---------|
| POST | `/api/admin/migration/bundles/validate` | Validate ZIP, required files, manifest, and checksums |
| POST | `/api/admin/migration/bundles/preview` | Return import counts without writing data |
| POST | `/api/admin/migration/bundles/import` | Import a validated Bundle after confirmation |

## Validation Order

1. ZIP is opened with path traversal protection.
2. Required files are checked.
3. `checksum.sha256` is validated.
4. `manifest.json` is checked for required contract fields.
5. Preview summary is returned before import.

## Imported Records

Current deterministic importer supports:

- organization
- suppliers
- categories
- bank accounts
- payable payments
- expenses as paid payables

Users, roles, permissions, and receivables are previewed but not imported yet.
