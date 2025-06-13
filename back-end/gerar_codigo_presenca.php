<?php
ob_start(); // Inicia o buffer de saída para capturar qualquer saída inesperada.

// Define o header e inicia a sessão ANTES de qualquer outra saída.
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 0); // Desabilita a exibição de erros para o cliente.
error_reporting(E_ALL); // Reporta todos os erros para o nosso handler.

// Shutdown handler para capturar erros fatais
function shutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        if (ob_get_length()) {
            ob_end_clean(); // Limpa qualquer saída, incluindo o erro HTML
        }
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Erro fatal capturado.',
            'error_details' => [
                'type'    => $error['type'],
                'message' => $error['message'],
                'file'    => $error['file'],
                'line'    => $error['line'],
            ],
        ]);
        exit;
    }
}
register_shutdown_function('shutdownHandler');

// Error handler para erros não fatais (warnings, notices, etc.)
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Não interromper para todos os tipos de erro, mas para depuração, vamos capturar
    if (ob_get_length()) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Erro (não fatal) capturado.',
        'error_details' => [
            'type'    => $errno,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
        ],
    ]);
    exit;
}
set_error_handler("customErrorHandler");

include_once './conexao/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
    exit;
}

// Verifica se o usuário é coordenador ou diretor
if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'coordenador' && $_SESSION['tipo'] !== 'diretor')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Somente coordenadores ou diretores podem gerar códigos.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['evento_id']) || !filter_var($data['evento_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do evento inválido ou não fornecido.']);
    exit;
}

$evento_id = (int)$data['evento_id'];
$id_usuario_logado = (int)$_SESSION['id'];

// Se for coordenador, verifica se ele é o dono do evento
if ($_SESSION['tipo'] === 'coordenador') {
    $stmt_check_owner = $mysqli->prepare("SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?");
    if (!$stmt_check_owner) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar verificação de propriedade do evento: ' . $mysqli->error]);
        exit;
    }
    $stmt_check_owner->bind_param("ii", $evento_id, $id_usuario_logado);
    $stmt_check_owner->execute();
    $result_check_owner = $stmt_check_owner->get_result();
    if ($result_check_owner->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Você não é o coordenador deste evento.']);
        $stmt_check_owner->close();
        exit;
    }
    $stmt_check_owner->close();
}

// Função aprimorada para gerar um código único, seguro e complexo.
function gerarCodigoUnico($length = 20) {
    // Define o conjunto de caracteres, incluindo especiais, para o código.
    $caracteres = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $codigo = '';
    $max = strlen($caracteres) - 1;

    // Gera o código com o comprimento desejado usando bytes aleatórios seguros.
    for ($i = 0; $i < $length; $i++) {
        $codigo .= $caracteres[random_int(0, $max)];
    }

    return $codigo;
}

$codigo_gerado = gerarCodigoUnico(20); // Gera código com 20 caracteres
$data_atual = date('Y-m-d H:i:s');

// Verifica se já existe um código para este evento
$stmt_check_existing = $mysqli->prepare("SELECT id FROM codigos_presenca WHERE evento_id = ?");
if (!$stmt_check_existing) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta de código existente: ' . $mysqli->error]);
    exit;
}
$stmt_check_existing->bind_param("i", $evento_id);
$stmt_check_existing->execute();
$result_existing = $stmt_check_existing->get_result();

if ($result_existing->num_rows > 0) {
    // Atualiza o código existente, reseta utilização
    $stmt_update = $mysqli->prepare("UPDATE codigos_presenca SET codigo = ?, data_geracao = ? WHERE evento_id = ?");
    if (!$stmt_update) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar atualização do código: ' . $mysqli->error]);
        exit;
    }
    $stmt_update->bind_param("ssi", $codigo_gerado, $data_atual, $evento_id);
    if ($stmt_update->execute()) {
        echo json_encode(['success' => true, 'codigo' => $codigo_gerado, 'message' => 'Código de presença atualizado com sucesso.', 'validade' => '1 hora']); // Mantendo 'validade' se for relevante
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar código de presença: ' . $stmt_update->error]);
    }
    $stmt_update->close();
} else {
    // Insere novo código
    $stmt_insert = $mysqli->prepare("INSERT INTO codigos_presenca (evento_id, codigo, data_geracao) VALUES (?, ?, ?)");
    if (!$stmt_insert) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar inserção do código: ' . $mysqli->error]);
        exit;
    }
    $stmt_insert->bind_param("iss", $evento_id, $codigo_gerado, $data_atual);
    if ($stmt_insert->execute()) {
        echo json_encode(['success' => true, 'codigo' => $codigo_gerado, 'message' => 'Código de presença gerado com sucesso.', 'validade' => '1 hora']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao gerar código de presença: ' . $stmt_insert->error]);
    }
    $stmt_insert->close();
}

$stmt_check_existing->close();
$mysqli->close();
ob_end_flush(); // Envia o buffer de saída se tudo correu bem e nenhum exit foi chamado
?>