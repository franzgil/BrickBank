# Rebrickable-Katalog

BrickBank bezieht die **Detaildaten der LEGO-Elemente** (Teilenamen, Kategorien,
Farben/RGB, Sets, Minifiguren, Themen) aus dem **Rebrickable-Datensatz**. Dieser
liegt als `rb_*`-Tabellen in **derselben Datenbank** wie BrickBank und wird
**ausschließlich lesend** genutzt.

## Genutzte Tabellen

| Tabelle | Inhalt |
|---------|--------|
| `rb_parts` | Teile (`part_num`, `name`, `part_cat_id`) |
| `rb_part_categories` | Teilekategorien (`id`, `name`) |
| `rb_colors` | Farben (`id`, `name`, `rgb`, `is_trans`) |
| `rb_colors_matching` | Farbsystem-Zuordnungen (z. B. zu BrickLink/LDraw) — für später |
| `rb_elements` | Teil+Farbe → offizielle LEGO-Element-ID (`element_id`, `part_num`, `color_id`) |
| `rb_sets` / `rb_themes` | Sets und Themen |
| `rb_minifigs` | Minifiguren |
| `rb_inventories` / `rb_inventory_parts` / `rb_inventory_sets` / `rb_inventory_minifigs` | Set-/Minifig-Inhalte |
| `rb_part_relationships` | Beziehungen zwischen Teilen (Varianten, Drucke, …) |

## Anbindung an BrickBank

Die zählbare Einheit `bb_element` verweist auf Rebrickable:

```
bb_element.part_num  → rb_parts.part_num
bb_element.color_id  → rb_colors.id
```

`bb_inventory_item` (Bestand) hängt wie gehabt an `bb_element`. Detaildaten werden
zur Laufzeit per JOIN aus `rb_parts`/`rb_colors`/`rb_elements` geholt — siehe
`App\Model\Repository\CatalogRepository` (read-only).

## Wichtige Designentscheidungen

- **Keine harten Fremdschlüssel** von `bb_element` auf `rb_*`. So lässt sich der
  Rebrickable-Datensatz unabhängig aktualisieren (typischerweise TRUNCATE + Reload
  der CSV-Importe), ohne BrickBank-Constraints zu verletzen. Die Integrität
  (existiert das `part_num`/`color_id`?) wird bei der Erfassung in der App geprüft.
- **Nur lesend:** BrickBank schreibt nie in `rb_*`. Pflege/Update des Katalogs
  erfolgt über den Rebrickable-Import außerhalb von BrickBank.
- **Identität:** Ein „Element“ ist die Kombination `part_num` + `color_id`. Die
  offizielle `rb_elements.element_id` ist optional (nicht jede Kombination hat
  eine; manche mehrere) und wird nur zur Anzeige herangezogen.

## Beispielabfragen

```sql
-- Detaildaten zu einem bb_element
SELECT e.part_num, rp.name AS teil, pc.name AS kategorie,
       rc.name AS farbe, rc.rgb
FROM bb_element e
JOIN rb_parts rp           ON rp.part_num = e.part_num
LEFT JOIN rb_part_categories pc ON pc.id = rp.part_cat_id
JOIN rb_colors rc          ON rc.id = e.color_id
WHERE e.id = ?;

-- In welchen Farben gibt es ein Teil?
SELECT DISTINCT c.id, c.name, c.rgb
FROM rb_elements e JOIN rb_colors c ON c.id = e.color_id
WHERE e.part_num = '3001';
```
