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

if (!isset($data['evento_id']) || !isset($data['codigo_presenca'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos. Certifique-se de preencher o código de presença.']);
    exit;
}
$evento_id = $data['evento_id'];
$codigo_presenca = $data['codigo_presenca'];

try {
    $mysqli->begin_transaction();

    // 1. Verificar se o aluno está inscrito no evento
    $check_inscricao_stmt = $mysqli->prepare("SELECT id FROM inscricoes WHERE aluno_id = ? AND evento_id = ?");
    $check_inscricao_stmt->bind_param('ii', $aluno_id, $evento_id);
    $check_inscricao_stmt->execute();
    $inscricao_result = $check_inscricao_stmt->get_result();
    if ($inscricao_result->num_rows === 0) {
        throw new Exception('Você não está inscrito neste evento.');
    }
    $check_inscricao_stmt->close();

    // 2. Verificar se a presença já foi confirmada
    $check_presenca_stmt = $mysqli->prepare("SELECT id FROM presencas WHERE aluno_id = ? AND evento_id = ?");
    $check_presenca_stmt->bind_param('ii', $aluno_id, $evento_id);
    $check_presenca_stmt->execute();
    $presenca_result = $check_presenca_stmt->get_result();
    if ($presenca_result->num_rows > 0) {
        throw new Exception('Sua presença já foi confirmada anteriormente.');
    }
    $check_presenca_stmt->close();

    // 3. Verificar se o código de presença é válido para este evento
    $check_codigo_stmt = $mysqli->prepare("SELECT id FROM codigos_presenca WHERE evento_id = ? AND codigo = ? AND utilizado = 0");
    $check_codigo_stmt->bind_param('is', $evento_id, $codigo_presenca);
    $check_codigo_stmt->execute();
    $codigo_result = $check_codigo_stmt->get_result();
    if ($codigo_result->num_rows === 0) {
        throw new Exception('Código de presença inválido ou já utilizado.');
    }
    $codigo_data = $codigo_result->fetch_assoc();
    $codigo_id = $codigo_data['id'];
    $check_codigo_stmt->close();

    // 4. Inserir a presença e marcar o código como utilizado
    $mysqli->begin_transaction();
    
    // Inserir presença
    $insert_stmt = $mysqli->prepare("INSERT INTO presencas (aluno_id, evento_id, data_presenca, codigo_presenca_id) VALUES (?, ?, NOW(), ?)");
    $insert_stmt->bind_param('iii', $aluno_id, $evento_id, $codigo_id);
    
    if ($insert_stmt->execute()) {
        if ($insert_stmt->affected_rows > 0) {
            // Marcar código como utilizado
            $update_codigo = $mysqli->prepare("UPDATE codigos_presenca SET utilizado = 1, data_utilizacao = NOW(), utilizado_por = ? WHERE id = ?");
            $update_codigo->bind_param('ii', $aluno_id, $codigo_id);
            
            if ($update_codigo->execute()) {
                $mysqli->commit();
                echo json_encode(['success' => true, 'message' => 'Presença confirmada com sucesso!']);
            } else {
                throw new Exception('Erro ao atualizar o código de presença.');
            }
            $update_codigo->close();
        } else {
            throw new Exception('Não foi possível confirmar a presença.');
        }
    } else {
        throw new Exception('Erro ao executar a confirmação de presença.');
    }
    $insert_stmt->close();

} catch (Exception $e) {
    $mysqli->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
?>
