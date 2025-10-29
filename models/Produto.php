<?php

class Produto {
    private $conn;
    private $table_name = "produtos";
    
    public $id;
    public $nome;
    public $preco;
    public $estoque;
    public $descricao;
    public $status;
    public $data_alteracao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function listarAtivos() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status = 'ativo' 
                  ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND status != 'excluido'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function buscarPorIds($ids) {
        if (empty($ids)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id IN ($placeholders) AND status != 'excluido'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($ids);
        
        return $stmt->fetchAll();
    }
}
