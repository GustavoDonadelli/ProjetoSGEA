<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SESSION['tipo'] != 'diretor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

try {
    $sql = "SELECT id, nome, email 
            FROM usuarios 
            WHERE tipo = 'coordenador'
            ORDER BY nome ASC";
    
    $result = $mysqli->query($sql);
    $coordenadores = [];
    
    while ($row = $result->fetch_assoc()) {
        $coordenadores[] = $row;
    }
    
    echo json_encode($coordenadores);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$mysqli->close();
?>