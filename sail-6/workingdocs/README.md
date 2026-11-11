# workingdocs

Agent/session handoff notes for SAIL 6.5 work. Keep entries dated and factual so the next agent can continue without re-discovering context.

Do **not** store secrets (sudo passwords, AMI secrets, customer credentials) here.

## Lab hosts

| Role | Host | Access |
|------|------|--------|
| **Deb builder** | `192.168.1.78` (`deb11-1`) | SSH `tech@192.168.1.78`. Repos under `~/Git/` (sail65, etc.). Build: `cd ~/Git/sail65/sail-6 && debuild --no-lintian -us -uc -b` → `~/Git/sail65/sail_*_all.deb`. Cannot push to GitHub over HTTPS; push from Mac. Mac has no `rsync` — sync with `tar`/`scp`. |
| Lab / customer rebuild | `192.168.1.130` (`adler`) | SSH `tech@192.168.1.130`. **Passwordless sudo** (`sudo -n`). Target **sail 6.5.0-16**. |
| Customer test VM | `192.168.9.4` (`adler-pbx-02`) | SSH `pbxadmin@192.168.9.4` (key). Sudo **requires password**. |
| Other test | `192.168.1.12` (`iitac2404`) | SSH `tech@192.168.1.12`. |

## Latest handoff

- [2026-11-11-apache-harden-console-handoff.md](./2026-11-11-apache-harden-console-handoff.md) — audio Aliases removed, Console cookie gate, HTTP harden, **6.5.0-16** on lab.
- [2026-11-04-fail2ban-refresh-handoff.md](./2026-11-04-fail2ban-refresh-handoff.md) — Fail2Ban jail.d + Net panel, **6.5.0-15**.
- [2026-10-28-29-vmail-pjsip-trunk-handoff.md](./2026-10-28-29-vmail-pjsip-trunk-handoff.md) — vmail ageing cron, PJSIP trunk recreate after restore, **6.5.0-13 / 6.5.0-14**.
- [2026-10-26-27-ami-recordings-handoff.md](./2026-10-26-27-ami-recordings-handoff.md) — AMI bulk contacts, recording lockdown, **6.5.0-11 / 6.5.0-12**.
- [2026-10-25-26-php2shell-restore-ami.md](./2026-10-25-26-php2shell-restore-ami.md) — PHP2Shell, restore/AMI early fixes, **6.5.0-9 / 6.5.0-10**.

## Open TODOs

- [2026-10-26-todo-harden-audio-aliases.md](./2026-10-26-todo-harden-audio-aliases.md) — **done** in 6.5.0-16 (aliases + Console gate); kept for history.
- [2026-10-26-todo-apache-sark-includes.md](./2026-10-26-todo-apache-sark-includes.md) — finish or retire `sarkServerName` / `preventIpAccess` includes (never wired into Apache).
