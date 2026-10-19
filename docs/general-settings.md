# Allgemein: Einstellungen für das Netzwerk

Die Seite **Netzwerkverwaltung → RRZE Settings → Allgemein** bündelt vier Einstellungen
für das Multisite-Netzwerk. Änderungen werden mit **Änderungen speichern**
übernommen. Die ersten drei Optionen wirken netzwerkweit; das Standard-Theme
wird ausschließlich beim Anlegen neuer Websites verwendet.

Jedes Feld zeigt eine kurze Erklärung und die wichtigsten Auswirkungen direkt
an. Unter **Details und Beispiele** lassen sich zusätzliche Hinweise auf- und
zuklappen. Das Aufklappen ändert keine Einstellung; per Tastatur lässt sich der
Bereich mit Tab erreichen und mit Eingabe oder Leertaste bedienen.
Die Texte sind auf maximal 70ch Breite begrenzt und passen sich auf schmaleren
Bildschirmen dem verfügbaren Platz an.

Die folgenden Standardwerte gelten für eine neue Plugin-Konfiguration.
Bereits gespeicherte Einstellungen können davon abweichen.

| Einstellung | Standardwert | Zweck |
| --- | --- | --- |
| Ersatzübersetzungen | Deaktiviert | Vorhandene Übersetzungen nutzen, wenn eine Sprachdatei fehlt. |
| Benutzerdefinierte Fehlerseite | Deaktiviert | WordPress-Abbruchmeldungen mit einheitlichem Layout und Kontaktadresse anzeigen. |
| White Label | Deaktiviert | Adminleiste, Footer, Dashboard und E-Mail-Absender anpassen. |
| Standard-Theme | WordPress-Standard verwenden | Das Start-Theme für neue Websites festlegen. |

## Ersatzübersetzungen (Translation Fallback)

**Aktiviert:** Fehlt eine angeforderte Übersetzungsdatei oder ist sie nicht lesbar,
sucht das Plugin im selben Verzeichnis nach einer verfügbaren Ersatzdatei.
Zuerst werden Varianten derselben Sprache berücksichtigt, anschließend englische
Übersetzungen. Das gilt sowohl für PHP-Übersetzungen als auch für JavaScript-Texte,
etwa im Block-Editor.

**Beispiel:** Eine Website verwendet „Deutsch (Sie)“. Ein Plugin liefert dafür
keine Datei, aber eine Datei für „Deutsch“. Dann kann dessen Oberfläche auf
Deutsch erscheinen; dabei kann die informelle Anrede verwendet werden.

Die Suchreihenfolge ist: vereinfachte Sprachvariante (etwa `de_DE_formal` →
`de_DE`), weitere installierte oder konfigurierte Varianten derselben Sprache,
reiner Sprachcode (etwa `de`), hinterlegte Ersatzvarianten derselben Sprache und
schließlich Englisch (standardmäßig `en_GB`, danach `en_US`). Verwendet wird die
erste lesbare Ersatzdatei. Ohne passende Datei bleibt der ursprüngliche
WordPress-Ladevorgang unverändert.

**Sinnvoll, wenn:** Themes oder Plugins nicht alle verwendeten Sprachvarianten
mitliefern. Es werden keine Sprachpakete heruntergeladen und keine Inhalte von
Beiträgen oder Seiten übersetzt. Einzelne fehlende Texte innerhalb einer bereits
lesbaren Übersetzungsdatei werden nicht durch diese Option ergänzt.

**Deaktiviert:** WordPress verarbeitet Übersetzungen ohne diese zusätzliche
Suche nach Ersatzdateien. Die eingestellte Website- oder Benutzersprache wird
durch die Option nicht geändert.

## Benutzerdefinierte Fehlerseite (Custom Error Page)

**Aktiviert:** WordPress-Abbruchmeldungen für normale Browseraufrufe erhalten
das RRZE-Layout. Dazu gehören beispielsweise Zugriffsfehler, die WordPress oder
ein Plugin über `wp_die()` ausgibt. Die Seite zeigt den Website-Namen, die
Fehlermeldung und die Administrations-E-Mail-Adresse der jeweiligen Website als
Kontakt an. Ein Zurück-Link erscheint, wenn die auslösende Funktion ihn anfordert.

**Vor dem Aktivieren prüfen:** Die Administrations-E-Mail-Adresse muss für die
öffentliche Anzeige geeignet sein. Die Vorlage verlinkt fest auf `/impressum`,
`/datenschutz` und `/barrierefreiheit`; prüfen Sie, ob diese Ziele auf den
betroffenen Websites erreichbar sind. Layout und Linkziele lassen sich auf
dieser Einstellungsseite nicht bearbeiten.

Die Option ersetzt weder die 404-Seite des Themes noch Fehlerseiten des
Webservers. Antworten für AJAX, JSON und XML behalten ihre eigenen Fehlerhandler.
Wurde der Kopf einer Adminseite bereits ausgegeben, erscheint nur die Meldung
innerhalb der begonnenen Seite statt des vollständigen RRZE-Layouts.

Beim Aufruf wird außerdem ein Fehlerkontext an die Logging-Schnittstelle
`rrze.log.error` übergeben. Er enthält unter anderem Fehlermeldung, Website,
Kontaktadresse, Anfragepfad und Benutzer-ID. Ob dieser gespeichert wird, hängt
von der angebundenen Logging-Komponente ab.

**Deaktiviert:** Das Plugin ersetzt die WordPress-Fehlerdarstellung nicht.

## White Label

**Aktiviert:** Diese Option schaltet mehrere Anpassungen gemeinsam ein:

- **Adminleiste:** Das WordPress-Symbol wird ersetzt und mit der Hauptwebsite
  (Website-ID `1`) verknüpft. Die Links zu WordPress-Informationen,
  WordPress.org, Dokumentation, Supportforen und Feedback werden entfernt.
  Das gilt für die Adminleiste im Verwaltungsbereich und auf der öffentlichen
  Website, wenn sie für angemeldete Benutzer angezeigt wird.
- **Admin-Footer:** Statt des WordPress-Dankestextes erscheinen Name und,
  sofern vorhanden, Untertitel der Hauptwebsite mit der ID `1`.
- **Meine Websites:** Die Websites werden alphabetisch nach Namen sortiert.
  Fehlt ein Name, wird die Domain verwendet.
- **Dashboard:** Das WordPress-Nachrichten-Widget wird entfernt.
  Website-Dashboards erhalten RRZE-Wartungs- und Fehlermeldungen sowie
  Webmaster-Nachrichten. Das Netzwerk-Dashboard erhält die Wartungs- und
  Fehlermeldungen. Diese Widgets laden externe RSS-Feeds von `www.rrze.fau.de`
  beziehungsweise `www.wp.rrze.fau.de`. „Auf einen Blick“ erhält zusätzlich
  Zähler für weitere Inhaltstypen.
- **E-Mail-Absender:** Beginnt eine Absenderadresse mit `wordpress@`, wird sie
  durch die gültige Administrations-E-Mail-Adresse der aktuellen Website
  ersetzt. Lautet der Absendername genau `WordPress`, wird stattdessen der
  Website-Titel verwendet. Andere Absenderadressen und -namen bleiben erhalten.
  Andere Mail-Plugins können das endgültige Ergebnis zusätzlich beeinflussen.

**Sinnvoll, wenn:** Das Netzwerk mit einem gemeinsamen Verwaltungsauftritt und
RRZE-Informationen betrieben wird. Prüfen Sie insbesondere die Daten der
Hauptwebsite und die E-Mail-Absender der einzelnen Websites. Die Einstellung
ändert keine Mailserver-Konfiguration und bietet keinen Upload für ein eigenes
Logo. Die genannten Anpassungen lassen sich hier nicht einzeln auswählen.

**Deaktiviert:** Diese Anpassungen werden vom Plugin nicht angewendet.

## Standard-Theme (Default Theme)

**Ein Theme ausgewählt:** Nach der Erstellung einer Website aktiviert das Plugin
das ausgewählte installierte Theme. So erhalten neue Websites ein einheitliches
Startdesign. Bereits vorhandene Websites behalten ihr bisheriges Theme.

Die Auswahl zeigt den Theme-Namen und in Klammern dessen Verzeichnisnamen.
Das Festlegen als Standard-Theme erteilt keine zusätzliche netzwerkweite
Freigabe für die spätere Theme-Auswahl durch Website-Administratoren; diese
Freigabe wird separat in der Theme-Verwaltung vorgenommen.

**WordPress-Standard verwenden:** Das Plugin nimmt keinen Theme-Wechsel vor;
das bei der Website-Erstellung von WordPress gewählte Theme bleibt aktiv.
Dasselbe gilt, wenn das hier gespeicherte Theme später entfernt wurde.

**Beispiel:** Nach dem Wechsel des Standard-Themes von A zu B erhalten neu
angelegte Websites B. Bestehende Websites mit A werden nicht umgestellt.
