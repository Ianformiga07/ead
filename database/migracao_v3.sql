-- ============================================================
--  MIGRAÇÃO v3 — CRMV EAD
--  1. Novo perfil 'operador' na tabela usuarios
--  2. Campos de endereço e dados completos do aluno/veterinário
--  3. Campos para certificado: nome_cert, instrutor, conteudo_programatico
-- ============================================================

USE plataforma_ead;

-- -----------------------------------------------------------
-- 1. ALTERAR ENUM perfil: adicionar 'operador'
-- -----------------------------------------------------------
ALTER TABLE usuarios
  MODIFY COLUMN perfil ENUM('admin','operador','aluno') NOT NULL DEFAULT 'aluno';

-- -----------------------------------------------------------
-- 2. CAMPOS ADICIONAIS DO USUÁRIO (veterinário)
-- -----------------------------------------------------------
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS crmv          VARCHAR(30)  DEFAULT NULL COMMENT 'Número de inscrição CRMV',
  ADD COLUMN IF NOT EXISTS data_nascimento DATE        DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS sexo          ENUM('M','F','O') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cep           VARCHAR(10)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS logradouro    VARCHAR(200) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS numero        VARCHAR(20)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS complemento   VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS bairro        VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS cidade        VARCHAR(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS estado        CHAR(2)      DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS especialidade VARCHAR(200) DEFAULT NULL COMMENT 'Especialidade/área de atuação';

-- -----------------------------------------------------------
-- 3. CAMPOS DO CERTIFICADO NO MODELO
-- -----------------------------------------------------------
-- Já migrado em v2. Garantir que existam:
ALTER TABLE modelos_certificado
  ADD COLUMN IF NOT EXISTS verso_conteudo   LONGTEXT     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS texto_frente     TEXT         DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS nome_cert        VARCHAR(300) DEFAULT NULL COMMENT 'Nome exibido no certificado (sobrescreve nome do curso)',
  ADD COLUMN IF NOT EXISTS instrutor        VARCHAR(500) DEFAULT NULL COMMENT 'Instrutor(es) exibidos no certificado',
  ADD COLUMN IF NOT EXISTS conteudo_prog    LONGTEXT     DEFAULT NULL COMMENT 'Conteúdo programático do certificado',
  ADD COLUMN IF NOT EXISTS ativar_verso     TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Liga/desliga verso do certificado';

-- -----------------------------------------------------------
-- 4. REMOVER conteudo_programatico e instrutores da tabela cursos
--    (mantidos apenas no modelo_certificado a partir de agora)
--    ATENÇÃO: só execute se quiser migrar. Os dados antigos ficam.
-- -----------------------------------------------------------
-- (Opcional — deixado comentado para segurança)
-- ALTER TABLE cursos DROP COLUMN IF EXISTS conteudo_programatico;
-- ALTER TABLE cursos DROP COLUMN IF EXISTS instrutores;

-- -----------------------------------------------------------
-- FIM DA MIGRAÇÃO v3
-- -----------------------------------------------------------
