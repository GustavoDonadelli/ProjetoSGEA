// api.js - Funções para integração com o backend

const API_URL = 'http://localhost:3000/api';
let authToken = localStorage.getItem('authToken');
let userData = JSON.parse(localStorage.getItem('userData') || '{}');

// Configurar cabeçalhos padrão para requisições
const getHeaders = () => {
    const headers = {
        'Content-Type': 'application/json'
    };
    
    if (authToken) {
        headers['x-auth-token'] = authToken;
    }
    
    return headers;
};

// Função para definir o token após o login
const setAuthToken = (token, user) => {
    authToken = token;
}