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

= 1.4.3 =
* Kompatibilit&auml;t mit WordPress 5.0.1, 4.9.9, 4.8.8, 4.7.12, 4.6.13 wiederhergestellt
* Import: Nicht kritischen Fehler mit dem Beitragsdatum beim Import als Entwurf behoben
* Beschriftungen korrigiert
* Getestet mit WordPress 5.0

= 1.4.2 =
* Templates: Behebt ein Problem, bei dem Shortcodes falsch geparst wurden
* Templates: Neuer Platzhalter f&uuml;r die Mannschaftsst&auml;rke
* Deaktivierung des Zeitlimits bei Updates, Importen etc. entfernt, da es bei manchen Hostern Probleme gab

= 1.4.1 =
* Abs&auml;tze wurden bei der Verwendung von Templates nicht richtig dargestellt
* Einstellungen f&uuml;r Ausz&uuml;ge gelten jetzt auch f&uuml;r oEmbeds

= 1.4.0 =
* Gestaltung von Einsatzberichten und Ausz&uuml;gen mit Hilfe von Templates
* Export von Einsatzberichten in den Formaten CSV und JSON (Dank an [Heiko](https://github.com/heikobornholdt/))
* Templates: Neue Platzhalter f&uuml;r Farbe der Einsatzart, Fahrzeuge, weitere Kr&auml;fte, Alarmierungsarten, Beitragstext, Beitragsbild, URL des Jahresarchivs
* Einsatzarten kann eine Farbe zugewiesen werden
* Farben k&ouml;nnen per Colorpicker ausgew&auml;hlt werden
* Import akzeptiert bei Wahrheitswerten neben 1/0 jetzt auch ja/nein
* Einstellungen auf mehrere Tabs aufgeteilt
* Verschiedene Leistungsverbesserungen
* Font Awesome auf Version 4.7 aktualisiert

== Upgrade Notice ==

= 1.4.3 =
Stellt die Kompatibilit&auml;t mit WordPress 5.0.1, 4.9.9, 4.8.8, 4.7.12, 4.6.13 wieder her

= 1.4.0 =
Templates f&uuml;r Einsatzberichte und Ausz&uuml;ge, Export von Einsatzberichten, und mehr

== Social Media ==

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)
