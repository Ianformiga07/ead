-- ============================================================
--  PLATAFORMA EAD - Script SQL Completo
--  Compatível com MySQL 5.7+ / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS plataforma_ead
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE plataforma_ead;

-- -----------------------------------------------------------
-- USUÁRIOS (admin e aluno)
-- -----------------------------------------------------------
CREATE TABLE usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    senha       VARCHAR(255) NOT NULL,
    cpf         VARCHAR(14)  DEFAULT NULL,
    telefone    VARCHAR(20)  DEFAULT NULL,
    perfil      ENUM('admin','aluno') NOT NULL DEFAULT 'aluno',
    status      TINYINT(1) NOT NULL DEFAULT 1,
    foto        VARCHAR(255) DEFAULT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- CURSOS
-- -----------------------------------------------------------
CREATE TABLE cursos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(200) NOT NULL,
    descricao     TEXT,
    tipo          ENUM('ead','presencial') NOT NULL DEFAULT 'ead',
    carga_horaria INT UNSIGNED NOT NULL DEFAULT 0,
    instrutores   VARCHAR(500) DEFAULT NULL,
    status        TINYINT(1) NOT NULL DEFAULT 1,
    tem_avaliacao TINYINT(1) NOT NULL DEFAULT 0,
    nota_minima   DECIMAL(5,2) DEFAULT 60.00,
    imagem        VARCHAR(255) DEFAULT NULL,
    conteudo_programatico TEXT,
    criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- AULAS
-- -----------------------------------------------------------
CREATE TABLE aulas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id   INT UNSIGNED NOT NULL,
    titulo     VARCHAR(200) NOT NULL,
    descricao  TEXT,
    url_video  VARCHAR(500) DEFAULT NULL,
    ordem      INT UNSIGNED NOT NULL DEFAULT 1,
    status     TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- MATERIAIS DIDÁTICOS
-- -----------------------------------------------------------
CREATE TABLE materiais (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id   INT UNSIGNED NOT NULL,
    titulo     VARCHAR(200) NOT NULL,
    arquivo    VARCHAR(255) NOT NULL,
    tipo       VARCHAR(50)  DEFAULT NULL,
    tamanho    INT UNSIGNED DEFAULT 0,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- MATRÍCULAS
-- -----------------------------------------------------------
CREATE TABLE matriculas (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id      INT UNSIGNED NOT NULL,
    curso_id      INT UNSIGNED NOT NULL,
    status        ENUM('ativa','concluida','cancelada') NOT NULL DEFAULT 'ativa',
    progresso     INT UNSIGNED NOT NULL DEFAULT 0,
    matriculado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    concluido_em  TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_matricula (aluno_id, curso_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- PROGRESSO DE AULAS
-- -----------------------------------------------------------
CREATE TABLE progresso_aulas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id   INT UNSIGNED NOT NULL,
    aula_id    INT UNSIGNED NOT NULL,
    assistido  TINYINT(1) NOT NULL DEFAULT 0,
    assistido_em TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uk_prog (aluno_id, aula_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- AVALIAÇÕES
-- -----------------------------------------------------------
CREATE TABLE avaliacoes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id   INT UNSIGNED NOT NULL UNIQUE,
    titulo     VARCHAR(200) NOT NULL DEFAULT 'Avaliação Final',
    descricao  TEXT,
    tentativas INT UNSIGNED NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- PERGUNTAS DA AVALIAÇÃO
-- -----------------------------------------------------------
CREATE TABLE perguntas (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avaliacao_id INT UNSIGNED NOT NULL,
    enunciado    TEXT NOT NULL,
    pontos       DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    ordem        INT UNSIGNED NOT NULL DEFAULT 1,
    FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- ALTERNATIVAS
-- -----------------------------------------------------------
CREATE TABLE alternativas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pergunta_id INT UNSIGNED NOT NULL,
    texto       TEXT NOT NULL,
    correta     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- TENTATIVAS DE AVALIAÇÃO
-- -----------------------------------------------------------
CREATE TABLE tentativas_avaliacao (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id     INT UNSIGNED NOT NULL,
    avaliacao_id INT UNSIGNED NOT NULL,
    nota         DECIMAL(5,2) DEFAULT NULL,
    aprovado     TINYINT(1) DEFAULT 0,
    realizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (avaliacao_id) REFERENCES avaliacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- RESPOSTAS DO ALUNO
-- -----------------------------------------------------------
CREATE TABLE respostas_aluno (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tentativa_id INT UNSIGNED NOT NULL,
    pergunta_id  INT UNSIGNED NOT NULL,
    alternativa_id INT UNSIGNED NOT NULL,
    correta      TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (tentativa_id) REFERENCES tentativas_avaliacao(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas(id) ON DELETE CASCADE,
    FOREIGN KEY (alternativa_id) REFERENCES alternativas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- CERTIFICADOS
-- -----------------------------------------------------------
CREATE TABLE certificados (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT UNSIGNED NOT NULL,
    curso_id        INT UNSIGNED NOT NULL,
    codigo          VARCHAR(64) NOT NULL UNIQUE,
    arquivo         VARCHAR(255) DEFAULT NULL,
    emitido_em      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cert (aluno_id, curso_id),
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- MODELOS DE CERTIFICADO
-- -----------------------------------------------------------
CREATE TABLE modelos_certificado (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id INT UNSIGNED NOT NULL UNIQUE,
    frente   VARCHAR(255) DEFAULT NULL,
    verso    VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- LOGS DO SISTEMA
-- -----------------------------------------------------------
CREATE TABLE logs (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED DEFAULT NULL,
    acao       VARCHAR(200) NOT NULL,
    detalhes   TEXT,
    ip         VARCHAR(45) DEFAULT NULL,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- DADOS INICIAIS
-- -----------------------------------------------------------
-- Senha: Admin@123 (bcrypt)
INSERT INTO usuarios (nome, email, senha, perfil, status) VALUES
('Administrador', 'admin@ead.com',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 1);

-- Nota: senha padrão 'password' — altere após o primeiro login!

-- ─────────────────────────────────────────────────────────────
-- MIGRAÇÃO v2: Pasta de vídeos para aulas
-- Execute no servidor: criar pasta public/uploads/videos/
-- As aulas com upload de vídeo salvam url_video como 'local://nome_arquivo.mp4'
-- ─────────────────────────────────────────────────────────────

-- ─────────────────────────────────────────────────────────────
-- MIGRAÇÃO: Coluna verso_conteudo em modelos_certificado
-- Execute no banco de dados:
ALTER TABLE modelos_certificado
  ADD COLUMN IF NOT EXISTS verso_conteudo LONGTEXT DEFAULT NULL COMMENT 'HTML do verso do certificado (CKEditor)',
  ADD COLUMN IF NOT EXISTS texto_frente TEXT DEFAULT NULL COMMENT 'Texto customizado da frente do certificado';
-- ─────────────────────────────────────────────────────────────
