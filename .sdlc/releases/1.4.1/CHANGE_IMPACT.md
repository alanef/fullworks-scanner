# Change Impact Assessment: 1.4.1

## Risk Level: Low

### Factors Considered
- Number of issues: 3 (one is tooling)
- Security-sensitive changes: none to the scanner. The library AJAX handlers keep their capability and nonce checks, and skip gains a capability check.
- API changes: no
- Database changes: none at runtime. Uninstall also deletes `FSS_form_rendered`.
- Breaking changes: no (WordPress minimum stays 6.8)

## Affected Areas

### Code Areas
- Bundled `alanef/free_plugin_lib` 1.2.4 -> 1.3.3: the opt-in notice, dismiss, the redirect from FSS settings to the opt-in screen.
- `includes/class-uninstall.php`: one added call.

### Dependencies
- free_plugin_lib ^1.3.3.
- Action Scheduler `*` -> `~4.1.0`. It was 4.1.0 in 1.4.0, so the shipped version is unchanged.

## Behaviour change users will notice
- Admins who left the opt-in screen without choosing are sent there again when they open the scanner settings, until they click Allow or Skip.
- On multi-plugin sites, the first-loaded copy of the library serves every Fullworks plugin. The fix applies once that copy is at least 1.3.2.

## Rollback Plan
Release the 1.4.0 tree as 1.4.2. Builds would then also need the Action Scheduler pin, because 1.4.0's `*` now resolves to 4.2.0.

### Database / Configuration Rollback
N/A

## Monitoring
- wp.org support forum for redirect or opt-in reports
- Action Scheduler queue health (unchanged version)
