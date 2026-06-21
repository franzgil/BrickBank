-- =====================================================================
-- BrickBank – Beispiel-Stammdaten (optional).
-- Nach schema.sql + migrations anwenden.
-- =====================================================================

SET NAMES utf8mb4;

-- Verein als Standard-Besitzer.
INSERT INTO owner (type, name) VALUES ('verein', 'afol.lu');

-- Ein paar gängige LEGO-Farben (Namen/Codes an BrickLink angelehnt).
INSERT INTO color (name, code, hex) VALUES
  ('Black',       '11', '05131d'),
  ('White',        '1', 'ffffff'),
  ('Bright Red',   '5', 'c91a09'),
  ('Bright Blue',  '7', '0055bf'),
  ('Bright Yellow','3', 'f2cd37'),
  ('Dark Gray',   '85', '6c6e68'),
  ('Light Gray',  '86', 'a0a5a9');
