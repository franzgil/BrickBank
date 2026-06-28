-- =====================================================================
-- Migration 004 – Lagerbaum je Konto
--
-- Jeder Standort/Behälter (bb_location) gehört zu einem Konto (bb_owner) –
-- das Konto ist die Wurzel des Baums. Äste ohne Eltern (parent_id NULL)
-- hängen direkt am Konto-Root. Bestand bleibt davon unabhängig (eine
-- Position kann einem anderen Besitzer gehören als der Ast, in dem sie liegt).
-- Die Typ-Reihenfolge (Tüte→Karton→Container) ist nur noch ein Hinweis.
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE bb_location
  ADD COLUMN owner_id INT NULL;   -- Konto, zu dem dieser Ast gehört

-- Backfill: vorhandene Orte einem Konto zuordnen
-- (Behälter → Label-Eigentümer, sonst Verein).
UPDATE bb_location l
LEFT JOIN bb_location_label ll ON ll.location_id = l.id
SET l.owner_id = COALESCE(
      ll.owner_id,
      (SELECT id FROM bb_owner WHERE type = 'verein' ORDER BY id LIMIT 1)
    );

ALTER TABLE bb_location
  ADD KEY idx_location_owner (owner_id),
  ADD FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL;
