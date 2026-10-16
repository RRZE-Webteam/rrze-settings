# Beiträge und Seiten im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → RRZE Settings → Beiträge und Seiten** konfigurieren Sie die
Listenansichten, Kategorien und Schlagwörter für Seiten sowie einen Ausschluss
aus der Website-Suche. Die Seite hieß bisher **Posts**. Der neue Name macht
deutlich, dass sich die Einstellungen auch auf Seiten beziehen.

Änderungen gelten nach **Änderungen speichern** für alle Websites des Netzwerks.
Kategorien, Schlagwörter und deren Zuordnungen werden weiterhin auf jeder
Website separat gepflegt; sie werden nicht zwischen Websites synchronisiert.

Die wichtigste Wirkung steht direkt am Feld. **Details und Beispiele** lassen
sich mit Tab erreichen und mit Eingabe oder Leertaste öffnen. Die Hilfetexte
sind auf maximal 70ch begrenzt und passen sich schmalen Bildschirmen an.

## Standardwerte

Für eine neue Plugin-Konfiguration sind alle fünf Optionen deaktiviert.
Bereits gespeicherte Einstellungen eines Netzwerks können davon abweichen.

| Einstellung | Betrifft |
| --- | --- |
| Letzte Änderung | Beitrags- und Seitenlisten in der Website-Verwaltung |
| Filter nach übergeordneter Seite | Seitenliste in der Website-Verwaltung |
| Seitenkategorien | Eigene Kategorien für Seiten |
| Seitenschlagwörter | Eigene Schlagwörter für Seiten |
| Suchausschluss | Reguläre WordPress-Suche auf der Website |

## Letzte Änderung

Die Option erweitert die vorhandene Spalte **Datum** in den Beitrags- und
Seitenlisten um die letzte Änderung. In der normalen Listenansicht bleiben
bei veröffentlichten oder geplanten Inhalten auch Veröffentlichungs- bzw.
Planungsdatum sichtbar. Ein Klick auf den Spaltentitel sortiert nach dem
Änderungsdatum.

**Beispiel:** Eine Informationsseite wurde vor zwei Jahren veröffentlicht, aber
gestern überarbeitet. Sortieren Sie die Datumsspalte nach der neuesten Änderung,
um diese Seite schnell zu finden.

Die Erweiterung gilt für Beiträge und Seiten, nicht für den Papierkorb. Sie
fügt kein Datum auf der öffentlichen Website ein und verändert keine
Veröffentlichungszeitpunkte. Nach dem Deaktivieren verwendet WordPress wieder
seine normale Datumsspalte; gespeicherte Änderungsdaten bleiben erhalten.

## Filter nach übergeordneter Seite

Die Option ergänzt in der Seitenliste ein Auswahlfeld. Wählen Sie eine Seite
und wenden Sie den Filter an: Angezeigt werden die ausgewählte Seite und
sämtliche untergeordneten Seiten, auch über mehrere Ebenen hinweg.

**Beispiel:** Die Auswahl von „Studium“ zeigt diese Seite zusammen mit
„Studiengänge“, „Bewerbung“ und deren Unterseiten. Mit **Alle Seitenebenen** und
erneutem Anwenden des Filters heben Sie diese Einschränkung auf. Andere aktive
Listenfilter, etwa eine Textsuche, gelten weiterhin.

Das Auswahlfeld steht in den Ansichten **Alle** und **Veröffentlicht** zur
Verfügung, nicht in Statusansichten wie **Entwürfe** oder **Papierkorb**.
Zur Auswahl stehen veröffentlichte Seiten in bis zu sechs Hierarchieebenen.
Nach dem Deaktivieren entfällt das zusätzliche Auswahlfeld. Inhalte,
Seitenhierarchie und Navigationsmenüs bleiben unverändert.

## Seitenkategorien

Seiten erhalten eigene Kategorien, getrennt von den Kategorien für Beiträge.
Kategorien können über- und untergeordnet sein. Diese thematische Gliederung
ändert nicht die über- und untergeordneten Seiten selbst.

**Beispiel:** Ordnen Sie Seiten den Kategorien „Studium“ und „Forschung“ zu.
„Studiengänge“ kann eine Unterkategorie von „Studium“ sein. Die Zuweisung erfolgt
im Seiteneditor. Die Seitenliste zeigt Kategorien in einer Spalte und bietet
einen Filter an, sobald verwendete Kategorien verfügbar sind.

Seitenkategorien sind für öffentliche Kategoriearchive und Navigationsmenüs
registriert. Wie sie auf der Website erscheinen, hängt vom Theme ab. Die
Aktivierung fügt keine Kategorie automatisch zu einem Menü hinzu.

Nach dem Deaktivieren stellt das Plugin die Seitenkategorien samt
Verwaltungsoberfläche nicht mehr bereit. Vorhandene Kategorien und Zuordnungen
werden nicht gelöscht und stehen nach erneuter Aktivierung wieder zur Verfügung.

## Seitenschlagwörter

Seiten erhalten eigene Schlagwörter, getrennt von den Schlagwörtern für Beiträge.
Schlagwörter beschreiben Themen ohne über- oder untergeordnete Ebenen.

**Beispiel:** Versehen Sie zusammengehörige Seiten mit „Bewerbung“ oder
„Barrierefreiheit“. Die Zuweisung erfolgt im Seiteneditor. In der Seitenliste
werden Schlagwörter angezeigt; verwendete Schlagwörter stehen auch als Filter
zur Verfügung.

Seitenschlagwörter sind im Editor und über die REST-API verfügbar. Sie werden
nicht als öffentliche Schlagwortarchive oder Einträge für Navigationsmenüs
registriert. Die Aktivierung allein zeigt noch keine Schlagwörter im Theme an.

Für den Suchausschluss einer Seite müssen sowohl **Seitenschlagwörter** als auch
**Suchausschluss** aktiviert sein. Außerdem benötigt die Seite ein Schlagwort
mit dem Slug `nosearch`.

Nach dem Deaktivieren stellt das Plugin die Seitenschlagwörter nicht mehr
bereit; sein Suchausschluss kann diese dann ebenfalls nicht berücksichtigen.
Gespeicherte Schlagwörter und Zuordnungen bleiben für eine spätere Aktivierung
erhalten.

## Suchausschluss mit nosearch

Die Option schließt entsprechend markierte Inhalte aus der regulären
WordPress-Suche auf der Website aus. So kann etwa eine Landingpage über einen
direkten Link erreichbar bleiben, ohne in diesen Suchergebnissen aufzutauchen.

1. Aktivieren Sie **Suchausschluss** und speichern Sie die Einstellungen.
2. Aktivieren Sie für Seiten zusätzlich **Seitenschlagwörter**. Beiträge
   verwenden ihre normalen Schlagwörter.
3. Legen Sie auf der jeweiligen Website ein Schlagwort mit dem Slug
   **nosearch** an. Der Slug ist die URL-Kurzform; allein der sichtbare Name
   genügt nicht. Eine Kategorie mit diesem Namen hat keine Wirkung.
4. Weisen Sie das Schlagwort den gewünschten Inhalten zu und speichern Sie
   diese. Die Netzwerkeinstellung legt keine Schlagwörter oder Zuordnungen an.
5. Prüfen Sie die Suche auf der Website mit einem passenden Suchbegriff.

Berücksichtigt werden Beitrags-, Seiten- und Medienschlagwörter
(`post_tag`, `page_tag`, `attachment_tag`), sofern die jeweilige Taxonomie aktiv
ist. Medienschlagwörter werden unter **RRZE Settings → Medien** aktiviert. Die Regel ist
für Medien nur relevant, wenn Anhänge überhaupt Teil der Suchergebnisse sind.

Die Filterung betrifft nur die Hauptabfrage einer WordPress-Suche auf der
Website. Verwaltungssuchen, separate Suchabfragen und externe Suchdienste sind
nicht erfasst. Andere Plugins können die Suchabfrage und damit das Ergebnis
verändern; prüfen Sie die Wirkung mit der tatsächlich eingesetzten Suche.

**Der Suchausschluss ist kein Zugriffsschutz.** Direkte Links bleiben im Rahmen
der vorhandenen Berechtigungen erreichbar. Die Option setzt auch keine
`noindex`-Anweisung für Google oder andere externe Suchmaschinen.

Nach dem Deaktivieren entfällt dieser zusätzliche Suchausschluss. Die
Schlagwörter und ihre Zuordnungen bleiben gespeichert.
