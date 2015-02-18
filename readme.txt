=== Einsatzverwaltung ===
Contributors: abrain
Donate link: http://www.abrain.de/software/unterstuetzen/
Tags: feuerwehr, einsatz
Requires at least: 3.1.0
Tested up to: 4.1
Stable tag: 0.8.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin zur Verwaltung von Feuerwehreins&auml;tzen

== Description ==

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Dieser kann wie ein normaler Beitrag ver&ouml;ffentlicht werden und somit zus&auml;tzlichen Inhalt wie z.B. Bilder bieten. Jeder Bericht bekommt eine eindeutige Einsatznummer und ist mit Alarmzeit und Einsatzdauer versehen. Zus&auml;tzlich kann man Einsatzart, eingesetzte Fahrzeuge, externe Kr&auml;fte und mehr angeben.

Die prim&auml;re Zielgruppe des Plugins sind Feuerwehren im deutschsprachigen Raum, es ist aber genauso geeignet f&uuml;r Rettungsdienste, die Wasserwacht, das THW und sonstige Hilfsorganisationen, die ihre Eins&auml;tze im Internet pr&auml;sentieren m&ouml;chten.

Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Information &uuml;ber Einsatzart, eingesetzte Fahrzeuge, Dauer und vieles mehr
* Shortcode zum Einbinden einer Liste von Eins&auml;tzen eines Jahres
* Widget zeigt die aktuellsten X Eins&auml;tze
* Import aus wp-einsatz

Uses Font Awesome by Dave Gandy - http://fontawesome.io

== Installation ==

Das Plugin kann entweder aus WordPress heraus aus dem [Pluginverzeichnis](http://wordpress.org/plugins/einsatzverwaltung/) installiert werden oder aber durch Hochladen der Plugindateien in das Verzeichnis `/wp-content/plugins/`.

In beiden F&auml;llen muss das Plugin erst aktiviert werden, bevor es benutzt werden kann.

__Es wird PHP 5.3.0 oder neuer ben&ouml;tigt__

== Frequently Asked Questions ==

= Wo finde ich die Anleitung bzw. Dokumentation? =

Die Dokumentation gibt es [hier](http://www.abrain.de/software/einsatzverwaltung/anleitung/), wenn etwas fehlt oder missverst&auml;ndlich erkl&auml;rt ist, bitte melden.

= Ich f&auml;nde es gut, wenn Funktionalit&auml;t X hinzugef&uuml;gt / verbessert werden k&ouml;nnte =

Die Aufgaben f&uuml;r die kommenden Versionen werden auf [GitHub](https://github.com/abrain/einsatzverwaltung/issues) verwaltet, Feedback ist jederzeit willkommen.

= Wie kann ich den Entwickler erreichen? =

Entweder [per Mail](mailto:kontakt@abrain.de), auf [Twitter](https://twitter.com/einsatzvw) oder [App.net](https://alpha.app.net/einsatzverwaltung). Bugs und Verbesserungsvorschl&auml;ge gerne auch als [Issue auf GitHub](https://github.com/abrain/einsatzverwaltung/issues).

== Changelog ==

= 0.8.4 =
* Fehlerbehebung: Erstellen von Standard-WordPress-Beitr&auml;gen war beeintr&auml;chtigt

= 0.8.3 =
* Fehlerbehebung: Einsatzdetails wurden nicht abgespeichert

= 0.8.2 =
* Verbesserung: Inhalt der Kurzfassung von Einsatzberichten ist nun f&uuml;r die Webseite und den Feed einstellbar
* Verbesserung: Autovervollst&auml;ndigung f&uuml;r das Feld Einsatzleiter
* Entfernt: Einstellung 'Auszug darf Links enthalten'

= 0.8.1 =
* Neu: Links zu externen Kr&auml;ften lassen sich optional in neuem Fenster &ouml;ffnen (neue Einstellung)
* Verbesserung: Tabelle der Einsatz&uuml;bersicht enth&auml;lt keine festen Breitenangaben mehr
* Verbesserung: Der Autor eines Einsatzberichtes kann eingestellt werden
* Fehlerbehebung: (Unsichtbare) Fehlermeldungen im Widget wurden abgestellt

= 0.8 =
* Neu: Import aus wp-einsatz
* Neu: Einsatzberichte k&ouml;nnen zusammen mit den Standardbeitr&auml;gen (z.B. auf der Startseite) angezeigt werden
* Neu: Hierarchie der Einsatzart kann im Widget angezeigt werden

= 0.7.1 =
* Neu: Einsatzberichte k&ouml;nnen mit der Jetpack-Funktion &quot;Publizieren&quot; ver&ouml;ffentlicht werden
* Hinweis: Einsatzverwaltung ist kompatibel mit WordPress 4.1

= 0.7.0 =
* Neu: Berechtigung zur Verwaltung von Einsatzberichten kann nun allen Benutzerrollen von WordPress zugeordnet werden
* Verbesserung: Shortcode einsatzliste kann Tabelle nach Monaten getrennt darstellen
* Hinweis: Der neue Shortcode-Parameter kann in der [Anleitung](http://www.abrain.de/software/einsatzverwaltung/anleitung/) nachgelesen werden
* Hinweis (subtil): Es gibt mittlerweile auch eine [Facebook-Seite](https://www.facebook.com/einsatzverwaltung)

= 0.6.0 =
* Neu: Fahrzeug kann mit Seite innerhalb Wordpress verkn&uuml;pft werden
* Neu: Externe Kr&auml;fte k&ouml;nnen mit Link zu Webseite versehen werden
* Neu: Gefilterte Einsatz&uuml;bersichten f&uuml;r einzelne Fahrzeuge, Einsatzarten oder ext. Kr&auml;fte
* Verbesserung: Einsatzarten k&ouml;nnen hierarchisch gegliedert werden
* Verbesserung: Shortcode einsatzliste kann alle Jahre anzeigen
* Verbesserung: Shortcode einsatzliste kann die letzten X Jahre anzeigen
* Verbesserung: Leere Einsatzdetails werden standardm&auml;&szlig;ig versteckt
* Verbesserung: Kurzfassung im Feed jetzt mit Zeilenumbr&uuml;chen
* Verbesserung: Icons werden mit Font Awesome dargestellt
* Fehlerbehebung: Shortcode einsatzjahre erzeugte falsche Links bei deaktivierten Permalinks
* Fehlerbehebung: Seitennavigation im Jahresarchiv war defekt
* Hinweis: Die neuen Shortcode-Parameter k&ouml;nnen in der [Anleitung](http://www.abrain.de/software/einsatzverwaltung/anleitung/) nachgelesen werden

= 0.5.4 =
* Fehlerbehebung: Datum f&uuml;r Feed wurde falsch gespeichert
* Hinweis: Die Daten werden beim Update automatisch korrigiert, bitte fertigen Sie vorher ein Backup an

= 0.5.3 =
* Fehlerbehebung: Plugin funktionierte nicht auf Servern mit PHP-Einstellung short_open_tag = false

= 0.5.2 =
* Neu: Widget kann Link zu Feed anzeigen
* Neu: Widget kann Einsatzort anzeigen
* Neu: Widget kann Einsatzart anzeigen
* Neu: Einsatzberichte werden im Dashboard bei "Auf einen Blick" angezeigt
* Neu: Icon im Adminbereich (ab WP 3.9)
* Fehlerbehebung: Schreibrechte wurden beim Speichern falsch gepr&uuml;ft

= 0.5.1 =
* Neu: Hinweis bei veralteter PHP-Version

= 0.5.0 =
* Neu: Feld f&uuml;r Alarmierungsart
* Neu: Feld f&uuml;r Einsatzort
* Neu: Feld f&uuml;r Einsatzleiter
* Neu: Feld f&uuml;r Mannschaftsst&auml;rke
* Kontaktadressen aktualisiert
* Hinweis auf Verwendungsempfehlung erst ab Version 1.0 entfernt, da hinf&auml;llig

= 0.4.0 =
* Neu: Format der Einsatznummer einstellbar
* Neu: Werkzeug zur Reparatur/Aktualisierung von Einsatznummern

= 0.3.2 =
* Fehlerbehebung: Datums- und Zeitangaben wurden in englischer Schreibweise angezeigt

= 0.3.1 =
* Fehlerbehebung: Bearbeiten normaler Beitr&auml;ge war beeintr&auml;chtigt

= 0.3.0 =
* Neu: Einstellungsseite
* Neu: Leere Angaben k&ouml;nnen im Kopf des Einsatzberichts versteckt werden
* Verbesserung: Shortcode _einsatzliste_ unterst&uuml;tzt Sortierung
* Verbesserung: Datum und Zeit werden gem&auml;&szlig; WordPress-Einstellungen dargestellt
* Hinweis: Fr&uuml;here Fehlalarm-Markierungen m&uuml;ssen neu gesetzt werden

= 0.2.1 =
* Fehlerbehebung: Einsatzende wurde falsch abgespeichert

= 0.2.0 =
* Neu: Einsatzberichte k&ouml;nnen als Fehlalarm markiert werden
* Neu: Pro Einsatzbericht ist nur noch eine Einsatzart ausw&auml;hlbar
* Neu: Externe Einsatzmittel k&ouml;nnen ab jetzt erfasst werden
* Neu: Validierung von Benutzereingaben
* Fehlerbehebung: Links beim Shortcode einsatzjahre wurden vereinzelt falsch generiert

= 0.1.2 =
* Kompatibilit&auml;t mit PHP < 5.3.0 wiederhergestellt

= 0.1.1 =
* Hinweis auf Entwicklungszustand eingef&uuml;gt

= 0.1.0 =
* Allererste Version
* Verwaltung von Eins&auml;tzen als eigener Beitragstyp
* Einsatzart und Fahrzeuge k&ouml;nnen zu Eins&auml;tzen vermerkt werden
* Einbinden einer Liste von Eins&auml;tzen eines Jahres per Shortcode
* Widget zeigt die aktuellsten X Eins&auml;tze

== Upgrade Notice ==

= 0.8.4 =
Wichtige Fehlerbehebung

= 0.8.3 =
Kritische Fehlerbehebung

= 0.8.2 =
Kleine Verbesserungen, siehe Changelog f&uuml;r Details

= 0.8.1 =
Kleine Verbesserungen, siehe Changelog f&uuml;r Details

= 0.8 =
Import aus wp-einsatz, Anzeige von Einsatzberichten als normale Beitr&auml;ge und Hierarchie der Einsatzart im Widget

= 0.7.1 =
Publizieren mit Jetpack aktiviert

= 0.7.0 =
Neue Rechteverwaltung, neue Darstellungsoption für Einsatzliste

= 0.6.0 =
Neuerungen, Verbesserungen, Fehlerbehebungen. Da ist f&uuml;r alle was dabei.

= 0.5.4 =
Korrektur des Datums im Feed, bitte Update erst nach Backup durchf&uuml;hren

= 0.5.3 =
Erhöhte Kompatibilität für zukünftige Installationen

= 0.5.2 =
Mehr Einstellungen im Widget

= 0.5.1 =
Pr&uuml;fung auf veraltete PHP-Version

= 0.5.0 =
Neue Eingabefelder, Plugin kann jetzt produktiv eingesetzt werden

= 0.4.0 =
Neue Features rund um die Einsatznummer

= 0.3.2 =
Behebt einen Darstellungsfehler der Datums- und Zeitangaben

= 0.3.1 =
Behebt einen Fehler, der das Erstellen normaler Beitr&auml;ge st&ouml;rte

= 0.3.0 =
Dieses Update bietet mehr Einstellm&ouml;glichkeiten

= 0.2.1 =
Fehlerbehebung

= 0.2.0 =
Umfangreichere Einsatzberichte und Validierung von Benutzereingaben, kleine Fehlerbehebung

= 0.1.2 =
Kompatibilit&auml;t mit PHP < 5.3.0 wiederhergestellt

= 0.1.1 =
Hinweis auf Entwicklungszustand eingef&uuml;gt

= 0.1.0 =
Kein Upgrade, sondern die erste Version

== Social Media ==

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* App.net: [@einsatzverwaltung](https://alpha.app.net/einsatzverwaltung)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)
