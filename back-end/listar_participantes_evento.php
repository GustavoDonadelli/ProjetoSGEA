<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../auth/sessao.php'; // Gerencia o estado da sessão e autenticação básica
include_once __DIR__ . '/conexao/connect.php'; // Conexão com o banco de dados

// Proteção específica para coordenador/diretor
if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'coordenador' && $_SESSION['tipo'] !== 'diretor')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Esta funcionalidade é permitida apenas para coordenadores ou diretores.']);
    exit;
}

if (!isset($_GET['evento_id']) || !filter_var($_GET['evento_id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID do evento não fornecido ou inválido.']);
    exit;
}

$evento_id = (int)$_GET['evento_id'];
$participantes = [];

try {
    $sql = "SELECT 
                u.nome AS aluno_nome,
                u.email AS aluno_email,
                i.data_inscricao,
                p.id AS presenca_id,
                p.data_presenca
            FROM 
                inscricoes i
            JOIN 
                usuarios u ON i.aluno_id = u.id
            LEFT JOIN 
                presencas p ON i.evento_id = p.evento_id AND i.aluno_id = p.aluno_id
            WHERE 
                i.evento_id = ?
            ORDER BY 
                u.nome ASC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro na preparação da consulta: " . $mysqli->error);
    }

    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $status_participacao = $row['presenca_id'] ? 'Presença Confirmada' : 'Inscrito (Pendente Confirmação)';
        $participantes[] = [
            'aluno_nome' => $row['aluno_nome'],
            'aluno_email' => $row['aluno_email'],
            'data_inscricao' => $row['data_inscricao'] ? date('d/m/Y H:i', strtotime($row['data_inscricao'])) : '-',
            'status_participacao' => $status_participacao,
            'data_presenca' => $row['data_presenca'] ? date('d/m/Y H:i', strtotime($row['data_presenca'])) : '-'
        ];
    }

    $stmt->close();
    echo json_encode(['success' => true, 'participantes' => $participantes]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar participantes: ' . $e->getMessage()]);
}

$mysqli->close();
?>
