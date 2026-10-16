# Editor im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → CMS → Editor** legen Sie die Editor-Vorgabe,
verfügbare Blöcke, automatische Sicherungen und Bearbeitungswerkzeuge fest.
Die Seite hieß bisher **Schreiben**. Der neue Name beschreibt ihren Schwerpunkt
und unterscheidet sie von der allgemeinen Inhaltsverwaltung.

Die Sprungübersicht führt zu sieben Abschnitten. Alle Felder bleiben sichtbar;
**Alle Änderungen speichern** übernimmt das gesamte Formular. Die Erklärungen
sind auf maximal 70ch begrenzt. Zusätzliche **Details und Beispiele** lassen sich
per Tastatur öffnen und schließen.

## Editor auswählen

**Block-Editor** macht ihn zur netzwerkweiten Vorgabe. Bei **Klassischer Editor**
bleiben individuelle Editor-Einstellungen der Websites und die weiter unten
konfigurierten Freigaben wirksam. Diese Auswahl erzwingt daher nicht auf jeder
Website den klassischen Editor.

Wenn das Netzwerk den klassischen Editor erlaubt, können Superadministratoren
unter **Einstellungen → Schreiben** einer Website den Editor wählen und
Website-Administratoren den Wechsel erlauben. Diese lokale WordPress-Seite
behält ihren Namen. In der Netzwerk-Website-Liste stehen eine Editor-Spalte und
ein Filter zur Verfügung.

Die Option **Neue Websites mit dem Block-Editor starten** setzt den lokalen
Starteditor beim Anlegen einer Website. Bestehende Websites werden nicht
umgestellt. Bei netzwerkweit vorgegebenem Block-Editor benötigen neue Websites
keine zusätzliche Freigabe.

Ein Editor-Wechsel wandelt keine Inhalte automatisch um. Prüfen Sie vor einer
Umstellung typische Seiten mit dem aktiven Theme und den benötigten Plugins.
Inhaltstypen müssen Editor und REST-API unterstützen, damit sie im Block-Editor
bearbeitet werden können.

## Freigaben für Editor und Blöcke

Diese Listen erlauben den Block-Editor für bestimmte Inhaltstypen, Themes oder
Websites und nehmen sie von den Blocklisten dieses Plugins aus. Sie gelten
auch, wenn der Block-Editor bereits die Netzwerkvorgabe ist. Deshalb sind die
Felder unabhängig von der Editor-Auswahl sichtbar.

| Liste | Eingabe pro Zeile | Beispiel |
| --- | --- | --- |
| Inhaltstypen | Technischer Bezeichner, nicht die übersetzte Beschriftung | `page`, `newsletter` |
| Themes | Exakter Name des aktiven Themes, einschließlich Groß- und Kleinschreibung | `FAU-Elemental` |
| Websites | Website-ID | `12` |

Theme-Freigaben gleichen keine Ordnernamen ab und übernehmen bei einem aktiven
Child-Theme nicht automatisch die Freigabe für das Eltern-Theme. Bei Website-IDs
wird nach dem Speichern die Adresse ergänzt; bestehende Einträge im Format
`12 - https://example.org` sind weiterhin gültig. Leere Listen gewähren keine
zusätzlichen Freigaben.

Die Freigaben heben nicht sämtliche Editor-Einschränkungen auf. Quellen- und
CSS-Einschränkungen gelten weiterhin. Inhaltstyp-Freigaben nehmen den jeweiligen
Typ zusätzlich vom Code-Editor-Verbot aus; für Websites gibt es dafür eine
separate Liste im Abschnitt **Code und eigene Felder**.

## Sichern und Bearbeitungssperren

**Autosave-Intervall aus Heartbeat ableiten** verwendet das Doppelte des unter
**CMS → Heartbeat** gespeicherten Editor-Intervalls, mindestens 60 Sekunden.
Beispiele: 30 Sekunden ergeben 60 Sekunden; 60 Sekunden ergeben 120 Sekunden.
Rollenspezifische Heartbeat-Abweichungen werden dabei nicht ausgewertet.

Bei aktivierter Ableitung wird das **eigene Autosave-Intervall** nicht verwendet.
Bei ausgeschalteter Ableitung gilt dieser Zahlenwert. Standard und Mindestwert
sind 60 Sekunden. Diese Einstellung betrifft den Block-Editor. Autosave sichert
Entwürfe und veröffentlicht keine Änderungen.

**Websites eine eigene Dauer der Bearbeitungssperre erlauben** ergänzt unter
**Einstellungen → Schreiben** der einzelnen Websites ein Sekundenfeld. Standard
und Mindestwert beim Speichern sind 150 Sekunden. Die Sperre erkennt gleichzeitige
Bearbeitung durch eine andere Person; der Wert beschreibt die Gültigkeit des
Sperrsignals, nicht die maximale Länge einer Bearbeitungssitzung.

Bei ausgeschalteter Option bleibt die normale WordPress-Bearbeitungssperre
erhalten. Sie wird durch dieses Häkchen nicht grundsätzlich ein- oder ausgeschaltet.

## Verfügbare Blöcke und Bestandsinhalte

Die Blocklisten steuern die Auswahl zum **Einfügen neuer Blöcke**. Bestehende
Blöcke werden nicht aus Beiträgen oder Seiten entfernt. Sie können weiterhin
dargestellt und bearbeitet werden, solange der zugehörige Blockanbieter
verfügbar ist. Das Deaktivieren eines Block-Plugins ist daher etwas anderes
als das Ausschließen seiner Blöcke im Inserter.

**Erlaubte Blöcke:** Eine leere Liste beginnt mit allen registrierten Blöcken.
Bei einer gefüllten Liste stehen die angegebenen Blöcke zur Auswahl.
**Ausgeschlossene Blöcke** werden anschließend abgezogen. Die Freigaben für
Inhaltstypen, Themes und Websites umgehen beide Listen.

Tragen Sie eine Kennung pro Zeile ein, etwa `core/heading` oder `core/html`.
Mit `core/*` erfassen Sie einen ganzen Namensraum. Beliebige Teilmuster wie
`core/image*` werden nicht unterstützt. In einer gefüllten Erlaubnisliste ohne
`core/*` werden `core/paragraph` und `core/missing` beim Speichern ergänzt.

**Beispiel:** Lassen Sie die Erlaubnisliste leer und tragen Sie `core/html` in
die Ausschlussliste ein. Auf Websites ohne entsprechende Freigabe wird dieser
Block nicht mehr zum Einfügen angeboten; vorhandene HTML-Blöcke bleiben erhalten.

Zwei Grenzen der aktuellen Umsetzung sind zu beachten:

- Ergibt die Filterung eine vollständig leere Blockliste, wird die vorherige
  WordPress-Freigabe verwendet. Die Listen eignen sich nicht dazu, sämtliche
  Blöcke gleichzeitig zu sperren.
- Variantenregeln wie `core/embed:youtube` oder `core/embed:*` werden durch ein
  zusätzliches Editor-Skript umgesetzt. Dieses wird derzeit nur geladen, wenn
  die Inhaltstyp-Ausnahmeliste gefüllt ist und keine Freigabe für den aktuellen
  Inhalt greift. Prüfen Sie solche Regeln im verwendeten Editor.

Die Blocklisten sind eine Vorgabe für den Editor und keine serverseitige
Inhaltsbereinigung für Importe, API-Anfragen oder andere Bearbeitungswege.

## Quellen und Bibliotheken

| Option | Wirkung |
| --- | --- |
| Blockverzeichnis ausblenden | Entfernt die WordPress-Integration zum Entdecken zusätzlicher Block-Plugins im Editor. Bereits installierte Blöcke bleiben entsprechend den Blocklisten verfügbar. |
| Online-Blockvorlagen deaktivieren | Verhindert das Laden von Vorlagen aus dem WordPress-Onlineverzeichnis. Lokal registrierte Vorlagen bleiben verfügbar. |
| Openverse ausblenden | Entfernt die Openverse-Medienquelle im Block-Editor. Die Mediathek bleibt verfügbar. |
| Schriftartenbibliothek ausblenden | Deaktiviert deren Oberfläche im Block-Editor. Installierte und vom Theme verwendete Schriften bleiben erhalten. |

Das Ausblenden einer Quelle löscht keine bereits eingefügten Inhalte.
Die separate Verwaltungsseite der Schriftartenbibliothek wird unter
**CMS → Funktionen und Dienste → Darstellung und Schriften** gesteuert.
Weitere Hinweise enthält die [Dokumentation zu Funktionen und Diensten](website-functions-settings.md).
Andere Plugins können eigene Quellen bereitstellen.

## Code und eigene Felder

**Code-Editor:** Das Häkchen blendet die Codebearbeitung im Block-Editor für
reguläre Website-Benutzer aus. Superadministratoren, freigegebene Inhaltstypen
und Websites in der Code-Editor-Ausnahmeliste bleiben ausgenommen. Tragen Sie
Website-IDs einzeln ein; die Adresse wird beim Speichern ergänzt. Die Freigabe
verleiht keine allgemeinen Bearbeitungsrechte.

Diese Option steuert nicht den klassischen Editor oder die Theme-Dateibearbeitung.
Soll auch der HTML-Block nicht neu angeboten werden, tragen Sie `core/html`
zusätzlich in die Ausschlussliste ein.

**Eigenes Block-CSS:** Die Option deaktiviert die Unterstützung für eigenes CSS
in den Block-Metadaten. Die zugehörige Theme-Liste enthält die **betroffenen
Themes**, keine Ausnahmen. Leer bedeutet alle Themes; bei ausgeschaltetem
Häkchen wird die Liste nicht verwendet.

Pro Zeile sind Theme-Name oder Theme-Ordner möglich. Der Vergleich ignoriert
Groß- und Kleinschreibung und berücksichtigt den aktiven Theme-Namen, den
Stylesheet-Ordner und den Eltern-Theme-Ordner. Diese Einschränkung gilt auch für
Superadministratoren und wird durch Editor-Freigaben nicht aufgehoben.
Gespeichertes CSS wird nicht gelöscht; dies ist kein allgemeiner CSS-Filter.

**Eigene Felder:** Das Häkchen entfernt die Standard-WordPress-Metadatenbox.
Gespeicherte Feldwerte bleiben erhalten. Eigene Oberflächen von Plugins wie
ACF werden dadurch nicht grundsätzlich abgeschaltet.

## Plugin-Kompatibilität

Tragen Sie pro Zeile den Pfad zur Plugin-Hauptdatei relativ zum Plugin-Verzeichnis
ein, zum Beispiel `tinymce-advanced/tinymce-advanced.php`. Anzeigename oder
Ordnername allein genügen nicht.

Wenn die Website den Block-Editor verwendet und ihre Verwaltung geladen wird,
werden eingetragene, lokal aktive Plugins dauerhaft deaktiviert. Netzwerkweit
aktive Plugins werden übersprungen. Dies kann auch andere Website-Funktionen
betreffen und blendet Plugins nicht nur im Editor aus.

Das Entfernen eines Eintrags reaktiviert das Plugin nicht automatisch. Eine
leere Liste löst keine zusätzlichen Deaktivierungen aus. Eine Block-Editor-Freigabe
für einzelne Inhalte allein löst diese Regel auf Website-Ebene nicht aus.

## Standardwerte einer neuen Konfiguration

| Einstellung | Standard |
| --- | --- |
| Editor-Vorgabe | Klassischer Editor |
| Block-Editor für neue Websites | Aus |
| Freigaben, Blocklisten, Theme-Liste für CSS, Plugin-Liste | Leer |
| Autosave-Intervall | 60 Sekunden |
| Ableitung aus Heartbeat | Aus |
| Individuelle Bearbeitungssperre | Aus; Website-Wert 150 Sekunden |
| Blockverzeichnis, Online-Vorlagen, Openverse, Schriftartenbibliothek ausblenden | Ein |
| Code-Editor ausblenden | Ein |
| Eigenes Block-CSS und Standardbox für eigene Felder deaktivieren | Aus |

Bereits gespeicherte Einstellungen können hiervon abweichen.
