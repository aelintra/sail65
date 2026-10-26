# PHP2Shell go-live — Ubuntu 24.04

CVE-2026-109618 and CVE-2026-109619 (php2shell) are critical pre-auth RCE bugs
in the **PHP runtime**, not in SAIL application code. SAIL uses Apache with
`libapache2-mod-php`, which PHP rates as the highest-risk SAPI. Application
login and firewall changes do not replace a patched PHP binary.

## Fixed package (Ubuntu 24.04 / noble)

| Package | Minimum fixed revision |
| --- | --- |
| `libapache2-mod-php8.3` (and related `php8.3-*`) | `8.3.6-0ubuntu0.24.04.13` (USN-8948-1) |

Ubuntu keeps base version `8.3.6` and backports the fix into the package
revision. Do **not** require upstream `8.3.36`. Always compare with
`dpkg --compare-versions`.

## Install / upgrade steps

Run on the new host **before exposing the admin UI**, ideally before or right
after installing the `sail` package:

```bash
sudo apt update
sudo apt install --only-upgrade \
  php8.3-cli php8.3-common libapache2-mod-php8.3 \
  php8.3-mbstring php8.3-gd php8.3-sqlite3 php8.3-curl \
  php8.3-mysql php8.3-ldap

dpkg-query -W -f='${Package} ${Version}\n' libapache2-mod-php8.3 php8.3-cli

dpkg --compare-versions \
  "$(dpkg-query -W -f='${Version}' libapache2-mod-php8.3)" \
  ge "8.3.6-0ubuntu0.24.04.13" && echo "PHP2Shell package OK"

# Optional: confirm CVE IDs appear in the package changelog
apt-get changelog libapache2-mod-php8.3 | grep -E 'CVE-2026-10961[89]'

# Required: running workers keep the old binary until restart
sudo systemctl restart apache2
```

Confirm the drop-in is active (shipped by sail ≥ 6.5.0-9):

```bash
php -i | grep session.upload_progress.enabled
# expect: session.upload_progress.enabled => Off => Off
```

That setting is defense-in-depth only. It does not replace the patched packages.

## Firewall

Shipped Shorewall defaults are unchanged. The customer sets firewall rules after
the initial install. Prefer restricting HTTP/HTTPS to trusted networks before
the host is production-facing, but that is an operational step, not a package
change.

## Restore data from the old system

Use a backup taken on the currently running older PBX (`sarkbak.*.zip` from
Backup / `spin.sh`), then restore with the existing **Backup** UI loader:

1. Copy the zip onto the new host (or upload via the Backup page).
2. Place it under `/opt/sark/bkup/` if needed, or use **Upload** in Backup.
3. Open **Backup** (`/php/sarkbackup/`), select the backup, restore with admin
   password, and choose which components to apply (database, Asterisk files,
   greetings, voicemail, LDAP as present in the zip).
4. The loader runs `srkV4reloader.sh` after DB restore and requests an Asterisk
   regen/reload when finished.

Shorewall rules restored from the backup (if included) or tuned by the customer
after install should be reviewed once the system is reachable.

## Post-deploy checklist

- [ ] `libapache2-mod-php8.3` ≥ `8.3.6-0ubuntu0.24.04.13`
- [ ] Apache fully restarted after the PHP upgrade
- [ ] `session.upload_progress.enabled` is Off under Apache PHP
- [ ] Old-system backup restored via sarkbackup; dialplan/SIP verified
- [ ] Customer firewall rules applied and reviewed
- [ ] No unexpected shell processes spawned by Apache/PHP workers
