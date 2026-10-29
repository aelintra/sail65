# Session handoff — 2026-10-28 / 2026-10-29

Voicemail ageing cron fix + PJSIP trunk instance recreate after restore. Lab on **sail 6.5.0-14**.

## Environment

| Role | Host | Notes |
|------|------|--------|
| Lab | `192.168.1.130` | Passwordless sudo. **sail 6.5.0-14**. |
| Customer test VM | `192.168.9.4` (`adler-pbx-02`) | SSH `pbxadmin@192.168.9.4` (key). In `sudo` group but **sudo needs password**. User installs debs from Git. Verified **6.5.0-14** trunk edit OK after SIPDRIVER=PJSIP. |
| Deb builder | `192.168.1.78` | `debuild --no-lintian -us -uc -b` → `sail_*_all.deb`. Push from Mac. |

Do **not** confuse with `192.168.4.9` (typo / unreachable).

## Releases

### 6.5.0-13 (`c725104` / build `6a8201e`)

- Cron: `bash /opt/sark/scripts/agevmail.sh` (was wrongly `perl`).
- `agevmail.sh`: path-based `*/Old/msg*` find; skip if voicemail tree missing.
- Lab: ageing verified with staged Old msg + temporary VMAILAGE=1.

Ageing only deletes **opened** mail under `Old/`, not INBOX. Restored customer vmail had many old INBOX msgs and **zero** Old files.

### 6.5.0-14 (`26ad3a8` / build `401eae8`)

- After DB restore, SIP trunks keep chan_sip `sipiaxpeer` and **NULL** `lineio.pjsipuser` / often NULL `pjsipreg`.
- Edit panel **pjsipuser** textarea is loaded from **file** `/etc/asterisk/sark_pjsip_{pkey}_trunk.conf`, not the DB column. That file is only created on **new** trunk wizard — restore omits it → blank PJSIP section when SIPDRIVER=PJSIP.
- Fix: `getPjsipTrunkInstance` auto-creates missing file (infer `pjsipreg` SND/RCV/NONE from host/register); `genPjsipTrunks` skips empty buffers.
- Also: Globals **SIPDRIVER** must be **PJSIP** (customer VM was left on chan_sip / `SIP` after restore — shows `sipiaxpeer` instead).

Lab test: nulled `pjsipuser`+`pjsipreg`, deleted Gamma trunk.conf, Commit/generator → file recreated, `pjsipreg=NONE`, ready_trunks emitted Gamma. Gamma left inactive.

Debs: `sail_6.5.0-13_all.deb`, `sail_6.5.0-14_all.deb` (repo root + builder).

## Key files

| Area | Path |
|------|------|
| Vmail cron | `etc/cron.d/sark`, `opt/sark/scripts/agevmail.sh` |
| PJSIP trunk instance | `opt/sark/php/srkHelperClass` (`inferPjsipreg`, `getPjsipTrunkInstance`, `createPjsipTrunkInstance`) |
| Generator | `opt/sark/php/srkGenClass` (`genPjsipTrunks`) |
| Trunk UI | `opt/sark/php/sarktrunk/view.php` (shows file via `getPjsipTrunkInstance` when SIPDRIVER=PJSIP) |
| Templates | `/etc/asterisk/sark_pjsip_trunk_{sndreg,rcvreg,trusted}.tmpl` |

## Open TODOs (unchanged)

1. Audio aliases — `/server-vmail` (sarkphone play), sounds/MOH.
2. `sark_includes` — finish or retire.

## Suggested next work

1. Harden sarkphone play → `downloadg.php`; drop `/server-vmail`.
2. Optional: document restore checklist (set SIPDRIVER=PJSIP, Commit once to recreate trunk/phone instance files).
