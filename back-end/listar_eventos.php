<?php
include('./conexao/connect.php');
header('Content-Type: application/json');

try {
    $sql = "SELECT e.id, e.nome, e.descricao, e.data_inicio, e.data_fim, e.local, 
                   u.nome AS coordenador_nome
            FROM eventos e
            JOIN usuarios u ON e.coordenador_id = u.id
            WHERE e.data_inicio >= NOW() - INTERVAL 1 DAY
            ORDER BY e.data_inicio ASC";
    
    $result = $mysqli->query($sql);
    $eventos = [];
    
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
    
    echo json_encode($eventos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$mysqli->close();
?>