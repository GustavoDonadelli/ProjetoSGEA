<?php
session_start();
include('./back-end/conexao/connect.php');

// Inicializa array para mensagens
$_SESSION['cadastro_mensagem'] = [
    'tipo' => '',
    'texto' => ''
];

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'Método de requisição inválido.'
    ];
    header("Location: index.php");
    exit();
}

// Coleta e sanitiza os dados do formulário
$nome = trim($mysqli->real_escape_string($_POST['nome'] ?? ''));
$email = trim($mysqli->real_escape_string($_POST['email'] ?? ''));
$senha = trim($_POST['senha'] ?? '');
$matricula = trim($mysqli->real_escape_string($_POST['matricula'] ?? ''));

// Validações básicas
if (empty($nome) || empty($email) || empty($senha) || empty($matricula)) {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'Todos os campos são obrigatórios.'
    ];
    header("Location: index.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'Formato de e-mail inválido.'
    ];
    header("Location: index.php");
    exit();
}

if (strlen($senha) < 6) {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'A senha deve ter no mínimo 6 caracteres.'
    ];
    header("Location: index.php");
    exit();
}

// Verifica se email ou matrícula já existem
$sql_check = "SELECT * FROM usuarios WHERE email = ? OR 
             (tipo = 'aluno' AND JSON_EXTRACT(dados_extras, '$.matricula') = ?)";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ss", $email, $matricula);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'E-mail ou matrícula já cadastrados.'
    ];
    header("Location: index.php");
    exit();
}

// Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Prepara os dados extras (matrícula)
$dados_extras = json_encode(['matricula' => $matricula]);

// Insere o novo usuário
$sql_insert = "INSERT INTO usuarios (nome, email, senha, tipo, dados_extras) 
               VALUES (?, ?, ?, 'aluno', ?)";
$stmt_insert = $mysqli->prepare($sql_insert);
$stmt_insert->bind_param("ssss", $nome, $email, $senha_hash, $dados_extras);

if ($stmt_insert->execute()) {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'sucesso',
        'texto' => 'Cadastro realizado com sucesso! Faça login para continuar.'
    ];
} else {
    $_SESSION['cadastro_mensagem'] = [
        'tipo' => 'erro',
        'texto' => 'Erro ao cadastrar: ' . $mysqli->error
    ];
}

// Fecha as conexões
$stmt_check->close();
$stmt_insert->close();
$mysqli->close();

// Redireciona de volta para index.php
header("Location: index.php");
exit();
?>