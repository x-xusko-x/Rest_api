# Rise CRM REST API Plugin - Specs

## Overview
- Rise CRM plugin that exposes CRUD-style REST endpoints for core CRM entities (users, projects, tasks, billing, support, communications).
- Ships as beta (`0.1.6`) and depends on Rise CRM >= 3.6, PHP 7.4+ (PHP 8 recommended for OpenAPI attributes), and MySQL or MariaDB (see `install/database.sql`).
- Drop the `Rest_api` folder into the Rise `plugins/` directory, run the installer from **Settings -> Plugins**, then configure defaults under **Settings -> REST API Settings**.

## Architecture Highlights
- All `/api/v1/*` routes map to controllers in `Controllers/Api/`. Each controller extends `Api_controller`, which centralises:
  - API enable/disable toggle (`api_enabled`), header or bearer auth (`X-API-Key`, `X-API-Secret`, `Authorization: Bearer base64(key:secret)`), and key lookup in `api_keys`.
  - Permission checks via `api_permission_groups` JSON policies (`create/read/update/delete` per resource) with system and custom groups.
  - HTTPS enforcement, per-key overrides for CORS, IP whitelists, expirations, and status (`active`, `inactive`, `revoked`).
  - Multi-tier rate limiting (minute/hour/day) backed by `Libraries/Api_rate_limiter` and `api_rate_limits`, with counters incremented atomically.
  - Request parsing, automatic cleanup of old logs, and optional OpenAPI-driven request/response validation (`Libraries/OpenAPI_validator`).
  - Structured JSON responses with pagination metadata and unified error handling.
- Administrative UI controllers (`Api_keys`, `Api_permission_groups`, `Api_logs`, `Rest_api_settings`, `Swagger`) render views under `Views/` for managing keys, limits, permissions, logs, and docs.
- Background stats aggregation hooks into CodeIgniter events (`Config/Events.php`) to update the persistent `api_statistics` snapshot every ~10 minutes.

## API Surface
- All endpoints live under `/api/v1/` and support `OPTIONS` for CORS preflight.
- Common operations are `index` (GET list), `show` (GET single), `create` (POST), `update` (PUT), `delete` (DELETE). Notable extras:
  - `POST /api/v1/clients/{id}/convert` converts a lead to a client.
  - `POST /api/v1/notifications/{id}/mark_read` acknowledges notifications.
  - `messages` omit PUT (read-only after creation).
- Core resources: `users`, `projects`, `tasks`, `clients`, `invoices`, `estimates`, `proposals`, `contracts`, `expenses`, `tickets`, `timesheets`, `events`, `notes`, `messages`, `notifications`, `announcements`.
- Pagination uses `limit` plus `offset` or `page` query params (default limit 50, cap 100); list responses return `{ data, meta.pagination, meta.response }`.

## Security and Access Control
- API key lifecycle: create keys in the admin UI, optionally tie them to a permission group, IP whitelist, per-key HTTPS/CORS overrides, and expiration date.
- Authentication fails fast with descriptive JSON, logging attempt metadata in `api_logs`.
- Permission groups (`Controllers/Api_permission_groups.php`) expose a DataTables UI, enforce non-deletable system groups, and count enabled endpoints for quick auditing.
- Rate-limit breaches surface as HTTP 429 responses that use the `TooManyRequests` schema.

## Monitoring and Operations
- `api_logs` stores request and response bodies (optional in list views for performance), status codes, timing, IP, and user agent.
- `Api_logs_model` offers filtered queries, pagination, and detail views; log retention is configurable (default 90 days) with a manual purge action.
- `api_statistics` tracks lifetime totals; admins can trigger recalculation (`Rest_api_settings::recalculate_statistics`) if data drifts.
- Cron-friendly helpers: rate-limit cleanup and statistics refresh routines are exposed via models and libraries.

## Database Footprint (install/database.sql)
- `api_keys`: API key metadata, hashed secrets, per-key security knobs, counters, and status flags.
- `api_logs`: Request audit trail with indexes for key, method, status, and timestamps.
- `api_rate_limits`: Rolling minute/hour/day counters per key (unique constraint prevents races).
- `api_settings`: Global configuration flags (enable API, default limits/CORS/IP whitelist, log retention, etc.).
- `api_statistics`: Persistent aggregate usage metrics.
- `api_permission_groups`: Named policy bundles with JSON-encoded CRUD permissions; seeded with a system "Default All" group.

## Admin Panel Tabs
- **Dashboard** - usage snapshot (total and active keys, today/month totals, success vs error split, average response time, lifetime averages).
- **General Settings** - toggles for enabling the API, default rate limits, HTTPS requirement, CORS origins, IP whitelist, log retention, validation switches.
- **API Keys** - CRUD UI with one-time secret reveal, assignment type (internal or external), status management, rate limit overrides, per-key security settings.
- **Permission Groups** - group editor with endpoint matrix, system-group badges, modal detail view.
- **Logs** - filterable viewer with optional body fetch and manual cleanup action.
- **Documentation** - intended to host Swagger UI once OpenAPI generation is healthy.

## Swagger and OpenAPI Status
- Cached spec in `writable/cache/openapi.json` is stale and inconsistent:
  - `info.title` and `version` ("Rest API" / `1.0.0`) do not match `Config/OpenAPI.php` ("Rise CRM - REST API", `0.1.6 - Beta 2`).
  - Only the `Users` tag and paths are present; no entries for projects, tasks, etc., implying most controllers lack PHP 8 attribute annotations or the scanner skipped them.
  - Components exist but reference data (for example `#/components/schemas/UserListResponse`) tied only to the Users controller, so Swagger UI renders almost empty.
  - Cached paths use `/users`, relying on server base `/api/v1`; without regenerating the cache the UI appears misaligned with actual routes.
- Fix suggestions:
  1. Add `#[OA\*]` attributes to the remaining controllers (currently only `Controllers/Api/Users.php` is annotated).
  2. Clear the cache (`/swagger/clear_cache`) or delete `writable/cache/openapi.json` after updating annotations so `Swagger_generator` rebuilds.
  3. Ensure PHP 8+ and the Composer `vendor/` dependencies (swagger-php, json-schema) are installed so attribute parsing works.

## Quick Start Checklist
1. Copy `Rest_api/` into Rise CRM's `plugins/` directory.
2. In the admin UI go to **Settings -> Plugins -> Install** for the REST API plugin.
3. Visit **Settings -> REST API Settings** to enable the API, set defaults, and configure security (HTTPS, CORS, IP whitelist, validation).
4. Create permission groups (optional) and issue API keys with appropriate limits and scope.
5. Test a call with `curl` including `X-API-Key` and `X-API-Secret` headers; monitor logs under **API Logs**.
6. Regenerate Swagger once annotations are complete to expose accurate docs.
