<?php
session_start();
include('./conexao/connect.php');
header('Content-Type: application/json');

try {
    $aluno_id = $_SESSION['id'] ?? null;

    // Query principal para buscar eventos
    // Adicionamos um LEFT JOIN para a tabela inscricoes para verificar se o aluno está inscrito
    // E um campo (subquery) para verificar se a presença foi confirmada
    $sql = "SELECT 
                e.id, e.nome, e.descricao, e.data_inicio, e.data_fim, e.local, 
                u.nome AS coordenador_nome,
                IF(i.aluno_id IS NOT NULL, TRUE, FALSE) AS inscrito,
                EXISTS(SELECT 1 FROM presencas p WHERE p.evento_id = e.id AND p.aluno_id = ?) AS presenca_confirmada
            FROM eventos e
            JOIN usuarios u ON e.coordenador_id = u.id
            LEFT JOIN inscricoes i ON i.evento_id = e.id AND i.aluno_id = ?
            WHERE e.data_inicio >= NOW() - INTERVAL 1 DAY
            ORDER BY e.data_inicio ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro ao preparar a query de listar eventos: " . $mysqli->error);
    }
    // Bind dos parâmetros aluno_id para a subquery de presenças e para o LEFT JOIN de inscrições
    $stmt->bind_param("ii", $aluno_id, $aluno_id);
    $stmt->execute();
    $result = $stmt->get_result();
    

    $eventos = [];
    
    while ($row = $result->fetch_assoc()) {
        // Converte os campos booleanos para o tipo correto em PHP para o json_encode
        $row['inscrito'] = (bool)$row['inscrito'];
        $row['presenca_confirmada'] = (bool)$row['presenca_confirmada'];
        $eventos[] = $row;
    }
    
    echo json_encode($eventos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
?>