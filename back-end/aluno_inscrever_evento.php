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

$response = ['success' => false];

try {
    // Verificar se o evento existe e não começou
    $stmt_check = $mysqli->prepare("SELECT data_inicio FROM eventos WHERE id = ?");
    $stmt_check->bind_param('i', $evento_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $evento_details = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$evento_details) {
        throw new Exception('Evento não encontrado.');
    }

    if (new DateTime($evento_details['data_inicio']) <= new DateTime()) {
        throw new Exception('Este evento já começou ou terminou. Inscrições encerradas.');
    }

    // Verificar se o aluno já está inscrito
    $stmt_already_inscrito = $mysqli->prepare("SELECT COUNT(*) AS count FROM inscricoes WHERE aluno_id = ? AND evento_id = ?");
    $stmt_already_inscrito->bind_param('ii', $aluno_id, $evento_id);
    $stmt_already_inscrito->execute();
    $result_already_inscrito = $stmt_already_inscrito->get_result()->fetch_assoc();
    $stmt_already_inscrito->close();

    if ($result_already_inscrito['count'] > 0) {
        throw new Exception('Você já está inscrito neste evento.');
    }

    // Realizar a inscrição
    $stmt_insert = $mysqli->prepare("INSERT INTO inscricoes (aluno_id, evento_id, data_inscricao) VALUES (?, ?, NOW())");
    $stmt_insert->bind_param('ii', $aluno_id, $evento_id);
    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['message'] = 'Inscrição realizada com sucesso!';
    } else {
        throw new Exception('Erro ao realizar inscrição: ' . $stmt_insert->error);
    }
    $stmt_insert->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // http_response_code(400); // Bad Request ou outro código apropriado
}

$mysqli->close();
echo json_encode($response);
?>
