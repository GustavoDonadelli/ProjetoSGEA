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
    // Verifica se o coordenador é o criador do evento
    $check_sql = "SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $_SESSION['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para ver as presenças deste evento']);
        exit();
    }
    
    // Obtém a lista de presenças
    $sql = "SELECT u.nome AS nome_aluno, p.data_presenca
            FROM presencas p
            JOIN usuarios u ON p.aluno_id = u.id
            WHERE p.evento_id = ?
            ORDER BY p.data_presenca DESC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $presencas = [];
    while ($row = $result->fetch_assoc()) {
        $presencas[] = $row;
    }
    
    echo json_encode($presencas);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>