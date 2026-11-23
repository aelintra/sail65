# Session handoff — 2026-11-23 (evening, closed)

Stock `certbot.timer` vs SAIL LE renew ownership. Masked on live webo1001; packaged as **6.5.0-26** on **`main`** (`2ee73ad`).

Prior same day: [2026-11-23-trunk-static-contact-rtt-handoff.md](./2026-11-23-trunk-static-contact-rtt-handoff.md) (**6.5.0-25** static trunk RTT).

## Problem

- Ubuntu `certbot` enables `certbot.timer` (twice-daily `certbot renew`) with no Shorewall `:80` open/close and no `apply-active-cert.sh`.
- SAIL already renews via cron `17 3 * * *` → `le-renew.sh` (open `:80` → renew + deploy-hook apply → close).
- Dual paths: stock timer can fail HTTP-01 when public `:80` is closed; unwanted on local/snakeoil/vendor-cert installs that never elected LE.
- webo1001 (handoff 17/11): both paths still enabled; LE cert valid until **2027-02-15**.

## Live (webo1001) — done

- Confirmed: `certbot.timer` **enabled/active**; SAIL cron present; `sail-le-renew.log` shows open/apply/close; package **6.5.0-24** (trunk RTT still hot-patched from earlier).
- **Masked now:** `systemctl disable --now` + `mask` → unit masked/inactive. Renewals = SAIL cron only.

## Fix (6.5.0-26) — done

| File | Change |
|------|--------|
| `debian/postinst` | `disable --now` + **mask** `certbot.timer` (survives certbot package upgrades). |
| `debian/prerm` | On **remove** only: **unmask** (do not re-enable). |
| `le-renew.sh` | Exit 0 if `le-domain` absent — no `:80` open on non-LE installs. |
| `etc/cron.d/sark` | Comment notes no-op without `le-domain`. |

Deb: `sail_6.5.0-26_all.deb` on `main` (built on `.78`).

## Deploy / hosts (session end)

| Host | sail | Notes |
|------|------|--------|
| webo1001 | package **-24**; trunk RTT hot-patch (= **-25**); **certbot.timer masked** live | Install **6.5.0-26** when convenient (includes **-25** + timer mask in postinst). |
| `.135` | **6.5.0-24** | Optional upgrade to **-26**. |
| `main` | **6.5.0-26** | `2ee73ad` + deb. |

## Open / next

1. webo1001: `dpkg -i sail_6.5.0-26_all.deb` when convenient (timer already masked; brings trunk RTT into package).
2. LDAP Directory/tenant OU smoke on webo1001 (carried).
3. Optional: upgrade `.135` to **-26**; API pull on lab; `sark_includes`; docs; deb prune; missing **-16/17** debs.
