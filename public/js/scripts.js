document.addEventListener('DOMContentLoaded', () => {
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
        document.getElementById('username').textContent = user.name;
    }
});

// Exemplo de funcionalidades (simulação)
function inscreverEvento() {
    alert('Inscrição realizada com sucesso!');
  }
  
  function cancelarInscricao() {
    alert('Inscrição cancelada!');
  }
  
  function criarEvento() {
    const nome = document.getElementById('nome-evento').value;
    const data = document.getElementById('data-evento').value;
    alert(`Evento "${nome}" criado para a data ${data}`);
  }
  
  function editarEvento() {
    alert('Evento editado!');
  }
  
  function excluirEvento() {
    alert('Evento excluído!');
  }
  
  function bloquearUsuario() {
    alert('Usuário bloqueado!');
  }
  
  function desbloquearUsuario() {
    alert('Usuário desbloqueado!');
  }
  
  function gerarRelatorio() {
    alert('Relatório gerado!');
  }

  // Exibir nome do usuário
document.getElementById('username').textContent = localStorage.getItem('username');

// Função de Logout
function logout() {
    fetch('/logout')
        .then(() => {
            localStorage.removeItem('user');
            window.location.href = '/login.html';
        });
}

// Função para salvar nome do usuário após login (adicione no login)
// No seu formulário de login:
localStorage.setItem('username', 'Gustavo');

// Carregar nome do usuário
document.addEventListener('DOMContentLoaded', () => {
    const username = localStorage.getItem('username');
    if (username) {
        document.getElementById('username').textContent = username;
    }
});

// Logout
function logout() {
    fetch('/logout')
        .then(() => {
            localStorage.removeItem('username');
            window.location.href = '/login.html';
        });
}

// Salvar nome após login
localStorage.setItem('username', 'Gustavo');