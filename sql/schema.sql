-- =====================================================================
-- BrickBank – Basis-Schema (Phase 1)
-- MySQL / InnoDB / utf8mb4
-- Reihenfolge: zuerst schema.sql, dann sql/migrations/* in Nummern-Folge,
-- optional sql/seed.sql. Siehe sql/README.md.
-- =====================================================================

SET NAMES utf8mb4;

-- --- Besitzer (Verein oder Privatmitglied) ---------------------------
CREATE TABLE bb_owner (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('verein','privat') NOT NULL,
  wcf_user_id INT NULL,          -- logische Referenz auf wcf1_user.userID (SSO)
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_wcf_user (wcf_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Lagerort (hierarchisch, selbst-referenzierend) ------------------
CREATE TABLE bb_location (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  name VARCHAR(120) NOT NULL,
  kind ENUM('raum','schrank','schublade','box','fach','sonstiges')
       NOT NULL DEFAULT 'sonstiges',
  note VARCHAR(255) NULL,
  KEY idx_location_parent (parent_id),
  FOREIGN KEY (parent_id) REFERENCES bb_location(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Element (Teil + Farbe = zählbare Einheit) ----------------------
-- Verweist auf den Rebrickable-Katalog (rb_parts.part_num, rb_colors.id).
-- Bewusst KEINE harten Fremdschlüssel auf rb_*, damit der Rebrickable-
-- Datensatz unabhängig (TRUNCATE+Reload) neu eingespielt werden kann.
CREATE TABLE bb_element (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_num VARCHAR(20) NOT NULL,     -- → rb_parts.part_num
  color_id INT NOT NULL,             -- → rb_colors.id
  UNIQUE KEY uq_element (part_num, color_id),
  KEY idx_element_part (part_num),
  KEY idx_element_color (color_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Bestandsposten -------------------------------------------------
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
