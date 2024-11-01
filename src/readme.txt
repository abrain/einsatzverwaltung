=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.org/unterstuetzen/
Tags: Feuerwehr, fire department, EMS
Requires at least: 5.6.0
Tested up to: 6.6
Requires PHP: 7.1.0
Stable tag: 1.12.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Public incident reports for fire departments and other rescue services

== Description ==

This plugin lets you create incident reports to inform the public about your work. Although initially developed for fire departments, it is also used by emergency medical services, mountain rescue services, water rescue services, and others.

You can assign vehicles, units, type of incident, types of alerting, and external resources to each report. There are shortcodes and widgets to list the reports, optionally filtered by unit. The reports have a default layout, but you can also get creative with the templating feature. Reports can be imported and exported, creating and editing reports can be restricted to certain user roles.

Significant parts of the plugin are still only available in German. Any help with translating those parts into English would be very welcome.

= Acknowledgements =

Uses Font Awesome by Dave Gandy - http://fontawesome.io

= Social Media =

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)

== Installation ==

The plugin does not require any setup, but it is recommended to take a look at the settings before you start publishing. Especially those in the Advanced section should not be changed inconsiderately later on.

== Frequently Asked Questions ==

= Is this the WordPress plugin for einsatzverwaltung.eu? =

No, this plugin has no affiliation with einsatzverwaltung.eu.

= Is there a manual? =

Yes, there is more [documentation](https://einsatzverwaltung.org/dokumentation/) on our website.

= Where should I send feature requests and bug reports to? =

Ideally, you check the issues on [GitHub](https://github.com/abrain/einsatzverwaltung/issues) if this has been addressed before. If not, feel free to open a new issue. You can also post a new topic on the support forum instead.

= Are there more FAQ? =

Yes, you can find them on [our website](https://einsatzverwaltung.org/faq/).

== Changelog ==

= 1.12.0 =
* Report number can be changed with QuickEdit
* Show more details in case of import errors
* More details can be used with Elementor
* Optionally show a range of report numbers
* Comments can be enabled for reports

= 1.11.2 =
* Fix: Content was duplicated when using the Avada Website Builder
* Fix: Prevent linebreaks for certain columns of the report list

= 1.11.1 =
* Reduce collisions with other occurrences of Font Awesome
* Add option to disable Font Awesome

= 1.11.0 =
* Alerting Methods: Can now be linked with a page from the same site or an arbitrary URL
* Shortcode `reportcount` can ignore weights
* Upgraded Font Awesome from version 4 to version 6
* Improved compatibility with PHP 8.2
* Dropped support for WordPress 5.5 and older

== Upgrade Notice ==
= 1.12.0 =
Minor enhancements, better compatibility with Elementor

= 1.11.2 =
Maintenance Release

= 1.11.1 =
Maintenance Release

= 1.11.0 =
Minor enhancements, upgraded Font Awesome, improved compatibility with PHP 8.2
