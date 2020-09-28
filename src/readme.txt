=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, Hilfsorganisation, fire brigade, rescue services, EMS
Requires at least: 4.7.0
Tested up to: 5.5
Requires PHP: 5.6.0
Stable tag: 1.7.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Public incident reports for fire brigades and other rescue services

== Description ==

This plugin lets you create incident reports to inform the public about your work. Although initially developed for fire brigades, it is also used by emergency medical services, mountain rescue services, water rescue services, and others.

You can assign vehicles, units, type of incident, types of alerting, and external resources to each report. There are shortcodes and widgets to list the reports, optionally filtered by unit. The reports have a default layout, but you can also get creative with the templating feature. Reports can be imported and exported, creating and editing reports can be restricted to certain user roles.

Significant parts of the plugin are still only available in German. Any help with translating those parts into English would be very welcome.

= Acknowledgements =

Uses Font Awesome by Dave Gandy - http://fontawesome.io

= Social Media =

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)

== Installation ==

The plugin does not require any setup but it is recommended to take a look at the settings before you start publishing. Especially those in the Advanced section should not be changed inconsiderately later on.

== Frequently Asked Questions ==

= Is this the WordPress plugin for einsatzverwaltung.eu? =

No, this plugin has no affiliation with einsatzverwaltung.eu.

= Is there a manual? =

Yes, there is more [documentation](https://einsatzverwaltung.abrain.de/dokumentation/) on our website.

= Where should I send feature requests and bug reports to? =

Ideally, you check the issues on [GitHub](https://github.com/abrain/einsatzverwaltung/issues) if this has been addressed before. If not, feel free to open a new issue. You can also post a new topic on the support forum instead.

= Are there more FAQ? =

Yes, you can find them on [our website](https://einsatzverwaltung.abrain.de/faq/).

== Changelog ==

= 1.7.1 =
* Fix: Associations with reports were not removed when a Unit got deleted
* Adjust numbering automatically, if reports represent more than one incident

= 1.7.0 =
* Vehicles: Can be marked as out of service so they are initially hidden when composing reports
* Vehicles: Custom sort order is respected when composing or editing reports
* Vehicles: Can now be linked with a page from the same site or an arbitrary URL
* Units: Can now be linked with a page from the same site or an arbitrary URL
* Templates: Added placeholder for end date and time of an incident
* Templates: Placeholder for yearly archive permalink can be used in widget footer
* Report list: Made the entire row clickable on mobile devices
* Improved compatibility with Essential Addons for Elementor
* Requires PHP 5.6 or newer

= 1.6.7 =
* Fix: Calculation of the duration could fail, if the time zone was specified as offset from UTC

= 1.6.6 =
* Fix: The duration was calculated incorrectly, when a switch to/from Daylight Saving Time happened during the incident

= 1.6.5 =
* Fix: Publishing reports privately would overwrite the time of alerting with the current time

= 1.6.4 =
* Fix incompatibility with themes based on Gantry 5 framework

= 1.6.3 =
* Units can be assigned to reports in Quick Edit and Bulk Edit mode

= 1.6.2 =
* Classic singular view of reports now also shows units
* Vehicles: Hide events of Pro Event Calendar from selector for vehicle page
* Settings: Placeholder text for empty reports can be set
* Resolved compatibility issue with NextGEN Gallery

= 1.6.1 =
* Fixed user privileges for editing units

= 1.6.0 =
* Added support for multiple units
* Added a shortcode to display the number of reports
* Templates: Added placeholder for units
* Templates: Added placeholder for type of incident incl. its hierarchy
* Templates: Added support for shortcodes
* Templates: Whitelisted more HTML tags
* Report list: Added option for quarterly subheadings
* Report list: Added parameter to limit to certain units
* Report list: Added parameter to limit to certain types of incident
* Improved accessibility of the navigation tabs on the Settings page

== Upgrade Notice ==

