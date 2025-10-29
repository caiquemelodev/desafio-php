let clienteModal;
let deleteModal;
let clientesTable;

$(document).ready(function() {
    clienteModal = new bootstrap.Modal(document.getElementById('clienteModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    clientesTable = $('#clientesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '../controllers/ClienteController.php',
            type: 'GET',
            cache: false,
            data: function(d) {
                return {
                    action: 'listar',
                    page: (d.start / d.length) + 1,
                    per_page: d.length,
                    search: d.search.value
                };
            },
            dataSrc: function(json) {
                json.recordsTotal = json.total || 0;
                json.recordsFiltered = json.total || 0;
                return json.data || [];
            },
            error: function(xhr, error, thrown) {
                mostrarAlerta('Erro ao carregar clientes', 'danger');
            }
        },
        columns: [
            { data: 'id' },
            { data: 'nome' },
            { data: 'cpf' },
            { data: 'email' },
            {
                data: 'status',
                render: function(data) {
                    return data === 'ativo' 
                        ? '<span class="badge badge-ativo">Ativo</span>' 
                        : '<span class="badge badge-inativo">Inativo</span>';
                }
            },
            {
                data: 'data_alteracao',
                render: function(data) {
                    return new Date(data).toLocaleString('pt-BR');
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    var nomeEscapado = row.nome.replace(/'/g, "\\'");
                    return '<button class="btn btn-sm btn-primary" onclick="editarCliente(' + row.id + ')" title="Editar"><i class="fas fa-edit"></i></button> ' +
                           '<button class="btn btn-sm btn-danger" onclick="confirmarExclusao(' + row.id + ', \'' + nomeEscapado + '\')" title="Excluir"><i class="fas fa-trash"></i></button>';
                }
            }
        ],
        language: {
            // Usar HTTPS explícito para evitar redirecionamento 301 e erro de CORS
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']]
    });
    
    $('#searchInput').on('keyup', function() {
        clientesTable.search(this.value).draw();
    });
    
    $('#cpf').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length <= 11) {
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            $(this).val(value);
        }
        
        if (value.replace(/\D/g, '').length === 11) {
            if (validarCPF(value)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $('#cpf-error').remove();
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                if ($('#cpf-error').length === 0) {
                    $(this).after('<div id="cpf-error" class="invalid-feedback">CPF inválido</div>');
                }
            }
        } else {
            $(this).removeClass('is-valid is-invalid');
            $('#cpf-error').remove();
        }
    });
    
    $('#email').on('blur', function() {
        const email = $(this).val().trim();
        if (email.length > 0) {
            if (validarEmail(email)) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $('#email-error').remove();
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                if ($('#email-error').length === 0) {
                    $(this).after('<div id="email-error" class="invalid-feedback">E-mail inválido</div>');
                }
            }
        }
    });
    
    $('#nome').on('blur', function() {
        const nome = $(this).val().trim();
        if (nome.length > 0) {
            if (nome.length >= 3) {
                $(this).removeClass('is-invalid').addClass('is-valid');
                $('#nome-error').remove();
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
                if ($('#nome-error').length === 0) {
                    $(this).after('<div id="nome-error" class="invalid-feedback">Nome deve ter no mínimo 3 caracteres</div>');
                }
            }
        }
    });
});

function abrirModalCliente() {
    $('#modalTitle').text('Novo Cliente');
    $('#clienteForm')[0].reset();
    $('#clienteId').val('');
    $('#status').val('ativo');
    limparErrosValidacao();
    clienteModal.show();
}

function editarCliente(id) {
    $.ajax({
        url: '../controllers/ClienteController.php',
        method: 'GET',
        data: { action: 'buscar', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const cliente = response.data;
                $('#modalTitle').text('Editar Cliente');
                $('#clienteId').val(cliente.id);
                $('#nome').val(cliente.nome);
                $('#cpf').val(cliente.cpf);
                $('#email').val(cliente.email);
                $('#status').val(cliente.status);
                limparErrosValidacao();
                clienteModal.show();
            } else {
                mostrarAlerta('Erro ao buscar cliente: ' + response.message, 'danger');
            }
        },
        error: function() {
            mostrarAlerta('Erro ao conectar com o servidor', 'danger');
        }
    });
}

function salvarCliente() {
    limparErrosValidacao();
    
    const nome = $('#nome').val().trim();
    const cpf = $('#cpf').val().trim();
    const email = $('#email').val().trim();
    const status = $('#status').val();
    
    let temErro = false;
    
    if (nome.length < 3) {
        mostrarErroValidacao('nome', 'Nome deve ter no mínimo 3 caracteres');
        temErro = true;
    }
    
    if (!validarCPF(cpf)) {
        mostrarErroValidacao('cpf', 'CPF inválido. Verifique os dígitos');
        temErro = true;
    }
    
    if (!validarEmail(email)) {
        mostrarErroValidacao('email', 'E-mail inválido');
        temErro = true;
    }
    
    if (temErro) {
        return;
    }
    
    const id = $('#clienteId').val();
    const action = id ? 'atualizar' : 'criar';
    const formData = {
        action: action,
        nome: nome,
        cpf: cpf,
        email: email,
        status: status
    };
    
    if (id) {
        formData.id = id;
    }
    
    $.ajax({
        url: '../controllers/ClienteController.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarAlerta(response.message, 'success');
                clienteModal.hide();
                clientesTable.ajax.reload(null, false);
            } else {
                mostrarAlerta(response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            try {
                const response = JSON.parse(xhr.responseText);
                mostrarAlerta(response.message || 'Erro ao salvar cliente', 'danger');
            } catch (e) {
                mostrarAlerta('CPF já está cadastrado.', 'danger');
            }
        }
    });
}

function confirmarExclusao(id, nome) {
    $('#deleteClienteId').val(id);
    $('#deleteClienteName').text(nome);
    deleteModal.show();
}

function excluirClienteConfirmado() {
    const idNum = parseInt($('#deleteClienteId').val(), 10);
    if (!Number.isInteger(idNum) || idNum <= 0) {
        mostrarAlerta('ID do cliente inválido para exclusão', 'danger');
        return;
    }
    // Escondemos o modal somente após confirmar o sucesso da exclusão
    excluirCliente(idNum);
}



function excluirCliente(id) {
    const clienteId = parseInt(id, 10);
    if (isNaN(clienteId) || clienteId <= 0) {
        mostrarAlerta('ID do cliente inválido', 'danger');
        return;
    }

    $.ajax({
        url: '../controllers/ClienteController.php',
        method: 'POST',
        data: { action: 'excluir', id: clienteId },
        dataType: 'json',
        cache: false,
        // Evitar "parsererror" caso o corpo venha vazio mesmo com status 200/204
        converters: {
            'text json': function(text) {
                return text ? JSON.parse(text) : null;
            }
        },
        success: function (response, _status, xhr) {
            // Sem corpo, mas status OK/No Content => considerar sucesso
            if (!response && (xhr.status === 200 || xhr.status === 204)) {
                deleteModal.hide();
                mostrarAlerta('Cliente excluído com sucesso', 'success');
                return clientesTable.ajax.reload(null, false);
            }

            if (response && response.success) {
                deleteModal.hide();
                mostrarAlerta(response.message || 'Cliente excluído com sucesso', 'success');
                return clientesTable.ajax.reload(null, false);
            }

            const msg = (response && response.message) ? response.message : 'Não foi possível excluir o cliente.';
            mostrarAlerta('Erro ao excluir cliente: ' + msg, 'danger');
        },
        error: function (jqXHR, status) {
            // Tratar parsererror com corpo vazio e status OK/No Content como sucesso
            if (status === 'parsererror' && (!jqXHR.responseText || jqXHR.responseText === '') && (jqXHR.status === 200 || jqXHR.status === 204)) {
                deleteModal.hide();
                mostrarAlerta('Cliente excluído com sucesso', 'success');
                return clientesTable.ajax.reload(null, false);
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
            mostrarAlerta('Erro ao excluir cliente: ' + message, 'danger');
        }
    });
}

function mostrarAlerta(mensagem, tipo) {
    const alertHtml = '<div class="alert alert-' + tipo + ' alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999; min-width: 300px;" role="alert">' + mensagem + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    $('body').append(alertHtml);
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]/g, '');
    
    if (cpf.length !== 11) {
        return false;
    }
    
    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }
    
    let soma = 0;
    let resto;
    
    for (let i = 1; i <= 9; i++) {
        soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
    }
    
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(9, 10))) return false;
    
    soma = 0;
    for (let i = 1; i <= 10; i++) {
        soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
    }
    
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.substring(10, 11))) return false;
    
    return true;
}

function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function mostrarErroValidacao(campo, mensagem) {
    const $campo = $('#' + campo);
    $campo.addClass('is-invalid');
    
    const erroId = campo + '-error';
    if ($('#' + erroId).length === 0) {
        $campo.after('<div id="' + erroId + '" class="invalid-feedback">' + mensagem + '</div>');
    }
}

function limparErrosValidacao() {
    $('.is-invalid').removeClass('is-invalid');
    $('.is-valid').removeClass('is-valid');
    $('.invalid-feedback').remove();
}
