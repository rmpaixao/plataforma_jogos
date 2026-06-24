-- ============================================================
-- MIGRAÇÃO: Atualizar banco de dados para nova estrutura
-- ============================================================
-- 
-- Esta migração transforma a estrutura antiga (com suporte
-- a WebSocket) para a nova estrutura (Apache-only / HTTP Polling)
-- para rodar em hospedagem compartilhada (Hostinger).
--
-- Principais mudanças:
--   salas:     status VARCHAR(20) com novos valores
--   jogadores: remove conn_id, pos_x, pos_y, is_online
--              adiciona jogador_id, score_quiz, score_acao
--              remove FK para salas (agora é independente)
--   perguntas: mantida igual
--   respostas: mantida igual
--
-- Como executar:
--   Opção 1: mysql -u root < database/migrar_para_nova_estrutura.sql
--   Opção 2: Cole no phpMyAdmin > Aba SQL > Executar
-- ============================================================

USE `plataforma_jogos`;

-- -----------------------------------------------------------
-- PASSO 1: Backup dos dados existentes (se houver)
-- -----------------------------------------------------------
-- Cria tabelas temporárias para preservar dados que podem ser migrados
DROP TABLE IF EXISTS `_backup_salas`;
DROP TABLE IF EXISTS `_backup_jogadores`;
DROP TABLE IF EXISTS `_backup_perguntas`;
DROP TABLE IF EXISTS `_backup_respostas`;

CREATE TABLE `_backup_salas` AS SELECT * FROM `salas`;
CREATE TABLE `_backup_jogadores` AS SELECT * FROM `jogadores`;
CREATE TABLE `_backup_perguntas` AS SELECT * FROM `perguntas`;
CREATE TABLE `_backup_respostas` AS SELECT * FROM `respostas`;

-- -----------------------------------------------------------
-- PASSO 2: Remover tabelas antigas (respeitando FKs)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `respostas`;
DROP TABLE IF EXISTS `jogadores`;
DROP TABLE IF EXISTS `perguntas`;
DROP TABLE IF EXISTS `salas`;

-- -----------------------------------------------------------
-- PASSO 3: Criar tabelas com a NOVA estrutura
-- -----------------------------------------------------------

-- Tabela: salas
CREATE TABLE `salas` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`      VARCHAR(10)  NOT NULL UNIQUE COMMENT 'Código da sala (ex: ABC123)',
    `host_name`    VARCHAR(20)  NOT NULL COMMENT 'Nome do professor/criador',
    `status`       VARCHAR(20)  NOT NULL DEFAULT 'aguardando' COMMENT 'aguardando, pergunta_X, minigame, fim',
    `max_players`  TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `criada_em`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `atualizada_em` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room_id` (`room_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: jogadores
CREATE TABLE `jogadores` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`      VARCHAR(10)  NOT NULL COMMENT 'Código da sala',
    `jogador_id`   VARCHAR(20)  NOT NULL COMMENT 'Identificador único do aluno (ex: jogador_42)',
    `nome`         VARCHAR(20)  NOT NULL COMMENT 'Nome do aluno',
    `score_quiz`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pontos do quiz',
    `score_acao`   INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Pontos do mini-game de ação',
    `entrou_em`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ultima_acao`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`),
    INDEX `idx_jogador` (`jogador_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: perguntas
CREATE TABLE `perguntas` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `room_id`         VARCHAR(10)  DEFAULT NULL COMMENT 'NULL = pergunta global',
    `pergunta`        VARCHAR(255) NOT NULL COMMENT 'Texto da pergunta',
    `opcao_a`         VARCHAR(100) NOT NULL,
    `opcao_b`         VARCHAR(100) NOT NULL,
    `opcao_c`         VARCHAR(100) NOT NULL,
    `opcao_d`         VARCHAR(100) NOT NULL,
    `resposta_certa`  CHAR(1)     NOT NULL COMMENT 'A, B, C ou D',
    `criado_em`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: respostas
CREATE TABLE `respostas` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `jogador_id`    VARCHAR(20) NOT NULL COMMENT 'ID do aluno',
    `room_id`       VARCHAR(10) NOT NULL,
    `pergunta_id`   INT UNSIGNED NOT NULL,
    `resposta`      CHAR(1)    NOT NULL COMMENT 'A, B, C ou D',
    `acertou`       TINYINT(1) NOT NULL DEFAULT 0,
    `respondido_em` DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_room` (`room_id`, `pergunta_id`),
    FOREIGN KEY (`pergunta_id`) REFERENCES `perguntas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- PASSO 4: Restaurar dados do backup (migrando campos)
-- -----------------------------------------------------------

-- 4a. Migrar salas: mantém room_id, host_name
--     status: converte 'waiting' -> 'aguardando', 'playing' -> 'pergunta_1', 'finished' -> 'fim'
INSERT INTO `salas` (`room_id`, `host_name`, `status`)
SELECT
    `room_id`,
    `host_name`,
    CASE `status`
        WHEN 'waiting'  THEN 'aguardando'
        WHEN 'playing'  THEN 'pergunta_1'
        WHEN 'finished' THEN 'fim'
        ELSE 'aguardando'
    END
FROM `_backup_salas`;

-- 4b. Migrar jogadores: conn_id vira parte do jogador_id
--     score vira score_quiz, score_acao = 0
--     remove pos_x, pos_y, is_online
INSERT INTO `jogadores` (`room_id`, `jogador_id`, `nome`, `score_quiz`, `score_acao`)
SELECT
    `room_id`,
    CONCAT('jogador_antigo_', `conn_id`),
    `nome`,
    `score` AS `score_quiz`,
    0       AS `score_acao`
FROM `_backup_jogadores`;

-- 4c. Migrar perguntas: mantém tudo igual
INSERT INTO `perguntas` (`id`, `room_id`, `pergunta`, `opcao_a`, `opcao_b`, `opcao_c`, `opcao_d`, `resposta_certa`)
SELECT `id`, `room_id`, `pergunta`, `opcao_a`, `opcao_b`, `opcao_c`, `opcao_d`, `resposta_certa`
FROM `_backup_perguntas`;

-- 4d. Migrar respostas: mantém tudo igual
INSERT INTO `respostas` (`jogador_id`, `room_id`, `pergunta_id`, `resposta`, `acertou`)
SELECT
    CONCAT('jogador_antigo_', `jogador_id`),
    `room_id`,
    `pergunta_id`,
    `resposta`,
    `acertou`
FROM `_backup_respostas`;

-- -----------------------------------------------------------
-- PASSO 5: Inserir perguntas seed (se não existirem)
-- -----------------------------------------------------------
INSERT IGNORE INTO `perguntas` (`pergunta`, `opcao_a`, `opcao_b`, `opcao_c`, `opcao_d`, `resposta_certa`) VALUES
('Qual é a capital do Brasil?',           'Rio de Janeiro', 'Brasília',      'São Paulo',  'Salvador',    'B'),
('Quanto é 7 × 8?',                       '48',             '56',            '64',         '72',          'B'),
('Qual planeta é conhecido como Vermelho?','Vênus',         'Marte',         'Júpiter',    'Saturno',     'B'),
('Quem escreveu "Dom Casmurro"?',          'Machado de Assis', 'José de Alencar', 'Carlos Drummond', 'Clarice Lispector', 'A'),
('Qual o maior oceano do mundo?',          'Atlântico',     'Pacífico',      'Índico',     'Ártico',      'B'),
('Em que ano o homem pisou na Lua?',       '1967',          '1968',          '1969',       '1970',        'C'),
('Qual a raiz quadrada de 144?',           '10',            '11',            '12',         '13',          'C'),
('Quantos estados tem o Brasil?',          '24',            '25',            '26',         '27',          'C');

-- -----------------------------------------------------------
-- PASSO 6: Remover tabelas de backup (opcional)
-- -----------------------------------------------------------
-- Descomente a linha abaixo APÓS confirmar que a migração foi bem-sucedida:
-- DROP TABLE IF EXISTS `_backup_salas`, `_backup_jogadores`, `_backup_perguntas`, `_backup_respostas`;

-- -----------------------------------------------------------
-- FIM DA MIGRAÇÃO
-- -----------------------------------------------------------
-- 
-- Para verificar se tudo deu certo:
--   SELECT COUNT(*) FROM salas;      -- Deve bater com o backup
--   SELECT COUNT(*) FROM jogadores;  -- Deve bater com o backup
--   SELECT COUNT(*) FROM perguntas;  -- Deve bater com o backup
--   SELECT COUNT(*) FROM respostas;  -- Deve bater com o backup
--
-- Após confirmar, execute:
--   DROP TABLE IF EXISTS `_backup_salas`, `_backup_jogadores`, `_backup_perguntas`, `_backup_respostas`;
-- ============================================================