# TODO — Harden Apache audio aliases / sarkphone play

Opened: 2026-10-26 (customer go-live prep).

## Done (6.5.0-12)

- **`/media` Alias removed** from `sark-ssl.conf`.
- Origrecs play/download via session-backed `/php/downloadg.php`:
  - Allowlisted roots; `inline=1` / recording-audio → stream + Range.
  - `dl=1` → attachment.
  - Lab verified play + download on `.130`.

## Still open

Same pattern remains for voicemail / greetings / MOH:

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

1. **sarkphone play** — point at `downloadg.php?inline=1` (voicemail already allowlisted); drop `/server-vmail`.
2. **Greetings / MOH** — same for `/server-sounds` and `/server-moh` (drop Indexes; narrow MOH alias).
3. Review `/console` ProxyPass (`Allow from all`).
