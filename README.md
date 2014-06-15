#Einsatzverwaltung
##Plugin zur Verwaltung von Feuerwehreins&auml;tzen

[![Flattr](https://api.flattr.com/button/flattr-badge-large.png)](http://flattr.com/thing/2638688/abraineinsatzverwaltung-on-GitHub)

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Dieser kann wie ein normaler Beitrag ver&ouml;ffentlicht werden und somit zus&auml;tzlichen Inhalt wie z.B. Bilder bieten. Analog zu Tags und Kategorien der bekannten Wordpress-Beitr&auml;ge kann man die Einsatzberichte mit Einsatzart und eingesetzten Fahrzeugen versehen. Jeder Bericht bekommt eine eindeutige Einsatznummer und ist mit Alarmzeit und Einsatzdauer versehen.

### Funktionen im &Uuml;berblick:

* Einsatzberichte als vollwertige Beitr&auml;ge ver&ouml;ffentlichen
* Information &uuml;ber Einsatzart, eingesetzte Fahrzeuge, Dauer und vieles mehr
* Shortcode zum Einbinden einer Liste von Eins&auml;tzen eines Jahres
* Widget zeigt die aktuellsten X Eins&auml;tze

### Geplante Funktionen:

* Import aus wp-einsatz (v0.6)
* Archivseite für Einsatzberichte als Tabelle darstellen (v1.0)
* Rechtemanagement (v1.0)
* Statistiken
* ...

### Installation

Das Plugin kann entweder aus WordPress heraus aus dem [Pluginverzeichnis](http://wordpress.org/plugins/einsatzverwaltung/) installiert werden oder aber durch Hochladen der Plugindateien in das Verzeichnis `/wp-content/plugins/`.

In beiden F&auml;llen muss das Plugin erst aktiviert werden, bevor es benutzt werden kann.

__Es wird PHP 5.3.0 oder h&ouml;her ben&ouml;tigt__

### Changelog

#### 0.5.1
* Neu: Hinweis bei veralteter PHP-Version

#### 0.5.0
* Neu: Feld f&uuml;r Alarmierungsart
* Neu: Feld f&uuml;r Einsatzort
* Neu: Feld f&uuml;r Einsatzleiter
* Neu: Feld f&uuml;r Mannschaftsst&auml;rke
* Kontaktadressen aktualisiert
* Hinweis auf Verwendungsempfehlung erst ab Version 1.0 entfernt, da hinf&auml;llig

#### 0.4.0
* Neu: Format der Einsatznummer einstellbar
* Neu: Werkzeug zur Reparatur/Aktualisierung von Einsatznummern

#### 0.3.2
* Fehlerbehebung: Datums- und Zeitangaben wurden in englischer Schreibweise angezeigt

#### 0.3.1
* Fehlerbehebung: Bearbeiten normaler Beitr&auml;ge war beeintr&auml;chtigt

#### 0.3.0
* Neu: Einstellungsseite
* Neu: Leere Angaben k&ouml;nnen im Kopf des Einsatzberichts versteckt werden
* Verbesserung: Shortcode _einsatzliste_ unterst&uuml;tzt Sortierung
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
