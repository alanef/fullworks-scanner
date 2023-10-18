=== Fullworks Security Scanner ===
Contributors: Fullworks
Tags: Security, vulnerabilities, plugin update, security
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.2
Stable tag: 1.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Fullworks Security Scanner examines your Core version, themes and plugins and reports on any issues detected by referencing the WordPress Vulnerability Database API and checking for abandoned plugins and themes.

== Description ==

This plugin checks your WordPress Core version, installed themes, and plugins, and generates a detailed report of any vulnerabilities found and emails to notify you that there is something to check. The scan results are obtained by referencing the comprehensive [WordPress Vulnerability Database API](https://vulnerability.wpsysadmin.com/).

Additionally, Fullworks Security Scanner will also alert you of any abandoned plugins or themes, which can pose a potential security risk to your website. With this plugin, you can easily keep your website secure and reduce the risk of security breaches.

The report page allows you accept any warnings or errors noted, for instance you may have a theme that is no longer supported but you are happy with it and don't want to change it. You can also ignore any plugins or themes that you know are not vulnerable.

The report also shows an extract of the change log where available for plugins that have updates available, to enable quick review of the changes.

Also you can run an audit from WP CLI by running `wp fullworks-scanner`. The WP CLI does not generate an email report but will show the results in the console.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/fullworks-scanner` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Fullworks Vulnerability Scanner screen to configure the plugin

== Frequently Asked Questions ==

= How often does the scan run? =
The scan runs once a day by default. You can change this in the settings.
= How do I change the email address that the scan results are sent to? =
You can change the email address in the settings.
= How can I run from WP CLI? =
You can run the scan from WP CLI by running `wp fullworks-scanner`
= If I run this only manually e.g. using WP CLI how do I stop scheduled scans? =
You can disable scheduled scans in the settings by setting the schedule to blank.
= How can I report asecurity bug with this plugin?
You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability.](https://patchstack.com/database/vdp/fullworks-scanner)


== Screenshots ==

1. Example scan report.

== Changelog ==
= 1.2.0 =
* Add WP CLI

= 1.1.1 =
* Merge in  'Add rescan now button to report'

= 1.1.0 =
* Take into consideration auto updates and dont report unless not updated for several days
* Display Change log summary on the report page for plugins with updates available
* Change report to names  and allow sorting of names in report
* Add rescan now button to report

= 1.0.2 =
* Changed slug to fullworks-scanner

= 1.0.1 =
* Plugin review feedback incorporated

= 1.0.0 =
* Initial version



