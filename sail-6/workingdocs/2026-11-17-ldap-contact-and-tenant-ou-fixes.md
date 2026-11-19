# LDAP contact add and tenant OU fixes (sail65 port)

**Date:** 2026-11-17 (ported); release **6.5.0-23** 2026-11-18  
**Branch:** `feature/letsencrypt-apache-65018` (LDAP cherry-pick from `fix/ldap-contact-and-tenant-ou`)  
**Source:** sail6 `workingdocs/ldap-contact-and-tenant-ou-fixes.md` (`vrev` → `main`, `306a64a`)

## Summary

Ported the sail6 Directory/`ldaphelper` fixes. Contact adds via `sarkldap` could fail (missing RDN `uid`, empty `o` → Invalid syntax). New tenants never got a matching LDAP OU under `ou=contacts`.

## Changes

| File | Change |
|------|--------|
| `opt/sark/php/srkLDAPHelperClass` | `Add()`: set `uid`, strip empty attrs, better error log; `AddNewTenant()`: log LDAP error text; success says tenant OU |
| `opt/sark/php/sarkldap/view.php` | Org empty-check used `$_POST['phone']` — fixed to `$_POST['org']` |
| `opt/sark/php/sarkcluster/view.php` | After successful tenant insert, `AddNewTenant($pkey)`; DB tenant kept on LDAP failure with UI warning |

Does **not** create per-tenant security objects. Does **not** delete LDAP OU on tenant delete.

## Repair existing systems

OU-only backfill for all clusters:

```bash
php -r '
include "/opt/sark/php/srkLDAPHelperClass";
include "/opt/sark/php/srkHelperClass";
include "/opt/sark/php/srkDbClass";
$helper = new helper;
$ldap = new ldaphelper;
if (!$ldap->Connect()) { fwrite(STDERR, "connect failed\n"); exit(1); }
foreach ($helper->getTable("cluster") as $row) {
  echo $row["pkey"] . ": " . $ldap->AddNewTenant($row["pkey"]) . "\n";
}
$ldap->Close();
'
```

Or `php /opt/sark/generator/fixupTenantLdap.php` if security objects are also wanted.

## Verify

- Add contact under any tenant OU
- Leave organisation empty
- New tenant creates corresponding LDAP OU
