<?php
// Configura mysqli para lançar exceções em caso de erro.
// Isso é crucial para um tratamento de erro consistente.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$usuario = 'root';
$senha = ''; // Deixe em branco se não houver senha para o root no XAMPP
$database = 'sgea'; // Certifique-se de que este banco de dados existe
$host = 'localhost';

// A conexão agora lançará uma exceção mysqli_sql_exception em caso de falha,
// que será capturada pelo script que incluiu este arquivo.
$mysqli = new mysqli($host, $usuario, $senha, $database);

// Opcional: Definir o charset da conexão para UTF-8, se ainda não estiver configurado no servidor.
// if (!$mysqli->set_charset('utf8mb4')) {
//     // Log ou tratar erro de charset, embora mysqli_report deva pegar isso.
//     error_log('Erro ao definir o charset: ' . $mysqli->error);
// }

// A verificação explícita de $mysqli->connect_error torna-se menos necessária
// com MYSQLI_REPORT_STRICT, pois uma exceção já teria sido lançada.
// No entanto, pode ser mantida para clareza ou como uma dupla verificação.
if ($mysqli->connect_error) {
    // Esta exceção também seria capturada pelo script chamador.
    throw new mysqli_sql_exception('Falha na conexão com o banco de dados (connect_error): ' . $mysqli->connect_error, $mysqli->connect_errno);
}
?>