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

    // Conexão com o banco de dados
    require __DIR__ . '/conexao/connect.php';
    
    // Inicia transação
    $mysqli->begin_transaction();

    try {
        // Verifica se o coordenador é o dono do evento
        $check_sql = "SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param("ii", $evento_id, $_SESSION['id']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows == 0) {
            throw new Exception('Você não tem permissão para excluir este evento', 403);
        }

        // 1. Exclui os códigos de presença relacionados
        $sql_codigos = "DELETE FROM codigos_presenca WHERE evento_id = ?";
        $stmt_codigos = $mysqli->prepare($sql_codigos);
        $stmt_codigos->bind_param("i", $evento_id);
        $stmt_codigos->execute();
        $stmt_codigos->close();

        // 2. Exclui as presenças registradas
        $sql_presencas = "DELETE FROM presencas WHERE evento_id = ?";
        $stmt_presencas = $mysqli->prepare($sql_presencas);
        $stmt_presencas->bind_param("i", $evento_id);
        $stmt_presencas->execute();
        $stmt_presencas->close();

        // 3. Exclui as inscrições no evento
        $sql_inscricoes = "DELETE FROM inscricoes WHERE evento_id = ?";
        $stmt_inscricoes = $mysqli->prepare($sql_inscricoes);
        $stmt_inscricoes->bind_param("i", $evento_id);
        $stmt_inscricoes->execute();
        $stmt_inscricoes->close();

        // 4. Finalmente exclui o evento
        $sql_evento = "DELETE FROM eventos WHERE id = ?";
        $stmt_evento = $mysqli->prepare($sql_evento);
        $stmt_evento->bind_param("i", $evento_id);
        $stmt_evento->execute();
        
        if ($stmt_evento->affected_rows === 0) {
            throw new Exception('Evento não encontrado ou já excluído', 404);
        }

        // Confirma todas as operações
        $mysqli->commit();

        // Resposta de sucesso
        echo json_encode([
            'success' => true,
            'message' => 'Evento e todos os registros relacionados foram excluídos com sucesso'
        ]);

    } catch (Exception $e) {
        // Desfaz todas as operações em caso de erro
        $mysqli->rollback();
        throw $e;
    }

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
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($mysqli)) $mysqli->close();
}