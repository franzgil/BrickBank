# BrickBank 🧱

**BrickBank** ist das Inventar-System der [afol.lu](https://afol.lu) Community
(Adult Fans of LEGO Luxembourg). Es beantwortet vor allem zwei Fragen:

1. **Wie viele Steine haben wir?**
2. **Wo sind sie gelagert?**

Dabei werden sowohl **Vereinsbestände** (gemeinsames Eigentum der Community)
als auch **Privatbestände** einzelner Mitglieder verwaltet — klar getrennt,
aber im selben System durchsuchbar.

> ⚠️ **Status: Planungs-/Konzeptphase.** Dieses Repository enthält aktuell nur
> Dokumentation.
>
> **Technologie:** eigenständige **MVC-App** in PHP/MySQL, technisch gekoppelt an
> die **AFOL.lu WoltLab Suite 5.5** per **Single Sign-on** (Login & Identität
> kommen von WoltLab). Siehe [`docs/ARCHITEKTUR.md`](docs/ARCHITEKTUR.md) und
> [`docs/INTEGRATION.md`](docs/INTEGRATION.md).

---

## Was BrickBank leistet (Vision)

- **Bestand erfassen** — Teile nach Teilenummer, Farbe und Menge inventarisieren.
- **Lagerorte abbilden** — hierarchische Orte (Raum → Schrank → Schublade → Box/Fach),
  damit jeder Posten auffindbar ist.
- **Eigentum trennen** — Vereins- vs. Privatbestand, jeweils einem Besitzer zugeordnet.
- **Suchen & filtern** — „Wo sind unsere roten 2×4-Steine und wie viele haben wir?“
- **Mengen pflegen** — entnehmen, zurücklegen, umlagern.

## Dokumentation

| Dokument | Inhalt |
|----------|--------|
| [`docs/KONZEPT.md`](docs/KONZEPT.md) | Domäne, Anwendungsfälle, Begriffe |
| [`docs/DATENMODELL.md`](docs/DATENMODELL.md) | Entitäten und ihre Beziehungen |
| [`docs/ARCHITEKTUR.md`](docs/ARCHITEKTUR.md) | MVC-Aufbau, Projektstruktur, Sicherheit |
| [`docs/INTEGRATION.md`](docs/INTEGRATION.md) | WoltLab-Suite-5.5-Kopplung (SSO) |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | Schrittweise Umsetzung (MVP → Ausbau) |

> 🖥️ **HTML-Version** der Dokumentation (im afol.lu-Look): [`docs/html/index.html`](docs/html/index.html)
> — lokal im Browser öffnen. Markenfarben/Logo/Schrift sind zentral in
> [`docs/html/assets/style.css`](docs/html/assets/style.css) (CSS-Variablen unter `:root`) anpassbar.

## Installation & Betrieb

Voraussetzungen: PHP 7.2+ (getestet bis 8.x), MySQL, Apache mit `mod_rewrite`.

```bash
# 1. Konfiguration anlegen und ausfüllen (DB + WoltLab-SSO)
cp config/config.example.php config/config.php

# 2. Datenbank einrichten (Reihenfolge siehe sql/README.md)
mysql brickbank < sql/schema.sql
mysql brickbank < sql/migrations/001_module_behaelter_inventur.sql
mysql brickbank < sql/migrations/002_katalog_rebrickable.sql
mysql brickbank < sql/seed.sql        # optional

# Teile/Farben kommen aus dem Rebrickable-Katalog (rb_*-Tabellen, gleiche DB,
# nur lesend) – per Rebrickable-Import bereitstellen. Siehe docs/REBRICKABLE.md

# 3. Webserver-DocumentRoot auf public/ zeigen lassen
#    (lokal zum Testen:)
php -S 127.0.0.1:8000 -t public public/index.php
```

- **Struktur:** `public/` (Front-Controller), `app/` (MVC: Core/Controller/Model/View/
  Integration/Service), `sql/`, `config/`, `docs/`.
- **WoltLab-SSO:** Tabellen-/Cookie-Namen in `config/config.php` an die laufende
  WSC-5.5-Instanz anpassen (siehe [`docs/INTEGRATION.md`](docs/INTEGRATION.md)).
- **QR (optional serverseitig):** `phpqrcode` unter `vendor/phpqrcode/qrlib.php`
  ablegen; ohne die Bibliothek werden QR-Codes clientseitig im Browser erzeugt.

## Mitmachen

Dieses Projekt entsteht für und mit der afol.lu Community. Vorschläge,
Korrekturen und Ideen sind willkommen — am besten als Issue oder Pull Request.
