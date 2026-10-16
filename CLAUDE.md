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

## Changelog and versions

One changelog: `fullworks-scanner/changelog.txt`, wp.org style, shipped in the zip and published
to the KB by `update-changelog.sh` where `.env.wordpress` exists. `readme.txt` may link to it
or repeat recent entries, but is not the source. Every user-visible change adds a typed bullet
under `= Unreleased =` at the top, in the same commit:

```
= Unreleased =
* Fixed: what was wrong, and for whom
* Added: / Changed: / Deprecated: / Removed: / Security:
```

While developing, carry a prerelease suffix (`1.4.0-alpha.1`, bump only the `N`); release.yml
refuses to publish one. Releasing drops it.

## Release

Always through the commands, never by hand:

```
/tk-release X.Y.Z    # scripts/release/bump-version.sh: every version location + changelog, read back
                     # scripts/release/check-dependencies.sh: shipped composer deps block, tooling warns
                     # then commit the release ("Release X.Y.Z") and push
                     # scripts/release/run-tests-and-mark.sh X.Y.Z: CI's gates locally (wp-env running)
                     # commit .sdlc/releases/X.Y.Z/ evidence and push
/tk-deploy live      # .claude/commands/deploy-live.md: preconditions, confirm, tag vX.Y.Z, watch CI
```

`/tk-release` runs the test hook before you commit, so run it **again after** the release
commit: its marker names the commit, and `/tk-deploy live` refuses it if plugin code changed
since. The tag runs `Build Release`,
which re-runs the checks, creates the GitHub release with the zip attached, then deploys to
whatever the plugin's `Type:` calls for.

## Where a release goes

`Build Release` reads a `Type:` header from the plugin's `readme.txt` and deploys
accordingly. Every plugin gets a GitHub release; `Type:` only decides what else:

| `readme.txt` | GitHub | WordPress.org | Freemius |
|---|---|---|---|
| *(no `Type:` header)* | yes | | |
| `Type: free` | yes | yes | |
| `Type: freemium` | yes | yes | yes |
| `Type: premium` | yes | | yes |

Omitting the header is the safe default -- a plugin is never published anywhere
public by accident. A header that is present but unrecognised **fails the
release**, because that is a typo rather than a choice, and silently falling back
to GitHub-only is how a plugin quietly stops reaching WordPress.org for months.
The resolved targets are echoed in the job log and the run summary.

With no `Type:` header the plugin also **updates itself from its GitHub releases** (managed
`includes/class-github-updater.php` and `Update URI:` header, both added by the sync). Adding a
`Type:` and re-syncing removes them, since WordPress.org forbids off-site updates.

WordPress.org deploys need the `SVN_USERNAME` and `SVN_PASSWORD` repository
secrets; the step fails loudly if they are missing or the SVN tag already exists.
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
