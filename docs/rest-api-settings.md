# REST-API-Zugriff im Netzwerk konfigurieren

Unter **Netzwerkverwaltung → CMS → REST-API** steuern Sie REST-Anfragen ohne
Anmeldung für alle Websites des Netzwerks. Über diese Schnittstelle tauschen
WordPress, Plugins und externe Anwendungen Daten aus. Eine **Route** ist ein
API-Pfad wie `/wp/v2/pages`. Ein **Namespace** wie `/wp/v2` fasst mehrere Routen
zusammen.

Die Seite gliedert sich in **Zugriff ohne Anmeldung**, **IP- und Routen-Ausnahmen**
und **Öffentliche Plugin-Endpunkte**. Das Inhaltsverzeichnis führt zum jeweiligen
Abschnitt und hebt ihn hervor. **Zur Übersicht** führt zurück. Alle Felder werden
gemeinsam gespeichert; die IP-Liste bleibt auch bei ausgeschalteter Sperre sichtbar.

Die Beschreibungen sind auf 70ch, die Übersicht-Links auf 75ch begrenzt.
**Details und Beispiele** lassen sich auch per Tastatur aufklappen. Änderungen
gelten nach dem Speichern für folgende Anfragen im gesamten Netzwerk.

## Welche Regel entscheidet

Die folgende Tabelle beschreibt die Netzwerkprüfung durch RRZE Settings.
Weitere Prüfungen durch WordPress, den Endpunkt selbst und RRZE Private Site
gelten zusätzlich.

| Anfrage | Regel in RRZE Settings |
| --- | --- |
| WordPress hat den Benutzer authentifiziert | Die hier konfigurierte Einschränkung für anonyme Anfragen greift nicht. |
| Anonym, genaue Route eines registrierten Plugin-Endpunkts | Die eigene Plugin-Checkbox muss aktiviert sein, und die HTTP-Methode muss zur Freigabe passen. |
| Anonym, andere Route, allgemeine Sperre ausgeschaltet | Die Anfrage wird durch diese Einstellung nicht blockiert. |
| Anonym, andere Route, allgemeine Sperre eingeschaltet | Eine passende IP-Adresse **oder** ein passender Routen-Eintrag erlaubt die Anfrage durch diese Sperre. |
| Anonym, andere Route, Sperre eingeschaltet, keine passende Ausnahme | Die Anfrage wird mit HTTP 403 abgewiesen. |

Eine breite IP- oder Namespace-Freigabe ersetzt die eigene Checkbox eines
registrierten Plugin-Endpunkts nicht. Das gilt auch bei ausgeschalteter allgemeiner
Sperre. Die Freigabe garantiert keinen erfolgreichen Aufruf: Der Endpunkt kann
beispielsweise eine gültige Signatur oder bestimmte Benutzerrechte verlangen.

## Zugriff ohne Anmeldung

**REST-Anfragen ohne Anmeldung einschränken** aktiviert die allgemeine Sperre.
Öffentliche Suchen, Formulare oder externe Anwendungen können dadurch betroffen
sein, wenn sie REST-Anfragen ohne WordPress-Authentifizierung senden. Geben Sie
die benötigten Routen oder registrierten Endpunkte gezielt frei.

Beim Ausschalten entfällt die allgemeine Sperre. Die eigenen Freigaben
registrierter Plugin-Endpunkte bleiben trotzdem erforderlich. Gespeicherte
IP- und Routenlisten werden durch das Ausschalten nicht gelöscht.

### Was als angemeldet gilt

Entscheidend ist, ob WordPress den Benutzer **für die REST-Anfrage** erkannt hat.
Bei der Anmeldung über Browser-Cookies braucht die Anfrage zusätzlich einen
gültigen REST-Nonce, also einen Sicherheitsnachweis. Ohne diesen Nonce behandelt
WordPress die Anfrage als anonym, auch wenn der Browser im Dashboard angemeldet
ist. Ein ungültiger Nonce führt bereits bei der WordPress-Authentifizierung zu
einem Fehler.

Auch Anwendungspasswörter können REST-Anfragen authentifizieren. Die
Benutzerrechte und die Berechtigungsprüfung des jeweiligen Endpunkts bleiben
maßgeblich. Diese Seite macht weder die öffentliche Website privat noch steuert
sie XML-RPC. XML-RPC wird unter
[CMS → Funktionen und Dienste](website-functions-settings.md#xml-rpc) konfiguriert.

## IP- und Routen-Ausnahmen

Die beiden Listen sind **alternative** Freigaben: Es genügt, dass eine davon zur
Anfrage passt. Eine IP-Adresse muss nicht zusätzlich zu einer erlaubten Route
passen. Für registrierte Plugin-Endpunkte gelten weiterhin deren eigene Freigaben.

### Erlaubte IP-Adressen und Netze

Eine passende Client-Adresse nimmt reguläre Routen von der allgemeinen Sperre
aus. Diese Ausnahme ist nicht auf GET und HEAD begrenzt. Der Endpunkt prüft
weiterhin seine eigenen Berechtigungen. Bei ausgeschalteter allgemeiner Sperre
hat die IP-Liste keine zusätzliche Wirkung.

Tragen Sie eine IPv4-/IPv6-Adresse oder ein CIDR-Netz pro Zeile ein:

```text
192.0.2.25 # Importdienst
198.51.100.0/24 # Beispielnetz
2001:db8::/64 # IPv6-Beispielnetz
```

Dies sind Dokumentationsadressen. Ersetzen Sie sie durch die tatsächlichen
Client-Adressen. `/24` bezeichnet hier ein IPv4-Netz, `/64` ein IPv6-Netz.
Kommentare stehen nach der Adresse hinter `#`. Verwenden Sie keine Hostnamen,
URLs oder Von-bis-Bereiche.

Gültige Einträge werden in CIDR-Schreibweise gespeichert, einzelne IPv4-Adressen
mit `/32`, einzelne IPv6-Adressen mit `/128`. Auch unterstützte abschließende
Platzhalter wie `198.51.100.*` werden in CIDR-Schreibweise umgewandelt. Ungültige
Einträge werden beim Speichern ausgelassen. Eine leere Liste enthält keine
IP-Ausnahmen.

Die IP-Ausnahme gewährt keinen Zugang zu einer privaten Website und ersetzt
keine Plugin-Checkbox. Sie gilt nur für die hier konfigurierte allgemeine Sperre.

### Client-Adressen hinter einem Reverse Proxy

Maßgeblich ist die durch den Server ermittelte Client-Adresse. Standardmäßig wird
`REMOTE_ADDR` verwendet. Weiterleitungs-Header werden nur bei separat
konfigurierten vertrauenswürdigen Proxys ausgewertet. Die Liste erlaubter
Besucheradressen stellt selbst kein Vertrauen in Proxys her.

Wenn die Anwendung hinter einem Reverse Proxy läuft, muss die Serveradministration
die tatsächlichen Infrastruktur-Proxys über `rrze_trusted_proxies` konfigurieren.
Details und ein Konfigurationsbeispiel stehen im
[README zu Client-Adressen und Reverse Proxys](../README.md#client-ip-addresses-and-reverse-proxies).

### Erlaubte Namespaces und Routen

Ein Eintrag nimmt die angegebene Route und ihre Unterrouten von der allgemeinen
Sperre aus. Diese Netzwerkfreigabe gilt für **alle HTTP-Methoden**, nicht nur
für lesende Anfragen. Berechtigungsprüfungen des Endpunkts bleiben wirksam.

```text
/wp/v2/pages
/wp/v2/posts
/oembed
```

| Eintrag | Umfasst | Umfasst nicht |
| --- | --- | --- |
| `/wp/v2/pages` | `/wp/v2/pages` und `/wp/v2/pages/42` | `/wp/v2/pages-other`, `/wp/v2/posts` |
| `/wp/v2` | Alle Routen unter `/wp/v2/` | Andere Namespaces |
| `/oembed` | Routen unter `/oembed/`, etwa `/oembed/1.0/embed` | Andere Namespaces |

Verwenden Sie den API-Pfad ohne Domain, `/wp-json`-Präfix, Abfrageparameter oder
`#`-Kommentare. Schrägstriche am Anfang und Ende sind optional. Groß- und
Kleinschreibung wird beachtet. Platzhalter werden nicht ausgewertet. Der Eintrag
`pages` allein passt nicht zu `/wp/v2/pages`.

Eine leere Liste enthält keine Routen-Ausnahmen. Gespeicherte Routen-Einträge
stehen außerdem RRZE Private Site für lokale öffentliche Ausnahmen zur Verfügung,
auch wenn die allgemeine Netzwerk-Sperre ausgeschaltet ist. Beim Entfernen eines
Eintrags entfällt auch die zugehörige lokale Ausnahme. Nach einer Pfadänderung
muss der Website-Administrator den neuen Eintrag lokal auswählen.

## Öffentliche Plugin-Endpunkte

Dieser Bereich zeigt von Plugins gemeldete Endpunkte mit Namen, HTTP-Methoden,
Route und Beschreibung. Er ist kein vollständiges Verzeichnis aller
WordPress-REST-Routen.

Eine aktivierte Checkbox erlaubt Anfragen ohne Anmeldung für die **genaue Route**
und die aufgeführten Methoden. Unterrouten sind nicht enthalten. Bei registrierten
Endpunkten erfolgt der Pfadvergleich ohne Beachtung der Groß- und Kleinschreibung;
das unterscheidet sich vom Vergleich der Routenliste.

| Methode | Bedeutung |
| --- | --- |
| GET | Inhalte abrufen |
| HEAD | Antwort-Header ohne den Inhalt abrufen |
| POST | Daten übermitteln, je nach Endpunkt auch eine Aktion auslösen |

Die eigene Berechtigungsprüfung des Plugins bleibt bestehen. Beispielsweise
muss eine signierte Download-URL weiterhin gültig sein. Beim Abwählen entfällt
die Freigabe für folgende anonyme Anfragen und auch die entsprechende lokale
öffentliche Ausnahme auf privaten Websites.

### Wenn ein Endpunkt fehlt

Ein nur auf einer einzelnen Website aktives Plugin wird nicht zwingend in der
Netzwerkverwaltung geladen. Rufen Sie die betroffene Website einmal auf und
laden Sie anschließend diese Einstellungsseite neu. Das Plugin muss seinen
Endpunkt über die dafür vorgesehene Schnittstelle melden.

Bereits entdeckte Meldungen bleiben für die Netzwerkverwaltung gespeichert.
Ein gelisteter Endpunkt kann deshalb von einer anderen Website stammen oder
auf der aktuellen Website nicht aktiv sein. Die Freigabe wirkt dort, wo der
Anbieter den Endpunkt auf der jeweiligen Website tatsächlich meldet.

## Zusammenspiel mit privaten Websites

RRZE Private Site besitzt eine zusätzliche Zugriffsprüfung. Für Besucher ohne
bereits bestehenden Website-Zugang sind zwei Schritte nötig:

1. Die Netzwerkverwaltung erlaubt eine Route oder aktiviert die eigene Checkbox
   eines registrierten Plugin-Endpunkts.
2. Der Website-Administrator wählt diese Ausnahme unter **Einstellungen → Lesen →
   Öffentliche REST-API-Routen** aus.

Diese lokalen **öffentlichen** Ausnahmen sind auf GET und HEAD beschränkt.
Ein ausschließlich für POST registrierter Endpunkt wird dort nicht angeboten.
Auch innerhalb eines lokal erlaubten Namespaces benötigt ein registrierter
Plugin-Endpunkt seine eigene Auswahl.

Besucher, die bereits durch Mitgliedschaft, IP-Adresse, Website-Passwort oder SSO
Zugang zur privaten Website haben, benötigen keine zusätzliche lokale Checkbox.
Die Netzwerkregeln und die Berechtigungsprüfung des Endpunkts gelten weiterhin.
Die GET-/HEAD-Grenze betrifft die öffentlichen Ausnahmen, nicht den bereits
bestehenden Website-Zugang.

Beispiel: Für einen öffentlichen FAUbox-Download ohne bisherigen Website-Zugang
muss der Download-Endpunkt sowohl im Netzwerk als auch lokal freigegeben sein.
Die Signaturprüfung der Download-URL bleibt erhalten.

## Einen abgewiesenen Aufruf prüfen

Prüfen Sie bei HTTP 403 die konkrete Route und HTTP-Methode sowie diese Punkte:

- Erkennt WordPress den Benutzer für diese Anfrage, einschließlich des nötigen
  REST-Nonce bei Cookie-Anmeldung?
- Handelt es sich um einen registrierten Plugin-Endpunkt, und ist dessen eigene
  Checkbox für die angefragte Methode freigegeben?
- Falls die allgemeine Sperre greift: Passt die tatsächliche Client-Adresse oder
  der vollständige API-Pfad zu einer Ausnahme?
- Falls RRZE Private Site schützt: Besteht bereits Website-Zugang oder eine
  passende lokale öffentliche Ausnahme für GET beziehungsweise HEAD?
- Sind die eigenen Voraussetzungen des Endpunkts erfüllt, etwa eine Signatur?

## Anfängliche Standardwerte

| Einstellung | Standard |
| --- | --- |
| Allgemeine Sperre | Aus |
| IP-Adressen und Netze | Leer |
| Namespaces und Routen | `oembed` |
| Plugin-Freigaben | Keine |

Bereits gespeicherte Werte bleiben durch die Überarbeitung der Oberfläche
unverändert. Auch bei der anfänglich ausgeschalteten allgemeinen Sperre benötigen
registrierte Plugin-Endpunkte eine ausdrückliche Freigabe für anonyme Anfragen.
