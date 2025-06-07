<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SESSION['tipo'] != 'coordenador') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

$evento_id = isset($_GET['evento_id']) ? filter_var($_GET['evento_id'], FILTER_VALIDATE_INT) : null;

if (!$evento_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do evento não fornecido']);
    exit();
}

try {
    error_log("[listar_presencas.php] Iniciando. Evento ID recebido: " . print_r($evento_id, true));
    // Verifica se o coordenador é o criador do evento
    $check_sql = "SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $_SESSION['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        $check_stmt->close(); // Fechar statement antes de sair
        echo json_encode([]); // Retorna array vazio se não for dono
        exit();
    }
    $check_stmt->close(); // Fechar statement se passou na verificação
    
    // Obtém a lista de presenças
    // Adicionando u.id as aluno_id para referência, caso necessário no frontend
    $sql = "SELECT u.nome AS nome_aluno, p.data_presenca, u.id as aluno_id
            FROM presencas p
            JOIN usuarios u ON p.aluno_id = u.id
            WHERE p.evento_id = ?
            ORDER BY p.data_presenca DESC";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) { // Adicionar verificação de falha na preparação
        throw new Exception("Erro ao preparar a query de listar presenças: " . $mysqli->error);
    }
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    error_log("[listar_presencas.php] Query executada. Número de linhas encontradas: " . $result->num_rows);
    
    $presencas = [];
    while ($row = $result->fetch_assoc()) {
        $presencas[] = $row;
    }
    
    echo json_encode($presencas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
    // $check_stmt já foi fechado nos caminhos lógicos anteriores
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
}
?>