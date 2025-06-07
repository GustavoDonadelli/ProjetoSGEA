<?php
// Ativa exibição de erros para debug (remova em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Deve ser a PRIMEIRA linha do script
header('Content-Type: application/json');

// Verifica se há saída antes do header
if (ob_get_length()) ob_clean();

session_start();
error_log('cadastrar_evento.php: Script iniciado. Request Method: ' . $_SERVER['REQUEST_METHOD'] . '. Timestamp: ' . microtime(true));
$request_data_log = file_get_contents('php://input');
error_log('cadastrar_evento.php: Dados recebidos (raw): ' . $request_data_log);

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
    error_log('cadastrar_evento.php: Antes de conectar ao BD. Timestamp: ' . microtime(true));
    require __DIR__ . '/conexao/connect.php';
    error_log('cadastrar_evento.php: Após conectar ao BD. Timestamp: ' . microtime(true));
    
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
    
    error_log('cadastrar_evento.php: Antes de preparar a query de inserção do evento. SQL: ' . $sql . '. Timestamp: ' . microtime(true));
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Erro na preparação da query: ' . $mysqli->error, 500);
    }

    $stmt->bind_param("sssssi", $nome, $descricao, $data_inicio, $data_fim, $local, $coordenador_id);
    
    error_log('cadastrar_evento.php: Antes de executar a query de inserção do evento. Timestamp: ' . microtime(true));
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar a query de inserção do evento: ' . $stmt->error, 500);
    }

    $evento_inserido_id = $stmt->insert_id;
    if (empty($evento_inserido_id)) {
        // Isso pode acontecer se a tabela não tiver AUTO_INCREMENT ou se a inserção não gerou um ID.
        throw new Exception('Não foi possível obter o ID do evento recém-criado.', 500);
    }

    // Gera código de presença
    $codigo_presenca_gerado = null;
    try {
        $codigo = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $sql_codigo = "INSERT INTO codigos_presenca (evento_id, codigo, data_geracao) VALUES (?, ?, NOW())";
        error_log('cadastrar_evento.php: Antes de preparar a query do código de presença. SQL: ' . $sql_codigo . '. Timestamp: ' . microtime(true));
        $stmt_codigo = $mysqli->prepare($sql_codigo);
        if (!$stmt_codigo) {
            throw new Exception('Erro na preparação da query do código de presença: ' . $mysqli->error, 500);
        }
        $stmt_codigo->bind_param("is", $evento_inserido_id, $codigo);
        
        error_log('cadastrar_evento.php: Antes de executar a query do código de presença. Evento ID: ' . $evento_inserido_id . '. Código: ' . $codigo . '. Timestamp: ' . microtime(true));
        if (!$stmt_codigo->execute()) {
            throw new Exception('Erro ao executar a query do código de presença: ' . $stmt_codigo->error, 500);
        }
        $codigo_presenca_gerado = $codigo;
    } catch (Throwable $e_codigo) {
        // Se a geração do código de presença falhar, o evento já foi criado.
        // Registre o erro e informe o usuário, mas considere o evento como criado.
        error_log('Falha ao gerar código de presença para evento ID ' . $evento_inserido_id . ': ' . $e_codigo->getMessage());
        $response_warning = 'Evento criado com sucesso, mas houve um erro ao gerar o código de presença: ' . $e_codigo->getMessage();
    }
    
    $response = [
        'success' => true,
        'message' => 'Evento cadastrado com sucesso!',
        'event_id' => $evento_inserido_id
    ];

    if ($codigo_presenca_gerado) {
        $response['codigo_presenca'] = $codigo_presenca_gerado;
    } elseif (isset($response_warning)) {
        $response['warning'] = $response_warning;
    }

    // Limpa buffer e envia resposta
    if (ob_get_length()) ob_clean();
    error_log('cadastrar_evento.php: Resposta enviada: ' . json_encode($response) . '. Timestamp: ' . microtime(true));
    echo json_encode($response);

} catch (Throwable $e) {
    // Limpa buffer antes do erro
    if (ob_get_length()) ob_clean();
    
    http_response_code($e->getCode() ?: 500);
    error_log('cadastrar_evento.php: Erro capturado: ' . $e->getMessage() . '. Código: ' . $e->getCode() . '. Timestamp: ' . microtime(true));
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_codigo)) $stmt_codigo->close();
    if (isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close();
}