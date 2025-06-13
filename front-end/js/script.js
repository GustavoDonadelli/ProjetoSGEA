// =============================================
// FUNÇÕES GERAIS E UTILITÁRIOS
// =============================================

// Funções para manipulação de modais
function abrirModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }
}

function fecharModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Fechar modais ao clicar no botão de fechar ou fora
document.querySelectorAll(".fechar-modal").forEach((btn) => {
  btn.addEventListener("click", function() {
    const modal = this.closest(".modal");
    if (modal) fecharModal(modal.id);
  });
});

document.querySelectorAll(".modal").forEach((modal) => {
  modal.addEventListener("click", (e) => {
    if (e.target === modal) fecharModal(modal.id);
  });
});

//Evento esta ativo?

function eventoEstaAtivo(dataInicio, dataFim) {
    const agora = new Date();
    const inicio = new Date(dataInicio);
    const fim = new Date(dataFim);
    
    return agora >= inicio && agora <= fim;
}

// Funções auxiliares de formatação
function formatarData(dataString) {
  if (!dataString) return '';
  const data = new Date(dataString);
  return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function formatarDataHora(dataString) {
  if (!dataString) return '';
  const data = new Date(dataString);
  return data.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// =============================================
// FUNÇÕES AJAX GENÉRICAS
// =============================================

/**
 * Função AJAX genérica para requisições HTTP
 * @param {string} url - Endpoint da API
 * @param {string} method - Método HTTP (GET, POST, PUT, DELETE)
 * @param {object} data - Dados a serem enviados (opcional)
 * @returns {Promise} - Promise com a resposta da requisição
 */

async function fetchAPI(url, method = 'GET', data = null) {
  const config = {
    method,
    headers: {
      'Content-Type': 'application/json',
    },
    credentials: 'include'
  };

  if (data) {
    config.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(url, config);
    
    // Verifica se a resposta é JSON
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      const text = await response.text();
      throw new Error(`Resposta inválida do servidor: ${text.substring(0, 100)}`);
    }
    
    const result = await response.json();
    
    if (!response.ok) {
      throw new Error(result.message || `HTTP error! status: ${response.status}`);
    }
    
    return result;
    
  } catch (error) {
    console.error(`Erro na requisição ${method} para ${url}:`, error);
    throw new Error(error.message || 'Erro na comunicação com o servidor');
  }
}

/**
 * Manipulador de formulários com AJAX
 * @param {string} formId - ID do formulário
 * @param {string} endpoint - URL do endpoint
 * @param {function} onSuccess - Callback para sucesso
 * @param {function} onError - Callback para erro (opcional)
 */

function setupFormAJAX(formId, endpoint, onSuccess, onError = null) {
  const form = document.getElementById(formId);
  if (!form) return;
  // Verifica se o formulário já foi inicializado por esta função
  if (form.dataset.formAjaxInitialized) return;
  form.dataset.formAjaxInitialized = 'true';

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    const messageDiv = document.getElementById('form-messages') || createMessageDiv(form);

    try {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner"></span> Processando...';

      // Coleta dados do formulário
      const formData = {};
      new FormData(form).forEach((value, key) => formData[key] = value);

      // Debug: mostra dados sendo enviados
      console.log('Enviando para', endpoint, 'dados:', formData);

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      });

      // Debug: mostra resposta bruta
      console.log('Resposta recebida:', response);

      const text = await response.text();
      
      // Debug: mostra conteúdo da resposta
      console.log('Conteúdo da resposta:', text);

      let result;
      try {
        result = JSON.parse(text);
      } catch (e) {
        throw new Error(`Resposta inválida do servidor: ${text.substring(0, 100)}`);
      }

      if (!response.ok) {
        throw new Error(result.message || `Erro ${response.status}`);
      }

      if (onSuccess) onSuccess(result, form);
      
    } catch (error) {
      console.error('Erro no formulário:', error);
      messageDiv.innerHTML = `<div class="error-message">${error.message}</div>`;
      
      if (onError) {
        onError(error, form);
      }
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalBtnText;
    }
  });

  function createMessageDiv(form) {
    const div = document.createElement('div');
    div.id = 'form-messages';
    form.parentNode.insertBefore(div, form);
    return div;
  }
}

// Configuração do formulário de evento
if (document.querySelector(".coordenador-section")) {
  setupFormAJAX(
    'event-form',
    './back-end/cadastrar_evento.php',
    (result, form) => {
      const messageDiv = document.getElementById('form-messages');
      messageDiv.innerHTML = '';
      
      if (result.success) {
        const successMsg = document.createElement('div');
        successMsg.className = 'success-message';
        successMsg.textContent = result.message || 'Evento cadastrado com sucesso!';
        
        if (result.codigo_presenca) {
          const codeMsg = document.createElement('div');
          codeMsg.className = 'code-message';
          codeMsg.innerHTML = `<strong>Código de Presença:</strong> ${result.codigo_presenca}`;
          messageDiv.appendChild(codeMsg);
        }
        
        messageDiv.appendChild(successMsg);
        form.reset();
        
        // Recarrega a lista de eventos após 2 segundos
        setTimeout(() => {
          if (typeof carregarEventosCoordenador === 'function') {
            carregarEventosCoordenador();
          } else {
            window.location.reload();
          }
        }, 2000);
      } else {
        throw new Error(result.message || 'Erro ao cadastrar evento');
      }
    },
    (error, form) => {
      const messageDiv = document.getElementById('form-messages') || createMessageDiv(form);
      messageDiv.innerHTML = `<div class="error-message">${error.message}</div>`;
    }
  );
}

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DE LOGIN
// =============================================

const container = document.querySelector(".container");
const registerBtn = document.querySelector(".register-btn");
const loginBtn = document.querySelector(".login-btn");

if (registerBtn && loginBtn) {
  registerBtn.addEventListener("click", () => container.classList.add("active"));
  loginBtn.addEventListener("click", () => container.classList.remove("active"));
}

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DO ALUNO
// =============================================

if (document.querySelector(".aluno-section")) {
  // Carrega eventos do servidor
  async function carregarEventos() {
    try {
      const eventos = await fetchAPI('./back-end/listar_eventos.php');
      const eventList = document.getElementById("event-list");
      
      eventList.innerHTML = eventos.map(evento => {
      let actionButton = '';
      if (evento.presenca_confirmada) {
        actionButton = `<button class="btn-status-presenca btn-presenca-ok" disabled>Presença Confirmada</button>`;
      } else if (evento.inscrito) {
        actionButton = `
            <button class=\"btn-confirmar-presenca\" data-id=\"${evento.id}\">Confirmar Presença</button>
            <button class=\"btn-cancelar-inscricao\" data-id=\"${evento.id}\">Cancelar Inscrição</button>
          `;
      } else {
        actionButton = `<button class="btn-inscrever" data-id="${evento.id}">Inscrever-se</button>`;
      }

      return `
      <div class="event-item">
        <div class="event-details">
          <span class="event-title">${evento.nome}</span>
          <span class="event-date">${formatarData(evento.data_inicio)} - ${evento.local}</span>
          <span class="event-coord">Coordenador: ${evento.coordenador_nome}</span>
        </div>
        <div class="event-actions">
          ${actionButton}
        </div>
      </div>
    `}).join('');

    // Reconfigura os event listeners para os botões
    configurarBotoesAcaoEvento();
    } catch (error) {
      console.error('Erro ao carregar eventos:', error);
      document.getElementById("event-list").innerHTML = '<p>Erro ao carregar eventos. Tente novamente.</p>';
    }
  }

  // Função para configurar os botões de ação do evento (inscrição e confirmação)
function configurarBotoesAcaoEvento() {
  // Configura botões de "Confirmar Presença" (só aparecem se inscrito e não confirmado)
  document.querySelectorAll('.btn-confirmar-presenca').forEach(button => {
    button.addEventListener('click', async function() {
      const eventoId = this.dataset.id;
      // Usar SweetAlert para pedir o código de presença
      const { value: codigo } = await Swal.fire({
        title: 'Confirmar Presença',
        input: 'text',
        inputLabel: 'Digite o código de presença fornecido pelo coordenador',
        inputPlaceholder: 'Código',
        showCancelButton: true,
        inputValidator: (value) => {
          if (!value || value.trim() === '') {
            return 'Você precisa digitar um código válido!'
          }
        }
      });

      if (codigo && codigo.trim() !== '') {
        try {
          const result = await fetchAPI('./back-end/confirmar_presenca.php', 'POST', {
            codigo: codigo.trim(),
            evento_id: eventoId // Garantir que evento_id está sendo enviado se o backend precisar
          });

          Swal.fire({
            title: result.success ? 'Sucesso!' : 'Erro!',
            text: result.message,
            icon: result.success ? 'success' : 'error'
          });

          if (result.success) {
            carregarEventos(); // Recarrega para atualizar o botão para "Presença Confirmada"
            carregarCertificados(); // Atualiza certificados se necessário
          }
        } catch (error) {
          Swal.fire('Erro', error.message || 'Ocorreu um erro ao tentar confirmar a presença.', 'error');
        }
      }
    });
  });

  // Configura botões de "Inscrever-se" (só aparecem se não inscrito)
  document.querySelectorAll('.btn-inscrever').forEach(button => {
    button.addEventListener('click', async function() {
      const eventoId = this.dataset.id;
      await inscreverNoEvento(eventoId); // Chama a função de inscrição existente
      // inscreverNoEvento já deve chamar carregarEventos no sucesso para atualizar o botão
    });
  });

  // Configura botões de "Cancelar Inscrição"
  document.querySelectorAll('.btn-cancelar-inscricao').forEach(button => {
    button.addEventListener('click', async function() {
      const eventoId = this.dataset.id;
      await cancelarInscricaoAluno(eventoId);
    });
  });
}

  // Função para cancelar a inscrição do aluno em um evento
async function cancelarInscricaoAluno(eventoId) {
  try {
    const result = await Swal.fire({
      title: 'Você tem certeza?',
      text: "Sua inscrição neste evento será cancelada.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Sim, cancelar inscrição!',
      cancelButtonText: 'Não'
    });

    if (result.isConfirmed) {
      const response = await fetchAPI('./back-end/cancelar_inscricao.php', 'POST', { evento_id: eventoId });
      
      Swal.fire({
        title: response.success ? 'Cancelada!' : 'Erro!',
        text: response.message,
        icon: response.success ? 'success' : 'error'
      });

      if (response.success) {
        carregarEventos(); // Recarrega os eventos para atualizar a UI
        // Não precisa carregar certificados aqui, pois o cancelamento não gera certificado
      }
    }
  } catch (error) {
    Swal.fire('Erro', error.message || 'Ocorreu um erro ao tentar cancelar a inscrição.', 'error');
  }
}

// Função para inscrever o aluno em um evento
  async function inscreverNoEvento(eventoId) {
    try {
      const result = await fetchAPI('./back-end/inscrever_evento.php', 'POST', {
        evento_id: eventoId
      });
      
      Swal.fire({
        title: result.success ? 'Sucesso!' : 'Erro!',
        text: result.message || (result.success ? 'Inscrição realizada com sucesso!' : 'Ocorreu um erro ao realizar a inscrição.'),
        icon: result.success ? 'success' : 'error'
      });

      if (result.success) {
        carregarEventos(); // Atualiza a lista de eventos e os botões
      }
    } catch (error) {
      Swal.fire('Erro', error.message || 'Ocorreu um erro ao tentar inscrever-se.', 'error');
    }
  }

  // Carrega certificados do servidor
  // Função melhorada para carregar certificados
  async function carregarCertificados() {
    try {
      const certificados = await fetchAPI('./back-end/listar_certificados.php');
      const certificateList = document.getElementById("certificate-list");
      
      certificateList.innerHTML = certificados.length > 0 
        ? certificados.map(cert => `
            <div class="certificate-item">
              <div class="certificate-header">
                <h3>${cert.evento_nome}</h3>
                <span class="certificate-date">${formatarData(cert.data_emissao)}</span>
              </div>
              <div class="certificate-body">
                <p class="certificate-description">Certificado de participação com carga horária de ${cert.carga_horaria || '8'} horas</p>
                <div class="certificate-footer">
                  <div class="verification-info">
                    <span class="verification-label">Código:</span>
                    <span class="verification-code">${cert.codigo_verificacao}</span>
                  </div>
                  <div class="certificate-actions">
                    <a href="${cert.link_certificado}" class="btn-download" download target="_blank">
                      <i class="fas fa-download"></i> Baixar
                    </a>
                    <button class="btn-verify" data-code="${cert.codigo_verificacao}">
                      <i class="fas fa-check-circle"></i> Verificar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          `).join('')
        : `<div class="no-certificates">
              <i class="fas fa-certificate"></i>
              <p>Nenhum certificado disponível ainda</p>
              <small>Participe de eventos e confirme sua presença para receber certificados</small>
            </div>`;

      // Configura botões de verificação
      document.querySelectorAll('.btn-verify').forEach(btn => {
        btn.addEventListener('click', async () => {
          const codigo = btn.getAttribute('data-code');
          try {
            const result = await fetchAPI(`./back-end/verificar_certificado.php?codigo=${codigo}`);
            if (result.success) {
              const cert = result.certificado;
              Swal.fire({
                title: 'Certificado Válido!',
                html: `
                  <div class="certificate-valid">
                    <p><strong>Aluno:</strong> ${cert.aluno_nome}</p>
                    <p><strong>Evento:</strong> ${cert.evento_nome}</p>
                    <p><strong>Data de Emissão:</strong> ${formatarData(cert.data_emissao)}</p>
                    <p><strong>Carga Horária:</strong> ${cert.carga_horaria || '8'} horas</p>
                    <div class="qr-code-container">
                      <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${window.location.origin}/verificar.php?codigo=${codigo}" 
                           alt="QR Code de Verificação" class="qr-code">
                      <small>Escaneie para verificar</small>
                    </div>
                  </div>
                `,
                icon: 'success',
                confirmButtonText: 'Fechar'
              });
            } else {
              throw new Error(result.message || 'Erro ao verificar certificado');
            }
          } catch (error) {
            Swal.fire({
              title: 'Erro na Verificação',
              text: error.message || 'Certificado inválido ou não encontrado',
              icon: 'error'
            });
          }
        });
      });

    } catch (error) {
      console.error('Erro ao carregar certificados:', error);
      document.getElementById("certificate-list").innerHTML = `
        <div class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <p>Erro ao carregar certificados: ${error.message}</p>
        </div>`;
    }
  }

  // Configuração do Modal de Exclusão de Conta
  const btnOpenDeleteModal = document.getElementById('btnOpenDeleteModal');
  const deleteAccountModal = document.getElementById('deleteAccountModal');
  const btnCancelDelete = deleteAccountModal ? deleteAccountModal.querySelector('.btn-cancel-delete') : null;

  if (btnOpenDeleteModal) {
    btnOpenDeleteModal.addEventListener('click', () => {
      abrirModal('deleteAccountModal');
    });
  }

  if (btnCancelDelete) {
    btnCancelDelete.addEventListener('click', () => {
      fecharModal('deleteAccountModal');
    });
  }

  // Se houver uma mensagem de erro de deleção (PHP set display:flex), 
  // o modal já estará aberto. O JS acima apenas garante que os botões de 
  // abrir/cancelar funcionem para interações futuras.

  // Configura botão de confirmar presença
  const btnConfirmar = document.querySelector(".btn-confirmar");
  if (btnConfirmar) {
    btnConfirmar.addEventListener("click", async () => {
      const codigo = prompt("Digite o código de presença:");
      if (!codigo) {
        alert("Por favor, insira um código válido.");
        return;
      }

      try {
        const result = await fetchAPI('./back-end/confirmar_presenca.php', 'POST', { codigo: codigo.trim() });
        alert(result.message || "Presença confirmada com sucesso!");
        carregarCertificados();
      } catch (error) {
        alert(error.message || "Erro ao confirmar presença");
      }
    });
  }

  // Carregar dados ao iniciar
  window.addEventListener('DOMContentLoaded', () => {
    carregarEventos();
    carregarCertificados();
  });
}

async function gerarCodigoPresenca(eventId) {
  if (!eventId) {
    alert('ID do evento não fornecido para gerar código.');
    return;
  }
  try {
    // Certifique-se que o endpoint e o payload estão corretos.
    const result = await fetchAPI('./back-end/gerar_codigo_presenca.php', 'POST', { evento_id: eventId });
    if (result.success) {
      alert(`Código de presença gerado/atualizado: ${result.codigo}`);
      if (typeof carregarEventosCoordenador === 'function') {
        carregarEventosCoordenador(); // Recarrega a lista para mostrar o novo código
      }
    } else {
      throw new Error(result.message || 'Erro ao gerar código de presença.');
    }
  } catch (error) {
    console.error('Erro ao gerar código de presença:', error);
    alert(error.message || 'Ocorreu um erro ao tentar gerar o código de presença.');
  }
}

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DO COORDENADOR
// =============================================

if (document.querySelector(".coordenador-section")) {

  // Carrega eventos do coordenador
  async function carregarEventosCoordenador() {
  try {
    const eventos = await fetchAPI('./back-end/listar_eventos_coordenador.php'); // Este endpoint deve retornar 'codigo_presenca'
    const eventList = document.querySelector(".coordenador-section .event-list");

    if (!eventList) {
        console.error('Elemento .event-list não encontrado na seção do coordenador.');
        return;
    }
    
    eventList.innerHTML = eventos.length > 0
      ? eventos.map(evento => {
          const eventoId = evento.id_evento || evento.id;
          const eventoNome = evento.nome_evento || evento.nome;
          const eventoDesc = evento.descricao_evento || evento.descricao;
          const eventoLocal = evento.local_evento || evento.local;

          return `
            <div class="event-item" data-id="${eventoId}">
              <h3>${eventoNome}</h3>
              ${eventoDesc ? `<p class="event-desc">${eventoDesc}</p>` : ''}
              <p><strong>Local:</strong> ${eventoLocal}</p>
              <p><strong>Início:</strong> ${formatarData(evento.data_inicio)}</p>
              <p><strong>Término:</strong> ${formatarData(evento.data_fim)}</p>
              <div class="codigo-container">
              ${evento.codigo_presenca
                ? `<p><strong>Código Presença:</strong> <span class="codigo-valor">${evento.codigo_presenca}</span></p>`
                : `<p><button class="btn btn-gerar-codigo" data-event-id="${eventoId}">Gerar Código de Presença</button></p>`
              }
            </div>
              <div class="event-actions">
                <button class="btn btn-editar" data-event-id="${eventoId}">Editar</button>
                <button class="btn btn-presenca" data-event-id="${eventoId}" data-event-name="${eventoNome}">Lista de Presença</button>
              </div>
            </div>
          `;
        }).join('')
      : '<p class="no-events">Nenhum evento cadastrado ainda.</p>';

    // Configura botões de ação
    document.querySelectorAll('.coordenador-section .event-item .btn-gerar-codigo').forEach(btn => {
        btn.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            if (eventId) {
                gerarCodigoPresenca(eventId); // Implementar esta função
            } else {
                console.error('Event ID não encontrado no botão Gerar Código.');
            }
        });
    });

    document.querySelectorAll('.coordenador-section .event-item .btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
          const eventId = this.getAttribute('data-event-id');
          if (eventId) {
            abrirModalEdicao(eventId);
          } else {
            console.error('Event ID não encontrado no botão Editar.');
          }
        });
      });
    
    document.querySelectorAll('.coordenador-section .event-item .btn-presenca').forEach(btn => {
      btn.addEventListener('click', function() {
        const eventId = this.getAttribute('data-event-id');
        const eventName = this.getAttribute('data-event-name');
        if (eventId && eventName) {
          exibirListaPresencaEmCaixa(eventId, eventName);
        } else {
          console.error('Event ID ou Event Name não encontrado no botão Lista de Presença.');
        }
      });
    });
  } catch (error) {
    console.error('Erro ao carregar eventos do coordenador:', error);
    const eventList = document.querySelector(".coordenador-section .event-list");
    if (eventList) {
        eventList.innerHTML = '<p class="error">Erro ao carregar eventos: ' + error.message + '</p>';
    } else {
        console.error('Elemento .event-list não encontrado para exibir mensagem de erro na seção do coordenador.');
    }
  }
}

  async function exibirListaPresencaEmCaixa(eventId, eventName) {
    const caixaPresenca = document.getElementById('caixa-lista-presenca');
    const tituloPresencaSpan = document.getElementById('presenca-event-title'); // Span para o nome do evento
    const conteudoPresencaDiv = document.getElementById('presenca-list-content'); // Div para a lista

    if (!caixaPresenca || !tituloPresencaSpan || !conteudoPresencaDiv) {
        console.error('Um ou mais elementos da caixa de lista de presença não foram encontrados no DOM.');
        Swal.fire('Erro de Interface', 'Não foi possível encontrar os elementos para exibir a lista de presença. Contate o suporte.', 'error');
        return;
    }

    caixaPresenca.style.display = 'block'; 
    tituloPresencaSpan.textContent = eventName; 
    conteudoPresencaDiv.innerHTML = '<p><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Carregando lista de participantes...</p>';

    try {
        const response = await fetchAPI(`./back-end/listar_participantes_evento.php?evento_id=${eventId}`);

        if (response.success && response.participantes) {
            if (response.participantes.length > 0) {
                let tableHTML = `
                  <div class="table-responsive">
                    <table class="table table-striped table-hover sgea-table">
                      <thead>
                        <tr>
                          <th>Nome do Aluno</th>
                          <th>Email</th>
                          <th>Data Inscrição</th>
                          <th>Status</th>
                          <th>Data Confirmação</th>
                        </tr>
                      </thead>
                      <tbody>
                `;
                response.participantes.forEach(p => {
                  let statusClass = '';
                  if (p.status_participacao === 'Presença Confirmada') {
                      statusClass = 'status-confirmada';
                  } else if (p.status_participacao === 'Inscrito (Pendente Confirmação)') {
                      statusClass = 'status-pendente';
                  }

                  tableHTML += `
                    <tr>
                      <td>${p.aluno_nome || 'N/A'}</td>
                      <td>${p.aluno_email || 'N/A'}</td>
                      <td>${p.data_inscricao ? new Date(p.data_inscricao).toLocaleDateString('pt-BR') : '-'}</td>
                      <td><span class="${statusClass}">${p.status_participacao || 'N/A'}</span></td>
                      <td>${p.data_presenca ? new Date(p.data_presenca).toLocaleDateString('pt-BR', { year: 'numeric', month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'}</td>
                    </tr>
                  `;
                });
                tableHTML += `
                    </tbody>
                  </table>
                </div>
              `;
              conteudoPresencaDiv.innerHTML = tableHTML;
          } else {
              conteudoPresencaDiv.innerHTML = '<p>Nenhum aluno inscrito neste evento ainda.</p>';
          }
      } else {
          const errorMessage = response.message || 'Não foi possível obter os dados dos participantes.';
          conteudoPresencaDiv.innerHTML = `<p class="error-message">${errorMessage}</p>`;
          Swal.fire('Erro ao Carregar', errorMessage, 'error');
      }
  } catch (error) {
      console.error('Falha ao carregar lista de participantes:', error);
      const detailedErrorMessage = error.message || 'Erro desconhecido.';
      conteudoPresencaDiv.innerHTML = `<p class="error-message">Ocorreu uma falha ao buscar a lista de participantes: ${detailedErrorMessage}. Verifique o console para mais detalhes.</p>`;
      Swal.fire('Erro Crítico', `Falha ao buscar lista: ${detailedErrorMessage}`, 'error');
  }
}

  // Abre modal de edição
 async function abrirModalEdicao(eventId) {
    try {
        const evento = await fetchAPI(`./back-end/obter_evento.php?id=${eventId}`);
        
        // Formata as datas para o input datetime-local
        const formatarParaInputDatetime = (datetime) => {
            if (!datetime) return '';
            const date = new Date(datetime);
            // Ajusta para o fuso horário local
            const tzOffset = date.getTimezoneOffset() * 60000;
            const localISOTime = new Date(date - tzOffset).toISOString().slice(0, 16);
            return localISOTime;
        };

        const modal = document.createElement("div");
        modal.classList.add("modal");
        modal.id = "modal-edicao";
        modal.innerHTML = `
            <div class="modal-content">
                <span class="fechar-modal">&times;</span>
                <h2>Editar Evento</h2>
                <form id="form-edicao">
                    <input type="hidden" name="id" value="${eventId}">
                    <div class="form-group">
                        <input type="text" name="nome" value="${evento.nome}" placeholder="Nome do Evento" required />
                    </div>
                    
                    <div class="form-group">
                        <textarea name="descricao" placeholder="Descrição do Evento">${evento.descricao || ''}</textarea>
                    </div>

                    <div class="form-group">
                        <label for="data_inicio">Data e Hora de Início:</label>
                        <input type="datetime-local" name="data_inicio" value="${formatarParaInputDatetime(evento.data_inicio)}" required />
                    </div>

                    <div class="form-group">
                        <label for="data_fim">Data e Hora de Término:</label>
                        <input type="datetime-local" name="data_fim" value="${formatarParaInputDatetime(evento.data_fim)}" required />
                    </div>

                    <div class="form-group">
                        <input type="text" name="local" value="${evento.local}" placeholder="Local do Evento" required />
                    </div>

                    <button type="submit" class="btn">Salvar Alterações</button>
                    <button type="button" class="btn btn-excluir">Excluir Evento</button>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        abrirModal("modal-edicao");
        
        // Configura eventos do modal
        modal.querySelector(".fechar-modal").addEventListener("click", () => {
            fecharModal("modal-edicao");
            modal.remove();
        });
        
        // Configura form de edição
        setupFormAJAX(
            'form-edicao',
            './back-end/editar_evento.php',
            (result) => {
                if (result.success) {
                    alert('Evento atualizado com sucesso!');
                    carregarEventosCoordenador();
                    fecharModal("modal-edicao");
                    modal.remove();
                } else {
                    throw new Error(result.message || 'Erro ao atualizar evento');
                }
            },
            (error) => {
                alert(error.message);
            }
        );
        
        // Configura botão de exclusão
        modal.querySelector(".btn-excluir").addEventListener("click", async () => {
            if (confirm("Tem certeza que deseja excluir este evento?")) {
                try {
                    const result = await fetchAPI('./back-end/excluir_evento.php', 'POST', { id: eventId });
                    if (result.success) {
                        alert('Evento excluído com sucesso!');
                        carregarEventosCoordenador();
                        fecharModal("modal-edicao");
                        modal.remove();
                    } else {
                        throw new Error(result.message || 'Erro ao excluir evento');
                    }
                } catch (error) {
                    alert(error.message || 'Erro ao excluir evento');
                }
            }
        });
    } catch (error) {
        console.error('Erro ao abrir modal de edição:', error);
        alert('Erro ao carregar dados do evento: ' + error.message);
    }
}

  // Abre modal de presença
  async function abrirModalPresenca(eventId) {
    try {
      const evento = await fetchAPI(`./back-end/obter_evento.php?id=${eventId}`);
      const modal = document.getElementById("modal-presenca");
      
      if (modal) {
        modal.querySelector("h2").textContent = `Gerenciamento: ${evento.nome}`;
        abrirModal("modal-presenca");
        
        const presencaHeader = document.createElement('div');
        presencaHeader.className = 'presenca-header';
        presencaHeader.innerHTML = `
          <div class="header-actions">
            <button class="btn-gerar-codigo" data-event-id="${eventId}">
              <i class="fas fa-key"></i> Gerar Código
            </button>
            <button class="btn-emitir-certificados" data-event-id="${eventId}">
              <i class="fas fa-certificate"></i> Emitir Certificados
            </button>
          </div>
          <div class="header-info">
            <small class="codigo-info">Código atual: ${evento.codigo_presenca || 'Nenhum'}</small>
            <small class="certificate-info">Certificados: ${evento.certificados_emitidos ? 'Emitidos' : 'Pendentes'}</small>
          </div>
        `;
        
        const presencaList = modal.querySelector(".presenca-list");
        presencaList.innerHTML = '';
        presencaList.appendChild(presencaHeader);
        
        // Configura evento para gerar código
        presencaHeader.querySelector('.btn-gerar-codigo').addEventListener('click', async (e) => {
          e.stopPropagation();
          try {
            const result = await fetchAPI('./back-end/gerar_codigo_presenca.php', 'POST', { 
              evento_id: eventId 
            });
            
            if (result.success) {
              Swal.fire({
                title: 'Código Gerado!',
                html: `
                  <div class="generated-code">
                    <p><strong>Novo código:</strong></p>
                    <div class="code-display">${result.codigo}</div>
                    <p>Validade: 2 horas</p>
                    <small>Compartilhe este código com os participantes</small>
                  </div>
                `,
                icon: 'success'
              });
              presencaHeader.querySelector('.codigo-info').textContent = `Código atual: ${result.codigo}`;
            } else {
              throw new Error(result.message || 'Erro ao gerar código');
            }
          } catch (error) {
            Swal.fire({
              title: 'Erro',
              text: error.message || 'Erro ao gerar código de presença',
              icon: 'error'
            });
          }
        });
        
        // Configura evento para emitir certificados
        presencaHeader.querySelector('.btn-emitir-certificados').addEventListener('click', async (e) => {
          e.stopPropagation();
          try {
            const { value: cargaHoraria } = await Swal.fire({
              title: 'Emitir Certificados',
              input: 'number',
              inputLabel: 'Carga Horária (horas)',
              inputPlaceholder: 'Digite a carga horária do evento',
              inputValue: '8',
              showCancelButton: true,
              inputValidator: (value) => {
                if (!value || parseInt(value) <= 0) {
                  return 'Por favor, insira uma carga horária válida (número maior que zero).';
                }
              }
            });

            if (cargaHoraria) {
              // Lógica para emitir certificados aqui...
              // Exemplo: const result = await fetchAPI('./back-end/emitir_certificados_evento.php', 'POST', { evento_id: eventId, carga_horaria: cargaHoraria });
              // if (result.success) { /* ... */ } else { /* throw new Error(result.message) */ }
              Swal.fire('Sucesso', `Certificados para o evento (ID: ${eventId}) com carga horária de ${cargaHoraria} horas seriam emitidos aqui. Lógica pendente.`, 'info');
              // Atualizar UI se necessário, por exemplo, o status de 'Certificados Emitidos'
              // presencaHeader.querySelector('.certificate-info').textContent = 'Certificados: Emitidos';
            }
          } catch (error) {
            Swal.fire({
              title: 'Erro na Emissão',
              text: error.message || 'Ocorreu um problema ao tentar configurar a emissão de certificados.',
              icon: 'error'
            });
          }
        });
        // A função exibirListaPresencaEmCaixa(eventId, eventName) deve ser chamada aqui para atualizar a lista após ações.
        // Se necessário, chame aqui: await exibirListaPresencaEmCaixa(eventId, eventName);
      }
    } catch (error) { // Este é o CATCH do TRY principal da função abrirModalPresenca
      console.error('Erro ao abrir modal de presença:', error);
      Swal.fire({
        title: 'Erro',
        text: 'Erro ao carregar dados de presença: ' + (error.message || String(error)),
        icon: 'error'
      });
    }
  }

  // Carregar dados ao iniciar
  window.addEventListener('DOMContentLoaded', carregarEventosCoordenador);
}

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DO DIRETOR
// =============================================

if (document.querySelector(".diretor-section")) {
  // Configura formulário de coordenador
  setupFormAJAX(
    'coordenador-form',
    './back-end/cadastrar_coordenador.php',
    (result, form) => {
      alert('Coordenador cadastrado com sucesso!');
      form.reset();
      carregarCoordenadores();
    }
  );

  // Carrega lista de coordenadores
  async function carregarCoordenadores() {
    try {
      const coordenadores = await fetchAPI('./back-end/listar_coordenadores.php');
      const coordenadorList = document.getElementById("coordenador-list");
      
      coordenadorList.innerHTML = coordenadores.length > 0
        ? coordenadores.map(coordenador => `
            <div class="coordenador-item">
              <span>${coordenador.nome} - ${coordenador.email}</span>
              <button class="btn-remover" data-id="${coordenador.id}">Remover</button>
            </div>
          `).join('')
        : '<p>Nenhum coordenador cadastrado ainda.</p>';

      // Configura botões de remoção
      document.querySelectorAll(".btn-remover").forEach(btn => {
        btn.addEventListener("click", async () => {
          const coordenadorId = btn.getAttribute('data-id');
          if (confirm("Tem certeza que deseja remover este coordenador?")) {
            try {
              await fetchAPI(`./back-end/remover_coordenador.php?id=${coordenadorId}`, 'DELETE');
              alert("Coordenador removido com sucesso!");
              carregarCoordenadores();
            } catch (error) {
              alert(error.message || "Erro ao remover coordenador");
            }
          }
        });
      });
    } catch (error) {
      console.error('Erro ao carregar coordenadores:', error);
      document.getElementById("coordenador-list").innerHTML = '<p>Erro ao carregar coordenadores.</p>';
    }
  }

  async function carregarEventosParaDiretor() {
    try {
      // Presume-se um endpoint que lista todos os eventos para o diretor
      const eventos = await fetchAPI('./back-end/listar_todos_eventos.php'); 
      const eventList = document.querySelector(".diretor-section #lista-eventos .event-list");

      if (!eventList) {
        console.error('Elemento .event-list não encontrado na seção #lista-eventos do diretor.');
        return;
      }

      if (eventos.length === 0) {
        eventList.innerHTML = '<p>Nenhum evento cadastrado no sistema.</p>';
        return;
      }

      eventList.innerHTML = eventos.map(evento => `
        <div class="event-item" data-id="${evento.id_evento}">
          <h3>${evento.nome_evento}</h3>
          <p>Data: ${formatarData(evento.data_inicio)} - ${formatarData(evento.data_fim)}</p>
          <p>Local: ${evento.local_evento}</p>
          <p>Capacidade: ${evento.capacidade_maxima}</p>
          <p>Coordenador: ${evento.nome_coordenador || 'Não especificado'}</p> <!-- Assumindo que o endpoint retorne nome_coordenador -->
          <div class="event-actions">
            <button class="btn btn-presenca" data-event-id="${evento.id_evento}" data-event-name="${evento.nome_evento}">Lista de Presença</button>
          </div>
        </div>
      `).join('');

    } catch (error) {
      console.error('Erro ao carregar eventos para o diretor:', error);
      const eventList = document.querySelector(".diretor-section #lista-eventos .event-list");
      if (eventList) {
        eventList.innerHTML = `<p class="error-message">Erro ao carregar eventos: ${error.message}</p>`;
      }
    }
  }

  // Carregar dados ao iniciar (Diretor)
  window.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.diretor-section')) {
      carregarCoordenadores();
      carregarEventosParaDiretor();
    }
  });
}

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DO ALUNO
// =============================================

function renderizarAcoesDoEvento(evento) {
    let acoesHtml = '';
    switch (evento.status) {
        case 'disponivel':
            acoesHtml = `<button class="btn btn-inscrever" data-evento-id="${evento.id}">Inscrever-se</button>`;
            break;
        case 'inscrito':
            if (evento.pode_confirmar_presenca) {
                acoesHtml += `<button class="btn btn-confirmar-presenca" data-evento-id="${evento.id}">Confirmar Presença</button>`;
            }
            if (evento.pode_cancelar_inscricao) {
                acoesHtml += `<button class="btn btn-cancelar-inscricao" data-evento-id="${evento.id}">Cancelar Inscrição</button>`;
            }
            if (!acoesHtml) {
                 acoesHtml = '<span class="info-status">Aguardando início do evento para confirmar presença.</span>';
            }
            break;
        case 'presenca_confirmada':
            acoesHtml = '<span class="presenca-confirmada">Presença Confirmada</span>';
            if (evento.pode_remover_presenca) {
                acoesHtml += `<button class="btn btn-remover-presenca" data-evento-id="${evento.id}">Remover Presença</button>`;
            }
            break;
    }
    return `<div class="event-actions">${acoesHtml}</div>`;
}

async function carregarEventosAluno() {
    const eventListDiv = document.getElementById('event-list');
    if (!eventListDiv) return;

    eventListDiv.innerHTML = '<div class="loading">Carregando eventos...</div>';
    try {
        const data = await fetchAPI('./back-end/aluno_eventos.php');
        if (data.success && data.eventos) {
            if (data.eventos.length === 0) {
                eventListDiv.innerHTML = '<p>Não há eventos futuros disponíveis no momento.</p>';
                return;
            }

            const html = data.eventos.map(evento => `
                <div class="event-item aluno-event-item">
                    <h4>${evento.nome}</h4>
                    <div class="event-details">
                        <p><strong>Data:</strong> ${formatarData(evento.data_inicio)} - ${formatarData(evento.data_fim)}</p>
                        <p><strong>Local:</strong> ${evento.local}</p>
                        <p><strong>Descrição:</strong> ${evento.descricao || 'Sem descrição.'}</p>
                    </div>
                    ${renderizarAcoesDoEvento(evento)}
                </div>
            `).join('');

            eventListDiv.innerHTML = html;
            // Configura os botões de ação após carregar os eventos
            configurarBotoesAcaoEvento();
        } else {
            throw new Error(data.message || 'Não foi possível carregar os eventos.');
        }
    } catch (error) {
        console.error('Erro ao carregar eventos do aluno:', error);
        eventListDiv.innerHTML = `<p class="error-message">Erro ao carregar eventos: ${error.message}</p>`;
    }
}

function configurarBotoesAcaoEvento() {
    console.log('DEBUG: Função configurarBotoesAcaoEvento foi chamada.');
    const eventListDiv = document.getElementById('event-list');
    if (!eventListDiv) {
        console.error('DEBUG: Elemento #event-list não encontrado.');
        return;
    }

    // Para evitar adicionar múltiplos listeners, removemos o anterior se existir.
    if (eventListDiv.dataset.listenerAttached === 'true') {
        console.log('DEBUG: Listener de clique já existe. Não será adicionado novamente.');
        return;
    }

    eventListDiv.addEventListener('click', async (e) => {
        console.log('DEBUG: Clique detectado dentro de #event-list. Elemento clicado:', e.target);
        
        const target = e.target.closest('button[data-evento-id]');
        if (!target) {
            console.log('DEBUG: O clique não foi em um botão de ação válido.');
            return;
        }

        const eventoId = target.dataset.eventoId;
        console.log(`DEBUG: Evento ID encontrado: ${eventoId}`);

        let endpoint = '';
        let successMessage = '';
        let confirmConfig = null;
        let dadosExtras = null;

        if (target.classList.contains('btn-inscrever')) {
            endpoint = './back-end/aluno_inscrever_evento.php';
            successMessage = 'Inscrição realizada com sucesso!';
        } else if (target.classList.contains('btn-cancelar-inscricao')) {
            endpoint = './back-end/aluno_cancelar_inscricao.php';
            successMessage = 'Inscrição cancelada com sucesso!';
            confirmConfig = { title: 'Confirmar Cancelamento', text: 'Deseja mesmo cancelar sua inscrição?' };
        } else if (target.classList.contains('btn-confirmar-presenca')) {
            // Solicitar código de presença
            const { value: codigo } = await Swal.fire({
                title: 'Confirmar Presença',
                text: 'Digite o código de presença fornecido pelo coordenador:',
                input: 'text',
                inputPlaceholder: 'Código de presença',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Por favor, insira o código de presença';
                    }
                }
            });

            if (!codigo) {
                console.log('DEBUG: Usuário cancelou a confirmação de presença.');
                return;
            }
            
            endpoint = './back-end/aluno_confirmar_presenca.php';
            successMessage = 'Presença confirmada com sucesso!';
            // Incluir o código de presença nos dados enviados
            dadosExtras = { evento_id: eventoId, codigo_presenca: codigo };
        } else if (target.classList.contains('btn-remover-presenca')) {
            endpoint = './back-end/aluno_remover_presenca.php';
            successMessage = 'Sua presença foi removida.';
            confirmConfig = { title: 'Confirmar Remoção', text: 'Deseja remover sua confirmação de presença?' };
        } else {
            console.log('DEBUG: O botão clicado não corresponde a nenhuma ação conhecida.');
            return;
        }
        
        console.log(`DEBUG: Endpoint definido: ${endpoint}`);

        try {
            if (confirmConfig) {
                const result = await Swal.fire({ ...confirmConfig, icon: 'warning', showCancelButton: true, confirmButtonText: 'Sim', cancelButtonText: 'Não' });
                if (!result.isConfirmed) return;
            }

            const result = await fetchAPI(endpoint, 'POST', dadosExtras || { evento_id: eventoId });
            if (result.success) {
                Swal.fire('Sucesso!', successMessage, 'success');
                carregarEventosAluno();
            } else {
                throw new Error(result.message || 'Ocorreu um erro.');
            }
        } catch (error) {
            console.error('DEBUG: Erro no bloco try/catch do handler de clique:', error);
            Swal.fire('Erro!', error.message, 'error');
        }
    });

    eventListDiv.dataset.listenerAttached = 'true';
    console.log('DEBUG: Event listener de clique adicionado a #event-list.');
}

async function carregarCertificadosAluno() {
  const certificateListDiv = document.getElementById('certificate-list');
  if (!certificateListDiv) return;

  certificateListDiv.innerHTML = '<div class="loading">Carregando certificados...</div>';
  try {
    const data = await fetchAPI('./back-end/listar_certificados.php');
    if (data && data.success) {
      if (!data.certificados || data.certificados.length === 0) {
        certificateListDiv.innerHTML = '<p>Você ainda não possui certificados.</p>';
        return;
      }
      
      let html = '<h3>Meus Certificados</h3>';
      data.certificados.forEach(certificado => {
        html += `
          <div class="certificate-item aluno-certificate-item">
            <h4>Certificado: ${certificado.nome_evento}</h4>
            <p><strong>Data de Conclusão:</strong> ${formatarData(certificado.data_fim)}</p>
            <p><strong>Carga Horária:</strong> ${certificado.carga_horaria || 'N/A'} horas</p>
            <p><strong>Emitido em:</strong> ${formatarData(certificado.data_presenca)}</p> 
            <a href="${certificado.url_visualizar || '#'}" class="btn btn-visualizar-certificado" target="_blank" ${!certificado.url_visualizar ? 'disabled title="Link de visualização não disponível"' : 'title="Visualizar/Baixar Certificado"'}>Visualizar Certificado</a>
          </div>
        `;
        // Nota: 'data_presenca' está sendo usada como data de emissão do certificado, pode precisar de ajuste se houver uma data específica de emissão de certificado.
      });
      certificateListDiv.innerHTML = html;
      // Se houver ações específicas para botões de certificado, adicionar listeners aqui.
    } else {
      // Se não houver certificados, apenas limpa a mensagem de carregamento
      certificateListDiv.innerHTML = '';
      return;
    }
  } catch (error) {
    console.error('Erro ao carregar certificados do aluno:', error);
    certificateListDiv.innerHTML = `<p class="error-message">Erro ao carregar certificados: ${error.message}</p>`;
  }
}

// Carregar dados específicos da página do aluno
window.addEventListener('DOMContentLoaded', () => {
  if (document.querySelector('.aluno-section')) {
    carregarEventosAluno();
    carregarCertificadosAluno();
    configurarBotoesAcaoEvento(); // Ativa os botões de inscrição, cancelamento, etc.
  }
});