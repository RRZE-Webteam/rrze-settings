# Funktionen und Dienste im Netzwerk steuern

Unter **Netzwerkverwaltung → CMS → Funktionen und Dienste** legen Sie fest,
welche WordPress-Funktionen und Dienste im Netzwerk verfügbar sind. Die Seite
hieß bisher **Website Funktionen**. Ihre technische Adresse bleibt
`admin.php?page=rrze-settings-website-functions`.

Die Einstellungen sind in fünf Bereiche gegliedert:

- Schnittstellen und KI
- Darstellung und Schriften
- Kommentare und Avatare
- Website-Verwaltung
- Übersetzung in der Google-Suche

Das Inhaltsverzeichnis führt zum jeweiligen Abschnitt und hebt ihn hervor.
**Zur Übersicht** führt zurück und hebt die Markierung auf. Trennlinien grenzen
die Bereiche ab. Beschreibungstexte sind auf 70ch, die Übersicht-Links auf 75ch
begrenzt. **Details und Beispiele** lassen sich auch per Tastatur öffnen.
**Änderungen speichern** speichert alle Felder gemeinsam für das gesamte Netzwerk.

Die meisten angehakten Optionen schränken eine Funktion ein. Die Option zur
Google-Übersetzung **erlaubt dagegen eine lokale Entscheidung**. Das Abschalten
einer Einschränkung stellt nicht immer frühere lokale Einstellungen wieder her.
Laden Sie betroffene Seiten nach dem Speichern neu.

## Schnittstellen und KI

### XML-RPC

**XML-RPC-Schnittstelle sperren** blockiert Anfragen an `xmlrpc.php` mit HTTP 403.
Anwendungen, die über XML-RPC veröffentlichen oder andere Aufgaben ausführen,
können diese Schnittstelle dann nicht mehr nutzen. Prüfen Sie die Anbindung
solcher Anwendungen vor dem Aktivieren.

Die REST-API wird separat unter **CMS → REST-API** gesteuert. Beim Ausschalten
entfällt die XML-RPC-Sperre durch dieses Plugin.

### WordPress-KI-Funktionen

**KI-Anbindung von WordPress deaktivieren** meldet die KI-Unterstützung von
WordPress als deaktiviert und unterbindet Anfragen über den WordPress-KI-Client.
Das betrifft auch Plugins, die diese Anbindung verwenden. Eigenständige
KI-Verbindungen anderer Plugins werden dadurch nicht allgemein gesperrt.

Installierte Plugins und gespeicherte Anbieter-Einstellungen werden nicht
gelöscht. Für den Zugriff auf die Einstellungsseite gibt es eine eigene Option.

### Einstellungen für KI-Verbindungen

**Seite für KI-Verbindungen ausblenden und direkten Zugriff sperren** entfernt
die entsprechende Einstellungsseite aus der Website-Verwaltung. Direkte Aufrufe
werden zum Dashboard weitergeleitet. Das gilt in der Website-Verwaltung auch
für Superadministratoren; die Netzwerkverwaltung wird davon nicht erfasst.

Diese Option sperrt die Oberfläche. Sie deaktiviert keine vorhandenen
KI-Verbindungen und verhindert für sich genommen keine KI-Anfragen. Dafür dient
die separate Option **WordPress-KI-Funktionen**.

Beim Ausschalten ist die Seite wieder zugänglich, sofern sie in der installierten
WordPress-Version vorhanden ist und der Benutzer die erforderlichen Rechte hat.

## Darstellung und Schriften

### Emoji-Darstellung

**Zusätzliche Emoji-Darstellung von WordPress deaktivieren** entfernt
Emoji-Skripte und ausgewählte Bildumwandlungen von WordPress, unter anderem in
E-Mails und im klassischen Editor. Vorhandene Emoji-Zeichen bleiben im Inhalt
gespeichert. Manuell eingefügte Bilder werden nicht entfernt.

Browser und Betriebssysteme können Emojis weiterhin mit eigenen Schriften
darstellen. Ihr Aussehen kann sich daher zwischen Geräten unterscheiden. Die
Option verhindert keine Emoji-Eingabe. Beim Ausschalten verwendet WordPress
wieder seine reguläre Verarbeitung.

### Google-Schriften

**Unterstützte Google-Fonts-Einbindungen deaktivieren** greift bei Themes und
Plugins, die bestimmte Schrift-Schalter in Übersetzungen verwenden. Der Filter
setzt diese Schalter auf „aus“.

Dies ist **keine allgemeine Sperre für externe Schriftanfragen**. Einbindungen
direkt in HTML, CSS oder JavaScript können weiterhin aktiv sein. Prüfen Sie
die tatsächlichen Anfragen des verwendeten Themes und der Plugins separat.
Lokal bereitgestellte Schriften bleiben verfügbar. Wo die Option greift,
bestimmen die Ersatzschriften des Themes die neue Darstellung.

### Schriftartenbibliothek in der Verwaltung

**Seite zur Schriftartenverwaltung ausblenden und direkten Zugriff sperren**
entfernt die separate Bibliotheksseite unter **Design**. Ein direkter Aufruf
wird mit einer Fehlermeldung und HTTP 403 abgewiesen. Diese Einschränkung gilt
auch für Superadministratoren.

Installierte Schriftdateien und deren Verwendung auf der Website bleiben
erhalten. Die Schriftartenbibliothek **im Block-Editor** wird separat unter
[CMS → Editor](editor-settings.md#quellen-und-bibliotheken) gesteuert.
Beim Ausschalten wird die Verwaltungsseite für berechtigte Benutzer wieder
zugänglich, sofern die WordPress-Version sie bereitstellt.

## Kommentare und Avatare

### Kommentarvorgaben

**Kommentare einschränken und geschlossene Vorgaben setzen** kombiniert drei
Wirkungen:

| Bereich | Wirkung |
| --- | --- |
| Alle Websites und vorhandene Inhalte | Nicht angemeldete Besucher können nicht kommentieren. Angemeldete Benutzer können nur bei geöffneten Kommentaren kommentieren. |
| Neue Beiträge und Seiten | Kommentare werden standardmäßig geschlossen, sofern kein ausdrücklicher Kommentarstatus übergeben wird. Eine ausdrückliche Wahl wird respektiert. |
| Neu angelegte Websites | Kommentare sowie eingehende Pingbacks/Trackbacks werden standardmäßig deaktiviert, ausgehende Link-Benachrichtigungen abgeschaltet und die Anmeldepflicht für Kommentare aktiviert. |

Vorhandene Kommentare werden nicht gelöscht. Bestehende Beiträge und Seiten
behalten ihren gespeicherten Kommentarstatus. Bestehende Websites erhalten die
Startwerte für neue Websites nicht nachträglich.

Beim Ausschalten entfällt die laufende Einschränkung durch dieses Plugin.
Bereits gespeicherte Website-Vorgaben und Kommentarstatus bleiben bestehen.
Passen Sie diese bei Bedarf lokal an, etwa unter **Einstellungen → Diskussion**
oder am jeweiligen Beitrag.

### Avatar-Anzeige

**WordPress-Avatare deaktivieren und deren Einstellungen ausblenden** schreibt
beim Laden einer Website die lokale Option zur Avatar-Anzeige auf „aus“ und setzt
den Standard-Avatar auf **Geheimnisvolle Person**. Der Avatar-Bereich unter
**Einstellungen → Diskussion** wird ausgeblendet.

Plugins können weiterhin eigene Profilbilder anzeigen, wenn sie die
WordPress-Einstellung nicht verwenden. Beim Ausschalten werden die Einstellungen
wieder zugänglich; frühere Werte werden nicht wiederhergestellt. Aktivieren Sie
die Avatar-Anzeige bei Bedarf wieder lokal.

## Website-Verwaltung

### Löschen von Websites

**Website-Administratoren am Löschen ihrer Website hindern** entfernt
**Werkzeuge → Website löschen** und sperrt diesen Löschablauf für Benutzer ohne
Superadministrator-Rechte. Superadministratoren können Websites weiterhin löschen.

Das Löschen einzelner Beiträge, Seiten und Medien bleibt möglich, sofern der
Benutzer die entsprechenden Rechte besitzt. Beim Ausschalten steht der übliche
WordPress-Löschablauf für berechtigte Benutzer wieder zur Verfügung.

### WordPress-Datenschutzwerkzeuge

**Datenschutzeinstellungen und Werkzeuge für personenbezogene Daten sperren**
entzieht die Berechtigung für **Einstellungen → Datenschutz** und die Werkzeuge
zum Exportieren und Löschen personenbezogener Daten. Dies gilt auch für
Superadministratoren.

Die vorhandene Datenschutzerklärungsseite bleibt gespeichert und entsprechend
ihrem Veröffentlichungsstatus erreichbar. Diese Option löscht keine
personenbezogenen Daten und ersetzt keine Datenschutzerklärung. Eigenständige
Datenschutzfunktionen anderer Plugins werden dadurch nicht allgemein gesperrt.
Beim Ausschalten gelten wieder die normalen WordPress-Berechtigungen.

### Erinnerung an die Administrator-E-Mail

**Wiederkehrende Prüfung der Administrator-E-Mail überspringen** unterdrückt die
gelegentliche Aufforderung nach der Anmeldung, die Administrator-E-Mail-Adresse
der Website zu bestätigen. Das ist etwa bei zentral gepflegten Adressen hilfreich.

Die gespeicherte Adresse und der E-Mail-Versand bleiben unverändert. Die Option
bestätigt keine neu eingetragene Adresse und schaltet die Bestätigung bei einer
Adressänderung nicht aus. Beim Ausschalten gilt wieder die reguläre Erinnerung.

## Übersetzung in der Google-Suche

### Lokale Übersetzungsvorgabe

**Websites das Abschalten von Übersetzungen in der Google-Suche erlauben**
schaltet zunächst nur eine lokale Option unter **Einstellungen → Lesen** frei.
Erst wenn ein Website-Administrator dort **Google anweisen, für diese Website
keine übersetzten Suchergebnisse anzubieten** aktiviert, gibt dieses Plugin auf
den öffentlichen HTML-Seiten über `wp_head` folgende Anweisung aus:

```html
<meta name="googlebot" content="notranslate">
```

Google beschreibt `notranslate` als Vorgabe für Übersetzungsfunktionen in der
Google-Suche. Es ist keine Zugriffssperre und verhindert nicht, dass Besucher
Inhalte selbst übersetzen. Siehe die
[Google-Dokumentation zu übersetzten Suchergebnissen](https://developers.google.com/search/docs/appearance/translated-results?hl=de).

| Netzwerkfreigabe | Lokale Option | Wirkung dieses Plugins |
| --- | --- | --- |
| Aus | Beliebiger gespeicherter Wert | Lokale Option verborgen; keine Anweisung im HTML. |
| Ein | Aus | Lokale Option sichtbar; keine Anweisung im HTML. |
| Ein | Ein | Lokale Option sichtbar; Anweisung wird ausgegeben. |

Beim Entziehen der Netzwerkfreigabe bleibt der lokale Wert gespeichert. Wird die
Freigabe erneut erteilt, gilt die gespeicherte Entscheidung wieder. Andere Plugins
oder Themes können unabhängig davon eigene Meta-Tags ausgeben.

## Anfängliche Standardwerte

Diese Werte stammen aus der Plugin-Konfiguration. Bereits gespeicherte
Netzwerkeinstellungen werden durch die Überarbeitung der Oberfläche nicht geändert.

| Einstellung | Standard |
| --- | --- |
| XML-RPC-Schnittstelle sperren | Aus |
| KI-Anbindung von WordPress deaktivieren | Ein |
| Seite für KI-Verbindungen sperren | Ein |
| Zusätzliche Emoji-Darstellung deaktivieren | Aus |
| Unterstützte Google-Fonts-Einbindungen deaktivieren | Aus |
| Schriftartenbibliothek in der Verwaltung sperren | Ein |
| Kommentare einschränken und geschlossene Vorgaben setzen | Ein |
| WordPress-Avatare deaktivieren | Aus |
| Löschen von Websites einschränken | Aus |
| WordPress-Datenschutzwerkzeuge sperren | Aus |
| Wiederkehrende Prüfung der Administrator-E-Mail überspringen | Aus |
| Lokale Google-Übersetzungsvorgabe erlauben | Aus |

Der lokale Standard für die Google-Übersetzungsvorgabe ist ebenfalls **Aus**.
