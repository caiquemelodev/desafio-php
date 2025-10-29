<?php

require_once '../config/database.php';
require_once '../models/Produto.php';

class ProdutoController {
    
    private $db;
    private $produto;
    
    public function __construct() {
        // Configurar headers
        $this->setHeaders();
        
        // Conectar ao banco
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
        }
        
        $this->produto = new Produto($this->db);
    }
    
    private function setHeaders() {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    private function jsonResponse($code, $data) {
        if (ob_get_length()) {
            ob_clean();
        }
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public function handleRequest() {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'listar_ativos':
                $this->listarAtivos();
                break;
            default:
                $this->jsonResponse(400, ['success' => false, 'message' => 'Ação inválida']);
        }
    }
    
    private function listarAtivos() {
        $resultado = $this->produto->listarAtivos();
        $this->jsonResponse(200, ['success' => true, 'data' => $resultado]);
    }
}

// Inicializar e executar
$controller = new ProdutoController();
$controller->handleRequest();
