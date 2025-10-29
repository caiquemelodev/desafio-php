let currentPage = 1;
let perPage = 10;
let filtroClienteId = '';
let filtroStatusPedido = 'todos';
let pedidoModal;
let produtosSelecionados = [];

$(document).ready(function() {
    // Inicializar modal Bootstrap
    pedidoModal = new bootstrap.Modal(document.getElementById('pedidoModal'));
    
    // Carregar dados iniciais
    carregarPedidos();
    carregarClientesFiltro();
    carregarClientesDropdown();
    carregarProdutosDropdown();
    
    // Event listeners
    $('#filtroCliente').on('change', function() {
        filtroClienteId = $(this).val();
        currentPage = 1;
        carregarPedidos();
    });
    
    $('#filtroStatus').on('change', function() {
        filtroStatusPedido = $(this).val();
        currentPage = 1;
        carregarPedidos();
    });
    
    $('#perPageSelect').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        carregarPedidos();
    });
});

function carregarPedidos() {
    $.ajax({
        url: '../controllers/PedidoController.php',
        method: 'GET',
        data: {
            action: 'listar',
            page: currentPage,
            per_page: perPage,
            cliente_id: filtroClienteId,
            status: filtroStatusPedido
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderizarTabela(response.data);
                renderizarPaginacao(response.total_pages, response.current_page);
            } else {
                mostrarAlerta('Erro ao carregar pedidos: ' + response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlerta('Erro ao conectar com o servidor', 'danger');
        }
    });
}

function renderizarTabela(pedidos) {
    const tbody = $('#pedidosTableBody');
    tbody.empty();
    
    if (pedidos.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> Nenhum pedido encontrado
                </td>
            </tr>
        `);
        return;
    }
    
    pedidos.forEach(function(pedido) {
        const statusBadge = pedido.status === 'ativo' 
            ? '<span class="badge badge-ativo">Ativo</span>' 
            : '<span class="badge badge-inativo">Inativo</span>';
        
        const dataPedido = new Date(pedido.data_pedido).toLocaleString('pt-BR');
        const valorFormatado = formatarMoeda(pedido.valor_total);
        
        const row = `
            <tr class="fade-in">
                <td>${pedido.id}</td>
                <td>${pedido.cliente_nome}</td>
                <td class="fw-bold text-success">${valorFormatado}</td>
                <td>${dataPedido}</td>
                <td>${statusBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editarPedido(${pedido.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(${pedido.id})" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function renderizarPaginacao(totalPages, currentPageNum) {
    const pagination = $('#pagination');
    pagination.empty();
    
    if (totalPages <= 1) return;
    
    // Botão anterior
    const prevDisabled = currentPageNum === 1 ? 'disabled' : '';
    pagination.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="mudarPagina(${currentPageNum - 1}); return false;">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
        </li>
    `);
    
    // Páginas
    let startPage = Math.max(1, currentPageNum - 2);
    let endPage = Math.min(totalPages, currentPageNum + 2);
    
    if (startPage > 1) {
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="mudarPagina(1); return false;">1</a></li>`);
        if (startPage > 2) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPageNum ? 'active' : '';
        pagination.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="#" onclick="mudarPagina(${i}); return false;">${i}</a>
            </li>
        `);
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        pagination.append(`<li class="page-item"><a class="page-link" href="#" onclick="mudarPagina(${totalPages}); return false;">${totalPages}</a></li>`);
    }
    
    // Botão próximo
    const nextDisabled = currentPageNum === totalPages ? 'disabled' : '';
    pagination.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="mudarPagina(${currentPageNum + 1}); return false;">
                Próximo <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `);
}

function mudarPagina(page) {
    currentPage = page;
    carregarPedidos();
}

function carregarClientesFiltro() {
    $.ajax({
        url: '../controllers/ClienteController.php',
        method: 'GET',
        data: { action: 'listar_ativos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#filtroCliente');
                response.data.forEach(function(cliente) {
                    select.append(`<option value="${cliente.id}">${cliente.nome}</option>`);
                });
            }
        }
    });
}

function carregarClientesDropdown() {
    $.ajax({
        url: '../controllers/ClienteController.php',
        method: 'GET',
        data: { action: 'listar_ativos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#cliente_id');
                select.empty();
                select.append('<option value="">Selecione um cliente...</option>');
                response.data.forEach(function(cliente) {
                    select.append(`<option value="${cliente.id}">${cliente.nome}</option>`);
                });
            }
        }
    });
}

function carregarProdutosDropdown() {
    $.ajax({
        url: '../controllers/ProdutoController.php',
        method: 'GET',
        data: { action: 'listar_ativos' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const select = $('#produtoSelect');
                select.empty();
                select.append('<option value="">Selecione um produto...</option>');
                response.data.forEach(function(produto) {
                    select.append(`
                        <option value="${produto.id}" 
                                data-preco="${produto.preco}" 
                                data-nome="${produto.nome}">
                            ${produto.nome} - R$ ${formatarMoeda(produto.preco)}
                        </option>
                    `);
                });
            }
        }
    });
}

function abrirModalPedido() {
    $('#modalTitle').text('Novo Pedido');
    $('#pedidoForm')[0].reset();
    $('#pedidoId').val('');
    $('#statusPedido').val('ativo');
    produtosSelecionados = [];
    renderizarProdutosSelecionados();
    pedidoModal.show();
}

function editarPedido(id) {
    $.ajax({
        url: '../controllers/PedidoController.php',
        method: 'GET',
        data: {
            action: 'buscar',
            id: id
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const pedido = response.data;
                $('#modalTitle').text('Editar Pedido');
                $('#pedidoId').val(pedido.id);
                $('#cliente_id').val(pedido.cliente_id);
                $('#statusPedido').val(pedido.status);
                
                // Carregar produtos do pedido
                produtosSelecionados = pedido.produtos.map(function(p) {
                    return {
                        produto_id: p.produto_id,
                        nome: p.produto_nome,
                        preco_unitario: parseFloat(p.preco_unitario),
                        quantidade: parseInt(p.quantidade)
                    };
                });
                
                renderizarProdutosSelecionados();
                pedidoModal.show();
            } else {
                mostrarAlerta('Erro ao buscar pedido: ' + response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlerta('Erro ao conectar com o servidor', 'danger');
        }
    });
}

function adicionarProduto() {
    const select = $('#produtoSelect');
    const produtoId = select.val();
    const quantidade = parseInt($('#quantidadeInput').val());
    
    if (!produtoId) {
        mostrarAlerta('Selecione um produto', 'warning');
        return;
    }
    
    if (quantidade <= 0) {
        mostrarAlerta('Quantidade deve ser maior que zero', 'warning');
        return;
    }
    
    const option = select.find('option:selected');
    const nome = option.data('nome');
    const preco = parseFloat(option.data('preco'));
    
    // Verificar se produto já foi adicionado
    const index = produtosSelecionados.findIndex(p => p.produto_id == produtoId);
    
    if (index >= 0) {
        // Atualizar quantidade
        produtosSelecionados[index].quantidade += quantidade;
    } else {
        // Adicionar novo produto
        produtosSelecionados.push({
            produto_id: produtoId,
            nome: nome,
            preco_unitario: preco,
            quantidade: quantidade
        });
    }
    
    renderizarProdutosSelecionados();
    
    // Limpar seleção
    select.val('');
    $('#quantidadeInput').val('1');
}

function removerProduto(index) {
    produtosSelecionados.splice(index, 1);
    renderizarProdutosSelecionados();
}

function renderizarProdutosSelecionados() {
    const tbody = $('#produtosTableBody');
    tbody.empty();
    
    if (produtosSelecionados.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Nenhum produto adicionado
                </td>
            </tr>
        `);
        $('#valorTotal').text('R$ 0,00');
        return;
    }
    
    let total = 0;
    
    produtosSelecionados.forEach(function(produto, index) {
        const subtotal = produto.preco_unitario * produto.quantidade;
        total += subtotal;
        
        const row = `
            <tr>
                <td>${produto.nome}</td>
                <td class="text-end">${formatarMoeda(produto.preco_unitario)}</td>
                <td class="text-center">${produto.quantidade}</td>
                <td class="text-end fw-bold">${formatarMoeda(subtotal)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removerProduto(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
    
    $('#valorTotal').text(formatarMoeda(total));
}

function salvarPedido() {
    const form = $('#pedidoForm')[0];
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    if (produtosSelecionados.length === 0) {
        mostrarAlerta('Adicione pelo menos um produto ao pedido', 'warning');
        return;
    }
    
    const id = $('#pedidoId').val();
    const action = id ? 'atualizar' : 'criar';
    
    const formData = {
        action: action,
        cliente_id: $('#cliente_id').val(),
        status: $('#statusPedido').val(),
        produtos: JSON.stringify(produtosSelecionados)
    };
    
    if (id) {
        formData.id = id;
    }
    
    $.ajax({
        url: '../controllers/PedidoController.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarAlerta(response.message, 'success');
                pedidoModal.hide();
                carregarPedidos();
            } else {
                mostrarAlerta(response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlerta('Erro ao conectar com o servidor', 'danger');
        }
    });
}

function confirmarExclusao(id) {
    if (confirm('Deseja realmente excluir este pedido?\n\nEsta ação não pode ser desfeita.')) {
        excluirPedido(id);
    }
}


function excluirPedido(id) {
  const pedidoId = parseInt(id, 10);
  if (isNaN(pedidoId) || pedidoId <= 0) {
    mostrarAlerta('ID do pedido inválido', 'danger');
    return;
  }

  $.ajax({
    url: '../controllers/PedidoController.php',
    method: 'POST',
    data: { action: 'excluir', id: pedidoId },
    dataType: 'json',
    cache: false,
 
    converters: {
      'text json': function (text) {
        return text ? JSON.parse(text) : null;
      }
    },
    success: function (response, _status, xhr) {
     
      if (!response && (xhr.status === 200 || xhr.status === 204)) {
        mostrarAlerta('Pedido excluído com sucesso', 'success');
        return carregarPedidos();
      }

      if (response && response.success) {
        mostrarAlerta(response.message || 'Pedido excluído com sucesso', 'success');
        carregarPedidos();
      } else {
        const msg = (response && response.message) ? response.message : 'Não foi possível excluir o pedido.';
        mostrarAlerta('Erro ao excluir pedido: ' + msg, 'danger');
      }
    },
    error: function (jqXHR, status, error) {

      if (status === 'parsererror' && (!jqXHR.responseText || jqXHR.responseText === '') && (jqXHR.status === 200 || jqXHR.status === 204)) {
        mostrarAlerta('Pedido excluído com sucesso', 'success');
        return carregarPedidos();
      }

      let message = 'Erro ao conectar com o servidor';
      if (jqXHR && jqXHR.responseText) {
        try {
          const json = JSON.parse(jqXHR.responseText);
          if (json && json.message) {
            message = json.message;
          }
        } catch (e) {
          if (jqXHR.status) {
            message += ` (HTTP ${jqXHR.status})`;
          }
        }
      }
      mostrarAlerta('Erro ao excluir pedido: ' + message, 'danger');
    }
  });
}
function limparFiltros() {
    $('#filtroCliente').val('');
    $('#filtroStatus').val('todos');
    $('#perPageSelect').val('10');
    filtroClienteId = '';
    filtroStatusPedido = 'todos';
    perPage = 10;
    currentPage = 1;
    carregarPedidos();
}

function formatarMoeda(valor) {
    return parseFloat(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function mostrarAlerta(mensagem, tipo) {
    const alertHtml = `
        <div class="alert alert-${tipo} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
             style="z-index: 9999; min-width: 300px;" role="alert">
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
