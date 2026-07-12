-- =====================================================================
-- Migration 008 – Einzelgewicht je Item (Zählen per Gewicht)
--
-- Für das Zählen per Waage: Einzelgewicht (Gramm) je Item. Aus Gesamtgewicht
-- und Einzelgewicht wird die Stückzahl berechnet (qty = gesamt / einzel).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE bb_item
  ADD COLUMN unit_weight DECIMAL(8,3) NULL;   -- Gramm je Stück
