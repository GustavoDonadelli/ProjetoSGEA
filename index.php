<?php
session_start();
// A conexão com o banco de dados será incluída apenas ao processar os formulários.

// Processar CADASTRO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome']) && isset($_POST['email']) && isset($_POST['senha'])) {
    include('./back-end/conexao/connect.php');
    
    // Coletar e sanitizar dados
    $nome = $mysqli->real_escape_string($_POST['nome']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];
    
    // Validações
    $erros = [];
    
    if (empty($nome)) $erros[] = "Nome é obrigatório";
    if (empty($email)) $erros[] = "E-mail é obrigatório";
    if (empty($senha)) $erros[] = "Senha é obrigatória";
    if (strlen($senha) < 6) $erros[] = "Senha deve ter no mínimo 6 caracteres";
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Formato de e-mail inválido";
    }
    
    // Verificar se email já existe
    $check_query = $mysqli->query("SELECT * FROM usuarios WHERE email = '$email'");
    
    if ($check_query->num_rows > 0) {
        $erros[] = "E-mail já cadastrado";
    }
    
    // Se não houver erros, proceder com o cadastro
    if (empty($erros)) {
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo) 
                VALUES ('$nome', '$email', '$senha_hash', 'aluno')";
        
        if ($mysqli->query($sql)) {
            $_SESSION['mensagem'] = 'Cadastro realizado com sucesso!';
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['mensagem'] = 'Erro ao cadastrar: ' . $mysqli->error;
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['mensagem'] = implode("\n", $erros);
        header("Location: index.php");
        exit();
    }
}

// Processar LOGIN
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && !isset($_POST['nome'])) {
    include('./back-end/conexao/connect.php');
    $email = $mysqli->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];
    
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $mysqli->query($sql);
    
    if ($result->num_rows == 1) {
        $usuario = $result->fetch_assoc();
        
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['id'] = $usuario['id'];
            $_SESSION['nome'] = $usuario['nome'];
            $_SESSION['tipo'] = $usuario['tipo'];
            
            // Redirecionar conforme o tipo de usuário
            switch ($usuario['tipo']) {
                case 'aluno':
                    header("Location: aluno.php");
                    break;
                case 'coordenador':
                    header("Location: coordenador.php");
                    break;
                case 'diretor':
                    header("Location: diretor.php");
                    break;
                default:
                    header("Location: index.php");
            }
            exit();
        } else {
            $_SESSION['mensagem'] = 'Senha incorreta. Se você alterou sua senha recentemente, certifique-se de estar usando a nova.';
            header("Location: index.php");
            exit();
        }
    } else {
        $_SESSION['mensagem'] = 'E-mail não encontrado';
        header("Location: index.php");
        exit();
    }
}

// Exibir mensagens flash
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGEA: Login</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../sgea/front-end/css/style.css">
</head>
<body>
    <?php if (!empty($mensagem)): ?>
        <script>alert('<?= addslashes($mensagem) ?>');</script>
    <?php endif; ?>

    <div class="container">
        <!-- Formulário de LOGIN -->
        <div class="form-box login">
            <form method="POST">
                <h1>Login</h1>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="senha" placeholder="Senha" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
            <div class="forgot-link">
                    <a href="esqueci_senha.php">Esqueceu sua senha?</a>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <!-- Formulário de CADASTRO -->
        <div class="form-box register">
            <form method="POST">
                <h1>Cadastre-se</h1>
                <div class="input-box">
                    <input type="text" name="nome" placeholder="Nome completo" required>
                    <i class="bx bxs-user"></i>
                </div>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Email" required>
                    <i class="bx bxs-envelope"></i>
                </div>
                <div class="input-box">
                    <input type="password" name="senha" placeholder="Senha" required>
                    <i class="bx bxs-lock-alt"></i>
                </div>
                <button type="submit" class="btn">Cadastrar</button>
            </form>
        </div>

        <div class="toggle-box">
            <div class="toggle-panel toggle-left">
                <h1>Seja bem-vindo!</h1>
                <p>Não possui uma conta?</p>
                <button class="btn register-btn">Cadastre-se</button>
            </div>
            <div class="toggle-panel toggle-right">
                <h1>Bem-vindo de volta!</h1>
                <p>Já possui uma conta?</p>
                <button class="btn login-btn">Faça o login</button>
            </div>
        </div>
    </div>

    <script src="../sgea/front-end/js/script.js"></script>

</body>
</html>