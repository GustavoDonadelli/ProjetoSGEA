<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SESSION['tipo'] != 'coordenador') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

try {
    $coordenador_id = $_SESSION['id'];
    $sql = "SELECT id, nome, descricao, data_inicio, data_fim, local 
            FROM eventos 
            WHERE coordenador_id = ?
            ORDER BY data_inicio DESC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $coordenador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $eventos = [];
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
    
    echo json_encode($eventos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>