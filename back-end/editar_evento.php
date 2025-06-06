<?php
// Deve ser a PRIMEIRA linha do script
header('Content-Type: application/json');

session_start();

// Verifica se o script está sendo acessado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Verifica autenticação
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit();
}

try {
    // Obtém os dados JSON do corpo da requisição
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados inválidos', 400);
    }

    // Validação do ID do evento
    $evento_id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
    if (!$evento_id || $evento_id <= 0) {
        throw new Exception('ID do evento inválido', 400);
    }

    // Validação dos campos obrigatórios
    $required = ['nome', 'local', 'data_inicio', 'data_fim'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("O campo {$field} é obrigatório", 400);
        }
    }

    // Conexão com o banco de dados
    require __DIR__ . '/conexao/connect.php';
    
    // Verifica se o coordenador é o dono do evento
    $check_sql = "SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $_SESSION['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        throw new Exception('Você não tem permissão para editar este evento', 403);
    }
    
    // Prepara os dados
    $nome = $mysqli->real_escape_string($data['nome']);
    $descricao = !empty($data['descricao']) ? $mysqli->real_escape_string($data['descricao']) : null;
    $local = $mysqli->real_escape_string($data['local']);
    
    // Formata as datas (datetime-local envia como YYYY-MM-DDTHH:MM)
    $data_inicio = str_replace('T', ' ', $data['data_inicio']) . ':00';
    $data_fim = str_replace('T', ' ', $data['data_fim']) . ':00';
    
    // Valida as datas
    if (strtotime($data_fim) <= strtotime($data_inicio)) {
        throw new Exception('Data de término deve ser posterior à data de início', 400);
    }

    // Atualiza o evento
    $sql = "UPDATE eventos 
            SET nome = ?, descricao = ?, data_inicio = ?, data_fim = ?, local = ?
            WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $mysqli->error, 500);
    }

    $stmt->bind_param("sssssi", $nome, $descricao, $data_inicio, $data_fim, $local, $evento_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar a query: ' . $stmt->error, 500);
    }

    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Evento atualizado com sucesso'
    ]);

} catch (Exception $e) {
    // Resposta de erro
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} finally {
    // Fecha conexões
    if (isset($stmt)) $stmt->close();
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($mysqli)) $mysqli->close();
}