-- Patch de compartilhamento: flags de link (2025-10-27)

ALTER TABLE documento_links 
  ADD COLUMN IF NOT EXISTS view_only TINYINT(1) NOT NULL DEFAULT 0 AFTER max_downloads,
  ADD COLUMN IF NOT EXISTS force_watermark TINYINT(1) NOT NULL DEFAULT 0 AFTER view_only;

-- Observação: se sua versão do MySQL não suportar IF NOT EXISTS em ADD COLUMN,
-- execute manualmente:
-- ALTER TABLE documento_links ADD COLUMN view_only TINYINT(1) NOT NULL DEFAULT 0 AFTER max_downloads;
-- ALTER TABLE documento_links ADD COLUMN force_watermark TINYINT(1) NOT NULL DEFAULT 0 AFTER view_only;
