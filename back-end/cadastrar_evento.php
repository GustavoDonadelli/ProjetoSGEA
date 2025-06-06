<?php
// Ativa exibição de erros para debug (remova em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Deve ser a PRIMEIRA linha do script
header('Content-Type: application/json');

// Verifica se há saída antes do header
if (ob_get_length()) ob_clean();

session_start();

// Verifica método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Método não permitido']));
}

// Verifica autenticação
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Acesso não autorizado']));
}

try {
    // Obtém os dados JSON
    $json = file_get_contents('php://input');
    if ($json === false) {
        throw new Exception('Erro ao ler dados da requisição', 400);
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Dados inválidos', 400);
    }

    // Validação dos campos
    $required = ['nome', 'local', 'data_inicio', 'data_fim'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("O campo {$field} é obrigatório", 400);
        }
    }

    // Conexão com o banco
    require __DIR__ . '/conexao/connect.php';
    
    // Prepara os dados
    $nome = $mysqli->real_escape_string($data['nome']);
    $descricao = !empty($data['descricao']) ? $mysqli->real_escape_string($data['descricao']) : null;
    $local = $mysqli->real_escape_string($data['local']);
    $coordenador_id = $_SESSION['id'];
    
    // Formata datas
    $data_inicio = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $data['data_inicio'])));
    $data_fim = date('Y-m-d H:i:s', strtotime(str_replace('T', ' ', $data['data_fim'])));
    
    // Valida datas
    if (strtotime($data_fim) <= strtotime($data_inicio)) {
        throw new Exception('Data de término deve ser posterior à data de início', 400);
    }

    // Insere evento
    $sql = "INSERT INTO eventos (nome, descricao, data_inicio, data_fim, local, coordenador_id) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $mysqli->error, 500);
    }

    $stmt->bind_param("sssssi", $nome, $descricao, $data_inicio, $data_fim, $local, $coordenador_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar a query: ' . $stmt->error, 500);
    }

    // Gera código de presença
    $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $sql_codigo = "INSERT INTO codigos_presenca (evento_id, codigo, data_geracao) VALUES (?, ?, NOW())";
    $stmt_codigo = $mysqli->prepare($sql_codigo);
    $stmt_codigo->bind_param("is", $stmt->insert_id, $codigo);
    
    $response = [
        'success' => true,
        'message' => 'Evento cadastrado com sucesso',
        'event_id' => $stmt->insert_id
    ];

    if ($stmt_codigo->execute()) {
        $response['codigo_presenca'] = $codigo;
    } else {
        $response['warning'] = 'Evento criado, mas o código de presença não foi gerado';
    }

    // Limpa buffer e envia resposta
    if (ob_get_length()) ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    // Limpa buffer antes do erro
    if (ob_get_length()) ob_clean();
    
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_codigo)) $stmt_codigo->close();
    if (isset($mysqli)) $mysqli->close();
}