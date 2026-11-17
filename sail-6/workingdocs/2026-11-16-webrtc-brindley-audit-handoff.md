# Session handoff — 2026-11-16

WebRTC readiness audit on Brindley build host `192.168.1.135`. No package/code changes this session. Notes also saved in Obsidian (`SARK65/Brindley 65 build`).

## Environment

| Role | Host | Notes |
|------|------|--------|
| Brindley lab / pre-live | `192.168.1.135` (`ubu2204arm`) | SSH `tech@192.168.1.135`. **Passwordless sudo**. sail **6.5.0-15**. Kernel **6.8.0-149-generic**. Asterisk **20.6**. `SIPDRIVER=PJSIP`. |
| Lab (Adler) | `192.168.1.130` | Still on **6.5.0-17** from prior handoff; not touched this session. |
| Deb builder | `192.168.1.78` | Unchanged. |
| Mac | `/Users/jeffstokoe/GiT/sail65` | `main` tip **6.5.0-17** (`d307433`). |

Next session: install onto the **real/customer** system and continue WebRTC bring-up from there (not necessarily re-fix only on `.135`).

## Dependabot (repo scan)

`aelintra/sail65`: **0 open** Dependabot alerts. Six historical grunt alerts remain `fixed` (jquery-ui leftover `package.json` removed in 6.5.0-10). No manifests left to fix.

## WebRTC audit — `192.168.1.135`

### Already OK

- `SIPDRIVER=PJSIP`; `pjsip.conf` include chain has `#include sark_pjsip_ready_webrtc.conf`.
- `transport-wss` defined/loaded (`bind=0.0.0.0:8089`); template `sark_pjsip_webrtc.tmpl` has `webrtc=yes`, `dtls_auto_generate_cert=yes`, codecs `ulaw,alaw,opus,vp8`.
- Modules: `res_http_websocket`, `res_pjsip*`, `res_rtp_asterisk`, `res_srtp` running.
- Asterisk HTTP **8088** up; `/ws` enabled (`http show status`).
- NAT: `external_media_address` / `external_signaling_address` = `74.83.26.203`; private `local_net`s set.
- RTP 10000–20000; Shorewall allows LAN UDP RTP.

### Blockers (WSS not usable today)

1. **Customer TLS certs missing (critical)**  
   Expected paths absent:
   - `/etc/ssl/certs/ssl-cert-sark-customer.pem`
   - `/etc/ssl/private/ssl-cert-sark-customer.key`  
   Apache on snakeoil. Install via Cert panel (`sarkcert`) or place files at those paths.

2. **Port 8089 / HTTP TLS down**  
   `sark_http.conf` has `tlsenable=yes` → `0.0.0.0:8089`, but nothing listens; `curl https://127.0.0.1:8089` fails. Browsers need `wss://…:8089/ws`. Same missing certs break `transport-tls` (5061) — Asterisk ERROR on reload.

3. **Shorewall: no 8088/8089 rules**  
   SIP 5060/5061 + RTP allowed from `$LAN`; WSS/WS ports not opened. Add after TLS works.

4. **No WebRTC endpoints yet**  
   DB: **0** `device=WebRTC` rows; `sark_pjsip_ready_webrtc.conf` is empty (`;` only). Only `anonymous` PJSIP endpoint loaded. Create Extensions → WebRTC after TLS/firewall, then Commit.

5. **`sark_rtp.conf` not active**  
   Live `/etc/asterisk/rtp.conf` is stock Debian (not symlink to `sark_rtp.conf`). ICE reports Yes; **STUN is `0.0.0.0:0`**. Remote/NAT clients may need STUN (or TURN) for media.

6. **`FQDN=None`**  
   OK for LAN-only tests; public WebRTC needs FQDN matching cert SANs.

### Verdict

Scaffolding is present; **secure WebSocket signaling is not available** until customer certs exist → 8089 binds → Shorewall allows 8089 → WebRTC extensions created.

## Suggested next work (real system install)

1. Install/upgrade sail on the target host (prefer **≥ 6.5.0-17** if packaging/hardenings wanted; `.135` is still on 6.5.0-15).
2. Install customer TLS via Cert panel → restart Asterisk → confirm `https://host:8089/httpstatus` and WSS `/ws`.
3. Shorewall: open **8089/tcp** (and optionally 8088) as required for clients.
4. Optionally symlink/activate `sark_rtp.conf` (or merge ICE/STUN) for remote media.
5. Create WebRTC extensions → Commit → register a browser client.
6. Still open from earlier: `sark_includes` A/B/C; CVE-2026-98432 on any remaining 24.04 boxes.

## Key paths (on PBX)

| Area | Path |
|------|------|
| HTTP / TLS WS | `/etc/asterisk/sark_http.conf` → 8088 / TLS 8089 |
| Transports | `/etc/asterisk/sark_pjsip_transport.conf` (`transport-wss`) |
| WebRTC template / ready | `sark_pjsip_webrtc.tmpl`, `sark_pjsip_ready_webrtc.conf` |
| Certs (expected) | `/etc/ssl/certs/ssl-cert-sark-customer.pem`, `…/private/…key` |
| Cert UI | `/opt/sark/php/sarkcert/view.php` |
| RTP / STUN (packaged, unused on `.135`) | `/etc/asterisk/sark_rtp.conf` |
| Shorewall | `/opt/sark/etc/shorewall/sark_rules` |
