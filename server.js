// server.js - Arquivo principal do servidor

const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = 'sua_chave_secreta_jwt'; // Em produção, use variáveis de ambiente

// Middlewares
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

// Conexão com MongoDB
mongoose.connect('mongodb://localhost:27017/sistema_eventos', {
    useNewUrlParser: true,
    useUnifiedTopology: true
})
.then(() => console.log('Conectado ao MongoDB'))
.catch(err => console.error('Erro ao conectar ao MongoDB:', err));

// Definição dos Schemas
const usuarioSchema = new mongoose.Schema({
    tipo: { type: String, required: true, enum: ['diretor', 'coordenador', 'aluno'] },
    nome: { type: String, required: true },
    email: { type: String, required: true, unique: true },
    senha: { type: String, required: true },
    matricula: { type: String, sparse: true }, // Apenas para alunos
    departamento: { type: String }, // Para coordenadores
    curso: { type: String }, // Para alunos
    dataCriacao: { type: Date, default: Date.now }
});

const eventoSchema = new mongoose.Schema({
    nome: { type: String, required: true },
    descricao: { type: String, required: true },
    data: { type: Date, required: true },
    local: { type: String, required: true },
    horaInicio: { type: String, required: true },
    horaFim: { type: String, required: true },
    capacidade: { type: Number, required: true },
    codigoConfirmacao: { type: String, required: true },
    coordenador: { type: mongoose.Schema.Types.ObjectId, ref: 'Usuario', required: true },
    status: { type: String, default: 'agendado', enum: ['agendado', 'em_andamento', 'encerrado'] },
    dataCriacao: { type: Date, default: Date.now }
});

const inscricaoSchema = new mongoose.Schema({
    aluno: { type: mongoose.Schema.Types.ObjectId, ref: 'Usuario', required: true },
    evento: { type: mongoose.Schema.Types.ObjectId, ref: 'Evento', required: true },
    presencaConfirmada: { type: Boolean, default: false },
    dataInscricao: { type: Date, default: Date.now },
    dataConfirmacao: { type: Date }
});

const certificadoSchema = new mongoose.Schema({
    aluno: { type: mongoose.Schema.Types.ObjectId, ref: 'Usuario', required: true },
    evento: { type: mongoose.Schema.Types.ObjectId, ref: 'Evento', required: true },
    dataEmissao: { type: Date, default: Date.now },
    codigo: { type: String, required: true, unique: true }
});

// Criação dos modelos
const Usuario = mongoose.model('Usuario', usuarioSchema);
const Evento = mongoose.model('Evento', eventoSchema);
const Inscricao = mongoose.model('Inscricao', inscricaoSchema);
const Certificado = mongoose.model('Certificado', certificadoSchema);

// Middleware de autenticação
const autenticar = (req, res, next) => {
    const token = req.header('x-auth-token');
    if (!token) return res.status(401).json({ msg: 'Acesso negado. Token não fornecido' });

    try {
        const decoded = jwt.verify(token, JWT_SECRET);
        req.usuario = decoded;
        next();
    } catch (err) {
        res.status(401).json({ msg: 'Token inválido' });
    }
};

// Middleware de verificação de papel
const verificarPapel = (papel) => {
    return (req, res, next) => {
        if (req.usuario.tipo !== papel) {
            return res.status(403).json({ msg: 'Acesso negado. Permissão insuficiente' });
        }
        next();
    };
};

// Rotas de Autenticação
app.post('/api/auth/login', async (req, res) => {
    const { email, senha, tipo } = req.body;

    try {
        // Verificar se o usuário existe
        const usuario = await Usuario.findOne({ email, tipo });
        if (!usuario) return res.status(400).json({ msg: 'Credenciais inválidas' });

        // Verificar senha
        const senhaCorreta = await bcrypt.compare(senha, usuario.senha);
        if (!senhaCorreta) return res.status(400).json({ msg: 'Credenciais inválidas' });

        // Criar e retornar JWT
        const payload = {
            id: usuario._id,
            nome: usuario.nome,
            email: usuario.email,
            tipo: usuario.tipo
        };

        jwt.sign(payload, JWT_SECRET, { expiresIn: '8h' }, (err, token) => {
            if (err) throw err;
            res.json({ 
                token,
                usuario: {
                    id: usuario._id,
                    nome: usuario.nome,
                    email: usuario.email,
                    tipo: usuario.tipo,
                    matricula: usuario.matricula,
                    departamento: usuario.departamento,
                    curso: usuario.curso
                }
            });
        });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

//Rota api funcionando
app.get('/api', (req, res) => {
    res.send('API está funcionando!');
});

// Rota para verificar token
app.get('/api/auth', autenticar, async (req, res) => {
    try {
        const usuario = await Usuario.findById(req.usuario.id).select('-senha');
        res.json(usuario);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// ROTAS DO DIRETOR

// Criar diretor inicial (apenas para setup)
app.post('/api/setup/diretor', async (req, res) => {
    try {
        // Verificar se já existe um diretor
        const diretorExistente = await Usuario.findOne({ tipo: 'diretor' });
        if (diretorExistente) {
            return res.status(400).json({ msg: 'Diretor já existe no sistema' });
        }

        const { nome, email, senha } = req.body;
        
        // Criptografar senha
        const salt = await bcrypt.genSalt(10);
        const senhaCriptografada = await bcrypt.hash(senha, salt);

        const novoDiretor = new Usuario({
            tipo: 'diretor',
            nome,
            email,
            senha: senhaCriptografada
        });

        await novoDiretor.save();
        res.json({ msg: 'Diretor criado com sucesso' });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Criar coordenador
app.post('/api/coordenadores', autenticar, verificarPapel('diretor'), async (req, res) => {
    try {
        const { nome, email, senha, departamento } = req.body;
        
        // Verificar se o email já está em uso
        const usuarioExistente = await Usuario.findOne({ email });
        if (usuarioExistente) {
            return res.status(400).json({ msg: 'Email já está em uso' });
        }

        // Criptografar senha
        const salt = await bcrypt.genSalt(10);
        const senhaCriptografada = await bcrypt.hash(senha, salt);

        const novoCoordenador = new Usuario({
            tipo: 'coordenador',
            nome,
            email,
            senha: senhaCriptografada,
            departamento
        });

        await novoCoordenador.save();
        res.json({ msg: 'Coordenador criado com sucesso', coordenador: novoCoordenador });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Listar coordenadores
app.get('/api/coordenadores', autenticar, verificarPapel('diretor'), async (req, res) => {
    try {
        const coordenadores = await Usuario.find({ tipo: 'coordenador' }).select('-senha');
        res.json(coordenadores);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Atualizar coordenador
app.put('/api/coordenadores/:id', autenticar, verificarPapel('diretor'), async (req, res) => {
    try {
        const { nome, email, departamento, senha } = req.body;
        
        // Verificar se o coordenador existe
        let coordenador = await Usuario.findById(req.params.id);
        if (!coordenador || coordenador.tipo !== 'coordenador') {
            return res.status(404).json({ msg: 'Coordenador não encontrado' });
        }

        // Verificar se o email já está em uso por outro usuário
        if (email && email !== coordenador.email) {
            const emailExistente = await Usuario.findOne({ email });
            if (emailExistente) {
                return res.status(400).json({ msg: 'Email já está em uso' });
            }
        }

        // Atualizar campos
        const camposAtualizados = {};
        if (nome) camposAtualizados.nome = nome;
        if (email) camposAtualizados.email = email;
        if (departamento) camposAtualizados.departamento = departamento;
        
        // Se senha for fornecida, criptografar
        if (senha) {
            const salt = await bcrypt.genSalt(10);
            camposAtualizados.senha = await bcrypt.hash(senha, salt);
        }

        coordenador = await Usuario.findByIdAndUpdate(
            req.params.id,
            { $set: camposAtualizados },
            { new: true }
        ).select('-senha');

        res.json(coordenador);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Remover coordenador
app.delete('/api/coordenadores/:id', autenticar, verificarPapel('diretor'), async (req, res) => {
    try {
        // Verificar se o coordenador existe
        const coordenador = await Usuario.findById(req.params.id);
        if (!coordenador || coordenador.tipo !== 'coordenador') {
            return res.status(404).json({ msg: 'Coordenador não encontrado' });
        }

        // Verificar se o coordenador tem eventos ativos
        const eventosAtivos = await Evento.find({ 
            coordenador: req.params.id,
            status: { $in: ['agendado', 'em_andamento'] }
        });

        if (eventosAtivos.length > 0) {
            return res.status(400).json({ 
                msg: 'Não é possível remover o coordenador pois ele possui eventos ativos' 
            });
        }

        await Usuario.findByIdAndRemove(req.params.id);
        res.json({ msg: 'Coordenador removido com sucesso' });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// ROTAS DO COORDENADOR

// Criar evento
app.post('/api/eventos', autenticar, verificarPapel('coordenador'), async (req, res) => {
    try {
        const { 
            nome, descricao, data, local, horaInicio, horaFim, 
            capacidade, codigoConfirmacao 
        } = req.body;
        
        const novoEvento = new Evento({
            nome,
            descricao,
            data,
            local,
            horaInicio,
            horaFim,
            capacidade,
            codigoConfirmacao,
            coordenador: req.usuario.id
        });

        await novoEvento.save();
        res.json({ msg: 'Evento criado com sucesso', evento: novoEvento });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Listar eventos do coordenador
app.get('/api/eventos/meus', autenticar, verificarPapel('coordenador'), async (req, res) => {
    try {
        const eventos = await Evento.find({ coordenador: req.usuario.id })
            .sort({ data: 1 })
            .populate('coordenador', 'nome email departamento');
        res.json(eventos);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Atualizar evento
app.put('/api/eventos/:id', autenticar, verificarPapel('coordenador'), async (req, res) => {
    try {
        // Verificar se o evento existe e pertence ao coordenador
        let evento = await Evento.findById(req.params.id);
        if (!evento) {
            return res.status(404).json({ msg: 'Evento não encontrado' });
        }

        // Verificar se o coordenador é o dono do evento
        if (evento.coordenador.toString() !== req.usuario.id) {
            return res.status(403).json({ msg: 'Acesso negado. Você não é o coordenador deste evento' });
        }

        // Verificar se o evento já foi encerrado
        if (evento.status === 'encerrado') {
            return res.status(400).json({ msg: 'Não é possível editar um evento encerrado' });
        }

        const { 
            nome, descricao, data, local, horaInicio, horaFim, 
            capacidade, codigoConfirmacao 
        } = req.body;

        // Montar objeto de atualização
        const eventoCampos = {};
        if (nome) eventoCampos.nome = nome;
        if (descricao) eventoCampos.descricao = descricao;
        if (data) eventoCampos.data = data;
        if (local) eventoCampos.local = local;
        if (horaInicio) eventoCampos.horaInicio = horaInicio;
        if (horaFim) eventoCampos.horaFim = horaFim;
        if (capacidade) eventoCampos.capacidade = capacidade;
        if (codigoConfirmacao) eventoCampos.codigoConfirmacao = codigoConfirmacao;

        evento = await Evento.findByIdAndUpdate(
            req.params.id,
            { $set: eventoCampos },
            { new: true }
        );

        res.json(evento);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Alterar status do evento (iniciar/encerrar)
app.put('/api/eventos/:id/status', autenticar, verificarPapel('coordenador'), async (req, res) => {
    try {
        const { status } = req.body;
        
        // Verificar se o status é válido
        if (!['agendado', 'em_andamento', 'encerrado'].includes(status)) {
            return res.status(400).json({ msg: 'Status inválido' });
        }
        
        // Verificar se o evento existe e pertence ao coordenador
        let evento = await Evento.findById(req.params.id);
        if (!evento) {
            return res.status(404).json({ msg: 'Evento não encontrado' });
        }

        // Verificar se o coordenador é o dono do evento
        if (evento.coordenador.toString() !== req.usuario.id) {
            return res.status(403).json({ msg: 'Acesso negado. Você não é o coordenador deste evento' });
        }

        // Verificar transições válidas de status
        if (evento.status === 'encerrado' && status !== 'encerrado') {
            return res.status(400).json({ msg: 'Não é possível reabrir um evento encerrado' });
        }

        evento = await Evento.findByIdAndUpdate(
            req.params.id,
            { $set: { status } },
            { new: true }
        );

        // Se o evento foi encerrado, gerar certificados para os presentes
        if (status === 'encerrado') {
            const inscricoes = await Inscricao.find({
                evento: req.params.id,
                presencaConfirmada: true
            });

            // Gerar certificados para cada aluno presente
            for (const inscricao of inscricoes) {
                // Gerar código único para o certificado
                const codigoCertificado = Math.random().toString(36).substring(2, 15) + 
                                          Math.random().toString(36).substring(2, 15);
                
                const novoCertificado = new Certificado({
                    aluno: inscricao.aluno,
                    evento: inscricao.evento,
                    codigo: codigoCertificado
                });

                await novoCertificado.save();
            }
        }

        res.json({ msg: `Status do evento alterado para ${status}`, evento });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Listar inscritos em um evento
app.get('/api/eventos/:id/inscritos', autenticar, verificarPapel('coordenador'), async (req, res) => {
    try {
        // Verificar se o evento existe e pertence ao coordenador
        const evento = await Evento.findById(req.params.id);
        if (!evento) {
            return res.status(404).json({ msg: 'Evento não encontrado' });
        }

        // Verificar se o coordenador é o dono do evento
        if (evento.coordenador.toString() !== req.usuario.id) {
            return res.status(403).json({ msg: 'Acesso negado. Você não é o coordenador deste evento' });
        }

        // Buscar inscrições do evento
        const inscricoes = await Inscricao.find({ evento: req.params.id })
            .populate('aluno', 'nome email matricula curso');

        res.json(inscricoes);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// ROTAS DO ALUNO

// Registrar aluno
app.post('/api/alunos/registrar', async (req, res) => {
    try {
        const { nome, email, senha, matricula, curso } = req.body;
        
        // Verificar se o email já está em uso
        const emailExistente = await Usuario.findOne({ email });
        if (emailExistente) {
            return res.status(400).json({ msg: 'Email já está em uso' });
        }

        // Verificar se a matrícula já está em uso
        const matriculaExistente = await Usuario.findOne({ matricula });
        if (matriculaExistente) {
            return res.status(400).json({ msg: 'Matrícula já está em uso' });
        }

        // Criptografar senha
        const salt = await bcrypt.genSalt(10);
        const senhaCriptografada = await bcrypt.hash(senha, salt);

        const novoAluno = new Usuario({
            tipo: 'aluno',
            nome,
            email,
            senha: senhaCriptografada,
            matricula,
            curso
        });

        await novoAluno.save();
        
        // Criar e retornar JWT
        const payload = {
            id: novoAluno._id,
            nome: novoAluno.nome,
            email: novoAluno.email,
            tipo: novoAluno.tipo
        };

        jwt.sign(payload, JWT_SECRET, { expiresIn: '8h' }, (err, token) => {
            if (err) throw err;
            res.json({ 
                token,
                usuario: {
                    id: novoAluno._id,
                    nome: novoAluno.nome,
                    email: novoAluno.email,
                    tipo: novoAluno.tipo,
                    matricula: novoAluno.matricula,
                    curso: novoAluno.curso
                }
            });
        });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Listar eventos disponíveis
app.get('/api/eventos/disponiveis', autenticar, verificarPapel('aluno'), async (req, res) => {
    try {
        // Buscar eventos agendados ou em andamento
        const eventos = await Evento.find({
            status: { $in: ['agendado', 'em_andamento'] },
            data: { $gte: new Date() } // Apenas eventos futuros ou de hoje
        })
        .sort({ data: 1 })
        .populate('coordenador', 'nome departamento');

        // Para cada evento, verificar se o aluno já está inscrito
        const eventosFormatados = await Promise.all(eventos.map(async (evento) => {
            const inscricao = await Inscricao.findOne({
                evento: evento._id,
                aluno: req.usuario.id
            });

            return {
                ...evento._doc,
                inscrito: !!inscricao
            };
        }));

        res.json(eventosFormatados);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Inscrever-se em um evento
app.post('/api/eventos/:id/inscrever', autenticar, verificarPapel('aluno'), async (req, res) => {
    try {
        // Verificar se o evento existe
        const evento = await Evento.findById(req.params.id);
        if (!evento) {
            return res.status(404).json({ msg: 'Evento não encontrado' });
        }

        // Verificar se o evento ainda aceita inscrições
        if (evento.status === 'encerrado') {
            return res.status(400).json({ msg: 'Este evento já foi encerrado' });
        }

        // Verificar se o aluno já está inscrito
        const inscricaoExistente = await Inscricao.findOne({
            evento: req.params.id,
            aluno: req.usuario.id
        });

        if (inscricaoExistente) {
            return res.status(400).json({ msg: 'Você já está inscrito neste evento' });
        }

        // Verificar se o evento ainda tem vagas
        const inscricoes = await Inscricao.countDocuments({ evento: req.params.id });
        if (inscricoes >= evento.capacidade) {
            return res.status(400).json({ msg: 'Evento com vagas esgotadas' });
        }

        // Criar nova inscrição
        const novaInscricao = new Inscricao({
            aluno: req.usuario.id,
            evento: req.params.id
        });

        await novaInscricao.save();
        res.json({ msg: 'Inscrição realizada com sucesso', inscricao: novaInscricao });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Confirmar presença em um evento
app.post('/api/eventos/:id/confirmar-presenca', autenticar, verificarPapel('aluno'), async (req, res) => {
    try {
        const { codigo } = req.body;
        
        // Verificar se o evento existe
        const evento = await Evento.findById(req.params.id);
        if (!evento) {
            return res.status(404).json({ msg: 'Evento não encontrado' });
        }

        // Verificar se o código de confirmação está correto
        if (evento.codigoConfirmacao !== codigo) {
            return res.status(400).json({ msg: 'Código de confirmação inválido' });
        }

        // Verificar se o evento está em andamento
        if (evento.status !== 'em_andamento') {
            return res.status(400).json({ msg: 'Só é possível confirmar presença em eventos em andamento' });
        }

        // Verificar se o aluno está inscrito
        let inscricao = await Inscricao.findOne({
            evento: req.params.id,
            aluno: req.usuario.id
        });

        if (!inscricao) {
            return res.status(400).json({ msg: 'Você não está inscrito neste evento' });
        }

        // Atualizar presença
        inscricao = await Inscricao.findOneAndUpdate(
            {
                evento: req.params.id,
                aluno: req.usuario.id
            },
            {
                presencaConfirmada: true,
                dataConfirmacao: Date.now()
            },
            { new: true }
        );

        res.json({ msg: 'Presença confirmada com sucesso', inscricao });
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Listar eventos do aluno
app.get('/api/eventos/minhas-inscricoes', autenticar, verificarPapel('aluno'), async (req, res) => {
    try {
        // Buscar inscrições do aluno
        const inscricoes = await Inscricao.find({ aluno: req.usuario.id })
            .populate({
                path: 'evento',
                populate: { path: 'coordenador', select: 'nome departamento' }
            });

        // Verificar certificados para cada evento encerrado com presença confirmada
        const inscricoesComCertificados = await Promise.all(inscricoes.map(async (inscricao) => {
            if (inscricao.presencaConfirmada && inscricao.evento.status === 'encerrado') {
                const certificado = await Certificado.findOne({
                    evento: inscricao.evento._id,
                    aluno: req.usuario.id
                });

                return {
                    ...inscricao._doc,
                    certificado: certificado ? {
                        id: certificado._id,
                        codigo: certificado.codigo,
                        dataEmissao: certificado.dataEmissao
                    } : null
                };
            }
            return inscricao._doc;
        }));

        res.json(inscricoesComCertificados);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Obter certificado
app.get('/api/certificados/:id', autenticar, verificarPapel('aluno'), async (req, res) => {
    try {
        // Buscar certificado
        const certificado = await Certificado.findById(req.params.id)
            .populate('evento')
            .populate('aluno');

        if (!certificado) {
            return res.status(404).json({ msg: 'Certificado não encontrado' });
        }

        // Verificar se o certificado pertence ao aluno autenticado
        if (certificado.aluno._id.toString() !== req.usuario.id) {
            return res.status(403).json({ msg: 'Acesso negado. Este certificado não pertence a você' });
        }

        // Buscar informações do coordenador
        const coordenador = await Usuario.findById(certificado.evento.coordenador);

        // Calcular duração do evento em horas
        const inicio = certificado.evento.horaInicio.split(':').map(Number);
        const fim = certificado.evento.horaFim.split(':').map(Number);
        const horaInicio = inicio[0] + inicio[1]/60;
        const horaFim = fim[0] + fim[1]/60;
        const duracao = Math.round((horaFim - horaInicio) * 10) / 10; // Arredondar para 1 casa decimal

        // Montar dados do certificado
        const dadosCertificado = {
            id: certificado._id,
            codigo: certificado.codigo,
            dataEmissao: certificado.dataEmissao,
            aluno: {
                nome: certificado.aluno.nome,
                matricula: certificado.aluno.matricula,
                curso: certificado.aluno.curso
            },
            evento: {
                nome: certificado.evento.nome,
                data: certificado.evento.data,
                local: certificado.evento.local,
                duracao: duracao
            },
            coordenador: {
                nome: coordenador.nome,
                departamento: coordenador.departamento
            }
        };

        res.json(dadosCertificado);
    } catch (err) {
        console.error(err.message);
        res.status(500).send('Erro no servidor');
    }
});

// Iniciar o servidor
app.listen(PORT, () => console.log(`Servidor rodando na porta ${PORT}`));