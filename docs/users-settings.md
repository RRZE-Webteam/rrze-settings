# Benutzer: Einstellungen für das Netzwerk

Unter **Netzwerkverwaltung → RRZE Settings → Benutzer** werden zusätzliche Rollen,
Benutzersuche, Kontaktseite und weitere benutzerbezogene Funktionen zentral
konfiguriert. Änderungen werden mit **Änderungen speichern** übernommen.

Jedes Feld zeigt die wichtigste Wirkung direkt an. Unter **Details und Beispiele**
stehen weitere Erklärungen. Die Bereiche lassen sich mit Tab erreichen und mit
Eingabe oder Leertaste auf- und zuklappen. Texte sind auf maximal 70ch begrenzt;
auf schmalen Bildschirmen passen sie sich dem verfügbaren Platz an.

## Standardwerte

Diese Werte gelten für eine neue Plugin-Konfiguration, nicht zwingend für ein
bereits eingerichtetes Netzwerk.

| Einstellung | Standardwert |
| --- | --- |
| Seitenautor | Deaktiviert |
| Superautor | Deaktiviert |
| Benutzersuche | Deaktiviert |
| Automatische Kontaktseite | Deaktiviert |
| Willkommenspanel ausblenden | Deaktiviert |
| Administrator-Warnung | Deaktiviert |
| Administrator-Schwellenwert pro Website | 3; mindestens 3 |
| Debug-Zugriff | Leere Liste; Superadministratoren bestehen die Prüfung weiterhin |

## Seitenautor und Superautor

Die Optionen stellen zusätzliche Rollen für die Zuweisung in der
Benutzerverwaltung bereit. Niemand erhält automatisch eine neue Rolle.

| Berechtigung der vom Plugin angelegten Rolle | Seitenautor | Superautor |
| --- | --- | --- |
| Eigene Seiten erstellen, veröffentlichen, bearbeiten und löschen | Ja | Ja |
| Eigene Beiträge erstellen, veröffentlichen, bearbeiten und löschen | Nein | Ja |
| Eigene bereits veröffentlichte Inhalte bearbeiten und löschen | Seiten | Beiträge und Seiten |
| Medien hochladen | Ja | Ja |
| Inhalte anderer Autoren bearbeiten | Nein | Nein |
| Benutzer oder Website-Einstellungen verwalten | Nein | Nein |

**Beispiel:** Ein Seitenautor pflegt eigene Informationsseiten. Ein Superautor
kann zusätzlich eigene Nachrichtenbeiträge veröffentlichen. „Superautor“
bedeutet nicht „Superadministrator“ und gibt keine Netzwerkverwaltung frei.
Andere Plugins können Berechtigungen zusätzlich beeinflussen.

Die Rollendefinitionen werden beim Laden der jeweiligen Website angelegt oder
entfernt. Es gibt keine sofortige Stapelverarbeitung aller Websites.
Eine bereits vorhandene gleichnamige Rolle wird nicht neu aufgebaut.

**Vor dem Deaktivieren:** Weisen Sie betroffenen Benutzern eine passende andere
Rolle zu. Das Plugin entfernt die Rollendefinition, migriert aber die bestehenden
Rollenzuweisungen nicht. Die Benutzer verlieren dadurch die Berechtigungen aus
dieser Rolle. Ihre Inhalte werden nicht gelöscht.

## Benutzersuche

**Aktiviert:** Die Suche in Benutzerlisten wird um zusätzliche Treffer aus
Profildaten ergänzt. Berücksichtigt werden Vorname, Nachname, Login,
Anzeigename, E-Mail-Adresse, Website-URL und der für URLs verwendete Benutzername.

**Beispiel:** `Anna Meyer` kann ein Konto finden, in dessen Profil Vor- und
Nachname in getrennten Feldern gespeichert sind. Für zusätzliche Treffer müssen
alle eingegebenen Wörter passen; Teiltreffer sind möglich. Die vorhandenen
WordPress-Suchergebnisse werden um diese Treffer ergänzt.

Enthält die Suche ein `@`, bleibt es bei der normalen WordPress-Suche.
Die Option ergänzt keine Suche auf der öffentlichen Website und legt keine
neuen Profilfelder an. **Deaktiviert:** Es werden keine zusätzlichen Treffer
durch diese Funktion ergänzt.

## Kontaktseite

**Aktiviert:** Auf den Unterwebsites wird bei Bedarf eine virtuelle Kontaktseite
erzeugt. Die Hauptwebsite des Netzwerks ist ausgenommen. Die Adresse richtet
sich nach der Sprache der Website, beispielsweise `/kontakt/` auf Deutsch und
`/contact/` auf Englisch, jeweils relativ zur Website-Adresse.

Die Seite listet die **Anzeigenamen und E-Mail-Adressen** der Konten mit der Rolle
„Administrator“ auf der jeweiligen Website. Netzwerk-Superadministratoren werden
nicht allein aufgrund ihres Netzwerkzugriffs aufgenommen. Prüfen Sie vor dem
Aktivieren, ob diese Kontaktdaten zur Veröffentlichung geeignet sind.
Bestehende Zugriffsbeschränkungen der Website gelten weiterhin.

Eine echte Seite mit demselben Slug verhindert die virtuelle Seite. Das gilt
auch für Entwürfe, private oder untergeordnete Seiten. Die vorhandene Seite wird
dadurch weder veröffentlicht noch verschoben. Eine untergeordnete Kontaktseite
führt also nicht automatisch zu einer erreichbaren Seite unter `/kontakt/`.

Die virtuelle Seite wird nicht in der Datenbank gespeichert und kann nicht im
Seiteneditor bearbeitet werden. Erstellen Sie für eigene Kontaktinhalte eine
normale Seite mit dem passenden Slug. Ohne Administrator-Konten enthält die
virtuelle Seite keine Kontaktliste.

**Deaktiviert:** Das Plugin erzeugt die virtuelle Seite nicht mehr. Echte
Kontaktseiten bleiben bestehen.

## Willkommenspanel

**Aktiviert:** Das WordPress-Willkommenspanel wird auf Website-Dashboards
ausgeblendet. Das ist beispielsweise sinnvoll, wenn eine eigene Einführung oder
Schulung bereitsteht. Andere Dashboard-Widgets und Berechtigungen bleiben
unverändert. Die Dashboard-Anpassungen von „White Label“ werden separat unter
„Allgemein“ gesteuert.

**Deaktiviert:** WordPress steuert das Panel wieder selbst. Ob eine Person es
sieht, hängt zusätzlich von deren Berechtigungen und persönlichen
Anzeigeeinstellungen ab.

## Administrator-Warnung

Die aktivierte Option ergänzt eine **bestätigbare Rückfrage**, keine feste
Obergrenze für Administratoren. Der Schwellenwert bezieht sich auf eine einzelne
Website. Standard und Mindestwert sind 3; bei deaktivierter Warnung wird der
Wert nicht verwendet.

**Beispiel:** Hat eine Website bereits drei Administratoren und steht der
Schwellenwert auf 3, erscheint beim Hochstufen eines weiteren bestehenden
Benutzers eine Rückfrage. Zum Fortfahren muss die angezeigte Bestätigungsphrase
exakt eingegeben werden. Bestehende Administratoren werden nicht herabgestuft.

Die Prüfung läuft in den Benutzerverwaltungsseiten der einzelnen Websites,
einschließlich Mehrfachänderungen von Rollen. Gezählt werden Konten mit der
Website-Rolle „Administrator“, nicht sämtliche Superadministratoren im Netzwerk.

Die Grenzen der aktuellen Umsetzung sind:

- Gezählt wird **vor** der Änderung. Wird die Schwelle durch eine Mehrfachänderung
  erst überschritten, gibt es dafür keine Warnung. Bei zwei bestehenden
  Administratoren und Schwelle 3 können beispielsweise mehrere Benutzer
  gleichzeitig ohne diese Rückfrage hochgestuft werden.
- Die Prüfung braucht vorhandene Benutzer-IDs. Das Erstellen neuer Konten und
  andere Vorgänge ohne vorhandene Benutzer-ID sind nicht abgedeckt.
- Die Bestätigung läuft über JavaScript. Sie ist keine serverseitig erzwungene
  Berechtigungsprüfung und erfasst keine Änderungen über Netzwerkverwaltung,
  API oder Kommandozeile.

**Deaktiviert:** Diese zusätzliche Rückfrage entfällt. Die normalen
WordPress-Berechtigungsprüfungen gelten weiterhin.

## Debug-Zugriff

Tragen Sie pro Zeile den **exakten Login-Namen** eines bestehenden Kontos ein,
beispielsweise:

```text
anna.meyer
max.mustermann
```

Verwenden Sie keine Anzeigenamen, E-Mail-Adressen oder Benutzer-IDs. Schreibweise
und Groß-/Kleinschreibung müssen dem tatsächlichen Login entsprechen. Leerzeichen
am Zeilenanfang und -ende sowie leere Zeilen werden beim Speichern entfernt.
Die Einträge werden beim Speichern nicht auf vorhandene Konten geprüft.

Die Liste gilt netzwerkweit für Komponenten, die
`\RRZE\Settings\Helper::userCanViewDebugLog()` verwenden. Superadministratoren
bestehen diese Prüfung immer; bei leerer Liste nur sie. Andere Komponenten
können zusätzlich eigene Zugriffsprüfungen durchführen.

Diese Einstellung aktiviert weder die Protokollierung noch einen Log-Viewer und
gewährt keinen allgemeinen Website-Zugang. Debug-Informationen können interne
Details und personenbezogene Daten enthalten. Tragen Sie nur Konten ein, die
diese Informationen für ihre Supportaufgaben benötigen. Das Entfernen eines
Logins entzieht die hier gewährte Zusatzberechtigung.
