# Session handoff — 2026-11-23

Trunks list missing latency/IP for **static AOR** SIP trunks (Magrathea on webo1001). Fixed in **6.5.0-25**; verified on webo1001 before package.

Prior: [2026-11-19-lab23-webrtc-dependabot-handoff.md](./2026-11-19-lab23-webrtc-dependabot-handoff.md).

## Problem

- Trunk `MagratheaCLI` → `sip:sipipgw.magrathea.net` (`contact=` on AOR, `max_contacts=0`).
- CLI `pjsip show contacts`: **Avail** with RTT (~20–40 ms). Magrathea OPTIONS often **403** but qualify still marks Reachable (not 2xx-only).
- AMI `PJSIPShowContacts`: only **dynamic** contacts (e.g. 9003/9004/9005). Permanent Magrathea contact **absent** → trunks panel State/IP **Unknown**.
- Panel reads AMI only (`get_pjsip_array` → `RoundtripUsec`).

## Fix (6.5.0-25)

| File | Change |
|------|--------|
| `srkAmiHelperClass` | After bulk `PJSIPShowContacts`, optional fallback `PJSIPShowEndpoint` / `ContactStatusDetail` for missing endpoints. IP: prefer URI if IP, else first Identify `Match` IP, else hostname. |
| `sarktrunk/view.php` | `get_pjsip_array($rows, true)` — fallback **on** for trunks only. |
| Extensions | Unchanged (`fallback` default false) — no N× AMI regression. |
| Registered trunks | Unchanged happy path (already in Contacts). |

## Verify (webo1001)

- CLI smoke with `srkDbClass` + `get_pjsip_array(..., true)` → host/IP + `OK (N ms)`.
- Trunks UI: MagratheaCLI latency + IP (e.g. `87.238.73.137` from Match).

## Deploy / hosts

| Host | Notes |
|------|--------|
| webo1001 | Hot-copied PHP during diagnose; install **6.5.0-25** deb for clean package state. |
| `.135` | Optional upgrade when convenient. |
| `main` | Release commit + `sail_6.5.0-25_all.deb`. |

## Open / next

- LDAP Directory/tenant OU smoke on webo1001 (still from prior handoff).
- Optional: API pull on lab `.135`; `sark_includes`; docs; deb prune; missing **-16/17** debs.
