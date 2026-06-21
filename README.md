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

## Mitmachen

Dieses Projekt entsteht für und mit der afol.lu Community. Vorschläge,
Korrekturen und Ideen sind willkommen — am besten als Issue oder Pull Request.
