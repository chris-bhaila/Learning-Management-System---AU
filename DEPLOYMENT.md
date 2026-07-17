# Pre-Deploy Checklist

Items that must be verified before this application goes live. Sourced from
`SECURITY_AUDIT.md` findings that are configuration, not code — nothing here
is fixed by a code change, so nothing enforces it automatically.

## Required environment changes

- **`APP_DEBUG` must be `false`.** With `APP_DEBUG=true`, Laravel returns
  full stack traces, local file paths, environment variable names, and query
  bindings in error responses. Acceptable in local development; a
  high-severity information-disclosure vulnerability in production.
- **`APP_ENV` must be `production`.** Do not deploy with `APP_ENV=local`.

Set both directly in the production environment's `.env` (or equivalent
secrets/config mechanism) — never commit a production `.env` to the repo,
and never change the local development `.env` to match; local development
is expected to keep running with `APP_DEBUG=true`.

## Also confirm before going live

- A CDN (e.g. Cloudflare) is in place at the infrastructure level.
  Laravel's rate limiting (see CLAUDE.md's Security section) only covers
  application-level brute force, not volumetric/infrastructure-level DDoS —
  this is explicitly out of this codebase's scope and must not be silently
  assumed covered.
