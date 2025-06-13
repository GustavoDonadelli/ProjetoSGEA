<?php
// Define um error handler para capturar warnings e erros, e retorná-los como JSON.
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Se os headers já foram enviados, não fazer nada para evitar mais erros.
    if (headers_sent()) {
        return;
    }
    // Limpa qualquer saída que possa ter sido gerada antes do erro.
    if (ob_get_length()) {
        ob_end_clean();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno no servidor.',
        'error_details' => [
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

header('Content-Type: application/json');
include_once __DIR__ . '/../auth/sessao.php'; // Garante que a sessão seja iniciada e o usuário esteja logado.
include_once __DIR__ . '/conexao/connect.php'; // Apenas para a conexão com o banco de dados.

// O sessao.php já garante que $_SESSION['id'] existe.
// Aqui, verificamos especificamente se o usuário logado é um 'aluno'.
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'aluno') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas alunos podem se inscrever em eventos.']);
    exit();
}

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
        echo json_encode(['success' => true, 'message' => 'Inscrição realizada com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao realizar inscrição: ' . $stmt->error]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>