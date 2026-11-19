# Session handoff — 2026-11-18

LDAP Directory fixes ported from sail6 into sail65, packaged as **6.5.0-23** on branch **`feature/letsencrypt-apache-65018`**. Evening session: admin-password forensics + WebRTC/WebPhone debug on Brindley EC2 (`webo1001`).

Detail for LDAP: [2026-11-17-ldap-contact-and-tenant-ou-fixes.md](./2026-11-17-ldap-contact-and-tenant-ou-fixes.md).  
LE background: [2026-11-17-letsencrypt-apache-handoff.md](./2026-11-17-letsencrypt-apache-handoff.md).  
Earlier WebRTC audit (lab): [2026-11-16-webrtc-brindley-audit-handoff.md](./2026-11-16-webrtc-brindley-audit-handoff.md).

## Git / packages

| Item | Value |
|------|--------|
| Branch | `feature/letsencrypt-apache-65018` (not merged to `main`) |
| LDAP fix commit | `3cf2978` (cherry-pick from `fix/ldap-contact-and-tenant-ou` / `e037415`) |
| Release commit | `903c47e` (changelog **6.5.0-23**) |
| Debs in repo | `9dc08e5` added **6.5.0-18..23** (`sail_6.5.0-*.deb`). Still no **16** or **17** on disk/builder. |
| Builder | `.78` built `sail_6.5.0-23_all.deb`; tree synced from GitHub tarball at `903c47e` |

### Deb retention (provisional)

Keep shipping the latest `.deb` in git with each release so installed revisions stay fetchable. Historically all builds were kept; **16/17** are missing. Prune policy for older debs still TBD — until then, keep what is committed.

## LDAP (6.5.0-23)

Same three bugs as sail6; all were present on sail65 `main`/LE tip before the port:

1. `Add()` missing RDN `uid` on contact entries
2. Empty `o` / org empty-check typo (`phone` vs `org`) → LDAP Invalid syntax
3. New tenant never created `ou=<tenant>,ou=contacts`

Files: `srkLDAPHelperClass`, `sarkldap/view.php`, `sarkcluster/view.php`.

**Not deployed yet** to webo1001. Install with `sudo dpkg -i sail_6.5.0-23_all.deb`. After deploy, backfill missing tenant OUs if needed (snippet in LDAP workingdoc).

## webo1001 Asterisk TLS / WSS

| Check | Result |
|-------|--------|
| SSH | `ubuntu` + `Documents/pemfiles/Brindley-Webo.pem` OK (EIP `99.80.166.113` also works) |
| Current certs | LE YE2 for `webo1001.terminator.cloud`; sark customer symlinks → LE fullchain/privkey |
| Transports | `transport-tls :5061`, `transport-wss :8089` up |
| HTTPS / WSS | `/httpstatus` **200** from browser + internet; `GET /ws` → **101** with `Sec-WebSocket-Protocol: sip` |
| Shorewall | `ACCEPT net $FW tcp 8089` |
| AWS SG | **8089 open** |
| PJSIP `default_realm` | **`vcloudpbx.com`** (`sark_pjsip_globals.conf`) — not `asterisk` |

### Brindley EC2 (unchanged access)

| Field | Value |
|-------|--------|
| FQDN | `webo1001.terminator.cloud` |
| Elastic IP | `99.80.166.113` |
| SG | `launch-wizard-56` / `launch-wizard-56b` (metadata) |
| SSH | `ubuntu` + `Brindley-Webo.pem` (also try `tech`) |

## Evening session (18/11) — admin password

Browser `admin` lives in SQLite `User` (`password` + `salt`); backups under `/opt/sark/bkup/sarkbak.*.zip`.

| Snapshot | Admin hash |
|----------|------------|
| `sarkbak` 17/11 04:00 + `sark.clean.db` | factory (`sarkadmin`) |
| Live / `sarkbak` 18/11 04:00 (before reset) | different hash (post UI change) |

**Cause of change:** not a silent rewrite. Apache + `auth.log` show Password UI change **17/11 12:24:43 UTC** from `92.62.8.37` (forced first-login → `sarkpasswd`); same second `chpasswd` updated **root**. Later package reloads (`srkV4reloader` at ~18:51 and ~21:19) restored that hash via customer dump — hash stayed stable until ops reset.

**Ops action:** live DB admin reset to factory **`sarkadmin`**; user confirmed login OK and reset from frontend.

Package upgrades rebuild DB via `srkV4reloader.sh` but re-import `last_data.sql` (includes `User`/`admin`). Factory seed remains in `always/1408278454.db_v4_set_defaults` (`INSERT OR IGNORE` — does not overwrite existing admin).

## Evening session (18/11) — WebRTC 9005 + WebPhone

### Sail / Asterisk (OK)

- Ext **9005** `device=WebRTC` in DB; Commit wrote **`/etc/asterisk/sark_pjsip_ready_webrtc.conf`** (not `ready_phones` — intentional split). `pjsip.conf` includes both.
- Endpoint **9005** loaded on `transport-wss`.
- Server-side WSS REGISTER with DB password → **401** then **200 OK**.
- Minor generator quirk: callerid line can emit a curly quote (`”`) in ready webrtc conf.

### WebPhone client (blocked — escalated)

Aelintra/PBX3-style WebPhone UI was pointed at webo1001:

- UI **Server (Proxy):** `wss://webo1001.terminator.cloud:8089/ws`
- Preview REGISTER looked correct for webo1001
- Browser actually opened: **`wss://dhbm8x.pbx3.com:8089/ws`** → `net::ERR_NAME_NOT_RESOLVED`
- Asterisk saw **no** REGISTER from the client while stuck on Connecting → Disconnected

**Conclusion:** client uses SIP Domain / tenant host (`dhbm8x.pbx3.com`) for the WebSocket URL instead of (or overriding) the configured proxy. **Referred to WebPhone / SPA dev team.** Sail WSS path is ready when the client hits the right host.

Realm note for clients: leave Realm blank or use **`vcloudpbx.com`** (Asterisk challenge realm). UI Realm `asterisk` is wrong for this box.

## Deploy status

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` lab | **6.5.0-19** | Unchanged |
| `webo1001.terminator.cloud` | **6.5.0-22** | Prod LE OK; WSS/TLS OK; WebRTC 9005 generated; **not yet on 6.5.0-23** |

## Open / next

1. **Wait:** WebPhone/SPA team fix so WSS uses configured proxy host (`webo1001…`), not tenant `dhbm8x.pbx3.com`.
2. After client fix: retest 9005 register + call; then consider further WebRTC extensions.
3. Deploy **6.5.0-23** to webo1001; smoke Directory contact add (empty org) + new tenant → LDAP OU; OU backfill if needed.
4. Optional: disable stock `certbot.timer` (use SAIL `le-renew.sh` only).
5. Optional: fix curly quote in WebRTC callerid generator template.
6. Still open: `sark_includes` A/B/C; `sark_rtp.conf` STUN; sail65-docs WebRTC page; decide deb prune policy; merge feature branch when soak OK.
7. Optional: locate/rebuild missing **6.5.0-16** / **17** debs if anyone still needs them.
