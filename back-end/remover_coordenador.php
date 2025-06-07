<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'DELETE') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

if ($_SESSION['tipo'] != 'diretor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

$coordenador_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$coordenador_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do coordenador não fornecido']);
    exit();
}

try {
    // Verifica se é um coordenador
    $check_sql = "SELECT id FROM usuarios WHERE id = ? AND tipo = 'coordenador'";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("i", $coordenador_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Usuário não é um coordenador']);
        exit();
    }
    
    // Remove o coordenador
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $coordenador_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao remover coordenador']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>