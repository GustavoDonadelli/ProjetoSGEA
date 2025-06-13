<?php
session_start();

// Verificação de acesso
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'aluno') {
    // Redireciona para a página de login se não estiver logado ou não for aluno
    header("Location: index.php");
    exit();
}
include('./back-end/conexao/connect.php'); // Adicionar conexão com o banco

$delete_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete_account') {
    if (isset($_POST['confirm_delete_text']) && $_POST['confirm_delete_text'] === 'delete') {
        if (isset($_SESSION['id'])) {
            $user_id = $_SESSION['id'];

            // Excluir o usuário
            $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                // Destruir todas as variáveis de sessão.
                $_SESSION = array();

                // Se é desejado destruir a sessão completamente, apague também o cookie de sessão.
                // Nota: Isso destruirá a sessão, e não apenas os dados de sessão!
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }

                // Finalmente, destruir a sessão.
                session_destroy();

                // Definir mensagem para a página de login e redirecionar
                session_start(); // Iniciar uma nova sessão para a mensagem flash
                $_SESSION['mensagem'] = 'Sua conta foi apagada com sucesso.';
                header("Location: index.php");
                exit();
            } else {
                $delete_message = 'Erro ao apagar a conta: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $delete_message = 'Erro: ID do usuário não encontrado na sessão.';
        }
    } else {
        $delete_message = 'Confirmação incorreta. Digite "delete" para apagar sua conta.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SGEA: Aluno</title>
    <link rel="stylesheet" href="../sgea/front-end/css/style.css" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        /* Estilos para a lista de eventos e certificados com barra de rolagem */
        .event-list, .certificate-list {
            max-height: 400px; /* Altura máxima antes de a rolagem aparecer */
            overflow-y: auto;  /* Adiciona barra de rolagem vertical quando necessário */
            padding-right: 15px; /* Espaço para a barra de rolagem não sobrepor o conteúdo */
            border-radius: 8px;
        }

        /* Estilo para cada item (card) na lista de eventos */
        .event-item {
            background-color: #f8f9fa; /* Um cinza bem claro para o fundo do card */
            border: 1px solid #dee2e6; /* Borda sutil */
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column; /* Organiza o conteúdo em coluna */
            gap: 10px; /* Espaço entre os elementos internos */
        }

        .event-item h3 {
            margin: 0;
            color: #343a40; /* Cor escura para o título */
            font-size: 1.2em;
        }

        .event-details p {
            margin: 0;
            color: #495057; /* Cor para o texto dos detalhes */
            line-height: 1.5;
            word-wrap: break-word; /* Garante que textos longos quebrem a linha */
        }

        .event-item .btn-inscrever {
            align-self: flex-end; /* Alinha o botão à direita */
            padding: 8px 16px;
            background-color: #28a745; /* Cor verde para o botão de inscrição */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .event-item .btn-inscrever:hover {
            background-color: #218838; /* Verde mais escuro no hover */
        }
    </style>
</head>
<body class="aluno-section">
    <header>
        <div class="header-content">
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?></h1>
            <nav>
                <button type="button" id="btnOpenDeleteModal" class="btn-header-action">Apagar Conta</button>
                <a href="../sgea/logout.php" class="sair-link btn-header-action">Sair</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- Container para as caixas -->
        <div class="caixa-container">
            <!-- Caixa de Eventos Disponíveis -->
            <div class="caixa" id="eventos">
                <h2>Eventos Disponíveis</h2>
                <div id="event-list" class="event-list">
                    <!-- Lista de eventos será carregada via JavaScript -->
                    <div class="loading">Carregando eventos...</div>
                </div>
            </div>

            <!-- Caixa de Certificados -->
            <div class="caixa" id="certificados">
                <h2>Meus Certificados</h2>
                <div id="certificate-list" class="certificate-list">
                    <!-- Lista de certificados será carregada via JavaScript -->
                    <div class="loading">Carregando certificados...</div>
                </div>
            </div>
        </div>
    </main>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="../sgea/front-end/js/script.js"></script>

    <!-- Modal de Exclusão de Conta -->
    <div id="deleteAccountModal" class="modal modal-delete-account" style="display: <?php echo !empty($delete_message) ? 'flex' : 'none'; ?>;">
        <div class="modal-content-delete">
            <span class="close-delete-modal-btn fechar-modal">&times;</span>
            <h2>Apagar Conta</h2>
            <p><strong>Atenção:</strong> Esta ação é irreversível e todos os seus dados serão permanentemente apagados.</p>
            <p>Para confirmar a exclusão da sua conta, digite <strong>delete</strong> no campo abaixo e clique em "Confirmar Exclusão".</p>
            
            <?php if (!empty($delete_message)): ?>
                <p style="color: red; margin-bottom: 10px;"><?php echo htmlspecialchars($delete_message); ?></p>
            <?php endif; ?>

            <form method="POST" action="aluno.php" id="deleteAccountForm">
                <input type="hidden" name="action" value="delete_account">
                <div class="input-box-modal-delete">
                    <input type="text" name="confirm_delete_text" placeholder="Digite 'delete' para confirmar" required>
                </div>
                <div class="modal-delete-buttons">
                    <button type="button" class="btn-cancel-delete">Cancelar</button>
                    <button type="submit" class="btn-confirm-delete">Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>