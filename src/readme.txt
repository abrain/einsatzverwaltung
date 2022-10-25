=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.org/unterstuetzen/
Tags: Feuerwehr, fire department, EMS
Requires at least: 5.1.0
Tested up to: 6.0
Requires PHP: 7.1.0
Stable tag: 1.10.0
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

The plugin does not require any setup but it is recommended to take a look at the settings before you start publishing. Especially those in the Advanced section should not be changed inconsiderately later on.

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

= 1.10.1 =
* Fix: Compatibility issue with PHP 8

= 1.10.0 =
* New API endpoint for third-party systems to create incident reports
* Roles for user permissions
* Shortcode `reportcount` can be filtered by Alerting Method
* Fallback featured image for reports based on Incident Category
* Autocomplete for incident location
* Fix: Changing the category setting for incident reports caused an error on fresh installations

= 1.9.7 =
* Fix: Compatibility issue with Elementor

= 1.9.6 =
* Fix: Compatibility issue with PHP 7.4 and newer during import

= 1.9.5 =
* Fix: In some cases incident numbers were not regenerated after changing the format
* Accessibility: Improve navigation of the widgets if the theme supports the navigation-widgets feature

= 1.9.4 =
* Fix: Editor would not show checkboxes for units if no vehicles existed

= 1.9.3 =
* Fix: Editor would not show checkboxes for units on a new site
* Fix: Unexpected format of the global post object could cause an error

= 1.9.2 =
* Fix: Units without vehicles could not be selected in the editor

= 1.9.1 =
* Fix: Too narrow PHP type check prevented creation of other post types

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

== Upgrade Notice ==
= 1.10.1 =
Maintenance Release

= 1.10.0 =
New API endpoint, roles, and more

= 1.9.7 =
Maintenance Release
