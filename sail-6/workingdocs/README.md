# workingdocs

Agent/session handoff notes for SAIL 6.5 work. Keep entries dated and factual so the next agent can continue without re-discovering context.

Do **not** store secrets (sudo passwords, AMI secrets, customer credentials) here.

## Lab hosts

| Role | Host | Access |
|------|------|--------|
| **Deb builder** | `192.168.1.78` (`deb11-1`) | SSH `tech@192.168.1.78`. Repos under `~/Git/` (sail65, etc.). Build: `cd ~/Git/sail65/sail-6 && debuild --no-lintian -us -uc -b` → `~/Git/sail65/sail_*_all.deb`. Cannot push to GitHub over HTTPS; push from Mac. |
| Lab / customer rebuild | `192.168.1.130` | SSH `tech@192.168.1.130`. **Passwordless sudo** (`sudo -n`). |
| Other test | `192.168.1.12` (`iitac2404`) | SSH `tech@192.168.1.12`. |

See dated session handoffs for fuller context.

## Open TODOs

- [2026-10-26-todo-harden-audio-aliases.md](./2026-10-26-todo-harden-audio-aliases.md) — close `/server-vmail` (sarkphone play → authenticated helper); review sounds/MOH Indexes.
- [2026-10-26-todo-apache-sark-includes.md](./2026-10-26-todo-apache-sark-includes.md) — finish or retire `sarkServerName` / `preventIpAccess` includes (never wired into Apache).
