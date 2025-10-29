

SET FOREIGN_KEY_CHECKS=0;


DROP TABLE IF EXISTS pedido_produtos;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS clientes;

CREATE TABLE clientes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  cpf VARCHAR(14) NOT NULL UNIQUE,
  email VARCHAR(255) NOT NULL,
  status ENUM('ativo','inativo','excluido') DEFAULT 'ativo',
  data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
  data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_cpf (cpf),
  INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(255) NOT NULL,
  preco DECIMAL(10,2) NOT NULL,
  estoque INT NOT NULL,
  descricao TEXT,
  status ENUM('ativo','inativo','excluido') DEFAULT 'ativo',
  data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE pedidos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
  status ENUM('ativo','inativo','excluido') DEFAULT 'ativo',
  data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cliente (cliente_id),
  INDEX idx_status (status),
  INDEX idx_data (data_pedido),
  CONSTRAINT fk_pedido_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE pedido_produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  INDEX idx_pedido (pedido_id),
  INDEX idx_produto (produto_id),
  CONSTRAINT fk_pedido_produtos_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pedido_produtos_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO produtos (nome, preco, estoque, descricao, status) VALUES
('Caneta Azul', 2.50, 100, 'Caneta esferográfica azul', 'ativo'),
('Caderno 100 folhas', 12.00, 50, 'Caderno pautado 100 folhas', 'ativo'),
('Mochila Escolar', 89.90, 20, 'Mochila resistente', 'ativo'),
('Lápis HB', 1.20, 200, 'Lápis grafite HB', 'ativo'),
('Borracha Branca', 1.50, 150, 'Borracha macia', 'ativo'),
('Marcador de Texto', 5.00, 60, 'Marcador amarelo fluorescente', 'ativo'),
('Agenda 2025', 25.00, 30, 'Agenda anual 2025', 'ativo'),
('Apontador', 3.00, 100, 'Apontador de metal', 'ativo'),
('Régua 30cm', 4.50, 80, 'Régua plástica 30cm', 'ativo'),
('Calculadora Científica', 120.00, 15, 'Calculadora científica básica', 'ativo');

INSERT INTO clientes (nome, cpf, email, status) VALUES
('João Silva', '123.456.789-00', 'joao.silva@email.com', 'ativo'),
('Maria Santos', '987.654.321-00', 'maria.santos@email.com', 'ativo'),
('Pedro Oliveira', '456.789.123-00', 'pedro.oliveira@email.com', 'ativo');

-- Reabilitar verificações
SET UNIQUE_CHECKS=1;
SET FOREIGN_KEY_CHECKS=1;
