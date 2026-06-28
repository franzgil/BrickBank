-- =====================================================================
-- BrickBank – Beispiel-Stammdaten (optional).
-- Nach schema.sql + migrations anwenden.
--
-- Hinweis: Teile/Farben/Elemente kommen aus dem Rebrickable-Katalog
-- (rb_parts, rb_colors, rb_elements) – hier werden KEINE Katalogdaten
-- geseedet, nur der Standard-Besitzer.
-- =====================================================================

SET NAMES utf8mb4;

-- Verein als Standard-Besitzer.
INSERT INTO bb_owner (type, name) VALUES ('verein', 'afol.lu');
