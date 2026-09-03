# Fullworks Security Scanner

A WordPress plugin that checks the installed WordPress core, plugins and themes against
the [WP Vulnerability](https://www.wpvulnerability.net/) database, flags abandoned or
removed plugins and themes, and emails a summary report.

[![Plugin Check](https://github.com/alanef/fullworks-scanner/actions/workflows/checks.yml/badge.svg)](https://github.com/alanef/fullworks-scanner/actions/workflows/checks.yml)

WordPress.org: https://wordpress.org/plugins/fullworks-scanner/

## Features

- Checks core, plugins and themes for known vulnerabilities
- Detects plugins removed from WordPress.org and plugins not tested against recent releases
- Reports available updates, with the latest changelog entry inline
- Scheduled scans via Action Scheduler, with a "rescan now" button
- Email summary of critical issues and warnings
- `wp fullworks-scanner` WP-CLI command

## Project structure

```
fullworks-scanner/                     # Repository root: development tooling
├── .github/workflows/                 # CI: checks on push, release on tag
├── tests/                             # PHPUnit suite (runs inside wp-env)
├── .wp-env.json                       # wp-env config (dev :8730, tests :8731)
├── composer.json                      # Dev dependencies and scripts
├── package.json                       # wp-env and test scripts
├── phpcs_sec.xml                      # PHPCS security ruleset
├── phpunit.xml.dist
├── run-tests.sh
├── CHANGELOG.md
└── fullworks-scanner/                 # The plugin
    ├── admin/
    ├── includes/
    │   └── vendor/                    # Production dependencies
    ├── languages/
    ├── .distignore                    # Excluded from the release zip
    ├── composer.json                  # Plugin dependencies
    ├── readme.txt
    └── fullworks-vulnerability-scanner.php
```

## Development

```bash
git clone https://github.com/alanef/fullworks-scanner.git
cd fullworks-scanner
composer install                       # dev tools (phpcs, phpunit)
composer install -d fullworks-scanner  # plugin dependencies
npm install
npm run start                          # http://localhost:8730  (admin / password)
```

### Quality checks

```bash
composer run check    # PHPCompatibility 7.4-8.4 + WordPress security sniffs
```

### Tests

```bash
npm test                          # PHPUnit in the wp-env tests container
npm test -- --filter VulnDB       # pass PHPUnit options through
```

### Build

```bash
wp package install wp-cli/dist-archive-command   # once
composer run build                               # zipped/fullworks-scanner-free.zip
```

## Release

See [CLAUDE.md](CLAUDE.md#release). In short: finalise `CHANGELOG.md`, set the version in
the plugin header, version constant and `readme.txt` stable tag, tag `vX.Y.Z` and push.
The release workflow builds the zip, creates the GitHub release and deploys to WordPress.org.

## License

GPL-3.0-or-later. See [licence.txt](fullworks-scanner/licence.txt).
