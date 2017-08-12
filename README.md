# Einsatzverwaltung
## Plugin zur Verwaltung von Feuerwehreins&auml;tzen

[![Flattr](https://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/thing/2638688/abraineinsatzverwaltung-on-GitHub) [![Build Status](https://travis-ci.org/abrain/einsatzverwaltung.svg)](https://travis-ci.org/abrain/einsatzverwaltung) [![Code Climate](https://codeclimate.com/github/abrain/einsatzverwaltung/badges/gpa.svg)](https://codeclimate.com/github/abrain/einsatzverwaltung) [![Test Coverage](https://codeclimate.com/github/abrain/einsatzverwaltung/badges/coverage.svg)](https://codeclimate.com/github/abrain/einsatzverwaltung/coverage)

Dieses Plugin f&uuml;gt WordPress eine neue Beitragsart "Einsatzbericht" hinzu. Diese Einsatzberichte werden wie gew&ouml;hnliche WordPress-Beitr&auml;ge erstellt, es k&ouml;nnen aber zus&auml;tzliche Informationen wie Alarmzeit, Art des Einsatzes, eingesetzte Fahrzeuge und vieles mehr angegeben werden. Zudem stellt das Plugin verschiedene M&ouml;glichkeiten zur Darstellung der Einsatzberichte zur Verf&uuml;gung.

Die prim&auml;re Zielgruppe des Plugins sind Feuerwehren im deutschsprachigen Raum, es ist aber genauso geeignet f&uuml;r Rettungsdienste, die Wasserwacht, das THW und sonstige Hilfsorganisationen, die ihre Eins&auml;tze im Internet pr&auml;sentieren m&ouml;chten.

[Plugin auf wordpress.org](https://wordpress.org/plugins/einsatzverwaltung/) - [Benutzerhandbuch](https://einsatzverwaltung.abrain.de/dokumentation/) - [Changelog](https://github.com/abrain/einsatzverwaltung/releases)

Uses Font Awesome by Dave Gandy - http://fontawesome.io

### Hinweise f&uuml;r Entwickler
Dieses Projekt arbeitet mit [git-flow](http://nvie.com/posts/a-successful-git-branching-model/).
Die Entwicklung findet im Branch [develop](https://github.com/abrain/einsatzverwaltung/tree/develop) statt, im Branch [master](https://github.com/abrain/einsatzverwaltung/tree/master) befindet sich immer der Stand der zuletzt ver&ouml;ffentlichten Version.
Pull Requests werden nur im Branch `develop` angenommen.

Das Plugin an sich liegt im Ordner `src`, alles andere dient der Unterst&uuml;tzung bei der Entwicklung.
Code aus einem anderen Branch als `master` sollte nicht f&uuml;r Produktivsysteme verwendet werden.

Der PHP-Code wird gem&auml;&szlig; dem Coding Style Guide [PSR-2](http://www.php-fig.org/psr/psr-2/) formatiert.

### Mindestvoraussetzungen
* PHP: 5.3.0
* WordPress: 4.4

Auch wenn PHP 5.3 vorausgesetzt und derzeit noch als Minimum erhalten wird, werden die automatisierten Tests nur mit den [aktuellen PHP-Versionen](https://secure.php.net/supported-versions.php) durchgef&uuml;hrt.

### Social Media

* Twitter: [@einsatzvw](https://twitter.com/einsatzvw)
* Facebook: [Einsatzverwaltung](https://www.facebook.com/einsatzverwaltung/)
* GNU social: [@einsatzverwaltung](https://gnusocial.abrain.de/einsatzverwaltung)
