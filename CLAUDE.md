<!-- tooling:start (managed by wordpress-plugin-boilerplate/tooling - do not edit by hand) -->
# Fullworks Security Scanner - Development Guide

Tooling in this repository is standardised across the Fullworks free plugins. The master
description lives in
[wordpress-plugin-boilerplate/CLAUDE.md](https://github.com/alanef/wordpress-plugin-boilerplate/blob/main/CLAUDE.md).
**Fix tooling problems there first, then roll out** with its `bin/sync-tooling.sh`; never
hand-edit the managed files listed there.

## This repository

| | |
|---|---|
| Plugin directory | `fullworks-scanner/` |
| Main file | `fullworks-scanner/fullworks-vulnerability-scanner.php` |
| Default branch | `master` |
| WordPress.org slug | `fullworks-scanner` |
| wp-env ports | dev `8730`, tests `8731` |
| Version locations | plugin header `Version:`, `readme.txt` `Stable tag:` and `FULLWORKS_SCANNER_PLUGIN_VERSION` in the main file |

CI fails when the version locations disagree.

## Commands

```bash
composer install && npm install   # first time
composer run check                # PHPCompatibility + WordPress security sniffs
npm run start                     # wp-env (dev :8730, tests :8731, admin/password)
npm test                          # PHPUnit inside the wp-env tests container
npm test -- --filter Foo          # pass PHPUnit args through
composer run build                # zipped/fullworks-scanner-free.zip via wp dist-archive
```

## Release

1. Update `CHANGELOG.md` (move Unreleased to the version and date).
2. Set the version in every location above (no prerelease suffix).
3. `composer run check && npm test`.
4. Commit, tag `vX.Y.Z`, push branch and tag.
5. The `Build Release` workflow re-runs the checks, creates the GitHub release with the zip
   attached and deploys trunk + tag to WordPress.org SVN (needs `SVN_USERNAME` and
   `SVN_PASSWORD` repository secrets).
<!-- tooling:end -->

# Fullworks Security Scanner - Development Guide

Repository layout: development tooling lives at the repository root; the shippable
plugin lives in `fullworks-scanner/`. Production dependencies are installed to
`fullworks-scanner/includes/vendor/`, development dependencies to the root `vendor/`.

## Version locations

The version is kept in three places and CI fails if they differ:

1. `fullworks-scanner/fullworks-vulnerability-scanner.php` - plugin header `Version:`
2. `fullworks-scanner/fullworks-vulnerability-scanner.php` - `define( 'FULLWORKS_SCANNER_PLUGIN_VERSION', '...' )`
3. `fullworks-scanner/readme.txt` - `Stable tag:`

Check with:

```bash
grep -n "Version:\|PLUGIN_VERSION\|Stable tag" fullworks-scanner/fullworks-vulnerability-scanner.php fullworks-scanner/readme.txt
```

During development the version carries a prerelease suffix (`1.4.0-alpha.1`).
The release workflow refuses to publish a version containing `-`.

## Changelog

`CHANGELOG.md` at the repository root is the single changelog (Keep a Changelog format).
Every user-visible change gets an entry under `## [Unreleased]` in the same commit.
`readme.txt` links to it; do not duplicate entries there.

## Quality checks

```bash
composer run check        # PHPCompatibility 7.4-8.4 + WordPress security sniffs
composer run phpcs        # security sniffs only
```

## Tests

PHPUnit runs inside the wp-env `tests` container against the WordPress core test
library. The repo root is mapped into the container (`tests/`, `vendor/`, `phpunit.xml.dist`).

```bash
composer install && npm install   # first time
npm run start                     # wp-env: dev site on :8730, tests site on :8731
npm test                          # runs ./run-tests.sh
npm test -- --filter Utilities    # pass PHPUnit args through
```

Test files live in `tests/` and are named `test-*.php`. HTTP calls are mocked with
the `pre_http_request` filter; nothing in the suite touches the network.

To try a different PHP version locally copy `.wp-env.override.json.example` to
`.wp-env.override.json` and run `npm run start` again.

## Build

```bash
composer run build   # -> zipped/fullworks-scanner-free.zip via wp dist-archive (.distignore applies)
```

Requires `wp package install wp-cli/dist-archive-command` once.

## Release

1. Move `## [Unreleased]` in `CHANGELOG.md` to `## [X.Y.Z] - YYYY-MM-DD`.
2. Set the version (drop the prerelease suffix) in the three locations above.
3. Run `composer run check` and `npm test`.
4. Commit, then tag and push:
   ```bash
   git tag vX.Y.Z
   git push origin master --tags
   ```
5. The `Build Release` workflow re-runs the checks, creates the GitHub release with
   the zip attached and deploys trunk + tag to WordPress.org SVN.

The deploy step needs the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets.
