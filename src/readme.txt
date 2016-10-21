=== Einsatzverwaltung ===
Contributors: abrain
Donate link: https://einsatzverwaltung.abrain.de/unterstuetzen/
Tags: Feuerwehr, Einsatz, Rettung, Rettungsdienst, THW, HiOrg, Wasserwacht, Bergrettung
Requires at least: 3.7.0
Tested up to: 4.6
Stable tag: 1.2.3
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

== Frequently Asked Questions ==

= Ist das hier das WordPress-Plugin für einsatzverwaltung.eu? =

Nein, dieses Plugin hat nichts mit einsatzverwaltung.eu zu tun.

= Wo finde ich die Anleitung bzw. Dokumentation? =

Die Dokumentation gibt es [hier](https://einsatzverwaltung.abrain.de/dokumentation/), wenn etwas fehlt oder missverst&auml;ndlich erkl&auml;rt ist, bitte melden.

= Ich f&auml;nde es gut, wenn Funktionalit&auml;t X hinzugef&uuml;gt / verbessert werden k&ouml;nnte =

Entweder einen Issue auf [GitHub](https://github.com/abrain/einsatzverwaltung/issues) er&ouml;ffnen (sofern es nicht schon einen solchen gibt) oder die anderen Kontaktm&ouml;glichkeiten nutzen.

= Wie kann ich den Entwickler erreichen? =

Entweder [per Mail](mailto:kontakt@abrain.de), per PN auf [Facebook](https://www.facebook.com/einsatzverwaltung), auf [Twitter](https://twitter.com/einsatzvw) oder [App.net](https://alpha.app.net/einsatzverwaltung). Bugs und Verbesserungsvorschl&auml;ge gerne auch als [Issue auf GitHub](https://github.com/abrain/einsatzverwaltung/issues).

= Meine eMails mag ich am liebsten verschl&uuml;sselt und signiert, hast Du da was? =

F&uuml;r eMails von/an [kontakt@abrain.de](mailto:kontakt@abrain.de) kann ich PGP anbieten, Schl&uuml;ssel-ID 8752EB8F.

= Du oder Sie? =

Das Du halte ich f&uuml;r die angenehmere Arbeitsgrundlage, aber man darf mich gerne auch siezen ohne dass ich mich alt f&uuml;hle.

= Sind das hier die ganzen FAQ? =

Nein, mehr gibt es [hier](https://einsatzverwaltung.abrain.de/faq/).

== Changelog ==

= 1.2.3 =
Verbesserungen:

* Kompatibilit&auml;t von Einsatzberichten und Kategorien verbessert

Sonstiges:

* Getestet mit WordPress 4.6

= 1.2.2 =
Verbesserungen:

* Einsatzliste: Trennung zwischen den Kalenderjahren kann abgeschalten werden
* Einsatzliste: Jahres&uuml;berschrift kann ausgeblendet werden
* Widget Letzte Eins&auml;tze (eigenes Format): Neuer Tag f&uuml;r laufende Nummer
* Widgets unterst&uuml;tzen Selective Refresh (neues Feature in der Live-Vorschau)

Fehlerbehebungen:

* Einsatzberichte konnten im Frontend anderer Plugins auftauchen
* Alarmzeit wurde bei Entw&uuml;rfen falsch gespeichert

Sonstiges:

* Getestet mit WordPress 4.5

= 1.2.1 =
Verbesserungen:

* Die Zebrastreifen der tabellarischen &Uuml;bersicht k&ouml;nnen jetzt abgeschalten werden
* Farbe f&uuml;r Zebrastreifen ist einstellbar, ebenso die betroffenen Zeilen (gerade/ungerade)

Fehlerbehebungen:

* Widget zeigte bei bestimmten Einstellungen nur als besonders markierte Eins&auml;tze an
* Kategoriezuordnung von Einsatzberichten wurde nicht aufgehoben, wenn Markierung f&uuml;r besonderen Einsatz entfernt wurde

= 1.2.0 =
* Die tabellarische &Uuml;bersicht passt sich nun Mobilger&auml;ten an
* Einsatzberichte k&ouml;nnen als besonders markiert werden
* Neue Optionen f&uuml;r Shortcode einsatzliste: Nur besondere Eins&auml;tze anzeigen, Anzahl der Berichte limitieren, Link zum Bericht muss nicht mehr der Titel sein, kein Link bei fehlendem Beitragstext, Links generell abschaltbar
* Beim Anlegen neuer Einsatzberichte wird die Alarmzeit vorbelegt
* Anweisungen beim CSV-Import klarer formuliert
* Einsatzberichte werden nun tats&auml;chlich der eingestellten Kategorie zugeordnet
* Anzeige der Einsatzberichte zwischen normalen Beitr&auml;gen ist an mehr Stellen m&ouml;glich und kann auf besondere Eins&auml;tze beschr&auml;nkt werden
* Beschriftungen (u.a. f&uuml;r die Barrierefreiheit) &uuml;berarbeitet
* Der Inhalt der Kurzfassung kann auch wieder WordPress selbst &uuml;berlassen werden
* Bei gesch&uuml;tzten Beitr&auml;gen wurden die Einsatzdetails auch ohne Eingabe des Passworts angezeigt

= 1.1.5 =
* CSV-Import: Leerzeichen zu Beginn des Feldes verhinderte Auswertung des Datums
* CSV-Import: Zu kurze Zeilen verursachten Fehlermeldung

= 1.1.4 =
* Links zum Jahresarchiv wurden falsch generiert, wenn Permalinkstruktur nicht mit einem Schr&auml;gstrich endete
* Pr&auml;fix der Permalinkstruktur (z.B. /archive/) wurde bei den Jahresarchiven nicht ber&uuml;cksichtigt

= 1.1.3 =
* Problem mit Benutzerrechten behoben
* Getestet mit WordPress 4.4
* Mindestanforderung auf WordPress 3.7 angehoben

= 1.1.2 =
* CSV-Import: Ein Leerzeichen in der Spaltenbeschriftung verhinderte den Import dieser Spalte
* Anpassungen f&uuml;r WordPress 4.4: Hierarchie der &Uuml;berschriften korrigiert und neue Labels f&uuml;r Screenreader angelegt

= 1.1.1 =
* Import: Einsatzende wurde nicht richtig formatiert abgespeichert
* Import: Mit Kommas getrennte Liste von Fahrzeugen wurde als ein einziges Fahrzeug angelegt

= 1.1.0 =
* Neues Widget kann per HTML komplett selbst gestaltet werden
* Anzeigereihenfolge der Fahrzeuge kann festgelegt werden
* Fahrzeuge k&ouml;nnen in Hierarchie (z.B. Standorte) organisiert werden (hat noch keine Auswirkung auf die Darstellung)
* Import von Einsatzberichten aus CSV-Dateien m&ouml;glich
* Problem mit Benutzerrechten behoben
* Einsatzberichte bleiben erhalten, wenn der Autor gel&ouml;scht wird
* Als privat markierte Einsatzberichte wurden bei der Berechnung der Einsatznummern nicht ber&uuml;cksichtigt

= 1.0.0 =
* Basispfad der Einsatzberichte (bisher einsaetze) kann eingestellt werden
* Hinweis bei &Uuml;berschneidung von Basispfad und dem Pfad einer Seite
* Einsatzberichte k&ouml;nnen Schlagworte der Beitr&auml;ge nutzen
* Jeder in WordPress vorhandene &ouml;ffentliche Beitragstyp kann nun als Fahrzeugseite verwendet werden
* Einsatzberichte k&ouml;nnen in einer bestimmten Beitragskategorie eingeblendet werden
* Neue Spalte f&uuml;r Einsatzliste: Datum + Zeit
* Kurze Spaltentitel der Einsatzliste (Nummer, Datum, Zeit, Dauer) werden auch bei wenig Platz nicht mehr umgebrochen

= 0.9.2 =
* Getestet mit WordPress 4.3
* Gleiche Spaltenbreite &uuml;ber gesamte Einsatzliste
* &Auml;nderungen an den Einsatzberichten sind jetzt &uuml;ber Revisionen nachverfolgbar
* Font Awesome auf Version 4.4 aktualisiert

= 0.9.1 =
* Getestet mit WordPress 4.2
* Fehlerbehebung: Administratoren hatten nicht sofort nach der Installation des Plugins Zugriff auf alle Funktionen
* Verbesserung: &Uuml;bersichtsseite der Fahrzeuge bzw. externen Einsatzmittel im Adminbereich zeigt jetzt auch die verlinkte Fahrzeugseite bzw. die angegebene URL
* Kontaktinformationen und FAQs aktualisiert

= 0.9.0 =
* Komplettsanierung: Unter der Haube wurde kr&auml;ftig umgebaut und zusammengefasst, klarere Strukturen beschleunigen die Entwicklung
* Neu: Spalten der Einsatzliste sind jetzt einstellbar
* Neu: F&uuml;r die Einsatzliste stehen mehr Spalten zur Auswahl (Alarmierungsart, Dauer, Einsatzart, Einsatzleiter, Einsatzort, Fahrzeuge, Laufende Nummer, Mannschaftsst&auml;rke, Weitere Kr&auml;fte)
* Verbesserung: Die Mannschaftst&auml;rke muss keine einzelne Zahl mehr sein, Angaben wie 1:8 sind m&ouml;glich
* Fehlerbehebung: Seitenweise Navigation im Jahresarchiv funktionierte nicht direkt nach der Aktivierung
* Font Awesome auf Version 4.3 aktualisiert
* Hinweis: Dieses Update entfernt alle Eintr&auml;ge zur Mannschaftsst&auml;rke, die 0 lauten. Ein Backup der Datenbank vor dem Update wird empfohlen.

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
* Hinweis: Der neue Shortcode-Parameter kann in der [Anleitung](https://einsatzverwaltung.abrain.de/dokumentation/) nachgelesen werden
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
* Hinweis: Die neuen Shortcode-Parameter k&ouml;nnen in der [Anleitung](https://einsatzverwaltung.abrain.de/dokumentation/) nachgelesen werden

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

= 1.1.5 =
Fehlerbehebungen bei der Importfunktion

= 1.1.4 =
Fehler bei Links zum Jahresarchiv ausgebessert

= 1.1.3 =
Problem mit Benutzerrechten behoben

= 1.1.2 =
Fehlerbehebung bei der Importfunktion und Anpassungen f&uuml;r WordPress 4.4

= 1.1.1 =
Fehlerbehebungen bei der Importfunktion

= 1.0.0 =
Ver&auml;nderbarer Basispfad, Kategorie f&uuml;r Einsatzberichte, Schlagw&ouml;rter und einiges mehr

= 0.9.2 =
Kleine Verbesserungen, siehe Changelog f&uuml;r Details

= 0.9.1 =
Update f&uuml;r WordPress 4.2, sowie Fehlerbehebung und Verbesserung

= 0.9.0 =
Siehe Changelog f&uuml;r Details und wichtigen Hinweis

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
Neue Rechteverwaltung, neue Darstellungsoption f&uuml;r Einsatzliste

= 0.6.0 =
Neuerungen, Verbesserungen, Fehlerbehebungen. Da ist f&uuml;r alle was dabei.

= 0.5.4 =
Korrektur des Datums im Feed, bitte Update erst nach Backup durchf&uuml;hren

= 0.5.3 =
Erh&ouml;hte Kompatibilit&auml;t f&uuml;r zuk&uuml;nftige Installationen

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
* GNU social: [@einsatzverwaltung](https://gnusocial.abrain.de/einsatzverwaltung)
