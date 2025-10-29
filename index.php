<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gerenciamento</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
        }
        .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        .btn-feature {
            padding: 20px;
            margin: 10px 0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-feature:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="welcome-card">
        <div class="text-center">
            <div class="icon-box bg-primary text-white">
                <i class="fas fa-store"></i>
            </div>
            <h1 class="mb-3">Sistema de Gerenciamento</h1>
            <p class="text-muted mb-4">
                Bem-vindo ao sistema de gerenciamento de clientes e pedidos.
                Escolha uma das opções abaixo para começar.
            </p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <a href="views/clientes.php" class="btn btn-primary btn-feature w-100 text-start">
                    <i class="fas fa-users fa-2x mb-2"></i>
                    <h5 class="mb-1">Clientes</h5>
                    <small>Gerenciar cadastro de clientes</small>
                </a>
            </div>
            <div class="col-md-6">
                <a href="views/pedidos.php" class="btn btn-success btn-feature w-100 text-start">
                    <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                    <h5 class="mb-1">Pedidos</h5>
                    <small>Gerenciar pedidos de clientes</small>
                </a>
            </div>
        </div>

        <hr class="my-4">

        <div class="text-center text-muted small">
            <p class="mb-0">
                <i class="fas fa-info-circle"></i> 
                Sistema desenvolvido com PHP, MySQL, Bootstrap e jQuery
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
