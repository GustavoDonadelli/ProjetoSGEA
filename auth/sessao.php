<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado.
if (!isset($_SESSION['id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado. Faça login para continuar.']);
    exit;
}

// A verificação específica do tipo de usuário (ex: 'aluno', 'coordenador') 
// deve ser feita no script que inclui este arquivo, se necessário.
// Exemplo de como um script pode verificar o tipo:
// if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'aluno') {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'message' => 'Acesso negado. Esta ação é permitida apenas para alunos.']);
//     exit;
// }
?>
