<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

if ($_SESSION['tipo'] != 'coordenador') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$evento_id = filter_var($data['evento_id'], FILTER_VALIDATE_INT);
$coordenador_id = $_SESSION['id'];

try {
    // Verifica se o coordenador é o criador do evento
    $check_sql = "SELECT id FROM eventos WHERE id = ? AND coordenador_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $coordenador_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Você não tem permissão para gerar código para este evento']);
        exit();
    }
    
    // Gera um código aleatório
    $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    
    // Insere o código no banco de dados
    $sql = "INSERT INTO codigos_presenca (evento_id, codigo, data_geracao) 
        VALUES (?, ?, NOW())";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $evento_id, $codigo);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'codigo' => $codigo,
            'validade' => '1 hora'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao gerar código']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>