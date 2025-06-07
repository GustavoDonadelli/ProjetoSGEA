<?php
include('./back-end/conexao/protect.php');
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SGEA: Aluno</title>
    <link rel="stylesheet" href="../sgea/front-end/css/style.css" />
</head>
<body class="aluno-section">
    <header>
        <div class="header-content">
            <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?></h1>
            <nav>
                <a href="#eventos">Eventos</a>
                <a href="#certificados">Certificados</a>
                <a href="../sgea/logout.php" class="sair-link">Sair</a>
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

    <script src="../sgea/front-end/js/script.js"></script>
</body>
</html>