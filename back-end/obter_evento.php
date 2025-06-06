<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

$evento_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$evento_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do evento não fornecido']);
    exit();
}

try {
    // Verifica permissões
    $sql = "SELECT id, nome, descricao, data_inicio, data_fim, local, coordenador_id 
            FROM eventos 
            WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Evento não encontrado']);
        exit();
    }
    
    $evento = $result->fetch_assoc();
    
    // Verifica se o usuário tem permissão para ver o evento
    if ($_SESSION['tipo'] == 'coordenador' && $evento['coordenador_id'] != $_SESSION['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit();
    }
    
    echo json_encode($evento);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>