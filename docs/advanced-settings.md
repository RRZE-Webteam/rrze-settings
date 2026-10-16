# CSS und Betrieb im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → RRZE Settings → CSS und Betrieb** verwalten Sie eigenes CSS,
zusätzliche Klassen im Block-Editor und eine Markierungsdatei für den
Serverbetrieb. Die Seite hieß bisher **Erweitert**. Ihre Adresse
`admin.php?page=rrze-settings-advanced` bleibt erhalten.

Die Einstellungen gelten netzwerkweit. Drei Abschnitte mit Sprunglinks,
Hervorhebung und Rücklinks zur Übersicht helfen bei der Orientierung:

- Netzwerkweites CSS
- CSS-Klassen im Block-Editor
- Betriebsmarkierung (Sentry)

**Änderungen speichern** speichert alle Abschnitte gemeinsam. Beschreibungstexte
sind auf 70ch, die Übersicht-Links auf 75ch begrenzt. Zusätzliche Erläuterungen
stehen unter **Details und Beispiele**.

## Netzwerkweites CSS

Tragen Sie CSS-Regeln ohne `<style>`-Tags ein. Die Felder verwenden eine Schrift
mit gleich breiten Zeichen und bieten Platz für mehrere eingerückte Zeilen.
Das Formular entfernt HTML-Tags, prüft aber nicht, ob das CSS gültig ist.

### CSS für Websites

Dieses Feld ergänzt CSS auf öffentlichen Seiten aller Websites über die
reguläre WordPress-Style-Ausgabe. Ein Selektor wirkt dort, wo passende Elemente
vorhanden sind. Themes und Plugins liefern weiterhin ihre eigenen Styles.

Beispiel für ein Theme, das die Klasse `site-footer` verwendet:

```css
.site-footer {
    margin-block-start: 2rem;
}
```

Prüfen Sie den Selektor bei den betroffenen Themes. Spezifischere Selektoren
oder spätere Regeln können eine eigene Regel überschreiben. Dieses Feld lädt
die Styles nicht automatisch auf der Anmeldeseite oder in der Bearbeitungsfläche
des Block-Editors.

Ein leeres Feld beendet nach dem Speichern die Ausgabe dieser Ergänzungen.
Theme-Dateien werden dabei nicht verändert. Falls alte Styles weiterhin
erscheinen, prüfen Sie die betroffenen Seiten-Caches.

### CSS für die Verwaltung

Dieses Feld ergänzt CSS in der WordPress-Verwaltung, einschließlich der
Netzwerkverwaltung. Die Regeln können alle Benutzer betreffen, die die jeweilige
Verwaltungsseite aufrufen.

Begrenzen Sie Selektoren auf die gewünschte Seite oder Komponente. Allgemeine
Regeln für Buttons, Formulare oder Navigation können auch Einstellungsseiten
und Bearbeitungswerkzeuge beeinflussen.

Die Bearbeitungsfläche des Block-Editors liegt in einem eigenen Iframe-Dokument.
CSS der umgebenden Verwaltung gestaltet dieses Dokument nicht automatisch.
Passende Styles müssen durch das Theme oder ein Plugin separat in den Editor
geladen werden. Die Klassen im nächsten Abschnitt bieten dafür zusätzliche
Selektoren, laden selbst aber keine Stylesheets.

CSS verändert die Darstellung. Es vergibt keine Berechtigungen: Ein per CSS
ausgeblendeter Button schützt die zugehörige Funktion nicht vor Zugriff.
Ein leeres Feld beendet nach dem Speichern die Ausgabe des zusätzlichen
Verwaltungs-CSS.

## CSS-Klassen im Block-Editor

Die folgenden Optionen ergänzen Klassen am `<body>` des Iframes
`editor-canvas`. Sie verändern weder gespeicherte Blöcke noch die öffentliche
Website. Ohne einen solchen Iframe gibt es kein Ziel für die Ergänzungen.

### Zusätzliche Body-Klassen

Tragen Sie mehrere Namen durch **Kommas** getrennt ein:

```text
site-layout, editor-preview
```

Verwenden Sie Buchstaben a-z, Ziffern, Bindestriche und Unterstriche. Geben Sie
keine führenden Punkte, CSS-Regeln oder vollständigen HTML-Attribute ein.
Leerzeichen oder Zeilenumbrüche allein trennen die Klassen in diesem Feld nicht
zuverlässig: Die Eingabe wird an Kommas aufgeteilt und jeder Name bereinigt.

Ein bereits im Editor geladenes Stylesheet kann die Klasse beispielsweise mit
`body.editor-preview` ansprechen. Das ist etwas anderes als die Einstellung
**Zusätzliche CSS-Klasse(n)** eines einzelnen Blocks.

Ein leeres Feld ergänzt keine manuellen Klassen. Die automatische Theme-Klasse
kann unabhängig davon aktiv bleiben.

### Automatische Theme-Klasse

**Aktives Theme als Body-Klasse im Editor ergänzen** erzeugt den Namen
`wp-theme-` gefolgt vom Verzeichnisnamen des aktiven Themes. Für
`twentytwentyfive` lautet die Klasse beispielsweise `wp-theme-twentytwentyfive`.

Bei einem Child-Theme wird dessen Verzeichnisname verwendet. Der Anzeigename
des Themes und das übergeordnete Theme werden nicht als weitere Klassen
hinzugefügt. Beim Ausschalten entfällt diese automatische Ergänzung; manuelle
Klassen können weiterhin gelten.

### Themes ohne zusätzliche Klassen

Tragen Sie genaue Theme-Verzeichnisnamen durch Kommas getrennt ein:

```text
twentytwentyfive, my-child-theme
```

Der Abgleich verwendet den Verzeichnisnamen des aktiven Themes mit seiner
genauen Schreibweise. Bei Child-Themes muss dessen Verzeichnis eingetragen
werden. Anzeigenamen und Platzhalter sind keine passenden Eingaben.

Für ein ausgenommenes Theme entfallen **beide Ergänzungen dieser Funktion**:
manuelle Body-Klassen und die automatische Theme-Klasse. Das netzwerkweite CSS,
das Theme und Klassen von WordPress oder anderen Plugins bleiben aktiv.
Eine leere Liste nimmt kein Theme aus.

| Manuelle Klassen | Automatische Klasse | Theme ausgenommen | Ergänzungen dieser Funktion |
| --- | --- | --- | --- |
| Eingetragen | An | Nein | Manuelle Klassen und Theme-Klasse |
| Leer | An | Nein | Nur Theme-Klasse |
| Eingetragen | Aus | Nein | Nur manuelle Klassen |
| Beliebig | Beliebig | Ja | Keine |

Nach Änderungen laden Sie den Editor neu und prüfen die Klassen am Body des
Editor-Iframes. Ist eine Klasse vorhanden, aber ohne sichtbare Wirkung, prüfen
Sie, ob das passende Stylesheet in diesem Iframe geladen wird.

## Betriebsmarkierung (Sentry)

Die Markierung ist eine lokale Schnittstelle für Server-Automatisierungen.
Sie richtet keinen externen Überwachungsdienst ein und versendet keine
Benachrichtigungen. Ein Werkzeug auf dem Server muss die Datei selbst überwachen
oder auslesen und eine gewünschte Reaktion auslösen.

### Update-Markierung

**Markierung nach Installationen und Updates schreiben** schreibt die Datei
`.rrze-settings-sentry.json` im WordPress-Stammverzeichnis (`ABSPATH`), wenn
WordPress einen abgeschlossenen Installations- oder Aktualisierungsvorgang
meldet. Erfasst werden die Typen Core, Plugin, Theme und Übersetzung.

Die Datei enthält das zuletzt erfasste Ereignis:

| Schlüssel | Inhalt |
| --- | --- |
| `last_update_unix` | Zeitpunkt als Unix-Zeitstempel |
| `last_update_utc` | Zeitpunkt in UTC als ISO-8601-Zeichenkette |
| `action` | `install`, `update` oder bei aktivierter Lösch-Markierung `delete` |
| `type` | `core`, `plugin`, `theme` oder `translation` |
| `bulk` | Ob WordPress den Vorgang als Sammelvorgang gemeldet hat |
| `items` | Betroffene Elemente, soweit WordPress entsprechende Angaben liefert |

Jedes Ereignis überschreibt den bisherigen Dateiinhalt. Die Datei ist daher
kein vollständiges Ereignisprotokoll. Eine Abschlussmeldung ist außerdem keine
Garantie, dass jedes Element eines Update-Vorgangs erfolgreich aktualisiert wurde.

WordPress benötigt Schreibzugriff auf das Stammverzeichnis. Kann der JSON-Inhalt
nicht geschrieben werden, versucht das Plugin, den Dateizeitstempel zu
aktualisieren. Ein geänderter Zeitstempel allein garantiert deshalb keinen neuen
JSON-Inhalt. Die Einstellungsseite zeigt keinen Schreibtest oder Dateistatus an.

Das Aktivieren allein erzeugt noch keine Datei; erst ein passendes Ereignis
schreibt die Markierung. Eine Datei gilt für die WordPress-Installation, nicht
jeweils für eine einzelne Website. Beim Deaktivieren bleibt eine bereits
vorhandene Datei bestehen und wird durch dieses Modul nicht weiter aktualisiert.
Direkte Dateiänderungen, etwa durch einen Deployment-Prozess, werden ohne die
entsprechenden WordPress-Hooks nicht erfasst.

### Lösch-Markierung

**Erfolgreiche Plugin- und Theme-Löschungen ebenfalls erfassen** wirkt nur bei
aktivierter **Update-Markierung**. Nach einer erfolgreichen Löschung durch
WordPress wird dieselbe Datei mit `action: delete` aktualisiert.

Die Option löst keine Löschungen aus und verhindert sie nicht. Sie erfasst keine
Plugin-Deaktivierungen und keine Löschungen von Beiträgen, Medien oder Websites.
Wird die Update-Markierung ausgeschaltet, bleibt die Auswahl für Löschungen
gespeichert. Sie wirkt erst wieder nach dem Einschalten der Hauptoption.

## Anfängliche Standardwerte

| Einstellung | Standard |
| --- | --- |
| CSS für Websites | Leer |
| CSS für die Verwaltung | Leer |
| Zusätzliche Body-Klassen | Leer |
| Automatische Theme-Klasse | An |
| Themes ohne zusätzliche Klassen | Leer |
| Update-Markierung | Aus |
| Lösch-Markierung | Aus |

Die Überarbeitung von Beschriftungen und Hilfetexten verändert bestehende
gespeicherte Werte nicht. Die Einstellungen für KI, KI-Verbindungen und die
Schriftartenbibliothek finden Sie unter
[RRZE Settings → Funktionen und Dienste](website-functions-settings.md).
