<?php
// Inicia a sessão e inclui a conexão no início do arquivo
session_start();
include(__DIR__ . '/back-end/conexao/connect.php');

// Verificação robusta de acesso
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'coordenador') {
    header("Location: index.php");
    exit();
}

// Verifica se a conexão com o banco foi estabelecida
if ($mysqli->connect_error) {
    die("Erro na conexão com o banco de dados: " . $mysqli->connect_error);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SGEA: Coordenador</title>
    <link rel="stylesheet" href="./front-end/css/style.css" />
</head>
<body class="coordenador-section">
    <header>
        <div class="header-content">
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
            <nav>
                <a href="./logout.php" class="sair-link">Sair</a>
            </nav>
        </div>
    </header>

    <main>
        <div class="caixa-container">
            <!-- Caixa de Cadastro de Eventos -->
            <div class="caixa">
                <section id="cadastrar-evento">
                    <h2>Cadastrar Novo Evento</h2>
                    <div id="form-messages"></div>
                    <form id="event-form">
                        <div class="form-group">
                            <input type="text" id="nome" name="nome" placeholder="Nome do Evento" required />
                        </div>
                        
                        <div class="form-group">
                            <textarea id="descricao" name="descricao" placeholder="Descrição do Evento"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="data_inicio">Data e Hora de Início:</label>
                            <input type="datetime-local" id="data_inicio" name="data_inicio" required />
                        </div>

                        <div class="form-group">
                            <label for="data_fim">Data e Hora de Término:</label>
                            <input type="datetime-local" id="data_fim" name="data_fim" required />
                        </div>

                        <div class="form-group">
                            <input type="text" id="local" name="local" placeholder="Local do Evento" required />
                        </div>

                        <button type="submit" class="btn">Cadastrar Evento</button>
                    </form>
                </section>
            </div>

            <!-- Caixa de Meus Eventos -->
            <div class="caixa">
                <section id="meus-eventos">
                    <h2>Meus Eventos</h2>
                    <div class="event-list">
                        <?php
                        try {
                            $coordenador_id = $_SESSION['id'];
                            $sql = "SELECT e.id, e.nome, e.descricao, e.data_inicio, e.data_fim, e.local, c.codigo as codigo_presenca 
                                    FROM eventos e
                                    LEFT JOIN codigos_presenca c ON e.id = c.evento_id
                                    WHERE e.coordenador_id = ? 
                                    ORDER BY e.data_inicio DESC";
                            $stmt = $mysqli->prepare($sql);
                            $stmt->bind_param("i", $coordenador_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result->num_rows > 0) {
                                while ($evento = $result->fetch_assoc()) {
                                    echo '<div class="event-item" data-id="'.(int)$evento['id'].'">';
                                    echo '<h3>'.htmlspecialchars($evento['nome']).'</h3>';
                                    if (!empty($evento['descricao'])) {
                                        echo '<p class="event-desc">'.htmlspecialchars($evento['descricao']).'</p>';
                                    }
                                    echo '<p><strong>Local:</strong> '.htmlspecialchars($evento['local']).'</p>';
                                    echo '<p><strong>Início:</strong> '.date('d/m/Y H:i', strtotime($evento['data_inicio'])).'</p>';
                                    echo '<p><strong>Término:</strong> '.date('d/m/Y H:i', strtotime($evento['data_fim'])).'</p>';
                                    
                                    // Mostra o código se existir, senão mostra botão para gerar
                                    if (!empty($evento['codigo_presenca'])) {
                                        echo '<p><strong>Código Presença:</strong> '.htmlspecialchars($evento['codigo_presenca']).'</p>';
                                    } else {
                                        echo '<button class="btn-gerar-codigo" data-event-id="'.(int)$evento['id'].'">Gerar Código de Presença</button>';
                                    }
                                    
                                    echo '<div class="event-actions">';
                                    echo '<button class="btn-editar">Editar</button>';
                                    echo '<button class="btn-presenca">Lista de Presença</button>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="no-events">Nenhum evento cadastrado ainda.</p>';
                            }
                            $stmt->close();
                        } catch (Exception $e) {
                            echo '<p class="error">Erro ao carregar eventos: '.htmlspecialchars($e->getMessage()).'</p>';
                        }
                        ?>
                    </div>
                </section>
            </div>
        </div>

        <!-- Modal para Lista de Presença -->
        <div id="modal-presenca" class="modal">
            <div class="modal-content">
                <span class="fechar-modal">&times;</span>
                <h2>Lista de Presença</h2>
                <div class="presenca-list">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </main>
    
    <script src="../sgea/front-end/js/script.js"></script>
</body>
</html>