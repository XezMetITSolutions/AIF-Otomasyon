-- Add gorusme_notlari column to toplanti_gundem table
SET @dbname = DATABASE();
SET @tablename = "toplanti_gundem";

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (table_name = @tablename)
   AND (table_schema = @dbname)
   AND (column_name = 'gorusme_notlari')) > 0,
  "SELECT 1",
  "ALTER TABLE toplanti_gundem ADD COLUMN gorusme_notlari TEXT NULL AFTER aciklama"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
