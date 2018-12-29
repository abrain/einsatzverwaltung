=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, Hilfsorganisation, Öffentlichkeitsarbeit
Requires at least: 4.6.0
Tested up to: 5.0
Requires PHP: 5.3.0
Stable tag: 1.4.3
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

Das Plugin kann entweder aus WordPress heraus aus dem [Pluginverzeichnis](https://wordpress.org/plugins/einsatzverwaltung/) installiert werden oder aber durch Hochladen der Plugindateien in das Verzeichnis `/wp-content/plugins/`.

In beiden F&auml;llen muss das Plugin erst aktiviert werden, bevor es benutzt werden kann.

__Es wird PHP 5.3.0 oder neuer ben&ouml;tigt__
(Getestet wird jedoch nur mit den [aktuellen PHP-Versionen](https://secure.php.net/supported-versions.php))

== Frequently Asked Questions ==

= Ist das hier das WordPress-Plugin für einsatzverwaltung.eu? =

Nein, dieses Plugin hat nichts mit einsatzverwaltung.eu zu tun.

= Wo finde ich die Anleitung bzw. Dokumentation? =

Die Dokumentation gibt es [hier](https://einsatzverwaltung.abrain.de/dokumentation/), wenn etwas fehlt oder missverst&auml;ndlich erkl&auml;rt ist, bitte melden.

= Ich f&auml;nde es gut, wenn Funktionalit&auml;t X hinzugef&uuml;gt / verbessert werden k&ouml;nnte =

Entweder einen Issue auf [GitHub](https://github.com/abrain/einsatzverwaltung/issues) er&ouml;ffnen (sofern es nicht schon einen solchen gibt) oder die anderen Kontaktm&ouml;glichkeiten nutzen.

= Wie kann ich den Entwickler erreichen? =

Entweder [per Mail](mailto:kontakt@abrain.de), per PN auf [Facebook](https://www.facebook.com/einsatzverwaltung) oder über [Twitter](https://twitter.com/einsatzvw). Bugs und Verbesserungsvorschl&auml;ge gerne auch als [Issue auf GitHub](https://github.com/abrain/einsatzverwaltung/issues).

= Meine eMails mag ich am liebsten verschl&uuml;sselt und signiert, hast Du da was? =

F&uuml;r eMails von/an [kontakt@abrain.de](mailto:kontakt@abrain.de) kann ich PGP anbieten, Schl&uuml;ssel-ID 8752EB8F.

= Du oder Sie? =

Das Du halte ich f&uuml;r die angenehmere Arbeitsgrundlage, aber man darf mich gerne auch siezen ohne dass ich mich alt f&uuml;hle.

= Sind das hier die ganzen FAQ? =

Nein, mehr gibt es [hier](https://einsatzverwaltung.abrain.de/faq/).

== Changelog ==

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

= 1.3.6 =
* Fehler behoben, bei dem in einem Export aus dem All-in-One Event Calendar von Time.ly Einsatzdetails in Ereignissen auftauchen konnten

= 1.3.5 =
* Unn&ouml;tige Dateien aus Font Awesome entfernt

= 1.3.4 =
* Fehler behoben, bei dem importierte Einsatzberichte, die nicht als besonders markiert waren, auf der Startseite angezeigt wurden, obwohl dort nur als besonders markierte auftauchen sollten

= 1.3.3 =
* Kompatibilit&auml;tsproblem mit "Page Builder by SiteOrigin" behoben
* Getestet mit WordPress 4.9

= 1.3.2 =
* Widgets: Die Symbole f&uuml;r die Vermerke k&ouml;nnen nun angezeigt werden
* Die Farbe f&uuml;r inaktive Vermerke kann nun eingestellt werden

= 1.3.1 =
* Anpassung der URLs: Zeitlimit entfernt
* Einsatzliste: Text kann in der mobilen Ansicht auch direkt nach der Spalten&uuml;berschrift umbrechen
* Import: Performance verbessert
* Import aus wp-einsatz repariert
* Getestet mit WordPress 4.8

= 1.3.0 =
* Neuer Vermerk 'Bilder im Bericht'
* Einsatzliste: Neue Spalten f&uuml;r Vermerke 'Bilder im Bericht' und 'Besonderer Einsatz'
* Vermerke werden in der &Uuml;bersicht im Adminbereich angezeigt
* Einsatznummer ist nun nicht mehr Teil der URL
* Import: Einsatznummer kann importiert werden
* Einsatznummern k&ouml;nnen wahlweise automatisch oder manuell verwaltet werden
* Werkzeug zum Reparieren der Einsatznummern entfernt
* Mindestanforderung auf WordPress 4.4 angehoben
* Getestet mit WordPress 4.7

== Upgrade Notice ==

= 1.4.3 =
Stellt die Kompatibilit&auml;t mit WordPress 5.0.1, 4.9.9, 4.8.8, 4.7.12, 4.6.13 wieder her

= 1.4.0 =
Templates f&uuml;r Einsatzberichte und Ausz&uuml;ge, Export von Einsatzberichten, und mehr

== Social Media ==

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)
