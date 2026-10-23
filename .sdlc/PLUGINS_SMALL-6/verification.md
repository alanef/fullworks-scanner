# Combined Verification Report: PLUGINS_SMALL-6 (FSS)

**Issue:** free_plugin_lib: setup notice can't be dismissed and never goes away (all free plugins)
**Date:** 2026-10-23
**Commit:** FSS 5891e17; free_plugin_lib v1.3.3 (4f34052)

## Executive Summary

| Review | Status |
|--------|--------|
| Commit Integrity | not-applicable (the issue claims no FSS SHAs); change-set non-empty |
| Code Review | passed (library: independent review plus fixes, see SUE 1.7.10 evidence; FSS: 3-file diff reviewed) |
| Acceptance Review | passed for FSS |
| Browser-Test Gate | n-a: no layout change; the notice markup and redirects were checked over HTTP in FSS's wp-env |
| **Overall** | **APPROVED WITH NOTES** |

## Library evidence (shared with every plugin in the rollout)

free_plugin_lib v1.3.0..v1.3.3, alanef/free_plugin_lib:
- 35 PHPUnit tests.
- Every fix mutation-checked: each mutation is caught by the test aimed at it.
- An independent review found the 1.3.1 redirect regression, fixed in 1.3.2, which was reproduced and re-checked over HTTP.

Full report: stop-user-enumeration `.sdlc/PLUGINS_SMALL-6/verification.md` (SUE 1.7.10).

## FSS-specific changes

- `composer.json`: `alanef/free_plugin_lib` ^1.2.4 -> ^1.3.3, and `woocommerce/action-scheduler` `*` -> `~4.1.0`.
  - `*` resolved 4.2.0, which declares `Requires at least: 6.9`, while FSS declares 6.8.
  - The bundled copy is now Action Scheduler 4.1.0 (Requires at least 6.8) and library 1.3.3.
- `includes/class-uninstall.php`: calls `\Fullworks_Free_Plugin_Lib\Main::plugin_uninstall( 'FSS' )` at the end of the existing uninstall routine.
  - The library has not registered its own uninstall hook since 1.3.0.
  - WordPress includes the main file, and with it the autoloader, before running a `register_uninstall_hook` callback.

## Acceptance (FSS: `$page` = 'fullworks-scanner-settings', a top-level menu, shortname FSS)

HTTP checks in FSS's wp-env, as admin with `FSS_form_rendered` = 'rendering':

| Check | Result |
|---|---|
| Dashboard / admin-ajax heartbeat | 200 / 200 (no 1.3.1-style hijack) |
| Notice | `data-shortname="FSS"`, links to `options-general.php?page=ffpl-opt-in-FSS` |
| Dismiss AJAX | `{"success":true}`, `FSS_notice_dismissed`=1, notice gone on reload |
| `admin.php?page=fullworks-scanner-settings` | 302 to `ffpl-opt-in-FSS` |
| Skip | 302, status `optout`, settings page then 200 |
| Uninstall (`uninstall_plugin()` with `WP_UNINSTALL_PLUGIN`) | removes `FSS_form_rendered`, `FULLWORKS_SCANNER_*` options and the `fwvs_file_audit` table |
| Uninstall control, the same run without the new line | `FSS_form_rendered` stays `optout`, so the line is what removes it |

Also: `composer run check` clean; `npm test` OK (68 tests, 200 assertions); checks.yml green on 5891e17 (run 39921653050).

## Notes
- The notice does not appear on FSS's own settings screen (`toplevel_page_fullworks-scanner-settings` is not in the library's screen list). Undecided admins are redirected to the opt-in screen from there anyway, so it doesn't matter in practice. The changelog makes no claim about it.
- The fix applies on a multi-plugin site only once the first-loaded copy of the library is at least 1.3.2.

## Commit Range Covered

`5891e17` (single commit) on fullworks-scanner master. No out-of-range commits.

---
Verified by: AI Assistant
Date: 2026-10-23
