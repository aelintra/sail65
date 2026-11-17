# Session handoff — 2026-11-17

Let's Encrypt for SAIL via **Apache HTTP-01** (PBX3-style flow, no nginx). Branch **`feature/letsencrypt-apache-65018`**. Releases **6.5.0-18** (LE + apply) / **6.5.0-19** (Shorewall open/close for :80).

Also mirrored in Obsidian: `nonGitStuff/obsidian/SARK65/Brindley 65 build.md` (do **not** copy secrets from there into git).

## Goal

Trusted TLS for Apache `:443` and Asterisk WSS `:8089` (WebRTC), matching PBX3’s custom → LE → snakeoil model.

## Git / packages

| Item | Value |
|------|--------|
| Branch | `feature/letsencrypt-apache-65018` (not merged to `main`) |
| Commits | `144dd71` (6.5.0-18), `f7d482d` (6.5.0-19) |
| Debs built on `.78` | `sail_6.5.0-18_all.deb`, `sail_6.5.0-19_all.deb` under `~/Git/sail65/` |

## Deploy status

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` (`ubu2204arm`) lab | **6.5.0-19** | LE UI + ACME Alias + open/close smoke **OK**. No public :80 → cannot issue real LE here. |
| `webo1001.terminator.cloud` (Brindley EC2) | **6.5.0-15** (last known) | **Deploy of 6.5.0-19 blocked** end of session — SSH `:22` unreachable from Jeff/US. UK team can reach **:443**; waiting on them to test `:22` / SG. |

### Brindley EC2 (from ops notes)

| Field | Value |
|-------|--------|
| FQDN | `webo1001.terminator.cloud` |
| Elastic IP | `99.80.166.113` (eu-west-1) |
| SG | `launch-wizard-56` |
| Instance | T3 Micro, AMD64, 64 GB |
| SSH key | `Brindley-Webo.pem` (local path: `Documents/pemfiles/Brindley-Webo.pem`) |
| SSH user (try first) | `ubuntu` with `-i` PEM; also try `tech` |
| Hostname (guest) | `ubu2204amd` (per 16/11 notes) |
| Net panel | FQDN set; ext length 4; timeserver London (17/11) |

**Next deploy command once :22 works:**

```bash
# from Mac (deb already on .78 or /tmp)
scp -i Documents/pemfiles/Brindley-Webo.pem \
  tech@192.168.1.78:Git/sail65/sail_6.5.0-19_all.deb \
  ubuntu@webo1001.terminator.cloud:/tmp/
ssh -i Documents/pemfiles/Brindley-Webo.pem ubuntu@webo1001.terminator.cloud \
  'sudo apt-get install -y /tmp/sail_6.5.0-19_all.deb'
```

Then LE (staging first if desired):

```bash
sudo SAIL_LE_STAGING=1 /opt/sark/scripts/le-first-cert.sh webo1001.terminator.cloud EMAIL
# or Cert panel → Get Let's Encrypt certificate
```

Also open Shorewall **8089/tcp** (and confirm SG) for WSS after cert applies.

## What landed in tree

| Area | Change |
|------|--------|
| Apache `:80` | `sark-http.conf`: ACME Alias + HTTPS-redirect exception for `/.well-known/acme-challenge/` |
| Scripts | `apply-active-cert.sh`, `le-acme-common.sh`, `le-first-cert.sh`, `le-renew.sh`, `le-remove-cert.sh`, **`le-port80-open/close.sh`** |
| Cert panel | LE setup (FQDN + email), renew, remove; custom paste → `/opt/sark/etc/ssl/custom/`; status from `tls-active.json` |
| Asterisk defaults | `sark_http.conf` + `sark_pjsip_transport.tmpl` → **snakeoil** so `:8089` can bind before a customer cert |
| Package | `certbot` + `ssl-cert` Depends; postinst dirs + migrate legacy customer PEM → custom/; runs apply; cron `17 3 * * *` → `le-renew.sh` |
| Shorewall (:80) | Public tcp/80 **closed** by default (LAN HTTP kept). Issue/renew opens via `sark_inline_le`, then closes (SG may stay open). Cloud VCL path still can force world-open 80/443/22. |

### Active cert model

1. **Custom** — `/opt/sark/etc/ssl/custom/{fullchain,privkey}.pem`
2. **Let's Encrypt** — `/etc/letsencrypt/live/<fqdn>/` + marker `/opt/sark/etc/identity/le-domain`
3. **Snakeoil**

`apply-active-cert.sh` symlinks `ssl-cert-sark-customer.{pem,key}` → active material, writes `sark-certs.conf`, rewrites Asterisk http + transport **and tmpl**, fixes LE `ssl-cert` group perms, reloads Apache, **restarts** Asterisk.

Panel privileged ops → **srk-ua-helper** (`request_syscmd`).

### Lab smoke on `.135` (6.5.0-19)

- Default: `ACCEPT net:$LAN $FW tcp 80` only (no permanent public :80)
- `le-port80-open` / `close` toggles `sark_inline_le`; Shorewall restarts clean
- ACME URI → **404** (not 301)
- `:8089` → **200** on snakeoil
- From Mac LAN, :80 still works after close (LAN rule — expected)

## Open / next

1. Restore SSH to **webo1001** (UK test :22 / add Jeff IP to SG if needed).
2. Install **6.5.0-19**; issue LE for `webo1001.terminator.cloud`; confirm Apache HTTPS + `:8089` WSS.
3. Shorewall + SG: allow **8089** for WebRTC clients; create WebRTC extensions → Commit.
4. Optional: disable stock `certbot.timer` so only SAIL `le-renew.sh` (open/close + apply) renews.
5. Still open: `sark_includes` A/B/C; `sark_rtp.conf` STUN for remote media; sail65-docs WebRTC page.
6. Merge feature branch to `main` when Brindley soak is OK.

## Key paths

| Item | Path |
|------|------|
| ACME webroot | `/opt/sark/var/acme-challenge` |
| LE domain marker | `/opt/sark/etc/identity/le-domain` |
| Status JSON | `/opt/sark/etc/identity/tls-active.json` |
| Custom PEMs | `/opt/sark/etc/ssl/custom/` |
| Shorewall LE toggle | `/etc/shorewall/sark_inline_le` |
| Renew log | `/var/log/sail-le-renew.log` |
