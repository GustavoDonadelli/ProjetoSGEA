<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../auth/sessao.php'; // Garante que está no diretório correto
include_once __DIR__ . '/conexao/connect.php'; // Garante que está no diretório correto

// Verifica se o usuário é aluno
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'aluno') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Esta ação é permitida apenas para alunos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['evento_id']) || !filter_var($data['evento_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do evento inválido.']);
    exit;
}

$evento_id = (int)$data['evento_id'];
$aluno_id = $_SESSION['id'];

$mysqli->begin_transaction();

try {
    // Verificar se a presença já foi confirmada para este evento
    $sql_check_presenca = "SELECT id FROM presencas WHERE evento_id = ? AND aluno_id = ?";
    $stmt_check_presenca = $mysqli->prepare($sql_check_presenca);
    if (!$stmt_check_presenca) {
        throw new Exception("Erro ao preparar verificação de presença: " . $mysqli->error);
    }
    $stmt_check_presenca->bind_param("ii", $evento_id, $aluno_id);
    $stmt_check_presenca->execute();
    $result_check_presenca = $stmt_check_presenca->get_result();

    if ($result_check_presenca->num_rows > 0) {
        $stmt_check_presenca->close();
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Não é possível cancelar a inscrição pois a presença já foi confirmada para este evento.']);
        $mysqli->rollback(); // Desfaz a transação, embora nada tenha sido feito ainda.
        exit;
    }
    $stmt_check_presenca->close();

    // Se a presença não foi confirmada, prosseguir com o cancelamento da inscrição
    $sql_cancelar = "DELETE FROM inscricoes WHERE evento_id = ? AND aluno_id = ?";
    $stmt_cancelar = $mysqli->prepare($sql_cancelar);
    if (!$stmt_cancelar) {
        throw new Exception("Erro ao preparar cancelamento: " . $mysqli->error);
    }
    $stmt_cancelar->bind_param("ii", $evento_id, $aluno_id);

    if ($stmt_cancelar->execute()) {
        if ($stmt_cancelar->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Inscrição cancelada com sucesso.']);
            $mysqli->commit();
        } else {
            // Não encontrou a inscrição para deletar, o que pode ser um erro de estado ou o aluno não estava inscrito.
            // Consideramos como sucesso se o objetivo é não estar inscrito.
            echo json_encode(['success' => true, 'message' => 'Inscrição não encontrada ou já cancelada.']);
            $mysqli->commit(); // Ou rollback se preferir tratar como erro
        }
    } else {
        throw new Exception("Erro ao cancelar inscrição: " . $stmt_cancelar->error);
    }
    $stmt_cancelar->close();

} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
?>
