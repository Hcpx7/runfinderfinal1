-- Atualização do esquema MySQL (adicionar colunas de plano e publish_date)
-- Execute no seu banco runfinder (após já ter importado o schema anterior)

-- Adiciona campos de plano ao usuário
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS plan_type VARCHAR(32) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS plan_expires DATE DEFAULT NULL;

-- Adiciona publish_date à tabela blogs
ALTER TABLE blogs
  ADD COLUMN IF NOT EXISTS publish_date DATE DEFAULT NULL;