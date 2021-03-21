=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, fire department, EMS
Requires at least: 5.1.0
Tested up to: 5.7
Requires PHP: 7.1.0
Stable tag: 1.9.0
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

= 1.9.0 =
* Vehicles can be associated with a unit
* Incident numbers can have a separator between the year the and sequential number
* Classic singular view of reports shows vehicles grouped by unit, if units are used
* Templates: Added placeholder for vehicles grouped by unit
* Units: Display order can be customized
* Editor: Vehicles appear grouped by unit
* Editor: Meta box for incident details is now mobile friendly
* Editor: Notice about wrong date format only appears after leaving the field
* Internationalized more labels

= 1.8.0 =
* Fix: Not all vehicles could be removed from an existing Incident Report
* Shortcode `reportcount` can be filtered by status (actual or false alarm)
* Templates: Added placeholder for featured image thumbnail
* Shortcodes `einsatzliste` and `reportcount` can be filtered by multiple Incident Categories
* Incident Categories can be marked as outdated
* Units were converted to a taxonomy
* Requires PHP 7.1 or newer
* Requires WordPress 5.1 or newer

== Upgrade Notice ==

