# Architektur

## Grundsatz

BrickBank ist eine **eigenständige Web-Anwendung im MVC-Muster** (eigener,
schlanker Code), die **technisch an die AFOL.lu WoltLab Suite 5.5 (WSC)
gekoppelt** ist:

- **Eigenes MVC** — eigene Controller, Models und Views; keine Abhängigkeit vom
  WSC-Framework im Code selbst.
- **WSC-Kopplung über SSO** — Login, Session und Benutzeridentität kommen von
  WoltLab (Single Sign-on). Details siehe [INTEGRATION.md](INTEGRATION.md).

## Technologie

| Schicht | Technologie |
|---------|-------------|
| Frontend | **HTML** (server-gerendert), CSS, dezentes Vanilla-JavaScript |
| Backend | **PHP 7.2+** (kompatibel mit der WSC-5.5-Umgebung, läuft auch unter PHP 8.x) |
| Datenbank | **MySQL** (InnoDB, utf8mb4) — eigene BrickBank-Tabellen |
| Auth/Identität | **WoltLab Suite 5.5** (read-only, via SSO) |

## MVC-Aufbau

```
        ┌─────────────┐      ┌──────────────┐      ┌────────────┐
HTTP ─▶ │  Controller │ ───▶ │    Model     │ ───▶ │   MySQL    │
        │ (Logik je   │      │ (Entities +  │      │ (BrickBank │
        │  Use Case)  │ ◀─── │  Repository) │ ◀─── │  Tabellen) │
        └──────┬──────┘      └──────────────┘      └────────────┘
               │
               ▼
        ┌─────────────┐
        │    View     │ ──▶ HTML an den Browser
        │ (Templates) │
        └─────────────┘
```

- **Model** — Entitäten (Owner, Location, Part, Color, Element, InventoryItem)
  plus Repositories, die den DB-Zugriff kapseln. Enthält die Domänenlogik.
- **View** — reine Darstellung; HTML-Templates ohne Geschäftslogik.
- **Controller** — nimmt die Anfrage entgegen, ruft Models, wählt die View.
- **Front-Controller + Router** — `public/index.php` leitet jede Anfrage an den
  passenden Controller (saubere URLs via `.htaccess`).
- **Auth-Middleware** — vor jedem geschützten Controller prüft die
  WSC-Anbindung den Login (siehe Integration).

## Vorgeschlagene Projektstruktur

```
brickbank/
├── public/                     # Web-Root (DocumentRoot zeigt hierhin)
│   ├── index.php               # Front-Controller / Routing-Einstieg
│   ├── assets/                 # CSS, JS, Bilder (afol.lu-Design)
│   └── .htaccess               # Rewrites → index.php
├── app/
│   ├── Core/                   # Router, Request, Response, Controller-Basis, View-Renderer
│   ├── Controller/             # ein Controller je Use Case (InventoryController, ...)
│   ├── Model/                  # Entities + Repositories (DB-Zugriff, Domänenlogik)
│   ├── View/                   # Templates (Listen, Formulare)
│   └── Integration/            # WoltLab-Anbindung (WcfSession, WcfUser) — gekapselt
├── config/
│   └── config.example.php      # Vorlage; echte config.php ist .gitignore'd
├── sql/
│   ├── schema.sql              # BrickBank-Tabellen (siehe DATENMODELL.md)
│   └── seed.sql                # Stammdaten (Farben etc.)
└── docs/
```

## Grundprinzipien

- **PDO** für DB-Zugriff — Prepared Statements gegen SQL-Injection.
- **Trennung der Schichten** — Controller enthalten kein SQL, Views keine Logik.
- **WSC-Anbindung gekapselt** — alle WoltLab-Zugriffe ausschließlich im
  `Integration/`-Layer, damit WSC-Updates nur an einer Stelle nachgezogen werden.
- **Keine Passwörter in BrickBank** — Authentifizierung gehört vollständig WoltLab.
- **utf8mb4** durchgängig; **Secrets** (DB- und WSC-Zugang) nie ins Repo.

## Anfrage-Ablauf

```
Browser ──▶ public/index.php (Front-Controller)
                │ 1. Router ermittelt Controller/Action
                │ 2. Auth-Middleware → Integration/WcfSession (SSO-Prüfung)
                ▼
           Controller ──▶ Model/Repository ──PDO──▶ MySQL (BrickBank)
                │
                ▼
           View (Template, afol.lu-Design) ──▶ Browser
```

## Sicherheits-Leitplanken

- **Prepared Statements** für jede Abfrage mit Nutzereingaben.
- **Output-Escaping** (`htmlspecialchars`) bei jeder HTML-Ausgabe.
- **CSRF-Token** für alle datenändernden Formulare.
- **Serverseitige Validierung** (Mengen ≥ 0, Pflichtfelder, Fremdschlüssel).
- **WSC-Datenbank nur lesend** — BrickBank schreibt nie in WoltLab-Tabellen.
- **Konfiguration getrennt** von Code; `config.php` nie committen.

## Bewusst offen gelassen

- Externe Katalog-Importe (BrickLink o. ä.) erst nach dem MVP.
- Eigene Rollen über die WSC-Gruppen hinaus (vorerst genügen WSC-Benutzergruppen).
