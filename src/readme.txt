=== Fullworks Security Scanner ===
Contributors: Fullworks
Tags: security, malware, scanner, stop hackers, prevent hacks, secure wordpress, wordpress security
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.4
Stable tag: 1.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Fullworks Security Scanner: Your Website's Guardian. Core, Themes, Plugins - Checked. Vulnerabilities - Squashed. Your Site - Secure.

== Description ==

This plugin is your website's security sentinel, keeping an eye on potential threats without making it sound too techy. It checks your WordPress core, themes, and plugins to find any weak spots. If it spots something amiss, you'll get a friendly email alert to let you know there's something worth looking at.

This clever tool relies on a powerful database to get its info, helping it keep an eye out for common WordPress vulnerabilities.

But it's not just about vulnerabilities. The plugin also keeps an eye on abandoned plugins and themes, which can be a backdoor for trouble. With this plugin, you can easily keep your website secure and reduce the risk of security breaches.

The report page gives you the power to decide what to do next. If it finds something, you can choose to ignore it, especially if you have an old theme you love but it's no longer getting updates. You can also give a thumbs-up to plugins or themes you know are safe.

Plus, the report gives you a quick peek at what's changed with plugins that have updates available. This helps you decide if those updates are worth it.

If you're more of a command-line person, you can run a security audit with the command wp fullworks-scanner. It won't send you an email, but it'll tell you what it found right there in your console.

In a nutshell, this handy WordPress security tool does the heavy lifting, making sure your site stays safe and sound. It's like having a watchful eye on your website without all the jargo


= Key Features =
&#x2713; Comprehensive Scan: Checks your WordPress Core version, installed themes, and plugins for vulnerabilities.
&#x2713; Vulnerability Database: References the WordPress Vulnerability Database API for up-to-date threat information.
&#x2713; Abandoned Plugin/Theme Detection: Identifies and alerts you about abandoned plugins or themes that can pose security risks.
&#x2713; Customizable Scanning Schedule: Adjust the scan frequency to match your needs.
&#x2713; User-Friendly Report: Provides detailed scan reports with the option to accept or ignore warnings and errors.
&#x2713; Change Log Highlights: Offers a quick review of plugin updates with extract from the change log.
&#x2713; WP CLI Integration: Run scans from the command line with the wp fullworks-scanner command.

= Easy to Use =

Don't miss out on the opportunity to fortify your WordPress website effortlessly and stay one step ahead of potential threats.
Try it now and experience the peace of mind that comes with superior website security. &#x1F512; &#x1F512; &#x1F512;

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/fullworks-scanner` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->Fullworks Vulnerability Scanner screen to configure the plugin

== Frequently Asked Questions ==

=What is the WordPress Vulnerability Database API, and how does it benefit the scan?=
The [WordPress Vulnerability Database API](https://vulnerability.wpsysadmin.com/) is a comprehensive resource that helps us identify potential security issues in your website's core, themes, and plugins, making your scan more robust.
=Can I customize the scan schedule to run more or less frequently than once a day?=
Yes, you have the flexibility to adjust the scan schedule in the plugin settings to better suit your needs.
=How do I uninstall or deactivate the Fullworks Security Scanner if I no longer need it?=
You can easily deactivate or uninstall the plugin from your WordPress dashboard by navigating to the plugins section.
=What kind of information is included in the email notification for scan results?=
The email notification typically contains a summary of the scan findings, including any vulnerabilities or issues detected in your WordPress setup.
=Can I exclude specific themes or plugins from being scanned?=
Yes, you can customize the scan to exclude specific themes or plugins that you don't want to be checked for vulnerabilities.
=Is my data safe during the scanning process?=
Rest assured, your website data is not accessed or compromised during the scan. The plugin focuses solely on the security aspects of your WordPress installation.
=How can I stay informed about updates and new features for Fullworks Security Scanner?=
To stay updated, you can subscribe to our newsletter or check our website for announcements regarding updates and new features.
=What should I do if I receive a false positive or false negative in the scan results?=
If you suspect an incorrect result, you can reach out to our support team for further assistance in interpreting the findings.
=Can I run the scan on multiple websites using a single installation of the plugin?=
Yes, you can use the same installation to manage multiple websites and receive scan results for each one.
=How can I provide feedback or suggest improvements for the Fullworks Security Scanner?=
We welcome your input! Feel free to provide feedback via the [WordPress plugin repository support forum](https://wordpress.org/support/plugin/fullworks-scanner/) .
= How often does the scan run? =
The scan runs once a day by default. You can change this in the settings.
= How do I change the email address that the scan results are sent to? =
You can change the email address in the settings.
= How can I run from WP CLI? =
You can run the scan from WP CLI by running `wp fullworks-scanner`
= If I run this only manually e.g. using WP CLI how do I stop scheduled scans? =
You can disable scheduled scans in the settings by setting the schedule to blank.
= How can I report a security bug with this plugin?
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



