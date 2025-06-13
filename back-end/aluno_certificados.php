<?php
include_once __DIR__ . '/../auth/sessao.php'; // Garante que a sessão seja iniciada e o usuário esteja logado.
include_once __DIR__ . '/conexao/connect.php'; // Apenas para a conexão com o banco de dados.

header('Content-Type: application/json');

// O sessao.php já garante que $_SESSION['id'] existe.
// Agora, verificamos se o tipo de usuário é 'aluno'.
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'aluno') {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas alunos podem visualizar certificados.']);
    exit();
}

$aluno_id = $_SESSION['id'];
$response = ['success' => true, 'certificados' => []];

try {
    // Consulta para buscar certificados do aluno
    // Esta consulta assume que existe uma tabela 'certificados' ou uma lógica para determinar quem tem certificado
    // Por exemplo, se a presença confirmada em um evento que JÁ OCORREU E TEVE CERTIFICADOS EMITIDOS garante um certificado.
    // Vamos simplificar: buscar eventos com presença confirmada que já terminaram.
    // A emissão real do certificado (geração de PDF, etc.) é outra etapa.

    $sql = "SELECT 
            evt.id AS id_evento, 
            evt.nome AS nome_evento, 
            evt.data_fim, 
            prs.data_presenca
          FROM 
            eventos AS evt
          JOIN 
            presencas AS prs ON evt.id = prs.evento_id
          WHERE 
            prs.aluno_id = ? AND evt.data_fim < CURDATE()
          ORDER BY 
            evt.data_fim DESC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da consulta de certificados: ' . $mysqli->error);
    }
    $stmt->bind_param('i', $aluno_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($certificado = $result->fetch_assoc()) {
        // Adicionar informações relevantes para o certificado
        $certificado['url_visualizar'] = './gerar_certificado.php?evento_id=' . $certificado['id_evento'] . '&aluno_id=' . $aluno_id; // Exemplo de URL
        $response['certificados'][] = $certificado;
    }
    $stmt->close();

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
    http_response_code(500);
}

$mysqli->close();
echo json_encode($response);
?>
