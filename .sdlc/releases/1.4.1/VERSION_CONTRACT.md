# Version Contract: 1.4.1

**Date:** 2026-10-23

## Canonical Source

These are set and read back by `scripts/release/bump-version.sh`:
- the plugin header `Version:` in fullworks-scanner/fullworks-vulnerability-scanner.php
- `Stable tag:` in fullworks-scanner/readme.txt
- `FULLWORKS_SCANNER_PLUGIN_VERSION`

## Expected State After Deploy

- master has every version location at `1.4.1`, and `= 1.4.1 =` at the top of changelog.txt.
- Tag `v1.4.1` exists on master HEAD. release.yml builds the zip, creates the GitHub release and deploys to wp.org SVN (`Type: free`).
- After the wp.org release is confirmed, the directory serves 1.4.1.
- The zip bundles free_plugin_lib 1.3.3 and Action Scheduler 4.1.x.

## Required Deploy Command

```
/tk-deploy live
```

## Post-Deploy Verification

See `.claude/commands/deploy-live.md`.
