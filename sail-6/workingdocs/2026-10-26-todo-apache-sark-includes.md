# TODO — Decide fate of `/etc/apache2/sark_includes/`

Opened: 2026-10-26 (customer go-live prep). Not blocking go-live.

## What exists

Repo: `sail-6/etc/apache2/sark_includes/` → installs to **`/etc/apache2/sark_includes/`**

| File | Default | Network UI writer |
|------|---------|-------------------|
| `sarkServerName.conf` | `ServerName sark.local` | On FQDN save / clear |
| `preventIpAccess.conf` | `#` | `doHttpFilter()` when FQDNHTTP set |

## Original intention (from code + help text)

1. **`sarkServerName.conf`** — keep Apache `ServerName` in sync with the Network FQDN (or `sark.local` when cleared).
2. **`preventIpAccess.conf`** — rewrite rules for **FQDNHTTP**: allow LAN; require FQDN in `Host` for remote HTTP/HTTPS; 403 bare-IP Host (anti-bot). Help: `tt_help_core.fqdnhttp`. UI toggle is **commented out** (`//ToDo` in `sarknetwork/view.php`).

## Why it does nothing today

- No Apache vhost/`apache2.conf` **`Include`** of `sark_includes/*`.
- Live `ServerName` comes from **`sark-name.conf`** (`ServerName 127.0.0.1`), not the include.
- Network save still runs the writers; most paths wrongly use  
  `/opt/sark/etc/apache2/sark_includes/` (never packaged) → error.log noise.  
  One branch uses `/etc/apache2/sark_includes/` but www-data cannot write root-owned files without `request_syscmd`/sudo.

## Decision options (later)

- **A. Finish it** — Include snippets from SSL/HTTP vhosts; fix write path to `/etc/apache2/sark_includes/`; write via `request_syscmd`; re-enable FQDNHTTP UI; optionally drive `sark-name.conf` instead of/in addition to the include.
- **B. Retire it** — Remove writers from `sarknetwork/view.php`, drop package files / leave inert stubs, document that FQDN HTTP filtering is customer FW / Shorewall.
- **C. Leave inert** — Silence log noise only (stop writing); keep files for a future decision.

## Related

- Commit-lamp `commitflag` ownership/clear fixed separately in `srkHelperClass` (same session).
- Audio Alias hardening: [2026-10-26-todo-harden-audio-aliases.md](./2026-10-26-todo-harden-audio-aliases.md).
