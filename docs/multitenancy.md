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
- Isolation: Uploads are tenant-scoped via `uploads_path()` helper (`storage/uploads/<host>/...`).
- Backups: Back up each tenant’s database individually for easier restore.
- Monitoring: Track errors and performance per host.

## Self-service Signup (Web)

- A simple provisioning page is available at `/signup`.
- It registers the tenant in `tenants/registry.php` and triggers `console install` in a child PHP process for that host.
- It automatically creates the tenant database and user using the provisioning credentials in `config.php`.
- You only provide the subdomain/host and the initial admin/company data.

### Provisioning Credentials

Set the following in `config.php` (see `config-sample.php`):

```
const PROVISION_DB_HOST = 'mysql';
const PROVISION_DB_USERNAME = 'root_or_admin_with_create_privileges';
const PROVISION_DB_PASSWORD = 'admin_password';
const DB_NAME_PREFIX = 'ea_';
const DB_USER_PREFIX = 'ea_';
```

The user must have privileges to `CREATE DATABASE`, `CREATE USER` and `GRANT` on the target server.

### Security & Email

- reCAPTCHA: set `RECAPTCHA_SITE_KEY` and `RECAPTCHA_SECRET` in `config.php` to enable bot protection on `/signup`.
- Email: configure `application/config/email.php` (SMTP recommended) so the welcome email is delivered to the admin.
