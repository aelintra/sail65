# Session handoff — 2026-11-23 (closed)

Trunks list missing latency/IP for **static AOR** SIP trunks (Magrathea on webo1001). Fixed as **6.5.0-25**, verified on webo1001, packaged and pushed to **`main`** (`3c0332b`).

Prior: [2026-11-19-lab23-webrtc-dependabot-handoff.md](./2026-11-19-lab23-webrtc-dependabot-handoff.md).

## Problem

- Trunk `MagratheaCLI` → `sip:sipipgw.magrathea.net` (`contact=` on AOR, `max_contacts=0`).
- CLI `pjsip show contacts`: **Avail** with RTT (~20–40 ms). Magrathea OPTIONS often **403** but qualify still marks Reachable (not 2xx-only).
- AMI `PJSIPShowContacts`: only **dynamic** contacts (e.g. 9003/9004/9005). Permanent Magrathea contact **absent** → trunks panel State/IP **Unknown**.
- Panel reads AMI only (`get_pjsip_array` → `RoundtripUsec`).

## Fix (6.5.0-25) — done

| File | Change |
|------|--------|
| `srkAmiHelperClass` | After bulk `PJSIPShowContacts`, optional fallback `PJSIPShowEndpoint` / `ContactStatusDetail` for missing endpoints. IP: prefer URI if IP, else first Identify `Match` IP, else hostname. |
| `sarktrunk/view.php` | `get_pjsip_array($rows, true)` — fallback **on** for trunks only. |
| Extensions | Unchanged (`fallback` default false) — no N× AMI regression. |
| Registered trunks | Unchanged happy path (already in Contacts). |

Deb: `sail_6.5.0-25_all.deb` on `main` (built on `.78`).

## Verify (webo1001) — done

- CLI smoke (`srkDbClass` + `get_pjsip_array(..., true)`) → IP + `OK (N ms)`.
- Trunks UI: MagratheaCLI latency + IP (e.g. `87.238.73.137` from Match).

## Deploy / hosts (session end)

| Host | sail | Notes |
|------|------|--------|
| webo1001 | **hot-patched** PHP (= `-25` behaviour); package still **-24** until `dpkg -i` | UI confirmed OK. Install **6.5.0-25** when convenient for clean package state. |
| `.135` | **6.5.0-24** | Optional upgrade to **-25**. |
| `main` | **6.5.0-25** | `3c0332b` + deb. |

## Open / next

1. webo1001: `dpkg -i sail_6.5.0-25_all.deb` when convenient (hot-copy already live).
2. LDAP Directory/tenant OU smoke on webo1001 (carried from prior handoff).
3. Optional: upgrade `.135` to **-25**; API pull on lab; `sark_includes`; docs; deb prune; missing **-16/17** debs.
