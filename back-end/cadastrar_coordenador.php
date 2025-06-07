<?php
include('./conexao/connect.php');
include('./conexao/protect.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

if ($_SESSION['tipo'] != 'diretor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$nome = filter_var($data['nome'], FILTER_SANITIZE_STRING);
$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$senha = $data['senha'];

try {
    // Validações
    if (empty($nome) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'E-mail inválido']);
        exit();
    }
    
    if (strlen($senha) < 6) {
        echo json_encode(['success' => false, 'message' => 'Senha deve ter no mínimo 6 caracteres']);
        exit();
    }
    
    // Verifica se email já existe
    $check_sql = "SELECT id FROM usuarios WHERE email = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'E-mail já cadastrado']);
        exit();
    }
    
    // Cria o hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Insere o coordenador
    $sql = "INSERT INTO usuarios (nome, email, senha, tipo) 
            VALUES (?, ?, ?, 'coordenador')";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $nome, $email, $senha_hash);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar coordenador']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$mysqli->close();
?>