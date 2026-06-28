-- =====================================================================
-- Migration 005 – Sortimentsbox/Einsatzkasten + eigene (parallele) ID
--
-- Neue etikettierbare Behältertypen mit automatischer ID (Code für QR/RFID),
-- plus optionale „eigene" ID (custom_code), die parallel zur automatischen
-- genutzt und gescannt werden kann.
-- =====================================================================

SET NAMES utf8mb4;

ALTER TABLE bb_location
  MODIFY kind ENUM('raum','schrank','schublade','box','fach','sonstiges',
                   'container','karton','tuete','sortimentsbox','einsatzkasten')
  NOT NULL DEFAULT 'sonstiges';

ALTER TABLE bb_location_label
  ADD COLUMN custom_code VARCHAR(32) NULL AFTER code,
  ADD UNIQUE KEY uq_label_custom (custom_code);
