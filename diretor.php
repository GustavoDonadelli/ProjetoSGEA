<?php
session_start();

// Verificação de acesso
if (!isset($_SESSION['id']) || $_SESSION['tipo'] !== 'diretor') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta
      name="viewport"
      content="width=device-width, initial-scale=1.0"
    />
    <title>SGEA: Diretor</title>
    <link
      rel="stylesheet"
      href="../sgea/front-end/css/style.css"
    />
  </head>
  <body>
    <header>
      <div class="header-content">
        <h1>Bem-vindo, <?php echo htmlspecialchars($_SESSION['nome']); ?>!</h1>
        <nav>
          <a
            href="../sgea/logout.php"
            class="sair-link"
            >Sair</a
          >
        </nav>
      </div>
    </header>

    <main>
      <!-- Container para as caixas lado a lado -->
      <div class="caixa-container">
        <!-- Caixa de Cadastro de Coordenador -->
        <div class="caixa">
          <section id="cadastrar-coordenador">
            <h2>Cadastrar Novo Coordenador</h2>
            <form id="coordenador-form">
              <input
                type="text"
                placeholder="Nome"
                required
              />
              <input
                type="email"
                placeholder="Email"
                required
              />
              <input
                type="password"
                placeholder="Senha"
                required
              />
              <button
                type="submit"
                class="btn"
              >
                Cadastrar
              </button>
            </form>
          </section>
        </div>

        <!-- Caixa de Lista de Coordenadores -->
        <div class="caixa">
          <section id="lista-coordenadores">
            <h2>Lista de Coordenadores</h2>
            <div
              id="coordenador-list"
              class="coordenador-list"
            >
              <!-- Lista de coordenadores cadastrados -->
            </div>
          </section>
        </div>
      </div>

      <!-- Container para as caixas lado a lado -->
      <div class="caixa-container">
        <!-- Caixa de Lista de Eventos Criados -->
        <div class="caixa">
          <section id="lista-eventos">
            <h2>Eventos Criados</h2>
            <div class="event-list">
              <!-- Lista de eventos criados -->
            </div>
          </section>
        </div>

        <!-- Caixa de Lista de Presença de Alunos (Adaptada) -->
        <div class="caixa" id="caixa-lista-presenca" style="display: none;">
          <h2 id="presenca-event-name">Lista de Presença do Evento</h2> <!-- Título será atualizado via JS -->
          <div id="presenca-list-content">
            <!-- Conteúdo da lista de presença será inserido aqui pelo JavaScript -->
          </div>
        </div>
      </div>
    </main>

    <script src="../sgea/front-end/js/script.js"></script>
  </body>
</html>
