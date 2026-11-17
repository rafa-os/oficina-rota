SET NAMES utf8mb4;
SET time_zone = '-03:00';

CREATE DATABASE IF NOT EXISTS oficina_rota CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE oficina_rota;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('administrador','mecanico') NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB;

CREATE TABLE veiculos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    placa VARCHAR(7) NOT NULL UNIQUE,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(80) NOT NULL,
    ano SMALLINT UNSIGNED NOT NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE ordens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    veiculo_id INT UNSIGNED NOT NULL,
    mecanico_id INT UNSIGNED NOT NULL,
    descricao VARCHAR(1000) NOT NULL,
    observacoes TEXT NOT NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME NOT NULL,
    situacao ENUM('agendada','em_andamento','concluida','cancelada') NOT NULL DEFAULT 'agendada',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (mecanico_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX agenda_mecanico (mecanico_id, inicio, fim),
    INDEX data_atendimento (inicio),
    CONSTRAINT chk_ordens_periodo CHECK (fim > inicio)
) ENGINE=InnoDB;

CREATE TABLE controle_agenda (
    id INT PRIMARY KEY
) ENGINE=InnoDB;
INSERT INTO controle_agenda VALUES (1);

CREATE TABLE operacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ordem_numero INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    acao ENUM('cadastro','atualizacao','exclusao') NOT NULL,
    antes LONGTEXT NULL,
    depois LONGTEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX historico_ordem (ordem_numero)
) ENGINE=InnoDB;

INSERT INTO usuarios (id, nome, email, senha, perfil) VALUES
(1, 'Rafael Oliveira de Souza', 'admin@rota.test', '$2y$10$bZnlqr5H0HQRHYbTvNeY.uFqnBKFl.LTGVEPPNPLRVpYj7WplL06S', 'administrador'),
(2, 'Carlos Mendes', 'mecanico@rota.test', '$2y$10$bZnlqr5H0HQRHYbTvNeY.uFqnBKFl.LTGVEPPNPLRVpYj7WplL06S', 'mecanico'),
(3, 'Ana Rocha', 'mecanico2@rota.test', '$2y$10$bZnlqr5H0HQRHYbTvNeY.uFqnBKFl.LTGVEPPNPLRVpYj7WplL06S', 'mecanico');

INSERT INTO clientes (nome, telefone, email) VALUES
('Mariana Costa', '(11) 98888-1101', 'mariana@example.test'),
('Pedro Almeida', '(11) 97777-2202', 'pedro@example.test'),
('Juliana Ribeiro', '(11) 96666-3303', 'juliana@example.test'),
('Lucas Martins', '(11) 95555-4404', 'lucas@example.test'),
('Beatriz Lima', '(11) 94444-5505', 'beatriz@example.test'),
('André Ferreira', '(11) 93333-6606', 'andre@example.test');

INSERT INTO veiculos (cliente_id, placa, marca, modelo, ano) VALUES
(1, 'ABC1D23', 'Chevrolet', 'Onix', 2021),
(2, 'DEF4G56', 'Volkswagen', 'Gol', 2019),
(3, 'HIJ7K89', 'Fiat', 'Argo', 2022),
(4, 'LMN1P23', 'Hyundai', 'HB20', 2020),
(5, 'QRS4T56', 'Renault', 'Sandero', 2018),
(6, 'UVW7X89', 'Toyota', 'Corolla', 2023);

INSERT INTO ordens (veiculo_id, mecanico_id, descricao, observacoes, inicio, fim, situacao) VALUES
(1, 2, 'Troca de óleo e filtro do motor', '', '2026-11-17 08:00:00', '2026-11-17 09:00:00', 'agendada'),
(2, 2, 'Revisão do sistema de freios', '', '2026-11-17 09:00:00', '2026-11-17 10:30:00', 'agendada'),
(3, 3, 'Alinhamento e balanceamento', '', '2026-11-17 08:30:00', '2026-11-17 09:30:00', 'agendada'),
(4, 3, 'Inspeção da suspensão dianteira', '', '2026-11-17 10:00:00', '2026-11-17 11:00:00', 'agendada'),
(5, 2, 'Substituição do filtro de ar', 'Filtro substituído e veículo liberado.', '2026-11-16 13:00:00', '2026-11-16 14:00:00', 'concluida'),
(6, 3, 'Revisão preventiva dos 20 mil km', '', '2026-11-16 14:00:00', '2026-11-16 16:00:00', 'em_andamento');

INSERT INTO operacoes (ordem_numero, usuario_id, acao, depois)
SELECT id, 1, 'cadastro', JSON_OBJECT('descricao', descricao, 'inicio', inicio, 'fim', fim, 'situacao', situacao, 'origem', 'demonstracao') FROM ordens;
