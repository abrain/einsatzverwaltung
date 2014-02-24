#Einsatzverwaltung
##Plugin zur Verwaltung von Feuerwehreins&auml;tzen - Verwendung des Plugins auf Produktivsystemen m&ouml;glich, aber erst ab Version 1.0 empfohlen

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Dieser kann wie ein normaler Beitrag ver&ouml;ffentlicht werden und somit zus&auml;tzlichen Inhalt wie z.B. Bilder bieten. Analog zu Tags und Kategorien der bekannten Wordpress-Beitr&auml;ge kann man die Einsatzberichte mit Einsatzart und eingesetzten Fahrzeugen versehen. Jeder Bericht bekommt eine eindeutige Einsatznummer und ist mit Alarmzeit und Einsatzdauer versehen.

### Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Einsatzart, eingesetzte Fahrzeuge und externe Kr&auml;fte zuordnen
* Einbinden einer Liste von Eins&auml;tzen eines Jahres per Shortcode
* Widget zeigt die aktuellsten X Eins&auml;tze

__ACHTUNG: Vor Version 1.0 kann sich noch etwas an der Datenstruktur &auml;ndern, bitte vorher nur die Funktion testen und noch keine Unmengen an Eins&auml;tzen eintragen, die m&uuml;ssen sonst unter Umst&auml;nden sp&auml;ter von Hand ge&auml;ndert werden.__

### Geplante Funktionen:

* Format der Einsatznummer einstellbar (v0.4)
* Import aus wp-einsatz (v0.5)
* Angabe der Alarmierungsart (v0.5)
* Archivseite für Einsatzberichte als Tabelle darstellen (v1.0)
* Rechtemanagement (v1.0)
* Statistiken
* ...

### Installation

Das Plugin kann entweder aus WordPress heraus aus dem Pluginverzeichnis installiert werden oder aber durch Hochladen der Plugindateien in das Verzeichnis `/wp-content/plugins/`.

In beiden F&auml;llen muss das Plugin erst aktiviert werden, bevor es benutzt werden kann.

__Es wird PHP 5.3.0 oder h&ouml;her ben&ouml;tigt__

### Changelog

#### 0.3.0
* Neu: Einstellungsseite
* Neu: Leere Angaben können im Kopf des Einsatzberichts versteckt werden
* Verbesserung: Shortcode _einsatzliste_ unterstützt Sortierung
* Verbesserung: Datum und Zeit werden gem&auml;&szlig; WordPress-Einstellungen dargestellt
* Hinweis: Fr&uuml;here Fehlalarm-Markierungen m&uuml;ssen neu gesetzt werden

#### 0.2.1
* Fehlerbehebung: Einsatzende wurde falsch abgespeichert

#### 0.2.0
* Neu: Einsatzberichte k&ouml;nnen als Fehlalarm markiert werden
* Neu: Pro Einsatzbericht ist nur noch eine Einsatzart ausw&auml;hlbar
* Neu: Externe Einsatzmittel k&ouml;nnen ab jetzt erfasst werden
* Neu: Validierung von Benutzereingaben
* Fehlerbehebung: Links beim Shortcode einsatzjahre wurden vereinzelt falsch generiert

#### 0.1.2
* Kompatibilit&auml;t mit PHP < 5.3.0 wiederhergestellt

#### 0.1.1
* Hinweis auf Entwicklungszustand eingef&uuml;gt

#### 0.1.0
* Allererste Version
* Verwaltung von Eins&auml;tzen als eigener Beitragstyp
* Einsatzart und Fahrzeuge k&ouml;nnen zu Eins&auml;tzen vermerkt werden
* Einbinden einer Liste von Eins&auml;tzen eines Jahres per Shortcode
* Widget zeigt die aktuellsten X Eins&auml;tze
