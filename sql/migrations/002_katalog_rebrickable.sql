-- =====================================================================
-- Migration 002 – Katalog auf Rebrickable umstellen.
--
-- bb_element verweist jetzt auf den Rebrickable-Datensatz
-- (rb_parts.part_num + rb_colors.id). Die lokalen Katalogtabellen
-- bb_part und bb_color entfallen.
--
-- Vorgehen: in Kind→Eltern-Reihenfolge löschen, damit es AUCH OHNE
-- wirksamen FOREIGN_KEY_CHECKS-Schalter funktioniert (manche Tools führen
-- jedes Statement in eigener Sitzung aus). bb_inventory_item wird mit
-- gelöscht und neu angelegt – im aktuellen Stand ist es leer (es gibt noch
-- keine Bestands-Erfassung), daher kein Datenverlust.
--
-- ACHTUNG: verwirft Inhalte von bb_inventory_item/bb_element/bb_part/bb_color.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Erst das Kind (referenziert bb_element), dann die Katalogtabellen.
DROP TABLE IF EXISTS bb_inventory_item;
DROP TABLE IF EXISTS bb_element;
DROP TABLE IF EXISTS bb_part;
DROP TABLE IF EXISTS bb_color;

-- 2) Element als Verweis auf Rebrickable (keine harten FKs auf rb_*).
CREATE TABLE bb_element (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_num VARCHAR(20) NOT NULL,     -- → rb_parts.part_num
  color_id INT NOT NULL,             -- → rb_colors.id
  UNIQUE KEY uq_element (part_num, color_id),
  KEY idx_element_part (part_num),
  KEY idx_element_color (color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Bestandsposten neu anlegen (FK wieder auf bb_element).
CREATE TABLE bb_inventory_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  element_id INT NOT NULL,
  location_id INT NOT NULL,
  owner_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_item (element_id, location_id, owner_id),
  KEY idx_item_location (location_id),
  FOREIGN KEY (element_id) REFERENCES bb_element(id),
  FOREIGN KEY (location_id) REFERENCES bb_location(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
