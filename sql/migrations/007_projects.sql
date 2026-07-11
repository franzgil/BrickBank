-- =====================================================================
-- Migration 007 – Projekte
--
-- Ein Projekt ist eine eigene Wurzel für einen Lagerbaum – genau wie ein
-- Konto (Verein/Privat). Deshalb wird es als bb_owner mit dem neuen Typ
-- 'projekt' abgebildet. Standorte/Behälter (bb_location) hängen wie gewohnt
-- über owner_id an diesem Projekt; die gesamte Baum-/Behälter-Logik gilt
-- unverändert.
--   note: freie Projektbeschreibung (auch für Konten nutzbar).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE bb_owner
  MODIFY COLUMN type ENUM('verein','privat','projekt') NOT NULL;

ALTER TABLE bb_owner
  ADD COLUMN note VARCHAR(255) NULL AFTER email;
