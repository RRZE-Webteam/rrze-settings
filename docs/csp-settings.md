# Content Security Policy im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → CMS → CSP** legen Sie fest, welche Quellen der
Browser für eine Website laden darf. Die Seite heißt **Content Security Policy
(CSP)**; der Menüpunkt bleibt kurz **CSP**.

Die Einstellungen gelten netzwerkweit für normale Website-Antworten, die
WordPress verarbeitet. CSP beschränkt Ressourcen im Browser. Sie vergibt keine
Benutzerrechte und macht eine Website nicht privat.

Fünf Abschnitte ordnen die Einstellungen:

- Aktivierung und Wirkung
- Grundregel und Quellen
- Skripte und Styles
- Bilder und Schriften
- Verbindungen und Einbettungen

Das Inhaltsverzeichnis springt zum jeweiligen Abschnitt und hebt ihn hervor.
**Zur Übersicht** führt zurück. Alle Felder werden gemeinsam gespeichert.
Die sieben Quellenlisten bleiben auch bei ausgeschalteter CSP bearbeitbar.
Beschreibungstexte sind auf 70ch, die Übersicht-Links auf 75ch begrenzt.

## Aktivierung und Wirkung

**Content Security Policy aktivieren und durchsetzen** ergänzt den HTTP-Header
`Content-Security-Policy` mit den konfigurierten Regeln. Der Browser blockiert
Ressourcen, die diese Richtlinie nicht erlaubt. Fehlende Freigaben können etwa
Formulare, Menüs, Schriftarten oder Videoplayer beeinträchtigen.

Diese Seite bietet **keinen Report-Only-Modus**, kein Meldeziel und keine
Berichtsauswertung. Bereiten Sie die Quellenlisten deshalb vor der Aktivierung
vor und prüfen Sie Änderungen auf einer geeigneten Testwebsite. Der Unterschied
zwischen Durchsetzung und Beobachtung ist im
[CSP-Standard des W3C](https://www.w3.org/TR/CSP3/#policy-delivery) beschrieben.

Beim Ausschalten ergänzt dieses Modul seinen CSP-Header nicht mehr. Die
Quellenlisten bleiben gespeichert. CSP-Vorgaben anderer Plugins, eines Proxys
oder des Webservers werden dadurch nicht entfernt. Mehrere ausgelieferte
Richtlinien gelten zusätzlich zueinander; eine großzügigere Richtlinie hebt eine
strengere nicht auf. Siehe
[W3C zur Wirkung mehrerer Richtlinien](https://www.w3.org/TR/CSP3/#multiple-policies).

Die Ausgabe erfolgt über die WordPress-Antwort-Header. Verwaltungsseiten,
statische Dateien und direkt vom Webserver oder Cache ausgelieferte Antworten
werden durch diese Anbindung nicht automatisch erfasst.

## Grundregel und Quellen

### Quellen eintragen

Tragen Sie eine Quelle pro Zeile ein. Die folgende Liste zeigt das Format und
ist keine allgemeine Freigabeempfehlung:

```text
'self'
https://cdn.example.org
```

Ersetzen Sie Beispieldomains durch die tatsächlichen Quellen. Verwenden Sie
keine Direktiven-Namen, vollständigen Header, Semikolons oder Kommentare im Feld.
Das Formular prüft nicht die vollständige CSP-Syntax.

| Wert | Bedeutung |
| --- | --- |
| `'self'` | Aktueller Ursprung der Website, nicht automatisch andere Websites oder Subdomains des Netzwerks. |
| `https://cdn.example.org` | Angegebener Host über HTTPS. |
| `*.example.org` | Subdomains von example.org; example.org selbst ist nicht eingeschlossen. |
| `data:` | Ressourcen als Daten-URLs, etwa eingebettete Bilder. |
| `blob:` | Ressourcen über Blob-URLs. |
| `https:` | Alle HTTPS-Hosts für den betreffenden Ressourcentyp; entsprechend weitreichend. |
| `'none'` | Keine Quellen für den von dieser Regel erfassten Ressourcentyp. |

Schlüsselwörter benötigen **gerade einfache Anführungszeichen**, keine
typografischen Anführungszeichen. Domains und Protokolle stehen ohne
Anführungszeichen. Die Schreibweisen beschreibt die
[MDN-Referenz zu CSP-Quellen](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy#fetch_directive_syntax).

### Leeres Feld und none unterscheiden

Ein **leeres Feld** lässt die jeweilige Direktive im erzeugten Header weg.
Das Plugin setzt nicht automatisch den anfänglichen Standardwert wieder ein.
Die Browser-Rückfallregel greift, soweit eine solche vorhanden ist.

Mit **`'none'`** wird eine ausdrückliche Sperre eingetragen. Wird dieser Wert
zusammen mit weiteren Quellen übermittelt, behält das Plugin beim Speichern nur
`'none'`. Das gilt auch für mehrere Zeilen, unterschiedliche Zeilenumbrüche und
von WordPress maskierte Formulareingaben.

### Grundregel default-src

`default-src` ist die Rückfallregel für Ressourcentypen ohne eigene passende
Direktive. Eine ausdrücklich gesetzte Regel wird **nicht mit der Grundregel
zusammengeführt**. Siehe die
[MDN-Erklärung zu default-src](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/default-src).

| Konfiguration | Wirkung auf Bilder |
| --- | --- |
| `default-src` enthält `'self'`, `img-src` ist leer | Bilder verwenden die Grundregel einschließlich etwaiger automatischer Ergänzungen. |
| `img-src` enthält nur `https://images.example.org` | Für Bilder gilt diese eigene Liste. `'self'` aus default-src wird nicht hinzugefügt. |
| `img-src` enthält `'none'` | Bilder sind unter dieser Direktive gesperrt. |
| Beide Felder leer | Diese Richtlinie liefert keine Quellenbeschränkung für Bilder. |

`default-src` steuert außerdem Ressourcentypen ohne eigenes Feld auf dieser Seite,
etwa Audio- und Videodateien über den Rückfall von `media-src`.

### Automatische Ergänzungen für Einbettungen

Das Plugin kann Hosts aus erfassten oEmbed-Anbietern und unterstützten
Einbettungsdiensten an `default-src` anhängen. Das geschieht nur, wenn die
Grundregel gefüllt ist und nicht genau `'none'` enthält.

Diese Ergänzungen werden nicht in das Textfeld zurückgeschrieben. Sie hängen
von den auf der jeweiligen Website erfassten Anbietern ab. Maßgeblich ist daher
der tatsächlich ausgelieferte Header. Ein gefülltes `frame-src` oder `script-src`
übernimmt diese Ergänzungen nicht automatisch.

## Skripte und Styles

### JavaScript script-src

`script-src` steuert Skriptquellen und JavaScript-Ausführung. Eine externe
Skriptquelle kann beispielsweise `https://scripts.example.org` sein.
Lokale Skriptdateien benötigen weiterhin `'self'` in dieser eigenen Liste.

`'unsafe-inline'` erlaubt Inline-JavaScript pauschal, solange Nonces oder Hashes
dies nicht übersteuern. `'unsafe-eval'` erlaubt Codeerzeugung aus Zeichenketten.
Beide Freigaben vermindern den Schutz. Diese Seite erzeugt keine Nonces und
ergänzt keine passenden Attribute an Skripten. Details:
[MDN zu script-src](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/script-src).

### Stylesheets style-src

`style-src` steuert CSS-Dateien und Inline-Styles. Themes und Plugins können
auf `'unsafe-inline'` angewiesen sein; Änderungen können das Layout beeinflussen.
Die Schriftdateien eines Stylesheets benötigen bei einem anderen Host zusätzlich
eine passende Freigabe in `font-src`. Details:
[MDN zu style-src](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/style-src).

## Bilder und Schriften

### Bilder img-src

Erlauben Sie hier die tatsächlichen Bild-Hosts, einschließlich verwendeter
Avatar- und Vorschaubilddienste. `data:` erlaubt Daten-URLs, `blob:` Blob-URLs.
Die Einstellung ändert keine Upload-Rechte und löscht keine Bilder. Sie sperrt
auch nicht den direkten serverseitigen Zugriff auf eine Bildadresse.

### Schriftdateien font-src

Erlauben Sie die Quelle der Schriftdatei selbst, nicht nur die des Stylesheets.
Blockierte Schriften können durch Ersatzschriften dargestellt werden. `data:`
erlaubt als Daten-URL eingebettete Schriften.

Schriftenverwaltung und Google-Fonts-Einbindungen werden separat unter
[CMS → Funktionen und Dienste](website-functions-settings.md#darstellung-und-schriften)
gesteuert. Eine CSP-Freigabe installiert keine Schrift.

## Verbindungen und Einbettungen

### Datenverbindungen connect-src

`connect-src` kontrolliert Ziele von Browser-Anfragen durch Seitenskripte, etwa
`fetch`, `XMLHttpRequest`, `WebSocket`, `EventSource` und `sendBeacon`.
Tragen Sie das tatsächliche Protokoll und den Host ein, beispielsweise
`https://api.example.org` oder `wss://socket.example.org`. Details:
[MDN zu connect-src](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/connect-src).

Die Regel steuert keine ausgehenden HTTP-Anfragen des WordPress-Servers. Sie
ersetzt weder CORS-Prüfungen noch die Zugriffsregeln unter
[CMS → REST-API](rest-api-settings.md). CSP-Blockierungen erscheinen in der
Browser-Konsole; aus ihnen folgt kein bestimmter HTTP-Status des Zielservers.

### Eingebettete Seiten frame-src

`frame-src` erlaubt Seiten, die die eigene Website in Frames oder Iframes lädt,
beispielsweise einen Videoplayer. Es regelt nicht, wer die eigene Website
einbetten darf. Dafür wäre `frame-ancestors` zuständig; dafür gibt es auf dieser
Seite kein Feld. Details:
[MDN zu frame-src](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Content-Security-Policy/frame-src).

Eine Freigabe kann die Einbettungssperre des externen Anbieters nicht aufheben.
Bei gefülltem `frame-src` werden automatische Einbettungs-Hosts aus `default-src`
nicht übernommen. Ein leeres `frame-src` fällt in der hier erzeugten Richtlinie
auf `default-src` zurück.

## Änderungen überprüfen

1. Öffnen Sie nach dem Speichern eine betroffene Website.
2. Prüfen Sie in den Browser-Entwicklerwerkzeugen den Antwort-Header
   `Content-Security-Policy` des HTML-Dokuments. Bei veralteten Werten kann ein
   Seiten-Cache weiterhin eine alte Antwort ausliefern.
3. Lesen Sie CSP-Meldungen in der Konsole. Sie nennen typischerweise die
   verletzte Direktive und die blockierte Ressource.
4. Prüfen Sie, welche konkrete Quelle in welcher Regel fehlt. Berücksichtigen
   Sie zusätzliche CSP-Header und Beschränkungen des eingebundenen Dienstes.
5. Kontrollieren Sie betroffene Funktionen wie Navigation, Formulare, Bilder,
   Schriften und Einbettungen erneut.

## Anfängliche Standardwerte

| Feld | Standard |
| --- | --- |
| CSP durchsetzen | Aus |
| default-src | `'self'` |
| script-src | `'self' 'unsafe-inline'` |
| style-src | `'self' 'unsafe-inline'` |
| img-src | `'self' data: *.gravatar.com` |
| font-src | `'self' data:` |
| connect-src | `'self'` |
| frame-src | `'self'` |

Diese Werte stammen aus der Plugin-Konfiguration. Bestehende gespeicherte
Richtlinien werden durch die Überarbeitung der Seite nicht geändert. Die
automatischen oEmbed-Ergänzungen können im ausgelieferten Header zusätzlich
auftauchen.
