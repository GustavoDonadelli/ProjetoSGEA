<?php
header('Content-Type: application/json');
require __DIR__ . '/conexao/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$codigo = filter_input(INPUT_GET, 'codigo', FILTER_SANITIZE_STRING);

try {
    if (empty($codigo)) {
        throw new Exception('Código de verificação não fornecido');
    }

    $sql = "SELECT 
                u.nome as aluno_nome, 
                e.nome as evento_nome, 
                c.data_emissao,
                c.carga_horaria,
                e.data_inicio,
                e.data_fim,
                coord.nome as coordenador_nome
            FROM certificados c
            JOIN usuarios u ON c.aluno_id = u.id
            JOIN eventos e ON c.evento_id = e.id
            JOIN usuarios coord ON e.coordenador_id = coord.id
            WHERE c.codigo_verificacao = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Certificado não encontrado ou código inválido');
    }
    
    $certificado = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'valido' => true,
        'certificado' => $certificado
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}