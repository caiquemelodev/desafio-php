<?php

class Pedido {
    private $conn;
    private $table_name = "pedidos";
    private $table_produtos = "pedido_produtos";
    
    public $id;
    public $cliente_id;
    public $valor_total;
    public $data_pedido;
    public $status;
    public $data_alteracao;
    public $produtos = [];
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function listar($page = 1, $per_page = 10, $cliente_id = null, $status = null) {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT p.*, c.nome as cliente_nome 
                  FROM " . $this->table_name . " p
                  INNER JOIN clientes c ON p.cliente_id = c.id
                  WHERE p.status != 'excluido'";
        
        if (!empty($cliente_id)) {
            $query .= " AND p.cliente_id = :cliente_id";
        }
        
        if (!empty($status) && $status !== 'todos') {
            $query .= " AND p.status = :status";
        }
        
        $query .= " ORDER BY p.id DESC LIMIT :offset, :per_page";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($cliente_id)) {
            $stmt->bindParam(':cliente_id', $cliente_id);
        }
        
        if (!empty($status) && $status !== 'todos') {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindParam(':per_page', $per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $pedidos = $stmt->fetchAll();
        
        // Contar total de registros
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " p
                        WHERE p.status != 'excluido'";
        
        if (!empty($cliente_id)) {
            $count_query .= " AND p.cliente_id = :cliente_id";
        }
        
        if (!empty($status) && $status !== 'todos') {
            $count_query .= " AND p.status = :status";
        }
        
        $count_stmt = $this->conn->prepare($count_query);
        
        if (!empty($cliente_id)) {
            $count_stmt->bindParam(':cliente_id', $cliente_id);
        }
        
        if (!empty($status) && $status !== 'todos') {
            $count_stmt->bindParam(':status', $status);
        }
        
        $count_stmt->execute();
        $total = $count_stmt->fetch()['total'];
        
        return [
            'data' => $pedidos,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    }
    
    public function buscarPorId($id) {
        $query = "SELECT p.*, c.nome as cliente_nome 
                  FROM " . $this->table_name . " p
                  INNER JOIN clientes c ON p.cliente_id = c.id
                  WHERE p.id = :id AND p.status != 'excluido'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $pedido = $stmt->fetch();
        
        if ($pedido) {
            // Buscar produtos do pedido
            $pedido['produtos'] = $this->buscarProdutosDoPedido($id);
        }
        
        return $pedido;
    }
    
    public function buscarProdutosDoPedido($pedido_id) {
        $query = "SELECT pp.*, prod.nome as produto_nome, prod.preco as preco_atual
                  FROM " . $this->table_produtos . " pp
                  INNER JOIN produtos prod ON pp.produto_id = prod.id
                  WHERE pp.pedido_id = :pedido_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pedido_id', $pedido_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function criar() {
        try {
            // Iniciar transação
            $this->conn->beginTransaction();
            
            // Validações
            if (empty($this->cliente_id) || empty($this->produtos)) {
                throw new Exception("Cliente e produtos são obrigatórios");
            }
            
            // Calcular valor total
            $this->valor_total = $this->calcularValorTotal();
            
            // Inserir pedido
            $query = "INSERT INTO " . $this->table_name . " 
                      (cliente_id, valor_total, status) 
                      VALUES (:cliente_id, :valor_total, :status)";
            
            $stmt = $this->conn->prepare($query);
            
            $this->status = !empty($this->status) ? $this->status : 'ativo';
            
            $stmt->bindParam(':cliente_id', $this->cliente_id);
            $stmt->bindParam(':valor_total', $this->valor_total);
            $stmt->bindParam(':status', $this->status);
            
            $stmt->execute();
            
            $pedido_id = $this->conn->lastInsertId();
            
            // Inserir produtos do pedido
            $this->inserirProdutos($pedido_id);
            
            // Confirmar transação
            $this->conn->commit();
            
            return $pedido_id;
            
        } catch (Exception $e) {
            // Reverter transação
            $this->conn->rollBack();
            return false;
        }
    }
    
    public function atualizar() {
        try {
            // Iniciar transação
            $this->conn->beginTransaction();
            
            // Validações
            if (empty($this->id) || empty($this->cliente_id) || empty($this->produtos)) {
                throw new Exception("ID, cliente e produtos são obrigatórios");
            }
            
            // Calcular valor total
            $this->valor_total = $this->calcularValorTotal();
            
            // Atualizar pedido
            $query = "UPDATE " . $this->table_name . " 
                      SET cliente_id = :cliente_id, 
                          valor_total = :valor_total, 
                          status = :status 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            
            $stmt->bindParam(':cliente_id', $this->cliente_id);
            $stmt->bindParam(':valor_total', $this->valor_total);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);
            
            $stmt->execute();
            
            // Remover produtos antigos
            $this->removerProdutos($this->id);
            
            // Inserir produtos atualizados
            $this->inserirProdutos($this->id);
            
            // Confirmar transação
            $this->conn->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Reverter transação
            $this->conn->rollBack();
            return false;
        }
    }
    
    public function excluir($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'excluido' 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    private function calcularValorTotal() {
        $total = 0;
        
        foreach ($this->produtos as $produto) {
            $total += $produto['preco_unitario'] * $produto['quantidade'];
        }
        
        return $total;
    }
    
    private function inserirProdutos($pedido_id) {
        $query = "INSERT INTO " . $this->table_produtos . " 
                  (pedido_id, produto_id, quantidade, preco_unitario, subtotal) 
                  VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($this->produtos as $produto) {
            $subtotal = $produto['preco_unitario'] * $produto['quantidade'];
            
            $stmt->bindParam(':pedido_id', $pedido_id);
            $stmt->bindParam(':produto_id', $produto['produto_id']);
            $stmt->bindParam(':quantidade', $produto['quantidade']);
            $stmt->bindParam(':preco_unitario', $produto['preco_unitario']);
            $stmt->bindParam(':subtotal', $subtotal);
            
            $stmt->execute();
        }
        
        return true;
    }
    
    private function removerProdutos($pedido_id) {
        $query = "DELETE FROM " . $this->table_produtos . " 
                  WHERE pedido_id = :pedido_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pedido_id', $pedido_id);
        
        return $stmt->execute();
    }
}
