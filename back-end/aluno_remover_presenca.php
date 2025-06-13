<?php
include_once __DIR__ . '/conexao/connect.php';
include_once __DIR__ . '/../auth/sessao.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'aluno') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

$aluno_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['evento_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do evento não fornecido.']);
    exit;
}
$evento_id = $data['evento_id'];

try {
    // Deletar a presença
    $stmt = $mysqli->prepare("DELETE FROM presencas WHERE aluno_id = ? AND evento_id = ?");
    $stmt->bind_param('ii', $aluno_id, $evento_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Presença removida com sucesso!']);
        } else {
            throw new Exception('Nenhum registro de presença encontrado para remover.');
        }
    } else {
        throw new Exception('Erro ao remover presença.');
    }
    $stmt->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
?>
