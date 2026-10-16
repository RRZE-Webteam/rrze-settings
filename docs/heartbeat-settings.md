# Heartbeat im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → RRZE Settings → Heartbeat** steuern Sie regelmäßige
Hintergrundanfragen von WordPress. Heartbeat unterstützt unter anderem die
Aktualisierung von Bearbeitungssperren, Anmeldestatus-Prüfungen und Autosave im
klassischen Editor. Plugins können darüber weitere Live-Aktualisierungen anbieten.

Die Seite gliedert sich in **Einsatzbereiche**, **Standardintervalle** und
**Rollen-Ausnahmen**. Das Inhaltsverzeichnis führt zu den Abschnitten; der
angesprungene Abschnitt wird hervorgehoben. **Zur Übersicht** führt zurück und
hebt die Markierung auf. Alle Felder werden gemeinsam gespeichert.

Die Einstellungen gelten im gesamten Netzwerk. Laden Sie betroffene Seiten nach
dem Speichern neu. Beschreibungstexte sind auf 70ch, die Linkliste der Übersicht
auf 75ch begrenzt. **Details und Beispiele** lassen sich per Tastatur aufklappen.

## Einsatzbereiche

### Öffentliche Website

**Heartbeat für nicht angemeldete Besucher abschalten** entfernt das
Heartbeat-Skript auf öffentlichen Seiten für ausgeloggte Besucher. Angemeldete
Besucher und die Customizer-Vorschau sind ausgenommen.

Die Option wirkt nur dort, wo ein Theme oder Plugin Heartbeat tatsächlich lädt.
Sie schaltet weder andere AJAX- oder REST-Anfragen noch geplante WordPress-Aufgaben
ab. Beim Ausschalten dieser Option entfernt dieses Plugin das Skript dort nicht
mehr; es lädt Heartbeat aber nicht automatisch auf jeder öffentlichen Seite.

### Verwaltung außerhalb der Editoren

**Heartbeat auf anderen Verwaltungsseiten abschalten** entfernt das Skript auf
Verwaltungsseiten, ausgenommen:

- `post.php` und `post-new.php`, unter anderem die klassischen und die
  Block-Inhaltseditoren für Beiträge, Seiten und andere Inhaltstypen;
- die ältere Website-Editor-Adresse mit `page=gutenberg-edit-site`;
- Seiten in der Liste **Weitere Ausnahmen in der Verwaltung**.

Die Einschränkung gilt auch für Superadministratoren. Heartbeat-abhängige
Plugin-Statusanzeigen, Anmeldestatus-Prüfungen oder andere Live-Aktualisierungen
können auf den betroffenen Seiten entfallen.

Die aktuelle WordPress-Seite `site-editor.php` ist in der vorhandenen Umsetzung
**nicht automatisch ausgenommen**. Soll Heartbeat dort verfügbar bleiben, tragen
Sie `site-editor.php` in die Ausnahmeliste ein. Bei der Intervallwahl behandelt
das Plugin diese Seite als übrige Verwaltung.

### Weitere Ausnahmen in der Verwaltung

Die Liste wird verwendet, wenn die Einschränkung der Verwaltung aktiviert ist.
Tragen Sie eine genaue WordPress-Seitenkennung pro Zeile ein:

```text
index.php
upload.php
site-editor.php
```

Diese Beispiele stehen für Dashboard, Mediathek und Website-Editor. Verwenden
Sie keine vollständigen URLs, Menünamen oder Platzhalter. Plugin-Seiten benötigen
ihren registrierten Hook-Suffix; der URL-Parameter `page` allein ist dafür nicht
immer ausreichend. Der Vergleich erfolgt exakt, einschließlich Groß- und
Kleinschreibung. Doppelte Einträge werden beim Speichern zusammengeführt.

Eine leere Liste entfernt die zusätzlichen Ausnahmen. Die automatisch geschützten
Editor-Seiten bleiben ausgenommen. Ein Eintrag verhindert nur das Abschalten
durch dieses Plugin: Er erzwingt weder das Laden von Heartbeat noch ein bestimmtes
Intervall. Andere Plugins können das Skript weiterhin beeinflussen.

## Standardintervalle

Die Intervalle sind Vorgaben in Sekunden. Sie gelten auf Verwaltungsseiten, auf
denen Heartbeat verfügbar ist, wenn keine Rollen-Ausnahme greift. Sie steuern
nicht den Takt von Heartbeat auf der öffentlichen Website.

| Einstellung | Geltungsbereich | Mindestwert | Anfänglicher Standard |
| --- | --- | --- | --- |
| Intervall im Inhaltseditor | Automatisch geschützte Editor-Seiten | 15 Sekunden | 15 Sekunden |
| Intervall in der übrigen Verwaltung | Andere freigegebene Verwaltungsseiten | 60 Sekunden | 60 Sekunden |

Längere Intervalle reduzieren regelmäßige Anfragen, verzögern aber Aktualisierungen.
Ein Wechsel von 60 auf 120 Sekunden halbiert ungefähr die regelmäßigen Anfragen
einer aktiven Seite. Das ist keine garantierte Anfragegrenze für die gesamte
Website: Mehrere offene Tabs, Plugins, der Seitenfokus und WordPress selbst
beeinflussen den tatsächlichen Takt. WordPress kann inaktive Tabs verlangsamen
oder Anfragen bei längerer Inaktivität aussetzen.

Besonders im Inhaltseditor müssen Bearbeitungssperren rechtzeitig erneuert
werden. Ist der Abstand zwischen Aktualisierungen länger als die Gültigkeit einer
Sperre, kann diese zwischenzeitlich ablaufen. Die normale WordPress-Sperrdauer
beträgt 150 Sekunden; dieses Plugin kann unter **RRZE Settings → Editor** eine abweichende
Dauer pro Website erlauben. Das Heartbeat-Intervall ist keine maximale
Bearbeitungszeit.

### Zusammenspiel mit Autosave

Der klassische Editor überträgt automatische Sicherungen über Heartbeat.
Längere Abstände können damit auch den Transport einer anstehenden Sicherung
verzögern.

Autosave im Block-Editor wird separat unter **RRZE Settings → Editor** konfiguriert.
Ist dort **Autosave-Intervall aus Heartbeat ableiten** aktiviert, verwendet es:

```text
Autosave-Intervall = max(60 Sekunden, 2 × gespeichertes Editor-Standardintervall)
```

Beispiel: Ein gespeicherter Standard von 60 Sekunden ergibt ein
Autosave-Intervall von 120 Sekunden. Rollen-Ausnahmen werden für diese Ableitung
nicht berücksichtigt. Ein Benutzer kann daher ein anderes effektives
Heartbeat-Intervall als das für die Ableitung verwendete Intervall haben.

### Intervall im Browser nachsetzen

Diese Option setzt das berechnete Intervall nach dem Laden der Verwaltungsseite
zusätzlich per JavaScript. Sie verwendet dieselbe Berechnung einschließlich
Rollen-Ausnahmen wie die PHP-Vorgabe. Der frühere Name mit „slow“ war irreführend:
Die Funktion setzt **keinen festen 60-Sekunden-Takt**.

Der Aufruf erfolgt einmal, sobald die Seite bereit ist. Er kann helfen, wenn die
anfängliche PHP-Vorgabe nicht wie erwartet greift. Er aktiviert kein entferntes
Heartbeat-Skript und verhindert keine späteren Änderungen durch WordPress oder
andere Plugins. Bei ausgeschalteter Option bleibt die PHP-Intervallvorgabe aktiv.

## Rollen-Ausnahmen

Das Feld erwartet ein JSON-Objekt mit technischen Rollennamen. Zum Beispiel:

```json
{
  "editor": {
    "editor": 30,
    "default": 90
  },
  "author": {
    "editor": 45,
    "default": 120
  }
}
```

Die äußeren Namen `editor` und `author` sind Benutzerrollen; verwenden Sie keine
übersetzten Anzeigenamen wie „Redakteur“. Eigene registrierte Rollen sind ebenfalls
möglich. Die inneren Schlüssel haben eine andere Bedeutung:

- `editor`: Intervall auf den automatisch erkannten Inhaltseditor-Seiten,
  mindestens 15 Sekunden.
- `default`: Intervall auf anderen Verwaltungsseiten, mindestens 60 Sekunden.
  **Fehlt `editor`, gilt `default` auch im Inhaltseditor.**

Das Plugin prüft die Rollen in der beim Benutzer gespeicherten Reihenfolge.
Für jede Rolle verwendet es im Inhaltseditor zuerst `editor`, falls vorhanden,
ansonsten `default`. Auf anderen Verwaltungsseiten sucht es nach `default`.
Der erste passende Wert gewinnt. Erst wenn keine Rolle passt, gilt das allgemeine
Intervall. Weder die Reihenfolge der Rollen im JSON noch das kürzeste Intervall
oder die Rolle mit den meisten Rechten entscheidet automatisch.

Rollen-Ausnahmen ändern keine Berechtigungen und heben das Abschalten von
Heartbeat nicht auf. Für Superadministratoren gibt es keine zusätzliche
Intervall-Ausnahme; ausschlaggebend sind ihre Rollen auf der jeweiligen Website.

Verwenden Sie ganze Sekunden und doppelte Anführungszeichen. JSON erlaubt keine
Kommentare oder abschließenden Kommas. Ein leeres Feld oder `{}` entfernt alle
Rollen-Ausnahmen; eine bereits gespeicherte leere Liste `[]` wird ebenfalls
akzeptiert. Danach gelten die allgemeinen Intervalle.

Ungültiges JSON, eine falsche Struktur oder ungültige Intervallwerte werden nicht
teilweise übernommen: Die bisherigen Rollenregeln bleiben erhalten, und nach
dem Speichern erscheint eine Fehlermeldung. Andere Einstellungen auf der Seite
werden gespeichert. Gültige Zahlen unterhalb der Mindestwerte werden auf die
jeweiligen Mindestwerte angehoben.

## Standardwerte einer neuen Konfiguration

Alle drei Checkboxen sind zunächst ausgeschaltet. Die Intervallvorgaben sind
15 Sekunden für Inhaltseditoren und 60 Sekunden für die übrige Verwaltung.
Als zusätzliche Ausnahme ist `index.php` eingetragen.

Die neue Konfiguration enthält bereits diese Rollen-Ausnahmen:

| Rolle | `editor` | `default` |
| --- | --- | --- |
| `administrator` | 15 Sekunden | 60 Sekunden |
| `editor` | 30 Sekunden | 90 Sekunden |
| `author` | 45 Sekunden | 120 Sekunden |

Diese Regeln haben Vorrang vor den allgemeinen Intervallen. Wenn eine Änderung
des allgemeinen Intervalls scheinbar nicht greift, prüfen Sie zuerst diese Liste.
Bestehende Installationen können andere gespeicherte Werte verwenden.
