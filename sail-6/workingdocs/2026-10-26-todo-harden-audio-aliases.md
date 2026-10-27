# TODO — Harden Apache audio aliases / sarkphone play

Opened: 2026-10-26 (customer go-live prep). Defer until after AMI bulk + media Alias removal ship.

## Context

`sark-ssl.conf` `/media` → `/opt/sark/media` is required by **origrecs** (play + download links resolve to `/media/recordings/…`). Removing it in 6.5.0-11 broke playback and made downloads save Apache 404 HTML as `*.wav.html`.

**6.5.0-12 interim:** Alias restored with **`Options -Indexes`** (no directory listing). Files remain fetchable by known URL without SAIL session — still needs authenticated stream later.

Same pattern remains for voicemail (and greetings indexing):

| Alias | Target | Used by | Risk |
|-------|--------|---------|------|
| `/media` | `/opt/sark/media` | **origrecs** play/download | Medium — no Indexes, but unauthenticated file fetch if path known |
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

1. **origrecs** — change `file_link` to session-backed stream (`playg.php` / `downloadg.php` with path allowlist under `/opt/sark/media/recordings/`), then drop `/media` Alias.
2. **sarkphone play** — same pattern for `/server-vmail`.
3. **Remove** open Aliases once play/download no longer need them.
4. Drop `Indexes` on `/server-sounds`; narrow `/server-moh`.
5. Review `/console` ProxyPass; tighten `downloadg.php` path allowlisting.
