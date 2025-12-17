-- ============================================================================
-- Migração: Adicionar colunas de ID de série
-- ============================================================================
-- Data: 20/11/2025
-- Versão: 1.0.32
-- Descrição: Adiciona colunas para armazenar os GUIDs das séries escolares
--            selecionadas pelo responsável e aluno
-- ============================================================================

-- Verifica se as colunas já existem antes de adicionar
-- (Evita erros em caso de execução múltipla)

SET @dbname = DATABASE();
SET @tablename = 'wp_positivo_agendamentos';
SET @columnname1 = 'responsavel_serie_id';
SET @columnname2 = 'aluno_serie_id';

-- Adiciona coluna responsavel_serie_id se não existir
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname1
  ) > 0,
  'SELECT ''Coluna responsavel_serie_id já existe'' AS msg;',
  'ALTER TABLE wp_positivo_agendamentos ADD COLUMN responsavel_serie_id varchar(255) DEFAULT NULL COMMENT ''GUID da série de interesse do responsável'' AFTER responsavel_serie_interesse;'
));

PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- Adiciona coluna aluno_serie_id se não existir
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      TABLE_SCHEMA = @dbname
      AND TABLE_NAME = @tablename
      AND COLUMN_NAME = @columnname2
  ) > 0,
  'SELECT ''Coluna aluno_serie_id já existe'' AS msg;',
  'ALTER TABLE wp_positivo_agendamentos ADD COLUMN aluno_serie_id varchar(255) DEFAULT NULL COMMENT ''GUID da série de interesse do aluno'' AFTER aluno_serie_interesse;'
));

PREPARE alterStatement FROM @preparedStatement;
EXECUTE alterStatement;
DEALLOCATE PREPARE alterStatement;

-- ============================================================================
-- Verificação pós-migração
-- ============================================================================

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM 
    INFORMATION_SCHEMA.COLUMNS
WHERE 
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'wp_positivo_agendamentos'
    AND COLUMN_NAME IN ('responsavel_serie_id', 'aluno_serie_id');

-- ============================================================================
-- Notas de Migração
-- ============================================================================
-- 
-- 1. Este script é idempotente (pode ser executado múltiplas vezes)
-- 2. As colunas são criadas com DEFAULT NULL (não afeta registros existentes)
-- 3. Registros antigos continuarão funcionando com o mapeamento legado
-- 4. Novos registros utilizarão os IDs dinâmicos das séries
-- 
-- ============================================================================
