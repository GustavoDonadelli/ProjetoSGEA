<?php
header('Content-Type: application/json');
require __DIR__ . '/conexao/connect.php';
require __DIR__ . '/conexao/protect.php';

// Verifica se é coordenador
if ($_SESSION['tipo'] !== 'coordenador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$evento_id = filter_var($data['evento_id'], FILTER_VALIDATE_INT);
$carga_horaria = filter_var($data['carga_horaria'], FILTER_VALIDATE_INT) ?: 8;

try {
    // Verifica se o coordenador é responsável pelo evento
    $check_sql = "SELECT id, nome FROM eventos WHERE id = ? AND coordenador_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("ii", $evento_id, $_SESSION['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Você não tem permissão para emitir certificados deste evento');
    }
    
    $evento = $check_result->fetch_assoc();
    
    // Verifica se já existem certificados para este evento
    $cert_check_sql = "SELECT id FROM certificados WHERE evento_id = ? LIMIT 1";
    $cert_check_stmt = $mysqli->prepare($cert_check_sql);
    $cert_check_stmt->bind_param("i", $evento_id);
    $cert_check_stmt->execute();
    
    if ($cert_check_stmt->get_result()->num_rows > 0) {
        throw new Exception('Certificados já foram emitidos para este evento');
    }
    
    // Obtém alunos que confirmaram presença
    $alunos_sql = "SELECT p.aluno_id, u.nome, u.email 
                   FROM presencas p
                   JOIN usuarios u ON p.aluno_id = u.id
                   WHERE p.evento_id = ?";
    $alunos_stmt = $mysqli->prepare($alunos_sql);
    $alunos_stmt->bind_param("i", $evento_id);
    $alunos_stmt->execute();
    $alunos_result = $alunos_stmt->get_result();

    $certificados_gerados = 0;
    $mysqli->begin_transaction();
    
    try {
        while ($aluno = $alunos_result->fetch_assoc()) {
            $codigo_verificacao = bin2hex(random_bytes(16));
            $data_emissao = date('Y-m-d H:i:s');
            $nome_arquivo = "certificado_{$evento_id}_{$aluno['aluno_id']}.pdf";
            $link_certificado = "certificados/$nome_arquivo";

            // Insere o certificado no banco
            $insert_sql = "INSERT INTO certificados 
                          (aluno_id, evento_id, codigo_verificacao, data_emissao, link_certificado, carga_horaria)
                          VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $mysqli->prepare($insert_sql);
            $insert_stmt->bind_param(
                "iisssi", 
                $aluno['aluno_id'], 
                $evento_id, 
                $codigo_verificacao, 
                $data_emissao, 
                $link_certificado,
                $carga_horaria
            );
            $insert_stmt->execute();
            
            // Gera o PDF
            require __DIR__ . '/pdf_generator.php';
            gerarPDFCertificado(
                $aluno['nome'],
                $evento_id,
                $evento['nome'],
                $codigo_verificacao,
                $nome_arquivo,
                $carga_horaria
            );
            
            $certificados_gerados++;
        }

        // Marca o evento como tendo certificados emitidos
        $update_sql = "UPDATE eventos SET certificados_emitidos = 1 WHERE id = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param("i", $evento_id);
        $update_stmt->execute();

        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Certificados gerados com sucesso',
            'certificados_gerados' => $certificados_gerados
        ]);

    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}