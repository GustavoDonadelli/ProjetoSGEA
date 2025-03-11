const express = require('express');
const mongoose = require('mongoose');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Conexão com o MongoDB
mongoose.connect('mongodb://localhost:27017/eventos-academicos');

// Servir arquivos estáticos
app.use(express.static(path.join(__dirname, '../public')));

// Rota para a raiz
app.get('/', (req, res) => {
    res.redirect('/login.html');
});

// Rotas para os painéis
app.get('/aluno', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/aluno.html'));
});

app.get('/coordenador', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/coordenador.html'));
});

app.get('/diretor', (req, res) => {
    res.sendFile(path.join(__dirname, '../public/diretor.html'));
});

// Iniciar servidor
app.listen(PORT, () => {
    console.log(`Servidor rodando na porta ${PORT}`);
});