<?php
header('Content-Type: application/json');
require __DIR__ . '/conexao/connect.php';
require __DIR__ . '/conexao/protect.php';

try {
    $sql = "SELECT 
                c.id, 
                e.nome as evento_nome, 
                c.data_emissao, 
                c.link_certificado, 
                c.codigo_verificacao,
                c.carga_horaria
            FROM certificados c
            JOIN eventos e ON c.evento_id = e.id
            WHERE c.aluno_id = ?
            ORDER BY c.data_emissao DESC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $certificados = [];
    while ($row = $result->fetch_assoc()) {
        $certificados[] = [
            'id' => $row['id'],
            'evento_nome' => $row['evento_nome'],
            'data_emissao' => $row['data_emissao'],
            'link_certificado' => $row['link_certificado'],
            'codigo_verificacao' => $row['codigo_verificacao'],
            'carga_horaria' => $row['carga_horaria']
        ];
    }
    
    echo json_encode(['success' => true, 'certificados' => $certificados]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}