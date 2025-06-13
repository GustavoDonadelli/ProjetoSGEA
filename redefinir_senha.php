<?php
session_start();
include('./back-end/conexao/connect.php');

// Lógica para processar a redefinição de senha será adicionada aqui

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nova_senha']) && isset($_POST['confirmar_senha'])) {
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $email_usuario = $_SESSION['email_para_redefinir']; // Supondo que o email foi guardado na sessão

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $_SESSION['mensagem_redefinir'] = "Todos os campos são obrigatórios.";
    } elseif ($nova_senha !== $confirmar_senha) {
        $_SESSION['mensagem_redefinir'] = "As senhas não coincidem.";
    } elseif (strlen($nova_senha) < 6) {
        $_SESSION['mensagem_redefinir'] = "A nova senha deve ter no mínimo 6 caracteres.";
    } else {
        // Hash da nova senha
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

        // Atualizar a senha no banco de dados
        // Esta query é um exemplo e precisa ser ajustada para sua tabela e coluna de email/id
        // É crucial garantir que você está atualizando a senha para o usuário correto.
        // Normalmente, você teria um token de redefinição de senha ou o email do usuário.
        // Por simplicidade, estou assumindo que o email do usuário está na sessão.
        // Você precisará de um passo anterior para colocar o email na sessão (ex: uma página 'esqueci_minha_senha.php' que pede o email).

        // ***** IMPORTANTE: Adicionar lógica para identificar o usuário (ex: por email ou token) *****
        // Por agora, vamos simular a atualização para um email específico ou ID.
        // Se você não tiver o email do usuário aqui, esta parte não funcionará.
        if (!empty($email_usuario)) {
            $stmt = $mysqli->prepare("UPDATE usuarios SET senha = ? WHERE email = ?");
            $stmt->bind_param("ss", $senha_hash, $email_usuario);

            if ($stmt->execute()) {
                $_SESSION['mensagem_redefinir'] = "Senha redefinida com sucesso! Você já pode fazer login com a nova senha.";
                unset($_SESSION['email_para_redefinir']); // Limpar o email da sessão
                // Redirecionar para a página de login ou exibir mensagem de sucesso
                // header("Location: index.php"); 
                // exit();
            } else {
                $_SESSION['mensagem_redefinir'] = "Erro ao redefinir a senha: " . $stmt->error;
            }
            $stmt->close();
        } else {
             $_SESSION['mensagem_redefinir'] = "Erro: Não foi possível identificar o usuário para redefinir a senha.";
        }
    }
    header("Location: redefinir_senha.php"); // Recarregar a página para mostrar a mensagem
    exit();
}

// Exibir mensagens flash
if (isset($_SESSION['mensagem_redefinir'])) {
    $mensagem = $_SESSION['mensagem_redefinir'];
    unset($_SESSION['mensagem_redefinir']);
}

// Simulação: Guardar email na sessão para teste (isso viria de um passo anterior)
// Em um fluxo real, o usuário digitaria o email em uma página 'esqueci_senha.php'
// e, se o email existir, ele seria redirecionado para esta página com um token ou o email na sessão.
// Para este exemplo, vamos assumir que o email 'teste@example.com' precisa redefinir a senha.
// $_SESSION['email_para_redefinir'] = 'teste@example.com'; 
// Você precisará implementar a lógica para obter o email do usuário corretamente.

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGEA: Redefinir Senha</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../sgea/front-end/css/style.css"> 
</head>
<body>
    <?php if (!empty($mensagem)): ?>
        <script>alert('<?= addslashes($mensagem) ?>');</script>
    <?php endif; ?>

    <div class="container-single-form">
        <div class="form-box"> 
            <form method="POST" action="redefinir_senha.php">
                <h1>Redefinir Senha</h1>
                
                <?php if(isset($_SESSION['email_para_redefinir'])): ?>
                    <p>Redefinindo senha para: <strong><?= htmlspecialchars($_SESSION['email_para_redefinir']) ?></strong></p>
                <?php else: ?>
                    <p style="color: red;">Para redefinir a senha, primeiro você precisa solicitar a redefinição através da página "Esqueci minha senha" e fornecer seu e-mail.</p>
                    <?php /* O formulário não será mostrado se não houver email na sessão */ ?>
                <?php endif; ?> 

                <?php if(isset($_SESSION['email_para_redefinir'])): ?>
                    <div class="input-box">
                        <input type="password" name="nova_senha" placeholder="Nova Senha" required>
                        <i class="bx bxs-lock-alt"></i>
                    </div>
                    <div class="input-box">
                        <input type="password" name="confirmar_senha" placeholder="Confirmar Nova Senha" required>
                        <i class="bx bxs-lock-alt"></i>
                    </div>
                    <button type="submit" class="btn">Redefinir Senha</button>
                <?php endif; ?>
                
                <div>
                    <a href="index.php" class="return-link">Voltar para o Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="../sgea/front-end/js/script.js"></script> 
</body>
</html>
