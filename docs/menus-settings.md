# Menüs im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → CMS → Menüs** aktivieren Sie Hilfen für die
klassische Menüverwaltung unter **Design → Menüs** und eine zusätzliche Spalte
in der Seitenliste. Änderungen gelten nach **Änderungen speichern** für alle
Websites des Netzwerks. Die Menüs selbst werden weiterhin auf jeder Website
separat gepflegt.

Die Optionen erweitern den klassischen Menüeditor, nicht den
**Navigationsblock im Website-Editor**. Ob die klassische Menüverwaltung auf
einer Website angeboten wird, hängt unter anderem vom verwendeten Theme ab.

Die wichtigste Wirkung steht direkt am Feld. Unter **Details und Beispiele**
finden Sie Anwendungsfälle und Grenzen. Die Hilfen lassen sich mit Tab erreichen
und mit Eingabe oder Leertaste öffnen und schließen. Texte sind auf maximal
70ch begrenzt und passen sich schmalen Bildschirmen an.

## Standardwerte

Für eine neue Plugin-Konfiguration sind alle drei Optionen deaktiviert.
Bereits gespeicherte Einstellungen eines Netzwerks können davon abweichen.

| Einstellung | Wirkung bei Aktivierung |
| --- | --- |
| Untermenüs einklappen | Unterpunkte im klassischen Menüeditor ein- und ausblenden |
| Menüzuordnung in der Seitenliste | Spalte „Menüs“ mit Links zum Filtern der Seitenliste |
| Mehr Suchtreffer | Bis zu 100 Treffer bei der Inhaltssuche zum Hinzufügen von Menüpunkten |

## Untermenüs einklappen

Menüpunkte mit Unterpunkten erhalten im klassischen Menüeditor ein zusätzliches
Bedienelement. Damit können Sie die untergeordneten Einträge vorübergehend
ausblenden. Das erleichtert die Arbeit an umfangreichen Menüs.

**Beispiel:** Klappen Sie den Bereich „Studium“ ein, während Sie „Forschung“
bearbeiten. Beim erneuten Aufklappen werden die Unterpunkte wieder angezeigt.
Es werden keine Menüpunkte gelöscht, verschoben oder auf der Website ausgeblendet.

WordPress merkt sich eingeklappte Bereiche als persönliche Anzeigeeinstellung.
Die Funktion benötigt JavaScript. Menüpunkte ohne Unterpunkte erhalten kein
zusätzliches Bedienelement.

**Deaktiviert:** Die zusätzlichen Bedienelemente entfallen; Unterpunkte werden
im Editor wieder normal angezeigt. Menüstruktur, Links und die Zuordnung zu
Theme-Positionen bleiben unverändert.

## Menüzuordnung in der Seitenliste

Die Option ergänzt unter **Seiten → Alle Seiten** die Spalte **Menüs**. Sie zeigt
die klassischen Menüs an, denen eine Seite zugeordnet ist. In der Beitragsliste
wird diese Spalte nicht ergänzt.

**Beispiel:** Ist eine Seite sowohl in „Hauptnavigation“ als auch in „Footer“
enthalten, erscheinen beide Menünamen. Ein Klick auf „Footer“ filtert die
Seitenliste auf Seiten dieses Menüs. Der Link öffnet nicht den Menüeditor.

Wenn die Spalte fehlt, prüfen Sie unter **Ansicht anpassen** oberhalb der
Seitenliste, ob **Menüs** eingeblendet ist.

Die Anzeige wertet gespeicherte Menüeinträge aus. Individuelle Links werden
nicht anhand ihrer URL einer Zielseite zugeordnet; Navigationsblöcke werden
nicht ausgewertet. Ein Gedankenstrich bedeutet daher lediglich, dass keine
Menüzuordnung erkannt wurde. Die Seite kann weiterhin über direkte Links oder
andere Navigationselemente erreichbar sein.

Auch Menüs ohne zugewiesene Theme-Position können erscheinen. Die Spalte zeigt
gespeicherte Zuordnungen und sagt nicht aus, ob das Theme ein Menü tatsächlich
auf der Website ausgibt.

**Deaktiviert:** Die zusätzliche Spalte und ihr Menüfilter entfallen. Seiten,
Menüs und gespeicherte Zuordnungen bleiben erhalten.

## Mehr Suchtreffer

Die Option erhöht die Treffergrenze der Inhaltssuche im klassischen Menüeditor
auf **100 Treffer pro Suche**. Die Suchkriterien und die Reihenfolge der Treffer
werden dadurch nicht verändert.

**Beispiel:** Öffnen Sie unter **Design → Menüs** den Reiter **Suchen** im Bereich
**Seiten**. Suchen Sie nach „Studium“, wählen Sie passende Seiten aus und fügen
Sie sie zum Menü hinzu. Die höhere Grenze hilft, wenn viele Inhalte zum
Suchbegriff passen.

Die Option betrifft die Auswahl von Inhalten wie Seiten und Beiträgen beim
Hinzufügen neuer Menüpunkte. Sie durchsucht nicht die bereits vorhandene
Menüstruktur. Die Suche nach Kategorien oder Schlagwörtern, die öffentliche
Website-Suche und der Navigationsblock werden nicht erweitert.

Auch bei mehr als 100 passenden Inhalten bleibt die Ausgabe auf 100 begrenzt.
Verwenden Sie dann einen genaueren Suchbegriff.

**Deaktiviert:** Es gilt wieder die reguläre Treffergrenze von WordPress,
standardmäßig 10 Treffer. Andere Plugins können die Suche zusätzlich beeinflussen.
