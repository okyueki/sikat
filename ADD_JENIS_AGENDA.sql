-- SQL Script untuk menambahkan kolom jenis_agenda ke tabel agendas
-- Jalankan script ini langsung di database jika migration error

-- Cek dulu apakah kolom sudah ada
SET @dbname = DATABASE();
SET @tablename = "agendas";
SET @columnname = "jenis_agenda";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists.'",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " ENUM('umum', 'kajian', 'kegiatan_rs', 'iht') DEFAULT 'umum' AFTER `judul`")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
