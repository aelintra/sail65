# Session handoff — 2026-10-26 / 2026-10-27

AMI Extensions hardening + call-recording lockdown. Lab on **sail 6.5.0-12**.

## Environment

| Role | Host | Notes |
|------|------|--------|
| Lab | `192.168.1.130` | Passwordless sudo (`sudo -n`). Installed **6.5.0-12**. Endpoints 2201–2203 used for transfer/park smoke. |
| Deb builder | `192.168.1.78` | `~/Git/sail65`; `debuild --no-lintian -us -uc -b` → `sail_*_all.deb`. Push to GitHub from Mac. |
| Mac | `/Users/jeffstokoe/GiT/sail65` | `origin/main` (`aelintra/sail65`) |

## Releases

### 6.5.0-11 (`6da39d4` / build `445414b`)

- Extensions/Trunks: one AMI **`PJSIPShowContacts`** instead of N× `PJSIPShowEndpoint`.
- Restore **`get_peer_array`** (splash/PDF); map contacts into legacy IPaddress/Status.
- Heal **commitflag** (www-data touch/unlink; sudo rm fallback).
- Trunk edit: `getPjsipTrunkInstance($pkey)` not whole tuple.
- Network: guard missing SMTP POST keys.
- Changelog also noted dropping `/media` Alias (partial; finalized in -12 path).

### 6.5.0-12 (`8b192f9` / build `0c5373d`)

- **No Apache `/media` Alias** — recordings not publicly URL-fetchable.
- Origrecs play/download via session-backed **`/php/downloadg.php`**:
  - Path allowlist (`/opt/sark/media/recordings`, vmail, logs, sounds).
  - `inline=1` / recording-audio default → stream + Range for jPlayer.
  - `dl=1` → attachment download (Safari-friendly).
- Origrecs JS cache-bust `sark-recording.js?v=20261027`; play uses `data-src`.
- Lab verified: play has audio; download works without exposing `/media`.

Debs: `sail_6.5.0-11_all.deb`, `sail_6.5.0-12_all.deb` (repo root + builder `~/Git/sail65/`).

## Key files

| Area | Path |
|------|------|
| AMI contacts | `opt/sark/php/srkAmiHelperClass`, AsteriskManager `getPjsipContacts` |
| Commit lamp | `opt/sark/php/srkHelperClass` (`commitOn`/`Off`/`IsPending`) |
| SSL aliases | `opt/sark/etc/apache2/sites-available/sark-ssl.conf` (no `/media`) |
| Download/stream | `opt/sark/php/downloadg.php` |
| Origrecs | `www/origrecs/lib/Recording.class.php`, `js/sark-recording.js`, `index.php` |

## Remaining open TODOs

See [README.md](./README.md):

1. **Audio aliases** — `/server-vmail` (sarkphone play), `/server-sounds`, `/server-moh` still open; recordings done.
2. **`sark_includes`** — never wired into Apache; finish or retire.

## Suggested next work

1. Sarkphone play → `downloadg.php?inline=1`; drop `/server-vmail`.
2. Same pattern for greetings/MOH aliases (or narrow + drop Indexes).
3. Decide A/B/C on `sark_includes` (silence log noise at minimum).
