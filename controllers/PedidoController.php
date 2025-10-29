<?php

require_once '../config/database.php';
require_once '../models/Pedido.php';
require_once '../models/Produto.php';

class PedidoController {
    
    private $db;
    private $pedido;
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
        
        $this->pedido = new Pedido($this->db);
        $this->produto = new Produto($this->db);
    }
    
    private function setHeaders() {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        
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
            default:
                $this->jsonResponse(400, ['success' => false, 'message' => 'Ação inválida']);
        }
    }
    
    private function listar() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $cliente_id = isset($_GET['cliente_id']) ? $_GET['cliente_id'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        
        $resultado = $this->pedido->listar($page, $per_page, $cliente_id, $status);
        
        $this->jsonResponse(200, [
            'success' => true,
            'data' => $resultado['data'],
            'total' => $resultado['total'],
            'total_pages' => $resultado['total_pages'],
            'current_page' => $resultado['current_page']
        ]);
    }
    
    private function buscar() {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'ID não informado']);
        }
        
        $resultado = $this->pedido->buscarPorId($id);
        
        if ($resultado) {
            $this->jsonResponse(200, ['success' => true, 'data' => $resultado]);
        } else {
            $this->jsonResponse(404, ['success' => false, 'message' => 'Pedido não encontrado']);
        }
    }
    
    private function criar() {
        // Validar campos obrigatórios
        if (empty($_POST['cliente_id']) || empty($_POST['produtos'])) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Cliente e produtos são obrigatórios']);
        }
        
        // Decodificar produtos (vem como JSON)
        $produtos_json = json_decode($_POST['produtos'], true);
        
        if (empty($produtos_json) || !is_array($produtos_json)) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Produtos inválidos']);
        }
        
        // Validar e buscar preços atuais dos produtos
        $produtos_validados = [];
        foreach ($produtos_json as $prod) {
            if (empty($prod['produto_id']) || empty($prod['quantidade']) || $prod['quantidade'] <= 0) {
                $this->jsonResponse(400, ['success' => false, 'message' => 'Dados de produto inválidos']);
            }
            
            // Buscar preço atual do produto
            $produto_info = $this->produto->buscarPorId($prod['produto_id']);
            
            if (!$produto_info) {
                $this->jsonResponse(404, ['success' => false, 'message' => 'Produto ID ' . $prod['produto_id'] . ' não encontrado']);
            }
            
            $produtos_validados[] = [
                'produto_id' => $prod['produto_id'],
                'quantidade' => $prod['quantidade'],
                'preco_unitario' => $produto_info['preco']
            ];
        }
        
        $this->pedido->cliente_id = $_POST['cliente_id'];
        $this->pedido->status = isset($_POST['status']) ? $_POST['status'] : 'ativo';
        $this->pedido->produtos = $produtos_validados;
        
        $resultado = $this->pedido->criar();
        
        if ($resultado) {
            $this->jsonResponse(201, ['success' => true, 'message' => 'Pedido criado com sucesso', 'id' => $resultado]);
        } else {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro ao criar pedido']);
        }
    }
    
    private function atualizar() {
        // Validar campos obrigatórios
        if (empty($_POST['id']) || empty($_POST['cliente_id']) || empty($_POST['produtos'])) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'ID, cliente e produtos são obrigatórios']);
        }
        
        // Decodificar produtos (vem como JSON)
        $produtos_json = json_decode($_POST['produtos'], true);
        
        if (empty($produtos_json) || !is_array($produtos_json)) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'Produtos inválidos']);
        }
        
        // Validar e buscar preços atuais dos produtos
        $produtos_validados = [];
        foreach ($produtos_json as $prod) {
            if (empty($prod['produto_id']) || empty($prod['quantidade']) || $prod['quantidade'] <= 0) {
                $this->jsonResponse(400, ['success' => false, 'message' => 'Dados de produto inválidos']);
            }
            
            // Buscar preço atual do produto
            $produto_info = $this->produto->buscarPorId($prod['produto_id']);
            
            if (!$produto_info) {
                $this->jsonResponse(404, ['success' => false, 'message' => 'Produto ID ' . $prod['produto_id'] . ' não encontrado']);
            }
            
            $produtos_validados[] = [
                'produto_id' => $prod['produto_id'],
                'quantidade' => $prod['quantidade'],
                'preco_unitario' => $produto_info['preco']
            ];
        }
        
        $this->pedido->id = $_POST['id'];
        $this->pedido->cliente_id = $_POST['cliente_id'];
        $this->pedido->status = isset($_POST['status']) ? $_POST['status'] : 'ativo';
        $this->pedido->produtos = $produtos_validados;
        
        $resultado = $this->pedido->atualizar();
        
        if ($resultado) {
            $this->jsonResponse(200, ['success' => true, 'message' => 'Pedido atualizado com sucesso']);
        } else {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro ao atualizar pedido']);
        }
    }
    
    private function excluir() {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        if ($id <= 0) {
            $this->jsonResponse(400, ['success' => false, 'message' => 'ID inválido']);
        }
        
        try {
            $resultado = $this->pedido->excluir($id);
            
            if ($resultado) {
                $this->jsonResponse(200, ['success' => true, 'message' => 'Pedido excluído com sucesso']);
            } else {
                $this->jsonResponse(500, ['success' => false, 'message' => 'Erro ao excluir pedido']);
            }
        } catch (PDOException $e) {
            // Se houver restrição de FK (ex.: itens do pedido)
            if ($e->getCode() === '23000' && isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1451) {
                $this->jsonResponse(409, ['success' => false, 'message' => 'Não é possível excluir: pedido possui itens associados.']);
            }
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro interno ao excluir pedido']);
        } catch (Throwable $t) {
            $this->jsonResponse(500, ['success' => false, 'message' => 'Erro inesperado ao excluir pedido']);
        }
    }
}

// Inicializar e executar
$controller = new PedidoController();
$controller->handleRequest();
