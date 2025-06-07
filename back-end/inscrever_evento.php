<?php
header('Content-Type: application/json');
include('./conexao/connect.php');
include('./conexao/protect.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$evento_id = filter_var($data['evento_id'], FILTER_VALIDATE_INT);
$aluno_id = $_SESSION['id'];

try {
    // Verifica se o aluno já está inscrito
    $check_sql = "SELECT id FROM inscricoes WHERE evento_id = ? AND aluno_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $aluno_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Você já está inscrito neste evento']);
        exit();
    }
    
    // Inscreve o aluno
    $sql = "INSERT INTO inscricoes (evento_id, aluno_id, data_inscricao) 
            VALUES (?, ?, NOW())";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $evento_id, $aluno_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Inscrição realizada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao realizar inscrição']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>