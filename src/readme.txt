=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, fire department, EMS
Requires at least: 5.1.0
Tested up to: 5.7
Requires PHP: 7.1.0
Stable tag: 1.8.0
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

= 1.8.0 =
* Fix: Not all vehicles could be removed from an existing Incident Report
* Shortcode `reportcount` can be filtered by status (actual or false alarm)
* Templates: Added placeholder for featured image thumbnail
* Shortcodes `einsatzliste` and `reportcount` can be filtered by multiple Incident Categories
* Incident Categories can be marked as outdated
* Units were converted to a taxonomy
* Requires PHP 7.1 or newer
* Requires WordPress 5.1 or newer

= 1.7.2 =
* Report list and Templates: Show sequential numbers as range if the report represents more than one incident
* Fix: Shortcode `reportcount` did not take into account if reports represented more than one incident
* Fix: Incident numbers would not have been correctly updated when changing the format
* Accessibility: Add aria-current attribute to links to the currently displayed page

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

== Upgrade Notice ==

