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
    // Verificar se o evento permite cancelamento (ex: não começou ainda)
    $stmt_check = $mysqli->prepare("SELECT data_inicio FROM eventos WHERE id = ?");
    $stmt_check->bind_param('i', $evento_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $evento_details = $result_check->fetch_assoc();
    $stmt_check->close();

    if (!$evento_details) {
        throw new Exception('Evento não encontrado.');
    }

    // Regra de negócio: permitir cancelamento apenas se o evento ainda não começou.
    // O script aluno_eventos.php já envia 'pode_cancelar', mas é bom verificar no backend também.
    if (new DateTime($evento_details['data_inicio']) <= new DateTime()) {
        throw new Exception('Este evento já começou ou terminou. Não é possível cancelar a inscrição.');
    }

    // Realizar o cancelamento da inscrição
    $stmt_delete = $mysqli->prepare("DELETE FROM inscricoes WHERE aluno_id = ? AND evento_id = ?");
    $stmt_delete->bind_param('ii', $aluno_id, $evento_id);
    
    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Inscrição cancelada com sucesso!';
        } else {
            throw new Exception('Nenhuma inscrição encontrada para cancelar ou você não está inscrito neste evento.');
        }
    } else {
        throw new Exception('Erro ao cancelar inscrição: ' . $stmt_delete->error);
    }
    $stmt_delete->close();

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    // http_response_code(400); // Bad Request ou outro código apropriado
}

$mysqli->close();
echo json_encode($response);
?>
