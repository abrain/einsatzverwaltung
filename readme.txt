=== Plugin Name ===
Contributors: abrain
Donate link: https://flattr.com/t/2638688
Tags: feuerwehr, einsatz
Requires at least: 3.1.0
Tested up to: 3.8.1
Stable tag: 0.3.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Plugin zur Verwaltung von Feuerwehreins&auml;tzen - Verwendung des Plugins auf Produktivsystemen m&ouml;glich, aber erst ab Version 1.0 empfohlen

== Description ==

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Dieser kann wie ein normaler Beitrag ver&ouml;ffentlicht werden und somit zus&auml;tzlichen Inhalt wie z.B. Bilder bieten. Analog zu Tags und Kategorien der bekannten Wordpress-Beitr&auml;ge kann man die Einsatzberichte mit Einsatzart und eingesetzten Fahrzeugen versehen. Jeder Bericht bekommt eine eindeutige Einsatznummer und ist mit Alarmzeit und Einsatzdauer versehen.

Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Einsatzart, eingesetzte Fahrzeuge und externe Kr&auml;fte zuordnen
* Einbinden einer Liste von Eins&auml;tzen eines Jahres per Shortcode
* Widget zeigt die aktuellsten X Eins&auml;tze

__ACHTUNG: Vor Version 1.0 kann sich noch etwas an der Datenstruktur &auml;ndern, bitte vorher nur die Funktion testen und noch keine Unmengen an Eins&auml;tzen eintragen, die m&uuml;ssen sonst unter Umst&auml;nden sp&auml;ter von Hand ge&auml;ndert werden.__

Geplante Funktionen:

* Format der Einsatznummer einstellbar (v0.4)
* Import aus wp-einsatz (v0.5)
* Angabe der Alarmierungsart (v0.5)
* Archivseite für Einsatzberichte als Tabelle darstellen (v1.0)
* Rechtemanagement (v1.0)
* Statistiken
* ...

== Installation ==

Das Plugin kann entweder aus WordPress heraus aus dem [Pluginverzeichnis](http://wordpress.org/plugins/einsatzverwaltung/) installiert werden oder aber durch Hochladen der Plugindateien in das Verzeichnis `/wp-content/plugins/`.

In beiden F&auml;llen muss das Plugin erst aktiviert werden, bevor es benutzt werden kann.

__Es wird PHP 5.3.0 oder h&ouml;her ben&ouml;tigt__

== Frequently Asked Questions ==

= Ich f&auml;nde es gut, wenn Funktionalit&auml;t X hinzugef&uuml;gt / verbessert werden k&ouml;nnte =

Die Aufgaben f&uuml;r die kommenden Versionen werden auf [GitHub](https://github.com/abrain/einsatzverwaltung/issues) verwaltet, Feedback ist jederzeit willkommen.

== Changelog ==

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
