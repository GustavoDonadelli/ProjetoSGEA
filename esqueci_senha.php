<?php
session_start();
include('./back-end/conexao/connect.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
    $email = $mysqli->real_escape_string($_POST['email']);

    if (empty($email)) {
        $_SESSION['mensagem_esqueci'] = "O campo e-mail é obrigatório.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem_esqueci'] = "Formato de e-mail inválido.";
    } else {
        // Verificar se o email existe no banco de dados
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Email encontrado, guardar na sessão e redirecionar para redefinir_senha.php
            $_SESSION['email_para_redefinir'] = $email;
            // Poderia gerar um token aqui e enviar por email, mas para simplificar, vamos direto
            header("Location: redefinir_senha.php");
            exit();
        } else {
            $_SESSION['mensagem_esqueci'] = "E-mail não encontrado em nosso sistema.";
        }
        $stmt->close();
    }
    header("Location: esqueci_senha.php"); // Recarregar para mostrar mensagem
    exit();
}

if (isset($_SESSION['mensagem_esqueci'])) {
    $mensagem = $_SESSION['mensagem_esqueci'];
    unset($_SESSION['mensagem_esqueci']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGEA: Esqueci Minha Senha</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../sgea/front-end/css/style.css">
</head>
<body>
    <?php if (!empty($mensagem)): ?>
        <script>alert('<?= addslashes($mensagem) ?>');</script>
    <?php endif; ?>

    <div class="container-single-form">
        <div class="form-box"> 
            <form method="POST" action="esqueci_senha.php">
                <h1>Esqueci Minha Senha</h1>
                <p style="margin-bottom: 15px; text-align: center;">Digite seu e-mail para iniciar o processo de redefinição de senha.</p>
                <div class="input-box">
                    <input type="email" name="email" placeholder="Seu E-mail" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    <i class="bx bxs-envelope"></i>
                </div>
                <button type="submit" class="btn">Enviar</button>
                <div>
                    <a href="index.php" class="return-link">Voltar para o Login</a>
                </div>
            </form>
        </div>
    </div>
    <script src="../sgea/front-end/js/script.js"></script>
</body>
</html>
