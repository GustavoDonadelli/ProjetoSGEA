<?php
// Definir cabeçalhos para JSON e CORS
// Forçar codificação UTF-8
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('auto');
mb_regex_encoding('UTF-8');

// Iniciar o buffer de saída
ob_start();

// Configurar cabeçalhos
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Função para enviar resposta JSON e encerrar o script
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Função para registrar logs
function logMessage($message) {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $logFile = $logDir . '/aluno_eventos.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// Iniciar log de requisição
logMessage("=== NOVA REQUISICAO ===");
logMessage("Sessao: " . print_r($_SESSION, true));
logMessage("POST: " . print_r($_POST, true));
logMessage("GET: " . print_r($_GET, true));

// Incluir arquivos necessários
try {
    include_once './conexao/connect.php';
    include_once '../auth/sessao.php';
} catch (Exception $e) {
    logMessage("Erro ao incluir arquivos: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Erro ao carregar dependências.'], 500);
}

// Verificar sessão
logMessage("Verificando sessao...");
logMessage("Dados da sessao: " . print_r($_SESSION, true));

if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'aluno') {
    $errorMsg = 'Acesso nao autorizado. Sessao: ' . print_r($_SESSION, true);
    logMessage($errorMsg);
    
    // Verificar se os cabeçalhos já foram enviados
    if (headers_sent($filename, $linenum)) {
        logMessage("Cabeçalhos já enviados em $filename na linha $linenum");
    }
    
    // Tentar enviar a resposta de erro
    try {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Acesso nao autorizado.']);
        exit;
    } catch (Exception $e) {
        logMessage("Erro ao enviar resposta de erro: " . $e->getMessage());
        exit(1);
    }
}

logMessage("Sessao valida. ID do aluno: " . $_SESSION['id']);

// Verificar se o ID do aluno é válido
if (!is_numeric($_SESSION['id'])) {
    logMessage("ID do aluno inválido: " . $_SESSION['id']);
    sendJsonResponse(['success' => false, 'message' => 'ID do aluno inválido.'], 400);
}

$aluno_id = (int)$_SESSION['id'];
$response = ['success' => true, 'eventos' => []];

logMessage("Iniciando consulta ao banco de dados...");

try {
    $sql = "SELECT 
                e.id, 
                e.nome, 
                e.data_inicio, 
                e.data_fim, 
                e.local, 
                e.descricao,
                i.id IS NOT NULL AS esta_inscrito,
                p.id IS NOT NULL AS presenca_confirmada
            FROM 
                eventos e
            LEFT JOIN 
                inscricoes i ON e.id = i.evento_id AND i.aluno_id = ?
            LEFT JOIN 
                presencas p ON e.id = p.evento_id AND p.aluno_id = ?
            WHERE 
                e.data_fim >= CURDATE()
            ORDER BY 
                e.data_inicio ASC";

    logMessage("Preparando consulta SQL...");
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        $error = 'Erro na preparacao da consulta: ' . $mysqli->error;
        logMessage($error);
        throw new Exception($error);
    }
    
    logMessage("Consulta preparada com sucesso.");
    logMessage("Executando consulta com aluno_id: $aluno_id");
    
    $stmt->bind_param('ii', $aluno_id, $aluno_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao executar consulta: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception('Erro ao obter resultados: ' . $stmt->error);
    }
    
    logMessage("Consulta executada. Numero de linhas: " . $result->num_rows);
    
    $eventos = [];
    $agora = new DateTime();

    while ($evento = $result->fetch_assoc()) {
        try {
            // Garantir que as datas são válidas
            $data_inicio = new DateTime($evento['data_inicio']);
            $data_fim = new DateTime($evento['data_fim']);
            
            // Define o status principal do evento para o aluno
            $evento['status'] = 'disponivel';
            if ($evento['presenca_confirmada']) {
                $evento['status'] = 'presenca_confirmada';
            } elseif ($evento['esta_inscrito']) {
                $evento['status'] = 'inscrito';
            }

            // Adiciona flags para o frontend saber quais botoes mostrar
            $evento_nao_comecou = $agora < $data_inicio;
            $evento_ativo = $agora >= $data_inicio && $agora <= $data_fim;

            $evento['pode_cancelar_inscricao'] = $evento['esta_inscrito'] && $evento_nao_comecou;
            $evento['pode_confirmar_presenca'] = $evento['esta_inscrito'] && !$evento['presenca_confirmada'] && $evento_ativo;
            $evento['pode_remover_presenca'] = $evento['presenca_confirmada'] && $evento_ativo;

            // Converte os booleanos para true/false para consistencia no JSON
            $evento['esta_inscrito'] = (bool)$evento['esta_inscrito'];
            $evento['presenca_confirmada'] = (bool)$evento['presenca_confirmada'];

            $eventos[] = $evento;
        } catch (Exception $e) {
            logMessage("Erro ao processar evento: " . $e->getMessage());
            continue; // Pula para o próximo evento em caso de erro
        }
    }
    
    $stmt->close();
    $response['eventos'] = $eventos;
    logMessage("Eventos processados com sucesso: " . count($eventos));
    
    // Log do conteúdo de cada evento
    foreach ($eventos as $index => $evento) {
        logMessage("Evento #$index: " . print_r($evento, true));
    }
    
    // Enviar resposta final
    sendJsonResponse($response);
    
} catch (Exception $e) {
    $errorMsg = 'Erro no servidor: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine();
    logMessage($errorMsg);
    logMessage("Stack trace: " . $e->getTraceAsString());
    
    // Limpar buffer de saída
    if (ob_get_length()) ob_clean();
    
    // Enviar resposta de erro
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao processar solicitação.',
        'error' => $e->getMessage()
    ]);
} finally {
    // Fechar conexão com o banco de dados se existir
    if (isset($mysqli)) {
        $mysqli->close();
    }
    
    // Encerrar o script
    exit;
}
?>
