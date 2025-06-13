<?php
header('Content-Type: application/json');
include_once './conexao/connect.php';
include_once './conexao/protect.php'; // Para garantir que apenas usuários logados acessem, ajuste conforme necessário

// Verificar se o usuário tem permissão de diretor (opcional, mas recomendado)
// if (!isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'diretor') {
//     http_response_code(403);
//     echo json_encode(['error' => 'Acesso negado. Somente diretores podem visualizar todos os eventos.']);
//     exit;
// }

try {
    $sql = "SELECT 
                e.id_evento, 
                e.nome_evento, 
                e.data_inicio, 
                e.data_fim, 
                e.local_evento, 
                e.capacidade_maxima, 
                e.descricao,
                e.id_coordenador,
                u.nome AS nome_coordenador
            FROM eventos e
            LEFT JOIN usuarios u ON e.id_coordenador = u.id_usuario
            ORDER BY e.data_inicio DESC";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $mysqli->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $eventos = [];

    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }

    echo json_encode($eventos);

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar eventos: ' . $e->getMessage()]);
}
?>
