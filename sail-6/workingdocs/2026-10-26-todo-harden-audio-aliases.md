# TODO — Harden Apache audio aliases / sarkphone play

Opened: 2026-10-26 (customer go-live prep). Defer until after AMI bulk + media Alias removal ship.

## Context

`sark-ssl.conf` previously exposed `/media` → `/opt/sark/media` with `Indexes` + `Require all granted` (call recordings). That Alias/Directory is **removed** in working tree (not yet packaged).

Same pattern remains for voicemail (and greetings indexing):

| Alias | Target | Used by | Risk |
|-------|--------|---------|------|
| `/server-vmail` | `/var/spool/asterisk/voicemail` | **sarkphone** play link only | High — list + fetch mailbox WAVs without Apache auth |
| `/server-sounds` | `/usr/share/asterisk/sounds` | **sarkgreeting** `<audio>` | Medium — Indexes + public read of greetings/prompts |
| `/server-moh` | `/usr/share/asterisk` (whole tree) | **sarkcluster** MOH `<audio>` | Alias broader than needed; listing of root currently 403 |

`/public` + `/provisioning` are intentional for phones — leave alone.

## sarkphone specifics

- Panel still active: `Panel.classname = sarkphone/main.php` (displayname **Phone**, weight `0`).
- Voicemail table: download already uses session-backed `/php/downloadg.php?dfile=…`.
- Play still uses open Alias:

```text
/server-vmail/default/{ext}/{INBOX|Old}/{file}
```

in `opt/sark/php/sarkphone/view.php` (`genMail()`).

## Suggested work (when returning)

1. **sarkphone play** — point play at an authenticated stream/download (reuse or extend `downloadg.php` with inline/`audio/*` Content-Type, or a small `playg.php`). Do **not** pass arbitrary filesystem paths without allowlisting under `/var/spool/asterisk/voicemail/…` for the logged-in extension.
2. **Remove** `Alias /server-vmail` and its `<Directory>` from `sark-ssl.conf` once play no longer needs it.
3. **Optionally** drop `Indexes` on `/server-sounds` (and narrow `/server-moh` to the real MOH parent dir only). Longer term: same authenticated media helper for greetings/MOH.
4. Review `/console` ProxyPass (`Allow from all`) separately — shell iframe bypasses Apache login.
5. Confirm `downloadg.php` path allowlisting while touching this (today it chmod’s and streams whatever `dfile` is given after session check).

## Out of scope for this note

- AMI `PJSIPShowContacts` bulk list fix (done in working tree).
- `/media` Alias removal (done in working tree).
- Package bump / build — fold audio hardening into a later release unless bundled with 6.5.0-11.
