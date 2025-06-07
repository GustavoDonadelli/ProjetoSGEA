<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

$evento_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$evento_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do evento não fornecido']);
    exit();
}

try {
    // Verifica permissões
    $sql = "SELECT id, nome, descricao, data_inicio, data_fim, local, coordenador_id 
            FROM eventos 
            WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Evento não encontrado']);
        exit();
    }
    
    $evento = $result->fetch_assoc();

    // Buscar o código de presença mais recente para este evento
    $sql_codigo = "SELECT codigo FROM codigos_presenca WHERE evento_id = ? ORDER BY data_geracao DESC LIMIT 1";
    $stmt_codigo = $mysqli->prepare($sql_codigo);
    if ($stmt_codigo) {
        $stmt_codigo->bind_param("i", $evento_id);
        $stmt_codigo->execute();
        $result_codigo = $stmt_codigo->get_result();
        if ($result_codigo->num_rows > 0) {
            $codigo_data = $result_codigo->fetch_assoc();
            $evento['codigo_presenca'] = $codigo_data['codigo'];
        } else {
            $evento['codigo_presenca'] = null; // Ou 'Nenhum', se preferir
        }
        $stmt_codigo->close();
    } else {
        // Falha ao preparar a query do código, logar ou tratar como preferir
        error_log('Erro ao preparar query para buscar código de presença em obter_evento.php: ' . $mysqli->error);
        $evento['codigo_presenca'] = null;
    }
    
    // Verifica se o usuário tem permissão para ver o evento
    if ($_SESSION['tipo'] == 'coordenador' && $evento['coordenador_id'] != $_SESSION['id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Acesso negado']);
        exit();
    }
    
    echo json_encode($evento);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>