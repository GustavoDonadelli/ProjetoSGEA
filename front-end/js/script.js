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
      
      eventList.innerHTML = eventos.map(evento => `
        <div class="event-item">
          <span class="event-title">${evento.nome}</span>
          <span class="event-date">${formatarData(evento.data_inicio)} - ${evento.local}</span>
          <span class="event-coord">Coordenador: ${evento.coordenador_nome}</span>
          <div class="event-actions">
            <button class="btn-confirmar-evento" data-id="${evento.id}">Confirmar Presença</button>
          </div>
        </div>
      `).join('');

      // Configura eventos de confirmação de presença
      configurarBotoesConfirmacao();
    } catch (error) {
      console.error('Erro ao carregar eventos:', error);
      document.getElementById("event-list").innerHTML = '<p>Erro ao carregar eventos. Tente novamente.</p>';
    }
  }

  // Função para configurar os botões de confirmação
  function configurarBotoesConfirmacao() {
    document.querySelectorAll(".btn-confirmar-evento").forEach(btn => {
      btn.addEventListener("click", async () => {
        const eventId = btn.getAttribute('data-id');
        const codigo = prompt("Digite o código de presença fornecido pelo coordenador:");
        
        if (!codigo) {
          alert("Por favor, insira um código válido.");
          return;
        }

        try {
          const result = await fetchAPI('./back-end/confirmar_presenca.php', 'POST', { 
            codigo: codigo.trim(),
            evento_id: eventId
          });
          
          if (result.success) {
            alert(result.message || "Presença confirmada com sucesso!");
            carregarCertificados();
          } else {
            // Mostra mensagem específica do servidor
            alert(result.message || "Erro ao confirmar presença");
            
            // Se for erro de não inscrito, oferece opção de se inscrever
            if (result.message && result.message.includes("não está inscrito")) {
              if (confirm("Deseja se inscrever no evento agora?")) {
                // Chama função para inscrever o aluno
                await inscreverNoEvento(eventId);
              }
            }
          }
        } catch (error) {
          alert(error.message || "Erro ao confirmar presença");
        }
      });
    });
  }

  // Função para inscrever o aluno em um evento
  async function inscreverNoEvento(eventoId) {
    try {
      const result = await fetchAPI('./back-end/inscrever_evento.php', 'POST', {
        evento_id: eventoId
      });
      
      if (result.success) {
        alert("Inscrição realizada com sucesso! Agora você pode confirmar presença.");
      } else {
        alert(result.message || "Erro ao realizar inscrição");
      }
    } catch (error) {
      alert(error.message || "Erro ao tentar inscrever-se");
    }
  }

  // Carrega certificados do servidor
  // Função melhorada para carregar certificados
  async function carregarCertificados() {
    try {
        const certificados = await fetchAPI('./back-end/listar_certificados.php');
        const certificateList = document.getElementById("certificate-list");
        
        if (certificados.length > 0) {
            certificateList.innerHTML = certificados.map(cert => `
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
            `).join('');
        } else {
            certificateList.innerHTML = `
                <div class="no-certificates">
                    <i class="fas fa-certificate"></i>
                    <p>Nenhum certificado disponível ainda</p>
                    <small>Participe de eventos e confirme sua presença para receber certificados</small>
                    <div class="info-box">
                        <p>Para receber certificados:</p>
                        <ol>
                            <li>Inscreva-se em um evento disponível</li>
                            <li>Compareça ao evento no dia e horário marcados</li>
                            <li>Confirme sua presença com o código fornecido</li>
                        </ol>
                    </div>
                </div>
            `;
        }

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

// =============================================
// FUNÇÕES ESPECÍFICAS DA PÁGINA DO COORDENADOR
// =============================================

if (document.querySelector(".coordenador-section")) {
  // Configura formulário de cadastro de evento
  setupFormAJAX(
    'event-form',
    './back-end/cadastrar_evento.php',
    (result, form) => {
      if (result.success) {
        alert('Evento cadastrado com sucesso!');
        form.reset();
        carregarEventosCoordenador();
      } else {
        throw new Error(result.message || 'Erro ao cadastrar evento');
      }
    },
    (error) => {
      alert(error.message);
    }
  );

  // Carrega eventos do coordenador
  async function carregarEventosCoordenador() {
    try {
      const eventos = await fetchAPI('./back-end/listar_eventos_coordenador.php');
      const eventList = document.querySelector(".event-list");
      
      eventList.innerHTML = eventos.length > 0
        ? eventos.map(evento => `
            <div class="event-item" data-id="${evento.id}">
              <h3>${evento.nome}</h3>
              ${evento.descricao ? `<p class="event-desc">${evento.descricao}</p>` : ''}
              <p><strong>Local:</strong> ${evento.local}</p>
              <p><strong>Início:</strong> ${formatarData(evento.data_inicio)}</p>
              <p><strong>Término:</strong> ${formatarData(evento.data_fim)}</p>
              <div class="event-actions">
                <button class="btn-editar">Editar</button>
                <button class="btn-presenca">Presença</button>
              </div>
            </div>
          `).join('')
        : '<p class="no-events">Nenhum evento cadastrado ainda.</p>';

      // Configura botões de ação
      document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
          const eventId = this.closest('.event-item').getAttribute('data-id');
          abrirModalEdicao(eventId);
        });
      });
      
      document.querySelectorAll('.btn-presenca').forEach(btn => {
        btn.addEventListener('click', function() {
          const eventId = this.closest('.event-item').getAttribute('data-id');
          abrirModalPresenca(eventId);
        });
      });
    } catch (error) {
      console.error('Erro ao carregar eventos:', error);
      document.querySelector(".event-list").innerHTML = '<p class="error">Erro ao carregar eventos: ' + error.message + '</p>';
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
                if (!value || value <= 0) {
                  return 'Por favor, digite uma carga horária válida';
                }
              }
            });
            
            if (cargaHoraria) {
              const result = await fetchAPI('./back-end/gerar_certificado.php', 'POST', {
                evento_id: eventId,
                carga_horaria: cargaHoraria
              });
              
              if (result.success) {
                Swal.fire({
                  title: 'Sucesso!',
                  html: `
                    <p>Certificados emitidos para ${result.certificados_gerados} participantes</p>
                    <small>Os participantes agora podem visualizar e baixar seus certificados</small>
                  `,
                  icon: 'success'
                });
                presencaHeader.querySelector('.certificate-info').textContent = 'Certificados: Emitidos';
              } else {
                throw new Error(result.message || 'Erro ao emitir certificados');
              }
            }
          } catch (error) {
            Swal.fire({
              title: 'Erro',
              text: error.message || 'Erro ao emitir certificados',
              icon: 'error'
            });
          }
        });
            
                    // Carrega a lista de presença
        try {
          const presencas = await fetchAPI(`./back-end/listar_presencas.php?evento_id=${eventId}`);
          
          const listContainer = document.createElement('div');
          listContainer.className = 'presenca-list-container';
          
          if (presencas.length > 0) {
            listContainer.innerHTML = `
              <div class="presenca-list-header">
                <span>Participante</span>
                <span>Status</span>
                <span>Certificado</span>
              </div>
              ${presencas.map(presenca => `
                <div class="presenca-item">
                  <span class="participant-name">${presenca.nome_aluno}</span>
                  <span class="status-presenca ${presenca.confirmado ? 'confirmado' : 'pendente'}">
                    ${presenca.confirmado ? 
                      `Confirmado em ${formatarData(presenca.data_presenca)}` : 
                      'Pendente'}
                  </span>
                  <span class="certificate-status">
                    ${presenca.certificado_emitido ? 
                      `<i class="fas fa-check-circle success"></i> Emitido` : 
                      `<i class="fas fa-clock warning"></i> Pendente`}
                  </span>
                </div>
              `).join('')}
            `;
          } else {
            listContainer.innerHTML = `
              <div class="no-presences">
                <i class="fas fa-user-slash"></i>
                <p>Nenhuma presença registrada ainda</p>
                <small>Gere um código e compartilhe com os participantes</small>
              </div>
            `;
          }
          
          presencaList.appendChild(listContainer);
        } catch (error) {
          console.error('Erro ao carregar presenças:', error);
          const errorMsg = document.createElement('div');
          errorMsg.className = 'error-message';
          errorMsg.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <p>Erro ao carregar presenças: ${error.message}</p>
          `;
          presencaList.appendChild(errorMsg);
        }
      }
    } catch (error) {
      console.error('Erro ao abrir modal de presença:', error);
      Swal.fire({
        title: 'Erro',
        text: 'Erro ao carregar dados de presença: ' + error.message,
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

  // Carregar dados ao iniciar
  window.addEventListener('DOMContentLoaded', carregarCoordenadores);
}