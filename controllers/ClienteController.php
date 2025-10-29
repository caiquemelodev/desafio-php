<?php

require_once '../config/database.php';
require_once '../models/Cliente.php';

class ClienteController {
    
    private $db;
    private $cliente;
    
    public function __construct() {
        // Configurar headers
        $this->setHeaders();
        
        // Conectar ao banco
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro de conexão com o banco de dados']);
        }
        
        $this->cliente = new Cliente($this->db);
    }
    
    private function setHeaders() {
        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        
        // Headers CORS
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
        
        // Headers JSON
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Tratar requisições OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
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
        $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
        
        switch ($action) {
            case 'listar':
                $this->listar();
                break;
            case 'buscar':
                $this->buscar();
                break;
            case 'criar':
                $this->criar();
                break;
            case 'atualizar':
                $this->atualizar();
                break;
            case 'excluir':
                $this->excluir();
                break;
            case 'listar_ativos':
                $this->listarAtivos();
                break;
            default:
                $this->jsonResponse(400, ['success' => false, 'message' => 'Ação inválida', 'action_recebida' => $action]);
        }
    }
    
    private function listar() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        
        $resultado = $this->cliente->listar($page, $per_page, $search);
        
        $this->jsonResponse(200, [
            'success' => true,
            'data' => $resultado['data'],
            'total' => $resultado['total'],
            'total_pages' => $resultado['total_pages'],
            'current_page' => $resultado['current_page']
        ]);
    }
    
    private function buscar() {
        try {
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            
            if (empty($id)) {
                $this->jsonResponse(400, ['success' => false, 'message' => 'ID não informado']);
            }
            
            $resultado = $this->cliente->buscarPorId($id);
            
            if ($resultado) {
                $this->jsonResponse(200, ['success' => true, 'data' => $resultado]);
            } else {
                $this->jsonResponse(404, ['success' => false, 'message' => 'Cliente não encontrado']);
            }
        } catch (Exception $e) {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro interno ao buscar cliente']);
        }
    }
    
    private function listarAtivos() {
        $resultado = $this->cliente->listarAtivos();
        $this->jsonResponse(200, ['success' => true, 'data' => $resultado]);
    }
    
    private function criar() {
        if (empty($_POST['nome']) || empty($_POST['cpf']) || empty($_POST['email']) || empty($_POST['status'])) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        }
        
        $this->cliente->nome = $_POST['nome'];
        $this->cliente->cpf = $_POST['cpf'];
        $this->cliente->email = $_POST['email'];
        $this->cliente->status = $_POST['status'];
        
        $resultado = $this->cliente->criar();
        
        if ($resultado) {
            $this->jsonResponse(201, ['success' => true, 'message' => 'Cliente criado com sucesso', 'id' => $resultado]);
        } else {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Erro ao criar cliente. Verifique se o CPF já está cadastrado.']);
        }
    }
    
    private function atualizar() {
        if (empty($_POST['id']) || empty($_POST['nome']) || empty($_POST['cpf']) || empty($_POST['email']) || empty($_POST['status'])) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        }
        
        $this->cliente->id = $_POST['id'];
        $this->cliente->nome = $_POST['nome'];
        $this->cliente->cpf = $_POST['cpf'];
        $this->cliente->email = $_POST['email'];
        $this->cliente->status = $_POST['status'];
        
        $resultado = $this->cliente->atualizar();
        
        if ($resultado) {
            $this->jsonResponse(200, ['success' => true, 'message' => 'Cliente atualizado com sucesso']);
        } else {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Erro ao atualizar cliente. Verifique os dados informados.']);
        }
    }
    
    private function excluir() {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'ID inválido']);
        }
        
        try {
            $resultado = $this->cliente->excluir($id);
            
            if ($resultado) {
                $this->jsonResponse(200, ['success' => true, 'message' => 'Cliente excluído com sucesso']);
            } else {
                $this->jsonResponse(500, ['success' => false, 'message' => 'Erro ao excluir cliente']);
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23000' && isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451) {
                $this->jsonResponse(409, ['success' => false, 'message' => 'Não é possível excluir: cliente possui pedidos associados.']);
            } else {
                $this->jsonResponse(500, ['success' => false, 'message' => 'Erro interno ao excluir cliente']);
            }
        }
    }
}

// Inicializar e executar
$controller = new ClienteController();
$controller->handleRequest();



