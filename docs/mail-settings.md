# E-Mail-Einstellungen für das Netzwerk

Unter **Netzwerkverwaltung → RRZE Settings → E-Mail** konfigurieren Sie die
technischen Absenderadressen von WordPress-E-Mails und Ausnahmen für zentral
verwaltete Administrator-Adressen. Die Seite hieß bisher **Mail**; ihre Adresse
`admin.php?page=rrze-settings-mail` bleibt erhalten.

Die beiden Abschnitte **Technischer Absender** und
**Administrator-E-Mail-Adresse** werden gemeinsam gespeichert. Sprunglinks und
Rücklinks zur Übersicht helfen bei der Orientierung. Beschreibungstexte sind auf
70ch, die Übersicht-Links auf 75ch begrenzt. **Details und Beispiele** enthält
ergänzende Erläuterungen.

## Technischer Absender

Der **Envelope-Sender** ist die technische Rücksendeadresse für Zustellfehler.
Er wird beim SMTP-Versand als `MAIL FROM` verwendet und erscheint typischerweise
als `Return-Path` in der empfangenen Nachricht. Die sichtbare **Von-Adresse**
(`From`), der Absendername und **Reply-To** sind getrennte Angaben.
Die [PHPMailer-Referenz zu Sender](https://phpmailer.github.io/PHPMailer/classes/PHPMailer-PHPMailer-PHPMailer.html#property_Sender)
beschreibt diese technische Rücksendeadresse.

Das Modul setzt den Envelope-Sender beim WordPress-Versand über PHPMailer.
Versanddienste, die diesen Ablauf umgehen, sind dadurch nicht automatisch
erfasst. Zugangsdaten, SMTP-Server und Versandweg werden außerhalb dieser Seite
eingerichtet. Andere Mail-Plugins und der Mailserver können das endgültige
Ergebnis beeinflussen.

### Zulässige Absenderdomains

Tragen Sie eine reine Domain pro Zeile ein:

```text
example.org
mail.example.org
```

Verwenden Sie keine E-Mail-Adressen, kein `@`, kein `https://`, keine Pfade und
keine Platzhalter. Die Domain muss genau passen: `example.org` schließt
`mail.example.org` nicht ein. Der Vergleich berücksichtigt die gespeicherte
Groß- und Kleinschreibung. Verwenden Sie übereinstimmende kleingeschriebene
Domains in der Liste und in den Absenderadressen.

Eine **gefüllte Liste** beschränkt die geprüfte technische Absenderadresse auf
diese Domains. Eine **leere Liste** setzt keine Domainbeschränkung. Sie bedeutet
weder „alle sperren“ noch „E-Mail-Versand ausschalten“.

Die Liste betrifft keine Empfängeradressen und keine sichtbaren Von-Adressen.
Ist eine technische Absenderadresse nicht zugelassen, verwendet das Plugin die
Ersatzadresse, statt den Versand abzubrechen. Das Formular prüft die Syntax der
Domain, nicht deren DNS-Einträge oder die Erreichbarkeit eines Postfachs.

### Ersatzadresse (Envelope-Sender)

Tragen Sie eine einzelne gültige E-Mail-Adresse ohne Anzeigenamen ein,
beispielsweise `bounces@example.org`. Ist die Domainliste gefüllt, muss auch die
Domain dieser Ersatzadresse darin enthalten sein.

Beim Versand gilt diese Reihenfolge:

1. Ist bereits ein technischer Absender gesetzt, prüft das Plugin diesen.
2. Ist er leer, prüft es stattdessen die Administrator-E-Mail-Adresse der
   aktuellen Website.
3. Eine gültige Adresse mit zugelassener Domain bleibt erhalten. Eine ungültige
   oder nicht zugelassene Adresse wird durch die konfigurierte Ersatzadresse
   ersetzt.

Ein bereits gesetzter, aber unzulässiger Absender führt direkt zur Ersatzadresse.
Die Administrator-Adresse ist in diesem Fall kein zusätzlicher Zwischenschritt.

Beispiele mit zugelassener Domain `example.org` und Ersatzadresse
`bounces@example.org`:

| Ausgangslage | Ergebnis dieses Moduls |
| --- | --- |
| Technischer Absender `service@example.org` | Bleibt erhalten |
| Technischer Absender `service@external.example` | Wird durch `bounces@example.org` ersetzt |
| Kein technischer Absender; Website-Administrator `admin@example.org` | Verwendet `admin@example.org` |
| Kein technischer Absender; Website-Administrator `admin@external.example` | Verwendet `bounces@example.org` |

Ein **leeres Feld** stellt keine Ersatzadresse bereit. Wird eine Ersatzadresse
benötigt, lässt das Modul den Envelope-Sender leer. Der weitere Ablauf hängt
dann vom Versandweg ab; beispielsweise verwendet PHPMailer beim SMTP-Versand
bei leerem Envelope-Sender seine Von-Adresse. Das Leeren des Feldes ist deshalb
keine Versandsperre.

Beim Speichern wird die Ersatzadresse geleert, wenn sie ungültig ist oder nicht
zur gefüllten Domainliste passt. Prüfen Sie sie deshalb auch nach einer Änderung
der Domainliste. Eine passende Domain allein bestätigt weder ein existierendes
Postfach noch die Berechtigung des Mailservers, unter dieser Domain zu senden.

### Sichtbare Absender und Zustellung

Diese Optionen ändern weder `From`, `Reply-To`, Empfänger noch Nachrichteninhalt.
Die Option **White Label** unter
[RRZE Settings → Allgemein](general-settings.md#white-label) kann die sichtbare
Von-Adresse und den Namen separat anpassen.

Zur Zuordnung ergänzt das Modul den Header `X-CMS-Website` mit Website-URL und
Website-ID. Dieser technische Header behält seinen Namen auch nach der
Umbenennung des Verwaltungsmenüs in RRZE Settings.

Die erfolgreiche Verarbeitung eines Versandauftrags garantiert noch keinen
Eingang beim Empfänger. Das erläutert auch die
[WordPress-Referenz zu wp_mail](https://developer.wordpress.org/reference/functions/wp_mail/).
Prüfen Sie bei Zustellproblemen den tatsächlich genutzten Versandweg,
Mailserver-Protokolle und die Header einer empfangenen Nachricht.

## Administrator-E-Mail-Adresse

Die Website-Option `admin_email` ist eine Adresse der Website. Sie ist von den
E-Mail-Adressen der Benutzerkonten und der Adresse der Netzwerkverwaltung
getrennt. Sie kann zugleich als Ausgangsadresse für den technischen Absender
dienen, wenn PHPMailer noch keinen Envelope-Sender hat.

### Voraussetzung der zentralen Verwaltung

Das Modul sucht einen aktiven Datensatz für die aktuelle Website in der zentralen
Tabelle `cms_websites_data`. Gibt dieser Datensatz eine nicht leere `site_email`
vor und besteht keine Ausnahme, greift die zentrale Verwaltung:

- Eine gültige zentrale Adresse wird bei Verwaltungsaufrufen als `admin_email`
  übernommen. Ausstehende lokale Adressänderungen werden dabei entfernt, wenn
  die zentrale Adresse die bisherige Adresse ersetzt.
- Für Benutzer ohne `manage_network_options` wird das Feld unter
  **Einstellungen → Allgemein** deaktiviert und aus den erlaubten
  Speicheroptionen entfernt.
- Die regelmäßige WordPress-Abfrage zur Administrator-Adresse und die
  Benachrichtigung über deren Änderung werden durch dieses Modul abgeschaltet.

Ohne passenden aktiven Datensatz oder bei leerer zentraler Adresse erzwingt
dieses Modul keine zentrale Verwaltung. Ist eine nicht leere zentrale Adresse
ungültig, wird sie nicht übernommen; die Bearbeitungssperre kann dennoch gelten.
Korrigieren Sie in diesem Fall den zentralen Datensatz oder tragen Sie die
Website als Ausnahme ein.

Auch ein Superadministrator kann eine lokale Änderung bei einem späteren
Verwaltungsaufruf wieder durch die gültige zentrale Vorgabe ersetzt bekommen.
Für eine dauerhafte lokale Pflege ist die Ausnahme maßgeblich.

### Websites mit eigener Administrator-Adresse

Tragen Sie eine bestehende Website-ID pro Zeile ein, keine Benutzer-ID:

```text
12
34
```

Die ID finden Sie in der Website-Liste des Netzwerks oder im Parameter `id`
der Bearbeitungsadresse, beispielsweise `site-info.php?id=12`.

Nach dem Speichern ergänzt das Formular die Website-Adresse und sortiert nach
URL. Erkannte Websites erscheinen beispielsweise als
`12 - https://website.example.org`. Vorhandene Zeilen in diesem Format können
beibehalten werden; für neue Einträge genügt die ID.

Bei einer Ausnahme setzt das Modul die Adresse nicht mehr aus den zentralen
Daten und hebt seine Bearbeitungssperre auf. Berechtigte Personen können die
Adresse unter **Einstellungen → Allgemein** mit dem normalen WordPress-Ablauf
einschließlich erforderlicher Bestätigung ändern. Die Ausnahme erteilt keine
zusätzlichen Benutzerrechte und ändert die Adresse nicht von selbst.

Entfernen Sie die gesamte Zeile, um eine Ausnahme aufzuheben. Eine gültige
zentrale Adresse kann die lokale Adresse dann bei einem folgenden
Verwaltungsaufruf wieder ersetzen. Eine leere Liste enthält keine ausdrücklichen
Ausnahmen. Die Absenderdomain-Regeln gelten weiterhin für alle Websites.

## Anfängliche Standardwerte

| Einstellung | Standard | Bedeutung |
| --- | --- | --- |
| Zulässige Absenderdomains | Leer | Keine Domainbeschränkung |
| Ersatzadresse | Leer | Keine konfigurierte Ersatzadresse |
| Websites mit eigener Administrator-Adresse | Leer | Keine ausdrücklichen Ausnahmen von zentralen Vorgaben |

Bereits gespeicherte Werte können davon abweichen. Die Überarbeitung der Seite
verändert diese Werte nicht.
