# Konzept

## Ausgangslage

Die afol.lu Community besitzt eine große Menge LEGO-Steine — teils gemeinsam
(z. B. für Bauprojekte und Events), teils privat von einzelnen Mitgliedern.
Steine lagern an verschiedenen Orten (bei Mitgliedern zu Hause, in einem
Vereinslager, in Kisten und Schubladen). Heute ist unklar:

- **Was** ist vorhanden (welche Teile, welche Farben)?
- **Wie viel** ist davon da?
- **Wo** liegt es konkret?
- **Wem** gehört es (Verein oder privat)?

BrickBank soll diese Fragen zentral und verlässlich beantworten.

## Kernziele

1. **Bestandsführung** — Mengen je Teil/Farbe nachvollziehbar erfassen.
2. **Lokalisierung** — jeder Bestandsposten ist einem konkreten Lagerort zugeordnet.
3. **Eigentumstrennung** — Vereins- und Privatbestand sind klar unterscheidbar.
4. **Auffindbarkeit** — schnelles Suchen/Filtern nach Teil, Farbe, Ort, Besitzer.

## Nicht-Ziele (vorerst)

- Kein Marktplatz / Verkauf.
- Kein Verleih-Workflow mit Fristen und Mahnungen (kann später kommen).
- Keine vollständige LEGO-Set-Datenbank — Fokus liegt auf losen Teilen (Bulk).

## Begriffe (Glossar)

| Begriff | Bedeutung |
|---------|-----------|
| **Teil (Part)** | Ein LEGO-Bauteil-Typ, identifiziert über eine Teilenummer (z. B. BrickLink-/LEGO-Design-ID). Unabhängig von der Farbe. |
| **Farbe (Color)** | Eine LEGO-Farbe (z. B. „Bright Red“). Ein Teil existiert i. d. R. in vielen Farben. |
| **Element** | Konkrete Kombination aus Teil + Farbe (das, was man tatsächlich zählt). |
| **Lagerort (Location)** | Physischer Aufbewahrungsort, hierarchisch (Raum → Schrank → Schublade → Box/Fach). |
| **Bestandsposten (Inventory Item)** | „X Stück von Element Y, an Ort Z, gehört Besitzer W.“ Die zentrale Einheit der Bestandsführung. |
| **Besitzer (Owner)** | Verein (gemeinsamer Bestand) oder ein einzelnes Mitglied (Privatbestand). |

## Zentrale Anwendungsfälle (Use Cases)

- **UC1 – Teil inventarisieren:** Nutzer erfasst „250× rote 2×4-Steine in Box A3
  im Vereinslager (Vereinsbesitz)“.
- **UC2 – Bestand suchen:** „Zeige alle roten 2×4-Steine“ → Liste mit Mengen
  und Lagerorten, summiert über alle Orte.
- **UC3 – Entnahme/Rücklage:** Menge eines Postens verringern/erhöhen
  (z. B. 50 Steine für ein Projekt entnommen).
- **UC4 – Umlagern:** Posten von einem Lagerort an einen anderen verschieben.
- **UC5 – Privatbestand führen:** Mitglied erfasst eigene Teile, getrennt vom
  Vereinsbestand, aber im selben System.
- **UC6 – Lagerort-Übersicht:** „Was liegt alles in Schublade B2?“

## Offene Fragen (für spätere Klärung)

- Sollen Teilenummern/Farben aus einer externen Quelle (z. B. BrickLink-Katalog)
  importiert werden, oder werden sie manuell gepflegt?
- Brauchen Privatbestände eine Sichtbarkeitssteuerung (privat vs. für Verein sichtbar)?
- Mehrsprachigkeit (LU/FR/DE/EN) nötig?
