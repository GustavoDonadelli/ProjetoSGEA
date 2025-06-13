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
$codigo = filter_var($data['codigo'], FILTER_SANITIZE_STRING);
$aluno_id = $_SESSION['id'];

try {
    // Verifica o código de presença
    $sql = "SELECT cp.id, cp.evento_id, e.data_inicio, e.data_fim 
            FROM codigos_presenca cp
            JOIN eventos e ON cp.evento_id = e.id
            WHERE cp.codigo = ? 
            AND cp.utilizado = 0
            AND cp.data_geracao >= NOW() - INTERVAL 1 HOUR";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Código inválido, expirado ou já utilizado']);
        exit();
    }
    
    $row = $result->fetch_assoc();
    $evento_id = $row['evento_id'];
    
    // Verifica se o aluno está inscrito (com mensagem mais descritiva)
    $inscricao_sql = "SELECT id FROM inscricoes WHERE evento_id = ? AND aluno_id = ?";
    $inscricao_stmt = $mysqli->prepare($inscricao_sql);
    $inscricao_stmt->bind_param("ii", $evento_id, $aluno_id);
    $inscricao_stmt->execute();
    $inscricao_result = $inscricao_stmt->get_result();
    
    if ($inscricao_result->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Você não está inscrito neste evento. Por favor, inscreva-se primeiro.'
        ]);
        exit();
    }
    
    // Verifica se o aluno já confirmou presença neste evento
    $presenca_sql = "SELECT id FROM presencas WHERE evento_id = ? AND aluno_id = ?";
    $presenca_stmt = $mysqli->prepare($presenca_sql);
    if (!$presenca_stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta de presença: ' . $mysqli->error]);
        exit();
    }
    $presenca_stmt->bind_param("ii", $evento_id, $aluno_id);
    $presenca_stmt->execute();
    $presenca_result = $presenca_stmt->get_result();

    if ($presenca_result->num_rows > 0) {
        // Presença já confirmada anteriormente
        echo json_encode(['success' => false, 'message' => 'Você já confirmou presença neste evento.']);
        if(isset($presenca_stmt)) $presenca_stmt->close();
        exit();
    } else {
        // Presença ainda não confirmada, então insere o novo registro
        $insert_presenca_sql = "INSERT INTO presencas (evento_id, aluno_id, data_presenca, codigo_presenca) VALUES (?, ?, NOW(), ?)";
        $insert_presenca_stmt = $mysqli->prepare($insert_presenca_sql);
        if (!$insert_presenca_stmt) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar inserção de presença: ' . $mysqli->error]);
            exit();
        }
        $insert_presenca_stmt->bind_param("iis", $evento_id, $aluno_id, $codigo);

        if ($insert_presenca_stmt->execute()) {
            // Marca o código de presença (da tabela codigos_presenca) como utilizado
            // $row['id'] é o ID do código da tabela codigos_presenca, obtido na validação do código
            $update_codigo_sql = "UPDATE codigos_presenca SET utilizado = 1 WHERE id = ?";
            $update_codigo_stmt = $mysqli->prepare($update_codigo_sql);
            if ($update_codigo_stmt) {
                $update_codigo_stmt->bind_param("i", $row['id']); // $row['id'] is codigos_presenca.id
                $update_codigo_stmt->execute();
                if(isset($update_codigo_stmt)) $update_codigo_stmt->close();
            } else {
                // Log error, mas continua pois a presença foi confirmada
                error_log('Erro ao preparar update do codigo_presenca: ' . $mysqli->error);
            }
            echo json_encode(['success' => true, 'message' => 'Presença confirmada com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao confirmar presença: ' . $insert_presenca_stmt->error]);
        }
        if(isset($insert_presenca_stmt)) $insert_presenca_stmt->close();
    }
    if(isset($presenca_stmt)) $presenca_stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>