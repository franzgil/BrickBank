# Integration mit der AFOL.lu WoltLab Suite 5.5

BrickBank ist eine eigenständige App, **delegiert Login und Identität aber an
WoltLab Suite 5.5 (WSC)** — Single Sign-on. Mitglieder melden sich einmal im
afol.lu-Forum an und sind damit auch in BrickBank angemeldet.

> ℹ️ **Hinweis zu Versionsdetails:** Exakte Tabellen-, Spalten- und Cookie-Namen
> (z. B. Tabellen-Präfix `wcf1_`, Cookie-Präfix) hängen von der konkreten
> Installation ab und können sich zwischen WSC-Versionen ändern. Die unten
> genannten Namen sind WSC-typisch und **vor der Umsetzung an der laufenden
> 5.5-Instanz zu verifizieren**. Die gesamte Anbindung wird im `Integration/`-
> Layer gekapselt, damit Anpassungen nur an einer Stelle nötig sind.

## Voraussetzungen

- **Gemeinsame Domain** — BrickBank läuft unter derselben Domain wie das Forum
  (z. B. `brickbank.afol.lu` oder `afol.lu/brickbank`), damit die
  WSC-Session-Cookies lesbar sind.
- **Lesezugriff auf die WSC-Datenbank** — ein DB-Benutzer mit **nur lesenden**
  Rechten auf die relevanten `wcf1_*`-Tabellen.

## Authentifizierung (SSO-Ablauf)

```
1. Anfrage an BrickBank
        │
        ▼
2. Integration/WcfSession liest das WSC-Session-Cookie
        │
        ▼
3. Auflösung der Session in der WSC-DB  →  userID
        │
   ┌────┴─────────────────────────┐
   ▼                              ▼
4a. gültiger Login            4b. kein/abgelaufener Login
    → userID, Gruppen             → Redirect zur WSC-Login-Seite
      geladen, weiter               mit Rücksprung-URL (?url=…)
```

- BrickBank **speichert keine Passwörter** und legt keine eigenen Logins an.
- Die Session-/Cookie-Prüfung erfolgt gegen die WSC-Datenbank (z. B. Tabelle
  `wcf1_user`/Session-Tabelle der 5.5-Instanz).

## Benutzerdaten (read-only)

Aus WoltLab werden ausschließlich gelesen:

| Feld | Quelle (WSC) | Verwendung in BrickBank |
|------|--------------|--------------------------|
| `userID` | `wcf1_user` | stabile Verknüpfung zum Mitglied |
| `username` | `wcf1_user` | Anzeige (z. B. „erfasst von …“) |
| `email` | `wcf1_user` | optionale Kontaktanzeige |
| Benutzergruppen | `wcf1_user_to_group` | Autorisierung (siehe unten) |

## Autorisierung

- Der Zugang zu BrickBank wird über **WSC-Benutzergruppen** gesteuert
  (z. B. nur Mitglieder der Gruppe „Aktiv“/„Mitglieder“ dürfen Bestände bearbeiten).
- Welche Gruppe welche Aktion darf, wird in der BrickBank-Konfiguration auf
  WSC-Gruppen-IDs abgebildet — so bleibt die Rechtevergabe im Forum die
  einzige Quelle der Wahrheit.

## Verknüpfung mit dem Datenmodell

Privatbestände gehören einem konkreten Forumsmitglied. Dazu trägt die
BrickBank-Tabelle `owner` für `type = 'privat'` die WSC-`userID`:

- `owner.wcf_user_id` → logische Referenz auf `wcf1_user.userID`
  (kein erzwungener Fremdschlüssel, da andere DB/Tabelle).

So lässt sich beim Login automatisch der passende Privat-Besitzer finden bzw.
beim ersten Zugriff anlegen.

## Design-Kopplung

- BrickBank übernimmt **Header, Footer und Stil** der WoltLab-Optik (das aktuelle
  afol.lu-Design in `public/assets/` bildet das nach).
- Optionaler Ausbau: echte WSC-Templates/Style serverseitig einbinden, damit
  Stiländerungen im Forum automatisch übernommen werden.

## Wartung & Sicherheit

- **Nur lesende** Zugriffe auf WSC-Tabellen — niemals schreiben.
- WSC-Updates können Session-Mechanik oder Schema ändern → Anbindung bleibt im
  `Integration/`-Layer isoliert und ist dort gezielt anpassbar.
- Zugangsdaten (WSC-DB, Cookie-/Präfix-Konfiguration) liegen in `config.php`
  außerhalb der Versionskontrolle.
