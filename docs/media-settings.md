# Medien im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → RRZE Settings → Medien** verwalten Sie Uploads, Bildverarbeitung,
Werkzeuge für die Mediathek und Ordnungssysteme für Medien. **Medien** bleibt der
Seitenname, weil die Einstellungen Bilder, Dokumente und andere Dateien betreffen.
Das Inhaltsverzeichnis **Auf dieser Seite** führt zu den vier Abschnitten;
**Zur Übersicht** führt jeweils zurück. Die Sprungziele sind per Tastatur
erreichbar und berücksichtigen die WordPress-Werkzeugleiste. Der angesprungene
Abschnitt erhält einen dezenten Hintergrund und eine farbige Kante wie auf der
Plugins-Seite. Der Rücksprung zur Übersicht hebt die Markierung wieder auf.
Trennlinien gliedern die Abschnitte und trennen sie von der Übersicht.
Die Linkliste der Übersicht ist auf 75ch begrenzt. Die Rücklinks stehen rechts
am jeweiligen Abschnittsrand, auch wenn sie auf schmalen Bildschirmen umbrechen.
Alle Felder bleiben sichtbar und werden zusammen gespeichert.

Die Einstellungen gelten für alle Websites im Netzwerk. Das Speichern allein
verarbeitet keine vorhandenen Dateien nachträglich. Kurze Erklärungen stehen
direkt an den Feldern; **Details und Beispiele** ergänzen Abhängigkeiten und
Auswirkungen auf vorhandene Medien. Die Textbreite ist auf 70ch begrenzt.

## Uploads und Dateitypen

### Dateinamen beim Upload

Die zusätzliche Bereinigung ersetzt unterstützte Umlaute und Akzente, entfernt
übrige Nicht-ASCII-Zeichen und schreibt die Dateiendung klein. Die Umwandlung des
Plugins enthält beispielsweise `ä → ae` und `ß → ss`; WordPress kann Zeichen
bereits vorher normalisiert haben. Das endgültige Ergebnis hängt deshalb auch
von der WordPress-Bereinigung ab.

Vorhandene Dateinamen, URLs, Titel, Bildunterschriften und Alternativtexte werden
nicht nachträglich geändert. Beim Ausschalten bleibt die eigene
Dateinamen-Bereinigung von WordPress aktiv.

### SVG-Verarbeitung

Diese Option ergänzt die SVG-Darstellung in der Mediathek und bereinigt Dateien
beim regulären SVG-Upload. Elemente und Attribute außerhalb der Freigabeliste
werden entfernt. Schlägt die Bereinigung fehl, wird der Upload abgelehnt.

Die Option gibt SVG **nicht selbst als Dateityp frei**. Dafür muss eine passende
MIME-Zuordnung bestehen, zum Beispiel:

```text
svg=image/svg+xml //Vektorgrafiken
```

Stimmen Sie zusätzliche Typen mit **Netzwerkverwaltung → Einstellungen →
Netzwerk-Einstellungen → Upload-Dateitypen** ab. Die Zuordnung und die
SVG-Verarbeitung erfüllen unterschiedliche Aufgaben.

Vorhandene SVG-Dateien werden nicht nachträglich bereinigt. Der separate Dialog
**Datei ersetzen** verwendet diese Upload-Bereinigung ebenfalls nicht. Beim
Ausschalten entfallen die zusätzliche Darstellung und Bereinigung; vorhandene
Dateien und anderweitig eingerichtete SVG-Upload-Freigaben bleiben bestehen.

### Zusätzliche Dateitypen

Pro Zeile steht eine Zuordnung von Dateiendungen zu einem MIME-Typ:

```text
csv=text/csv //Tabellen
svg|svgz=image/svg+xml //Vektorgrafiken
```

- Dateiendungen klein und ohne führenden Punkt schreiben.
- Mehrere Endungen mit `|` trennen.
- Zwischen Endungen und MIME-Typ steht `=`.
- Ein optionaler Kommentar beginnt mit `//`; davor steht ein Leerzeichen.

Die Einträge ergänzen die erlaubten Upload-Typen. Ein hier ergänzter Typ wird
durch das Entfernen allein aus dem allgemeinen Netzwerkfeld **Upload-Dateitypen**
nicht zuverlässig wieder gesperrt. Halten Sie beide Listen aufeinander abgestimmt.

Eine MIME-Zuordnung konvertiert keine Dateien und stellt weder eine
Vorschauerzeugung noch eine Inhaltsbereinigung bereit. Auch eine Zuordnung für
SVGZ garantiert keine Vorschau komprimierter SVGs. Eine leere Liste bedeutet,
dass dieses Plugin keine eigenen Zuordnungen ergänzt. WordPress oder andere
Plugins können dieselben Typen weiterhin erlauben.

## Bildverarbeitung

### PDF-Vorschaubilder

**Keine Vorschaubilder für PDFs erzeugen** unterdrückt die Ersatz-Bildgrößen,
die WordPress für Nicht-Bilddateien erzeugt. In der aktuellen Umsetzung von
WordPress betrifft das PDF-Vorschauen. PDF-Uploads und Downloads bleiben möglich;
für neue PDFs wird üblicherweise ein Dateisymbol statt der ersten Seite angezeigt.

Vorhandene Vorschaudateien werden nicht gelöscht, normale Fotogrößen sind nicht
betroffen. Nach dem Ausschalten kann WordPress wieder PDF-Vorschauen erzeugen,
sofern der Server die Verarbeitung unterstützt. Fehlende Vorschauen vorhandener
PDFs werden durch den Wechsel allein nicht nachträglich erzeugt.

### Große Bilder verkleinern und maximale Bildkante

Die Verkleinerung verarbeitet **JPEG, PNG und GIF** beim Upload sowie beim
Ersetzen über dieses Plugin. Der Zahlenwert begrenzt Breite und Höhe unter
Beibehaltung des Seitenverhältnisses. Ein Bild mit 4000 × 3000 Pixeln wird bei
einem Wert von 2000 auf 2000 × 1500 verkleinert. Kleinere Bilder werden nicht
vergrößert; es findet kein Zuschnitt statt.

Die verkleinerte Datei wird nur übernommen, wenn sie weniger Bytes benötigt als
das Original. Die Pixelgrenze ist deshalb keine uneingeschränkt garantierte
Obergrenze. Bei Übernahme **ersetzt die kleinere Datei das hochgeladene Original**;
diese Funktion bewahrt keine separate Kopie des größeren Originals auf.

Der Pixelwert wird nur bei aktivierter Verkleinerung verwendet. Standard sind
2000 Pixel, das Minimum sind 1024 Pixel. Leere oder kleinere Werte werden beim
Speichern durch den Standard ersetzt.

Weitere Grenzen und Ausnahmen:

- `noresize` im Dateinamen, etwa `poster-noresize.jpg`, überspringt diese
  Verkleinerung. Der Text muss kleingeschrieben sein. Beim Ersetzen zählt der
  beibehaltene Zieldateiname. Technisch wird der vollständige Dateipfad geprüft.
- WebP, AVIF und SVG werden von dieser Funktion nicht verarbeitet.
- Bei animierten GIFs hängt der Erhalt der Animation von der Bildverarbeitung
  des Servers ab.
- Die Einstellung ist keine Stapelverarbeitung der vorhandenen Mediathek.
- WordPress und andere Plugins können unabhängig davon Bilder skalieren und
  weitere Bildgrößen erzeugen. `noresize` und das Ausschalten dieser Funktion
  unterbinden diese anderen Verarbeitungsschritte nicht.

### JPEG-Bilder nachschärfen

Die Option verarbeitet JPEG-Dateien, wenn WordPress bearbeitete Bilder speichert,
beispielsweise neu erzeugte Vorschaubilder. Sie benötigt die PHP-Erweiterung
**Imagick**; ohne sie hat das Häkchen keine Wirkung.

Neben dem Nachschärfen wird der Kontrast normalisiert. Die JPEG-Datei wird mit
Qualität 92 gespeichert. Aussehen und Dateigröße können sich ändern; es handelt
sich nicht um eine verlustfreie Optimierung. PNG und GIF werden nicht nachgeschärft.

Die Option ist unabhängig von der automatischen Verkleinerung. Das Einschalten
bearbeitet vorhandene Dateien nicht nachträglich. Das Ausschalten macht früheres
Nachschärfen nicht rückgängig.

## Werkzeuge für die Mediathek

### Mediendateien ersetzen

**Datei ersetzen** erscheint in der Medienliste und beim Bearbeiten eines
Medieneintrags. Zum Ersetzen sind Rechte zum Hochladen und zum Bearbeiten dieses
Anhangs erforderlich. Die neue Datei muss denselben MIME-Typ haben: Ein PDF kann
durch ein PDF ersetzt werden, ein JPEG nicht durch ein PDF.

Der Medieneintrag, der Dateiname und die zugehörige Dateiadresse bleiben erhalten.
Dadurch verwenden bestehende Links den neuen Dateiinhalt. Browser- oder
Website-Caches können vorübergehend noch die vorherige Fassung anzeigen.

**Die alte Datei wird überschrieben und nicht als Version aufbewahrt.**
Bildmetadaten und erzeugte Bildgrößen werden aktualisiert. Eine aktivierte
Bildverkleinerung wird dabei ebenfalls angewendet; die SVG-Upload-Bereinigung
wird in diesem separaten Dialog nicht verwendet.

Das Ausschalten entfernt die zusätzliche Aktion. Frühere Ersetzungen bleiben
bestehen und werden nicht rückgängig gemacht.

### Dateigröße in der Mediathek

Die Option ergänzt in der **Listenansicht** der Mediathek die Spalte **Größe**.
Sie zeigt die Größe der angehängten Datei in Bytes, KB oder MB. Gemeint sind
weder Pixelabmessungen noch die Summe aller erzeugten Bildvarianten oder der
gesamte Speicherverbrauch einer Website.

Fehlende Größenangaben werden beim Anzeigen aus erreichbaren lokalen Dateien
gelesen und gespeichert. Die Spalte bietet eine Sortierung nach Dateigröße;
Dateien ohne gespeicherten Größenwert können in der sortierten Ansicht fehlen,
bis der Wert erfasst wurde. Bei nicht erreichbaren Dateien ist die angezeigte
Größe kein verlässlicher Nachweis ihrer tatsächlichen Größe.

Fehlt die Spalte, wechseln Sie in die Listenansicht und prüfen Sie **Ansicht
anpassen**. Das Ausschalten der Spalte löscht keine Dateien.

## Medien ordnen

Die drei Ordnungssysteme sind unabhängig voneinander aktivierbar. Sie gelten
für Medienanhänge; Begriffe und Zuordnungen werden **pro Website** verwaltet.

| Einstellung | Aufbau | Beispiel | Verwaltung auf der Website |
| --- | --- | --- | --- |
| Dokumentgruppen | Hierarchisch, eigene Begriffsgruppe | Ordnungen → Prüfungsordnungen | Medien → Dokumente |
| Medienkategorien | Hierarchisch, getrennt von Beitrags- und Seitenkategorien | Veranstaltungen → Tagungen | Medien → Kategorien |
| Medienschlagwörter | Frei kombinierbar, ohne Hierarchie | Campus, Forschung, Team | Medien → Schlagwörter |

**Dokumente** ist der Name einer Begriffsgruppe, kein Dateityp-Filter. Auch Bilder
und andere Anhänge können ihr zugeordnet werden. Die Funktion wandelt keine
Dateien um und erteilt keine PDF-Upload-Freigabe.

Legen Sie Begriffe auf der jeweiligen Website an und ordnen Sie sie beim
Bearbeiten von Medien zu. Verwendete Begriffe stehen zum Filtern in der
Mediathek und in der Medienauswahl zur Verfügung. Ohne passende Begriffe und
Zuordnungen kann ein Filter noch fehlen.

Diese Zuordnungen erzeugen keine physischen Ordner, verschieben keine Dateien
und ändern keine Datei-URLs. Sie beschränken auch nicht den Zugriff auf Dateien
und werden nicht automatisch als Schlagwörter oder Archive auf der Website
ausgegeben.

Beim Ausschalten eines Ordnungssystems entfallen dessen Verwaltung und Filter.
Gespeicherte Begriffe, Zuordnungen und Mediendateien bleiben erhalten und können
nach dem erneuten Aktivieren wieder verwendet werden.

## Standardwerte

In einer neuen Konfiguration sind alle hier gezeigten Checkboxen ausgeschaltet.
Die maximale Bildkante beträgt **2000 Pixel**, wird aber erst bei aktivierter
Bildverkleinerung verwendet. Die Liste zusätzlicher Dateitypen ist leer.
Bereits gespeicherte Einstellungen können hiervon abweichen.
