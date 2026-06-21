-- =====================================================================
-- Migration 001 – Modul: Behälter, Etiketten & Inventur
-- Voraussetzung: schema.sql wurde angewendet.
-- Siehe docs/MODUL-BEHAELTER-INVENTUR.md
-- =====================================================================

SET NAMES utf8mb4;

-- 1) location.kind um Behältertypen erweitern (bestehende Werte bleiben).
ALTER TABLE bb_location
  MODIFY kind ENUM('raum','schrank','schublade','box','fach','sonstiges',
                   'container','karton','tuete')
  NOT NULL DEFAULT 'sonstiges';

-- 2) Etiketten-/Behälterdaten (1:1 zu einer Behälter-location).
CREATE TABLE bb_location_label (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,        -- ortsneutraler Code, z. B. T-000345
  code_seq INT NOT NULL,            -- laufende Nummer je Typ
  owner_id INT NULL,                -- optionaler Eigentümer des Behälters als Ganzes
  rfid_epc VARCHAR(64) NULL,        -- optionale UHF-RFID-EPC (spätere Ausbaustufe)
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_label_location (location_id),
  UNIQUE KEY uq_label_code (code),
  FOREIGN KEY (location_id) REFERENCES bb_location(id) ON DELETE CASCADE,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Bewegungshistorie (jeder Standortwechsel eines Behälters).
CREATE TABLE bb_location_movement (
  id INT AUTO_INCREMENT PRIMARY KEY,
  location_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,        -- Code redundant (Historie bleibt lesbar)
  from_parent_id INT NULL,
  from_code VARCHAR(16) NULL,       -- Snapshot des bisherigen Orts-Codes
  to_parent_id INT NULL,
  to_code VARCHAR(16) NULL,         -- Snapshot des neuen Orts-Codes
  wcf_user_id INT NULL,             -- wer hat umgeräumt (aus SSO)
  note VARCHAR(160) NULL,
  moved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_move_location (location_id),
  KEY idx_move_time (moved_at),
  FOREIGN KEY (location_id) REFERENCES bb_location(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Inventurläufe.
CREATE TABLE bb_stocktake (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  root_location_id INT NULL,        -- optional auf einen Container/Raum eingegrenzt
  owner_id INT NULL,                -- optional auf einen Besitzer eingegrenzt
  wcf_user_id INT NULL,             -- wer hat die Inventur gestartet
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  finished_at DATETIME NULL,
  FOREIGN KEY (root_location_id) REFERENCES bb_location(id) ON DELETE SET NULL,
  FOREIGN KEY (owner_id) REFERENCES bb_owner(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Einzelne Scans eines Inventurlaufs.
CREATE TABLE bb_stocktake_scan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  stocktake_id INT NOT NULL,
  code VARCHAR(16) NOT NULL,
  scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scan_stocktake (stocktake_id),
  KEY idx_scan_code (code),
  FOREIGN KEY (stocktake_id) REFERENCES bb_stocktake(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
