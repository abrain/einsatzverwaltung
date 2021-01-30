=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, Hilfsorganisation, Öffentlichkeitsarbeit
Requires at least: 4.7.0
Tested up to: 5.4
Requires PHP: 5.6.0
Stable tag: 1.6.7
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Public incident reports for fire brigades and other rescue services

== Description ==

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Diese Einsatzberichte werden wie gew&ouml;hnliche WordPress-Beitr&auml;ge erstellt, es k&ouml;nnen aber zus&auml;tzliche Informationen wie Alarmzeit, Art des Einsatzes, eingesetzte Fahrzeuge und vieles mehr angegeben werden. Zudem stellt das Plugin verschiedene M&ouml;glichkeiten zur Darstellung der Einsatzberichte zur Verf&uuml;gung.

Die prim&auml;re Zielgruppe des Plugins sind Feuerwehren im deutschsprachigen Raum, es ist aber genauso geeignet f&uuml;r Rettungsdienste, die Wasserwacht, das THW und sonstige Hilfsorganisationen, die ihre Eins&auml;tze im Internet pr&auml;sentieren m&ouml;chten.

__Dieses Plugin steht in keiner Verbindung zu einsatzverwaltung.eu!__

Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Information &uuml;ber Einsatzart, eingesetzte Fahrzeuge, Dauer und vieles mehr
* Unterscheidung von mehreren Einheiten
* Shortcodes zum Einbinden von Einsatzlisten und Einsatzzahlen
* Widget zeigt die aktuellsten Einsatzberichte
* Import aus wp-einsatz und CSV-Dateien
* Export als CSV und JSON
* Newsfeed f&uuml;r Einsatzberichte
* Pflege der Einsatzberichte kann auf bestimmte Benutzerrollen beschr&auml;nkt werden

= Aknowledgements =

Uses Font Awesome by Dave Gandy - http://fontawesome.io

= Social Media =

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)

== Installation ==

The plugin does not require any setup but I recommend to take a look at the settings before you start publishing. Especially those in the Advanced section should not be changed inconsiderately later on.

== Frequently Asked Questions ==

= Ist das hier das WordPress-Plugin für einsatzverwaltung.eu? =

Nein, dieses Plugin hat nichts mit einsatzverwaltung.eu zu tun.

= Wo finde ich die Anleitung bzw. Dokumentation? =

Die Dokumentation gibt es [hier](https://einsatzverwaltung.abrain.de/dokumentation/), wenn etwas fehlt oder missverst&auml;ndlich erkl&auml;rt ist, bitte melden.

= Where should I send feature requests and bug reports to? =

Ideally, you check the issues on [GitHub](https://github.com/abrain/einsatzverwaltung/issues) if this has been addressed before. If not, feel free to open a new issue. You can also post a new topic on the support forum instead.

= Sind das hier die ganzen FAQ? =

Nein, mehr gibt es [hier](https://einsatzverwaltung.abrain.de/faq/).

== Changelog ==

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

