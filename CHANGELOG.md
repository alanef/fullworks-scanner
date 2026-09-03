# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.0] - 2026-09-03

### Changed
- Tested up to WordPress 7.1.
- Minimum WordPress version is now 6.8, required by the bundled Action Scheduler 4.1.
- Updated bundled libraries: Action Scheduler 3.9.2 to 4.1.0 and the Fullworks opt-in library 1.0.1 to 1.2.4. The 1.2.4 library no longer shows a duplicate advert when another Fullworks plugin is active.
- Translations are loaded by WordPress.org automatically; the plugin no longer calls `load_plugin_textdomain`.

### Fixed
- "Translation loading for the fullworks-scanner domain was triggered too early" notice on WordPress 6.7 and later. Settings labels and the white-label title are now built after `init`.
- The report table sort order was ignored. Clicking a column heading now sorts the report.
- The summary email waited on the wrong job group, so it could be sent before plugin scans had finished.
- The "auto update not working" check never found the recorded update time because of a stray space in the option name.
- Network activation on a newly created site used a deprecated hook (`wpmu_new_blog`) and the wrong plugin file name.
- Admin styles and scripts were registered under the version number instead of the plugin name.
- The internal version constant was stuck at 1.1.1.
- Re-scanning an issue that was already recorded updated the wrong row (the row count was used as the row ID).
- The opt-in registration used the shortname `FSS-Free`, which the opt-in service does not recognise; it is now `FSS`. The first-visit opt-in prompt also now knows the settings page slug.

## [1.3] - 2025-03-03

### Added
- Opt in to Fullworks news and offers.

## [1.2.0]

### Added
- WP CLI command `wp fullworks-scanner`.

## [1.1.1]

### Added
- Rescan now button on the report.

## [1.1.0]

### Added
- Auto updates are taken into consideration and not reported unless not updated for several days.
- Change log summary on the report page for plugins with updates available.
- Report uses names and allows sorting of names.

## [1.0.2]

### Changed
- Slug changed to fullworks-scanner.

## [1.0.1]

### Fixed
- Plugin review feedback incorporated.

## [1.0.0]

- Initial version.
