-- =====================================================================
-- Migration 003 – Phase A: Items, Bestand (Holdings) & Mengen-Audit
--
-- Überführt das Bestandsmodell von (bb_element + bb_inventory_item) auf das
-- vereinheitlichte Modell aus KONZEPT-2.0:
--   bb_item (element|set|minifig)  ·  bb_holding (+ Sichtbarkeit + Zustand)
--   bb_inventory_movement (lückenloses Mengen-Audit)
-- Plus Lagerverwaltung: bb_location.managed_by_wcf_user_id.
--
-- Vorhandene Daten werden verlustfrei migriert; alte Tabellen danach entfernt.
-- ACHTUNG: zusammen mit dem zugehörigen Code-Stand (git pull) einspielen.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Lager-Verwaltung am Ort (Lagerwart).
ALTER TABLE bb_location
  ADD COLUMN managed_by_wcf_user_id INT NULL;   -- WSC-Mitglied, optional

-- 2) Einheitliches Item (Teil/Set/Minifig).
CREATE TABLE bb_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('element','set','minifig') NOT NULL,
  part_num VARCHAR(20) NULL,   -- element → rb_parts.part_num
  color_id INT NULL,           -- element → rb_colors.id
  set_num  VARCHAR(20) NULL,   -- set     → rb_sets.set_num
  fig_num  VARCHAR(20) NULL,   -- minifig → rb_minifigs.fig_num
  item_key VARCHAR(48) NOT NULL,
  UNIQUE KEY uq_item_key (item_key),
  KEY idx_item_element (part_num, color_id),
  KEY idx_item_set (set_num),
  KEY idx_item_fig (fig_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bestehende Elemente → Items.
INSERT INTO bb_item (type, part_num, color_id, item_key)
SELECT 'element', part_num, color_id, CONCAT('E:', part_num, ':', color_id)
FROM bb_element;

-- 3) Bestand (Holding) mit Sichtbarkeit + Zustand.
CREATE TABLE bb_holding (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  location_id INT NOT NULL,
  owner_id INT NOT NULL,
  visibility ENUM('privat','intern','verein') NOT NULL DEFAULT 'privat',
  cond ENUM('neu','gebraucht') NOT NULL DEFAULT 'gebraucht',
  quantity INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_holding (item_id, location_id, owner_id, cond),
  KEY idx_holding_loc (location_id),
  KEY idx_holding_item (item_id),
  FOREIGN KEY (item_id) REFERENCES bb_item(id),
  FOREIGN KEY (location_id) REFERENCES bb_location(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bestehende Bestandsposten migrieren (Sichtbarkeit aus Eigentum abgeleitet).
INSERT INTO bb_holding (item_id, location_id, owner_id, visibility, cond, quantity, updated_at)
SELECT it.id, ii.location_id, ii.owner_id,
       CASE o.type WHEN 'verein' THEN 'intern' ELSE 'privat' END,
       'gebraucht', ii.quantity, ii.updated_at
FROM bb_inventory_item ii
JOIN bb_element e ON e.id = ii.element_id
JOIN bb_item it  ON it.item_key = CONCAT('E:', e.part_num, ':', e.color_id)
JOIN bb_owner o  ON o.id = ii.owner_id;

-- 4) Mengen-Audit + Startbestand als Zugang.
CREATE TABLE bb_inventory_movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  owner_id INT NOT NULL,
  cond ENUM('neu','gebraucht') NOT NULL,
  from_location_id INT NULL,
  to_location_id INT NULL,
  quantity INT NOT NULL,          -- Betrag (> 0)
  type ENUM('zugang','entnahme','umbuchung','korrektur') NOT NULL,
  wcf_user_id INT NULL,
  note VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_im_item (item_id),
  KEY idx_im_time (created_at),
  FOREIGN KEY (item_id) REFERENCES bb_item(id),
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id),
  FOREIGN KEY (from_location_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (to_location_id) REFERENCES bb_location(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bb_inventory_movement (item_id, owner_id, cond, to_location_id, quantity, type, note)
SELECT item_id, owner_id, cond, location_id, quantity, 'zugang', 'Migration: Startbestand'
FROM bb_holding WHERE quantity > 0;

-- 5) Alte Tabellen entfernen (jetzt unreferenziert).
DROP TABLE IF EXISTS bb_inventory_item;
DROP TABLE IF EXISTS bb_element;

SET FOREIGN_KEY_CHECKS = 1;
