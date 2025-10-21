# Multitenancy (Database per Tenant)

This repository includes a lightweight database-per-tenant mode to support self-service onboarding.

## Overview

- One application (same codebase) serves multiple customers.
- Each customer (tenant) has its own database.
- The current tenant is resolved by host (subdomain) or by the `TENANT_HOST` environment variable (for CLI).

## Files

- `tenants/registry.php`: PHP array mapping host -> DB credentials.
- `application/helpers/tenant_helper.php`: Host resolution and registry utilities.
- `application/config/database.php`: Loads tenant DB settings when available; falls back to global `Config::DB_*`.
- `bin/provision-tenant.php`: CLI script to register a tenant in the registry.

## Provisioning a Tenant

1) Register the tenant in the registry:

```
php bin/provision-tenant.php --host=cliente.seuapp.com --db_host=localhost \
  --db_name=ea_cliente --db_user=ea_cliente --db_pass=senha
```

2) Run installation/migrations for that tenant:

- Linux/macOS:

```
TENANT_HOST=cliente.seuapp.com php index.php console install
```

- Windows PowerShell:

```
$env:TENANT_HOST="cliente.seuapp.com"; php index.php console install
```

> The console `install` command runs fresh migrations and seeds initial data (admin, service, provider, settings).

## Running Migrations per Tenant

- To migrate a single tenant:

```
TENANT_HOST=cliente.seuapp.com php index.php console migrate
```

- To reset (fresh) a single tenant (destructive):

```
TENANT_HOST=cliente.seuapp.com php index.php console migrate fresh
```

## HTTP Requests

During normal web requests, the app resolves the current tenant by reading `$_SERVER['HTTP_HOST']` and loading the matching DB credentials from `tenants/registry.php`.

If a host is not registered, the app falls back to the single-tenant configuration defined in `config.php`.

## Migrate All Tenants

Run migrations for all registered tenants sequentially:

```
php bin/migrate-all-tenants.php
```

This script sets `TENANT_HOST` for each host and runs `console migrate` in a child PHP process.

## Notes & Recommendations

- DNS: Configure wildcard DNS (e.g., `*.seuapp.com`) and route all subdomains to the same application.
- Isolation: Consider separating uploads per tenant (e.g., `storage/uploads/<tenant>/...`) if you need per-tenant backups of files.
- Backups: Back up each tenant’s database individually for easier restore.
- Monitoring: Track errors and performance per host.
