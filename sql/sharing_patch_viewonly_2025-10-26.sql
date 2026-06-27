-- Patch: adiciona coluna view_only em documento_links (2025-10-26)
ALTER TABLE documento_links ADD COLUMN view_only TINYINT(1) NOT NULL DEFAULT 0 AFTER max_downloads;
-- Patch para adicionar coluna de somente visualização em links de compartilhamento (2025-10-26)

ALTER TABLE documento_links
  ADD COLUMN IF NOT EXISTS view_only TINYINT(1) NOT NULL DEFAULT 0 AFTER downloads;
