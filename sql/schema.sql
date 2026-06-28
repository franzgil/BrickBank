-- =====================================================================
-- BrickBank – Vollständiges Schema (aktueller Stand, inkl. Phase A)
-- MySQL / InnoDB / utf8mb4
--
-- FRISCHE Installation: NUR diese Datei einspielen (sie enthält alle
--   Tabellen) + optional seed.sql. KEINE migrations/ nötig.
-- BESTEHENDE Installation: NICHT erneut einspielen – stattdessen die
--   migrations/ in Nummern-Folge anwenden (Evolution). Siehe sql/README.md.
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

-- --- Lagerort (hierarchisch) + Etiketten/Behälter --------------------
CREATE TABLE bb_location (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,                 -- übergeordneter Ast; NULL = direkt am Konto-Root
  owner_id INT NULL,                  -- Konto (Wurzel des Baums), zu dem dieser Ast gehört
  name VARCHAR(120) NOT NULL,
  kind ENUM('raum','schrank','schublade','box','fach','sonstiges',
            'container','karton','tuete') NOT NULL DEFAULT 'sonstiges',
  note VARCHAR(255) NULL,
  managed_by_wcf_user_id INT NULL,   -- Lagerwart (WSC-Mitglied), optional
  KEY idx_location_parent (parent_id),
  KEY idx_location_owner (owner_id),
  FOREIGN KEY (parent_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_location_label (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  code_seq INT NOT NULL,
  owner_id INT NULL,
  rfid_epc VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_label_location (location_id),
  UNIQUE KEY uq_label_code (code),
  FOREIGN KEY (location_id) REFERENCES bb_location(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_location_movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  from_parent_id INT NULL,
  from_code VARCHAR(16) NULL,
  to_parent_id INT NULL,
  to_code VARCHAR(16) NULL,
  wcf_user_id INT NULL,
  note VARCHAR(160) NULL,
  moved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_move_location (location_id),
  KEY idx_move_time (moved_at),
  FOREIGN KEY (location_id) REFERENCES bb_location(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Item: zählbare Katalog-Einheit (Teil/Set/Minifig) ---------------
-- Verweist auf Rebrickable (rb_*). Keine harten FKs auf rb_* (Reimport).
CREATE TABLE bb_item (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('element','set','minifig') NOT NULL,
  part_num VARCHAR(20) NULL,   -- element → rb_parts.part_num
  color_id INT NULL,           -- element → rb_colors.id
  set_num  VARCHAR(20) NULL,   -- set     → rb_sets.set_num
  fig_num  VARCHAR(20) NULL,   -- minifig → rb_minifigs.fig_num
  item_key VARCHAR(48) NOT NULL,    -- kanonischer Schlüssel: E:part:color, S:set, M:fig
  UNIQUE KEY uq_item_key (item_key),
  KEY idx_item_element (part_num, color_id),
  KEY idx_item_set (set_num),
  KEY idx_item_fig (fig_num)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --- Bestand (Holding): Menge je Item·Ort·Besitzer·Zustand -----------
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

-- --- Mengen-Bewegungen (Audit) --------------------------------------
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

-- --- Inventur (Behälter-Soll/Ist) -----------------------------------
CREATE TABLE bb_stocktake (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  root_location_id INT NULL,
  owner_id INT NULL,
  wcf_user_id INT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  FOREIGN KEY (root_location_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bb_stocktake_scan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stocktake_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scan_stocktake (stocktake_id),
  KEY idx_scan_code (code),
  FOREIGN KEY (stocktake_id) REFERENCES bb_stocktake(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
