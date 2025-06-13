<?php
require_once './conexao/connect.php'; // Ajuste o caminho se necessário
require_once __DIR__ . '/../auth/sessao.php'; // Para verificar a sessão e tipo de usuário, se aplicável

header('Content-Type: application/json');

// // Validação básica de sessão (descomente e ajuste conforme necessário)
// if (!isset($_SESSION['id']) || ($_SESSION['tipo'] !== 'coordenador' && $_SESSION['tipo'] !== 'diretor')) {
//     echo json_encode(['error' => 'Acesso não autorizado.']);
//     exit;
// }

if (!isset($_GET['evento_id'])) {
    echo json_encode(['error' => 'ID do evento não fornecido.']);
    exit;
}

$evento_id = intval($_GET['evento_id']);

if ($evento_id <= 0) {
    echo json_encode(['error' => 'ID do evento inválido.']);
    exit;
}

$sql = "SELECT u.nome AS nome_aluno, u.email AS email_aluno, 'confirmado' AS status_presenca, p.data_presenca
        FROM presencas p
        JOIN usuarios u ON p.aluno_id = u.id
        WHERE p.evento_id = ?
        ORDER BY u.nome ASC";

try {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $mysqli->error);
    }

    $stmt->bind_param('i', $evento_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $presencas_confirmadas = [];
    while ($row = $result->fetch_assoc()) {
        $presencas_confirmadas[] = $row;
    }

    $stmt->close();
    // $mysqli->close(); // Comente ou remova se a conexão for gerenciada globalmente

    echo json_encode($presencas_confirmadas);

} catch (Exception $e) {
    // Em ambiente de produção, logue o erro em vez de expô-lo diretamente
    error_log('Erro ao listar presenças confirmadas: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro ao buscar lista de presença. Detalhes: ' . $e->getMessage()]);
}
?>
