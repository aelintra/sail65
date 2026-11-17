# Session handoff — 2026-11-17

Let's Encrypt for SAIL via **Apache HTTP-01** (PBX3-style flow, no nginx). Release **sail 6.5.0-18** (changelog bumped; deb not built this session).

## Goal

Trusted TLS for Apache `:443` and Asterisk WSS `:8089` (WebRTC), matching PBX3’s custom → LE → snakeoil model.

## What landed in tree

| Area | Change |
|------|--------|
| Apache `:80` | `sark-http.conf`: ACME Alias + HTTPS-redirect exception for `/.well-known/acme-challenge/` |
| Scripts | `apply-active-cert.sh`, `le-acme-common.sh`, `le-first-cert.sh`, `le-renew.sh`, `le-remove-cert.sh` |
| Cert panel | LE setup (FQDN + email), renew, remove; custom paste → `/opt/sark/etc/ssl/custom/`; status from `tls-active.json` |
| Asterisk defaults | `sark_http.conf` + `sark_pjsip_transport.tmpl` → **snakeoil** so `:8089` can bind before customer cert |
| Package | `certbot` + `ssl-cert` Depends; postinst dirs + migrate legacy customer PEM → custom/; runs apply; cron `17 3 * * *` → `le-renew.sh` |

### Active cert model

1. **Custom** — `/opt/sark/etc/ssl/custom/{fullchain,privkey}.pem`
2. **Let's Encrypt** — `/etc/letsencrypt/live/<fqdn>/` + marker `/opt/sark/etc/identity/le-domain`
3. **Snakeoil**

`apply-active-cert.sh` symlinks stable paths `ssl-cert-sark-customer.{pem,key}` → active material, writes `sark-certs.conf`, rewrites Asterisk http + transport **and tmpl** (so Commit does not wipe paths), fixes LE `ssl-cert` group perms, reloads Apache, **restarts** Asterisk.

Panel privileged ops go through **srk-ua-helper** (`request_syscmd`), not www-data sudo (certbot not in sailsudoers).

## Build / install

```bash
# on Mac: sync to builder, then on .78:
cd ~/Git/sail65/sail-6 && debuild --no-lintian -us -uc -b
# install sail_6.5.0-18_all.deb on target
```

## First LE on a host (e.g. Brindley / live)

1. DNS A/AAAA for FQDN → PBX public IP; Shorewall tcp/80 (already in `sark_rules`).
2. Install **6.5.0-18**; confirm ACME:  
   `curl -sS -o /dev/null -w '%{http_code}\n' http://FQDN/.well-known/acme-challenge/test` → **404** (not 301).
3. Cert panel → FQDN + email → Get certificate  
   or: `sudo SAIL_LE_STAGING=1 /opt/sark/scripts/le-first-cert.sh FQDN email` (staging first).
4. Verify: `cat /opt/sark/etc/identity/tls-active.json`; `https://FQDN/` without `-k`;  
   `curl -skI https://127.0.0.1:8089/httpstatus` → HTTP listener on **8089**.

## Open / next

1. Build + install **6.5.0-18** on real Brindley system; issue LE; then WebRTC endpoints.
2. Optional: hot-fix ACME Alias on `.135` (6.5.0-15) before full upgrade for lab ACME reachability tests.
3. Still open: `sark_includes` A/B/C; activate `sark_rtp.conf` STUN if remote WebRTC media needs it.

## Key paths

| Item | Path |
|------|------|
| ACME webroot | `/opt/sark/var/acme-challenge` |
| LE domain marker | `/opt/sark/etc/identity/le-domain` |
| Status JSON | `/opt/sark/etc/identity/tls-active.json` |
| Custom PEMs | `/opt/sark/etc/ssl/custom/` |
| Renew log | `/var/log/sail-le-renew.log` |
