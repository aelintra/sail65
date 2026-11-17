# Session handoff — 2026-11-17

Let's Encrypt for SAIL via **Apache HTTP-01** (PBX3-style flow, no nginx). Branch **`feature/letsencrypt-apache-65018`**. Releases **6.5.0-18** (LE + apply) / **6.5.0-19** (Shorewall open/close for :80) / **6.5.0-20** (staging→prod without bricking Apache).

Also mirrored in Obsidian: `nonGitStuff/obsidian/SARK65/Brindley 65 build.md` (do **not** copy secrets from there into git).

## Goal

Trusted TLS for Apache `:443` and Asterisk WSS `:8089` (WebRTC), matching PBX3’s custom → LE → snakeoil model.

## Git / packages

| Item | Value |
|------|--------|
| Branch | `feature/letsencrypt-apache-65018` (not merged to `main`) |
| Commits | `144dd71` (6.5.0-18), `f7d482d` (6.5.0-19); **6.5.0-20** staging→prod heal; **6.5.0-21** postinst stop clobbering shorewall `rules` |
| Debs built on `.78` | through `sail_6.5.0-20_all.deb`; **6.5.0-21** in tree (not built yet) |

## Deploy status

| Host | sail | Notes |
|------|------|--------|
| `192.168.1.135` (`ubu2204arm`) lab | **6.5.0-19** | LE UI + ACME Alias + open/close smoke **OK**. No public :80 → cannot issue real LE here. |
| `webo1001.terminator.cloud` (Brindley EC2) | **6.5.0-19** | Installed 17/11; production LE (YE2) for `webo1001.terminator.cloud` applied to `:443` + `:8089`. Shorewall **8089** opened; **SG 8089** still pending (WebRTC client source unknown). Cron `le-renew.sh` present; stock `certbot.timer` still enabled. |

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
| Scripts | `apply-active-cert.sh`, `le-acme-common.sh`, `le-first-cert.sh`, `le-renew.sh`, `le-remove-cert.sh`, **`le-port80-open/close.sh`**; **6.5.0-20**: staging→prod replaces staging lineage after snakeoil fallback; `le_heal_apache_ssl_if_broken` before ACME |
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

1. Build/deploy **6.5.0-21** (postinst no longer `cp -f` shorewall `rules` on upgrade). webo1001 on **6.5.0-20** with production YE2; keep **8089** in `sark_rules` or `rules` (now preserved across upgrades).
2. SG: allow **8089** once WebRTC client source is known (Shorewall already open).
3. Create WebRTC extensions → Commit.
4. Optional: disable stock `certbot.timer` so only SAIL `le-renew.sh` (open/close + apply) renews.
5. Still open: `sark_includes` A/B/C; `sark_rtp.conf` STUN for remote media; sail65-docs WebRTC page.
6. Merge feature branch to `main` when Brindley soak is OK.

**Staging→prod note:** Do not manually `certbot delete` while Apache still points at live LE paths. From **6.5.0-20**, `le-first-cert.sh` (without `SAIL_LE_STAGING=1`) detects a staging live cert, falls back to snakeoil, deletes the lineage, then issues production.

## Key paths

| Item | Path |
|------|------|
| ACME webroot | `/opt/sark/var/acme-challenge` |
| LE domain marker | `/opt/sark/etc/identity/le-domain` |
| Status JSON | `/opt/sark/etc/identity/tls-active.json` |
| Custom PEMs | `/opt/sark/etc/ssl/custom/` |
| Shorewall LE toggle | `/etc/shorewall/sark_inline_le` |
| Renew log | `/var/log/sail-le-renew.log` |
