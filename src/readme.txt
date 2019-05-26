=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, Hilfsorganisation, Öffentlichkeitsarbeit
Requires at least: 4.7.0
Tested up to: 5.1
Requires PHP: 5.3.0
Stable tag: 1.5.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Verwaltung und Darstellung von Einsatzberichten der Feuerwehr und anderer Hilfsorganisationen

== Description ==

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Diese Einsatzberichte werden wie gew&ouml;hnliche WordPress-Beitr&auml;ge erstellt, es k&ouml;nnen aber zus&auml;tzliche Informationen wie Alarmzeit, Art des Einsatzes, eingesetzte Fahrzeuge und vieles mehr angegeben werden. Zudem stellt das Plugin verschiedene M&ouml;glichkeiten zur Darstellung der Einsatzberichte zur Verf&uuml;gung.

Die prim&auml;re Zielgruppe des Plugins sind Feuerwehren im deutschsprachigen Raum, es ist aber genauso geeignet f&uuml;r Rettungsdienste, die Wasserwacht, das THW und sonstige Hilfsorganisationen, die ihre Eins&auml;tze im Internet pr&auml;sentieren m&ouml;chten.

__Dieses Plugin steht in keiner Verbindung zu einsatzverwaltung.eu!__

Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Information &uuml;ber Einsatzart, eingesetzte Fahrzeuge, Dauer und vieles mehr
* Shortcode zum Einbinden von Einsatzlisten
* Widget zeigt die aktuellsten Einsatzberichte
* Import aus wp-einsatz und CSV-Dateien
* Newsfeed f&uuml;r Einsatzberichte
* Pflege der Einsatzberichte kann auf bestimmte Benutzerrollen beschr&auml;nkt werden

Uses Font Awesome by Dave Gandy - http://fontawesome.io

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

= 1.5.1 =
* URLs für PATHINFO-Permalinks (beginnen mit /index.php/) repariert
* Block-Editor kann für Einsatzberichte deaktiviert werden

= 1.5.0 =
* Grundlegende Unterstützung für den neuen Blockeditor
* API teilweise aktiviert
* Permalinks für Einsatzberichte einstellbar gemacht
* Templates: Neuer Platzhalter für Einsatzleiter
* Shortcode einsatzjahre jetzt über Parameter konfigurierbar
* Auszug für Einsatzberichte kann manuell definiert werden
* Schlagwörter sind jetzt für Einsatzberichte abschaltbar
* Fehler bei der Behandlung der Alarmzeit von geplanten Beiträgen behoben
* Mindestanforderung auf WordPress 4.7 angehoben

== Upgrade Notice ==

= 1.6.0 =
Support for multiple units, report count shortcode and more

== Social Media ==

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)
