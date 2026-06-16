# Architektur

## Technologie-Entscheidung

BrickBank wird als klassische **server-gerenderte Web-Anwendung** umgesetzt:

| Schicht | Technologie |
|---------|-------------|
| Frontend | **HTML** (server-gerendert), CSS, dezentes Vanilla-JavaScript |
| Backend | **PHP 7** |
| Datenbank | **MySQL** (InnoDB, utf8mb4) |

Diese Wahl ist bewusst pragmatisch: weit verbreitet, günstig zu hosten
(Standard-LAMP-Hosting genügt), niedrige Einstiegshürde für Mitwirkende und
robust für ein Inventar-Tool ohne hohe Interaktivitäts-Anforderungen.

## Grundprinzipien

- **PDO statt mysqli** für DB-Zugriff — Prepared Statements gegen SQL-Injection.
- **Trennung von Logik und Darstellung** — kein SQL/PHP-Logik mitten im HTML;
  schlanke Templates, separate Datenzugriffs-Schicht.
- **utf8mb4** durchgängig (Namen, Farben in mehreren Sprachen).
- **Keine Secrets im Repo** — DB-Zugangsdaten über Konfigurationsdatei außerhalb
  der Versionskontrolle bzw. Umgebungsvariablen.

## Vorgeschlagene Projektstruktur

```
brickbank/
├── public/                 # Web-Root (DocumentRoot zeigt hierhin)
│   ├── index.php           # Front-Controller / Routing
│   ├── assets/             # CSS, JS, Bilder
│   └── .htaccess           # Rewrites, schützt Nicht-Public-Pfade
├── src/
│   ├── Database.php        # PDO-Verbindung (Singleton/Factory)
│   ├── Repository/         # DB-Zugriff je Entität (OwnerRepository, ...)
│   ├── Controller/         # Anwendungslogik je Anwendungsfall
│   └── helpers.php         # gemeinsame Funktionen (escape, redirect, ...)
├── templates/              # HTML-Templates (Listen, Formulare)
├── sql/
│   ├── schema.sql          # Tabellen (siehe DATENMODELL.md)
│   └── seed.sql            # Beispiel-Stammdaten (Farben etc.)
├── config/
│   └── config.example.php  # Vorlage; echte config.php ist .gitignore'd
└── docs/
```

## Anfrage-Ablauf (vereinfacht)

```
Browser ──HTTP──▶ public/index.php (Front-Controller)
                        │  ermittelt Route
                        ▼
                   Controller  ──▶  Repository  ──PDO──▶  MySQL
                        │
                        ▼
                   Template (HTML)  ──▶  Browser
```

## Sicherheits-Leitplanken

- **Prepared Statements** für jede Abfrage mit Nutzereingaben.
- **Output-Escaping** (`htmlspecialchars`) bei jeder Ausgabe in HTML.
- **CSRF-Token** für alle Formulare, die Daten ändern.
- **Eingabevalidierung** serverseitig (Mengen ≥ 0, Pflichtfelder, Fremdschlüssel).
- **Konfiguration getrennt** von Code; `config.php` nie committen.

## Bewusst offen gelassen

- Authentifizierung/Rollen: zunächst minimal (z. B. ein gemeinsamer Login),
  Ausbau zu Mitglieder-Accounts in einer späteren Phase (siehe ROADMAP).
- Externe Katalog-Importe (BrickLink o. ä.) erst nach dem MVP.
