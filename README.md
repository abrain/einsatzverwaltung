# Einsatzverwaltung
## Plugin zur Verwaltung von Feuerwehreins&auml;tzen

![Wordpress Plugin: Required WP Version](https://img.shields.io/wordpress/plugin/wp-version/einsatzverwaltung.svg)
![Wordpress Plugin: Tested WP Version](https://img.shields.io/wordpress/plugin/tested/einsatzverwaltung.svg)
![Wordpress Plugin Active Installs](https://img.shields.io/wordpress/plugin/installs/einsatzverwaltung.svg)
[![Wordpress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/einsatzverwaltung.svg)](https://wordpress.org/support/plugin/einsatzverwaltung/reviews/)
[![Build Status](https://drone.abrain.dev/api/badges/abrain/einsatzverwaltung/status.svg?ref=refs/heads/develop)](https://drone.abrain.dev/abrain/einsatzverwaltung)

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Diese Einsatzberichte werden wie gew&ouml;hnliche WordPress-Beitr&auml;ge erstellt, es k&ouml;nnen aber zus&auml;tzliche Informationen wie Alarmzeit, Art des Einsatzes, eingesetzte Fahrzeuge und vieles mehr angegeben werden. Zudem stellt das Plugin verschiedene M&ouml;glichkeiten zur Darstellung der Einsatzberichte zur Verf&uuml;gung.

Die prim&auml;re Zielgruppe des Plugins sind Feuerwehren im deutschsprachigen Raum, es ist aber genauso geeignet f&uuml;r Rettungsdienste, die Wasserwacht, das THW und sonstige Hilfsorganisationen, die ihre Eins&auml;tze im Internet pr&auml;sentieren m&ouml;chten.

[Plugin auf wordpress.org](https://wordpress.org/plugins/einsatzverwaltung/) - [Benutzerhandbuch](https://einsatzverwaltung.abrain.de/dokumentation/) - [Changelog](https://github.com/abrain/einsatzverwaltung/releases)

Uses Font Awesome by Dave Gandy - http://fontawesome.io

### Hinweise f&uuml;r Entwickler
Dieses Projekt arbeitet mit [git-flow](http://nvie.com/posts/a-successful-git-branching-model/).
Die Entwicklung findet im Branch [develop](https://github.com/abrain/einsatzverwaltung/tree/develop) statt, im Branch [master](https://github.com/abrain/einsatzverwaltung/tree/master) befindet sich immer der Stand der zuletzt ver&ouml;ffentlichten Version.

Pull Requests können nur gegen `develop` gestellt werden.
Die Änderungen im Fork müssen auf einem eigenen Branch (also nicht `develop`) vorgenommen werden, da sonst die Code Coverage nicht korrekt bestimmt wird.

Das Plugin an sich liegt im Ordner `src`, alles andere dient der Unterst&uuml;tzung bei der Entwicklung.
Code aus einem anderen Branch als `master` sollte nicht f&uuml;r Produktivsysteme verwendet werden.

Der PHP-Code wird gem&auml;&szlig; dem Coding Style Guide [PSR-2](https://www.php-fig.org/psr/psr-2/) formatiert.
Klassen werden automatisch geladen, die Dateien sind nach [PSR-4](https://www.php-fig.org/psr/psr-4/) zu organisieren.

### Mindestvoraussetzungen
* PHP: 7.1
* WordPress: 5.1

### Social Media

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Mastodon: [@einsatzverwaltung](https://chaos.social/@einsatzverwaltung)
