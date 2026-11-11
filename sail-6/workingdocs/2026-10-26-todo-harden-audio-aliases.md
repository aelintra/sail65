# TODO — Harden Apache audio aliases / sarkphone play

Opened: 2026-10-26 (customer go-live prep).

## Done (6.5.0-12)

- **`/media` Alias removed** from `sark-ssl.conf`.
- Origrecs play/download via session-backed `/php/downloadg.php`:
  - Allowlisted roots; `inline=1` / recording-audio → stream + Range.
  - `dl=1` → attachment.
  - Lab verified play + download on `.130`.

## Done (6.5.0-16)

- **`/server-vmail` Alias removed**; **sarkphone** play → `downloadg.php?inline=1`.
- **`/server-moh` Alias removed**; **sarkcluster** MOH `<audio>` → `downloadg.php?inline=1`;
  allowlist `/usr/share/asterisk/moh-{cluster}/…`.
- **`/server-sounds` Alias removed** (and its Indexes `<Directory>`);
  **sarkgreeting** play → `downloadg.php?inline=1` (sounds root already allowlisted;
  download was already session-backed via `dtype=greet`).

## Still open

`/public` + `/provisioning` are intentional for phones — leave alone.

## Console (6.5.0-16)

- `/console` kept (shellinabox on 127.0.0.1:63005).
- Iframe → `/php/console_gate.php` (SAIL session) sets signed `SAIL_CONSOLE` cookie, redirects to `/console/`.
- Apache RewriteMap `srkConsoleCookieCheck` forbids `/console` without valid cookie.

## Apache cuts 2–5 (6.5.0-16)

- Removed `www/event.php` (dead AMI reader + hardcoded secret).
- DocumentRoot `Options -Indexes` (ssl / http / prov-ssl).
- HTTP→HTTPS redirect for all non-provisioning URIs (`/provisioning`, `/public`, `/snom`, `Config*.cfg` stay on :80).
- Dropped `.phps` AddType, empty `cgi-bin` ScriptAliases, and `Satisfy any`.
