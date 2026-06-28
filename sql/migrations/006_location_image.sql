-- =====================================================================
-- Migration 006 – Bild je Ast/Standort
--
-- Jeder Ast (bb_location) kann ein selbst hochgeladenes Bild haben
-- (relativer Pfad unter public/, z. B. assets/uploads/locations/xyz.jpg).
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE bb_location
  ADD COLUMN image_path VARCHAR(255) NULL;
