# workingdocs

Agent/session handoff notes for SAIL 6.5 work. Keep entries dated and factual so the next agent can continue without re-discovering context.

Do **not** store secrets (sudo passwords, AMI secrets, customer credentials) here.

## Lab hosts

| Role | Host | Access |
|------|------|--------|
| **Deb builder** | `192.168.1.78` (`deb11-1`) | SSH `tech@192.168.1.78`. Repos under `~/Git/` (sail65, etc.). Build: `cd ~/Git/sail65/sail-6 && debuild --no-lintian -us -uc -b` → `~/Git/sail65/sail_*_all.deb`. Cannot push to GitHub over HTTPS; push from Mac. Mac has no `rsync` — sync with `tar`/`scp`. |
| Lab / customer rebuild | `192.168.1.130` (`adler`) | SSH `tech@192.168.1.130`. **Passwordless sudo** (`sudo -n`). Target **sail 6.5.0-17**. Kernel **6.8.0-149** (CVE-2026-98432 patched). |
| Brindley lab | `192.168.1.135` (`ubu2204arm`) | SSH `tech@192.168.1.135`. **Passwordless sudo**. sail **6.5.0-24** (rtp → sark_rtp; STUN live). No public :80. |
| Brindley EC2 | `webo1001.terminator.cloud` (`99.80.166.113`) | Key `Brindley-Webo.pem`; SSH as `ubuntu`. sail **6.5.0-24**; prod LE YE2; Shorewall + **SG 8089 open**; WebRTC **9005** OK; rtp → sark_rtp (STUN live). |
| Customer test VM | `192.168.9.4` (`adler-pbx-02`) | SSH `pbxadmin@192.168.9.4` (key). Sudo **requires password**. |
| Other test | `192.168.1.12` (`iitac2404`) | SSH `tech@192.168.1.12`. |

## Latest handoff

- [2026-11-19-lab23-webrtc-dependabot-handoff.md](./2026-11-19-lab23-webrtc-dependabot-handoff.md) — Session closed: **6.5.0-24** on `.135` + webo1001 (rtp→sark_rtp, media OK); LE on `main`; 9005 OK; no F2B for SSL scanner noise; sark65api Dependabot merged.
- [2026-11-18-ldap-asterisk-tls-handoff.md](./2026-11-18-ldap-asterisk-tls-handoff.md) — LDAP **6.5.0-23**; webo1001 WSS/9005 server-side; admin-password forensics; WebPhone WSS host bug (since fixed).
- [2026-11-17-ldap-contact-and-tenant-ou-fixes.md](./2026-11-17-ldap-contact-and-tenant-ou-fixes.md) — LDAP fix detail / OU backfill snippet.
- [2026-11-17-letsencrypt-apache-handoff.md](./2026-11-17-letsencrypt-apache-handoff.md) — LE Apache HTTP-01 + Shorewall :80 open/close; branch `feature/letsencrypt-apache-65018`.
- [2026-11-16-webrtc-brindley-audit-handoff.md](./2026-11-16-webrtc-brindley-audit-handoff.md) — WebRTC audit (lab `.135`); certs/8089/Shorewall blockers.
- [2026-11-11-apache-harden-console-handoff.md](./2026-11-11-apache-harden-console-handoff.md) — audio Aliases removed, Console cookie gate, HTTP harden, **6.5.0-17** on lab; CVE-2026-98432 kernel note.
- [2026-11-04-fail2ban-refresh-handoff.md](./2026-11-04-fail2ban-refresh-handoff.md) — Fail2Ban jail.d + Net panel, **6.5.0-15**.
- [2026-10-28-29-vmail-pjsip-trunk-handoff.md](./2026-10-28-29-vmail-pjsip-trunk-handoff.md) — vmail ageing cron, PJSIP trunk recreate after restore, **6.5.0-13 / 6.5.0-14**.
- [2026-10-26-27-ami-recordings-handoff.md](./2026-10-26-27-ami-recordings-handoff.md) — AMI bulk contacts, recording lockdown, **6.5.0-11 / 6.5.0-12**.
- [2026-10-25-26-php2shell-restore-ami.md](./2026-10-25-26-php2shell-restore-ami.md) — PHP2Shell, restore/AMI early fixes, **6.5.0-9 / 6.5.0-10**.

## Open TODOs

- webo1001: smoke Directory/tenant OU (LDAP from **-23**); optional more WebRTC extensions; optional disable stock `certbot.timer`.
- `feature/letsencrypt-apache-65018` merged to `main` (through **6.5.0-24**).
- Optional: upgrade `/opt/sark65api_L12` on lab hosts per merged install-doc upgrade section.
- [2026-10-26-todo-harden-audio-aliases.md](./2026-10-26-todo-harden-audio-aliases.md) — **done** in 6.5.0-16 (aliases + Console gate); kept for history.
- [2026-10-26-todo-apache-sark-includes.md](./2026-10-26-todo-apache-sark-includes.md) — finish or retire `sarkServerName` / `preventIpAccess` includes (never wired into Apache).
