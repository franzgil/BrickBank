-- =====================================================================
-- Migration 002 – Katalog auf Rebrickable umstellen.
--
-- bb_element verweist jetzt auf den Rebrickable-Datensatz
-- (rb_parts.part_num + rb_colors.id). Die lokalen Katalogtabellen
-- bb_part und bb_color entfallen.
--
-- ACHTUNG: verwirft vorhandene Inhalte von bb_element/bb_part/bb_color.
-- Nur ausführen, wenn dort noch keine produktiven Daten liegen.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bb_element;
DROP TABLE IF EXISTS bb_part;
DROP TABLE IF EXISTS bb_color;

CREATE TABLE bb_element (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_num VARCHAR(20) NOT NULL,     -- → rb_parts.part_num
  color_id INT NOT NULL,             -- → rb_colors.id
  UNIQUE KEY uq_element (part_num, color_id),
  KEY idx_element_part (part_num),
  KEY idx_element_color (color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
