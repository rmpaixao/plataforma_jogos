-- ============================================================
-- Schema do Banco de Dados - Plataforma de Jogos Multiplayer
-- ============================================================
-- Execute este script para criar o banco e as tabelas
-- 
-- Uso: mysql -u root < database/schema.sql
--   Ou: Cole no phpMyAdmin > SQL
-- ============================================================

CREATE DATABASE IF NOT EXISTS `plataforma_jogos`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `plataforma_jogos`;

-- -----------------------------------------------------------
-- Tabela: salas
-- Armazena as salas de jogo criadas
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `salas` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`    VARCHAR(10)  NOT NULL UNIQUE COMMENT 'Código da sala (ex: ABC123)',
    `host_name`  VARCHAR(20)  NOT NULL COMMENT 'Nome do criador da sala',
    `status`     ENUM('waiting', 'playing', 'finished') NOT NULL DEFAULT 'waiting',
    `max_players` TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Tabela: jogadores
-- Registra os jogadores que entram nas salas
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jogadores` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`    VARCHAR(10)  NOT NULL COMMENT 'Código da sala',
    `conn_id`    INT UNSIGNED NOT NULL COMMENT 'ID da conexão WebSocket',
    `nome`       VARCHAR(20)  NOT NULL COMMENT 'Nome do jogador',
    `score`      INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pontuação acumulada',
    `pos_x`      FLOAT        NOT NULL DEFAULT 400 COMMENT 'Posição X na arena',
    `pos_y`      FLOAT        NOT NULL DEFAULT 300 COMMENT 'Posição Y na arena',
    `is_online`  TINYINT(1)   NOT NULL DEFAULT 1,
    `entrou_em`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ultima_acao` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_conn_id` (`conn_id`),
    FOREIGN KEY (`room_id`) REFERENCES `salas`(`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Tabela: perguntas
-- Banco de perguntas para o modo quiz (Kahoot-style)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `perguntas` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`       VARCHAR(10)  DEFAULT NULL COMMENT 'NULL = pergunta global, preenchido = por sala',
    `pergunta`      VARCHAR(255) NOT NULL COMMENT 'Texto da pergunta',
    `opcao_a`       VARCHAR(100) NOT NULL,
    `opcao_b`       VARCHAR(100) NOT NULL,
    `opcao_c`       VARCHAR(100) NOT NULL,
    `opcao_d`       VARCHAR(100) NOT NULL,
    `resposta_certa` CHAR(1)     NOT NULL COMMENT 'A, B, C ou D',
    `criado_em`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`)
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Tabela: respostas
-- Histórico de respostas dos jogadores no quiz
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `respostas` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `jogador_id`  VARCHAR(20)  NOT NULL COMMENT 'Nome do jogador',
    `room_id`     VARCHAR(10)  NOT NULL,
    `pergunta_id` INT UNSIGNED NOT NULL,
    `resposta`    CHAR(1)     NOT NULL COMMENT 'A, B, C ou D',
    `acertou`     TINYINT(1)  NOT NULL DEFAULT 0,
    `respondido_em` DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`, `pergunta_id`),
    FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------
-- Seed: Perguntas de exemplo
-- -----------------------------------------------------------
INSERT INTO `perguntas` (`pergunta`, `opcao_a`, `opcao_b`, `opcao_c`, `opcao_d`, `resposta_certa`) VALUES
('Qual é a capital do Brasil?',          'Rio de Janeiro', 'Brasília',     'São Paulo',  'Salvador',   'B'),
('Quanto é 7 × 8?',                      '48',             '56',           '64',         '72',         'B'),
('Qual planeta é conhecido como Vermelho?', 'Vênus',       'Marte',        'Júpiter',    'Saturno',    'B'),
('Quem escreveu "Dom Casmurro"?',         'Machado de Assis', 'José de Alencar', 'Carlos Drummond', 'Clarice Lispector', 'A'),
('Qual o maior oceano do mundo?',         'Atlântico',     'Pacífico',     'Índico',     'Ártico',     'B'),
('Em que ano o homem pisou na Lua?',      '1967',          '1968',         '1969',       '1970',       'C'),
('Qual a raiz quadrada de 144?',          '10',            '11',           '12',         '13',         'C'),
('Quantos estados tem o Brasil?',         '24',            '25',           '26',         '27',         'C');