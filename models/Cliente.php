<?php

class Cliente {
    private $conn;
    private $table_name = "clientes";
    
    public $id;
    public $nome;
    public $cpf;
    public $email;
    public $status;
    public $data_criacao;
    public $data_alteracao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function listar($page = 1, $per_page = 10, $search = '') {
        $offset = ($page - 1) * $per_page;
        
        $search_param = !empty($search) ? "%{$search}%" : '';
        
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE status != 'excluido'";
        
        if (!empty($search)) {
            $query .= " AND (nome LIKE :search1 OR email LIKE :search2 OR cpf LIKE :search3)";
        }
        
        $query .= " ORDER BY id DESC LIMIT :offset, :per_page";
        
        $stmt = $this->conn->prepare($query);
        
        if (!empty($search)) {
            $stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
            $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
            $stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
        
        $stmt->execute();
        
        $clientes = $stmt->fetchAll();
        
        $count_query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                        WHERE status != 'excluido'";
        
        if (!empty($search)) {
            $count_query .= " AND (nome LIKE :search1 OR email LIKE :search2 OR cpf LIKE :search3)";
        }
        
        $count_stmt = $this->conn->prepare($count_query);
        
        if (!empty($search)) {
            $count_stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
            $count_stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
            $count_stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
        }
        
        $count_stmt->execute();
        $total = $count_stmt->fetch()['total'];
        
        return [
            'data' => $clientes,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'current_page' => $page
        ];
    }
    
    public function buscarPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE id = :id AND status != 'excluido'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function listarAtivos() {
        $query = "SELECT id, nome FROM " . $this->table_name . " 
                  WHERE status = 'ativo' 
                  ORDER BY nome ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function criar() {
        if (!$this->validar()) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (nome, cpf, email, status) 
                  VALUES (:nome, :cpf, :email, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cpf = $this->formatarCPF($this->cpf);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->status = htmlspecialchars(strip_tags($this->status));
        
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':cpf', $this->cpf);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':status', $this->status);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    public function atualizar() {
        if (!$this->validar()) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET nome = :nome, 
                      cpf = :cpf, 
                      email = :email, 
                      status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->cpf = $this->formatarCPF($this->cpf);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(':nome', $this->nome);
        $stmt->bindParam(':cpf', $this->cpf);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    public function excluir($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'excluido' 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    private function validar() {
        if (empty($this->nome) || strlen($this->nome) < 3) {
            return false;
        }
        
        if (!$this->validarCPF($this->cpf)) {
            return false;
        }
        
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        if (!in_array($this->status, ['ativo', 'inativo'])) {
            return false;
        }
        
        if ($this->cpfExiste()) {
            return false;
        }
        
        return true;
    }
    
    private function validarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
    
    private function formatarCPF($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    
    private function cpfExiste() {
        $cpf_formatado = $this->formatarCPF($this->cpf);
        
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE cpf = :cpf AND status != 'excluido'";
        
        if (!empty($this->id)) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cpf', $cpf_formatado);
        
        if (!empty($this->id)) {
            $stmt->bindParam(':id', $this->id);
        }
        
        $stmt->execute();
        
        
        return $stmt->rowCount() > 0;
    }
}