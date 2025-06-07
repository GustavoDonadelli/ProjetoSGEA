<?php
require_once('tcpdf/tcpdf.php');

function gerarPDFCertificado($aluno_nome, $evento_id, $evento_nome, $codigo_verificacao, $nome_arquivo, $carga_horaria) {
    // Cria novo PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurações do documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SGEA - Sistema de Gestão de Eventos Acadêmicos');
    $pdf->SetTitle('Certificado de Participação');
    $pdf->SetSubject('Certificado');
    
    // Remove cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Adiciona página
    $pdf->AddPage('L', 'A4');
    
    // Configurações de estilo
    $primaryColor = '#0066cc';
    $secondaryColor = '#333333';
    
    // HTML do certificado
    $html = '
    <style>
        .certificate-container {
            width: 100%;
            height: 100%;
            position: relative;
            border: 15px solid #f5f5f5;
        }
        .certificate-border {
            border: 2px solid ' . $primaryColor . ';
            padding: 20px;
            height: 100%;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            width: 120px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: ' . $primaryColor . ';
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 16px;
            color: ' . $secondaryColor . ';
            margin-bottom: 30px;
        }
        .content {
            text-align: center;
            margin: 40px 0;
        }
        .text {
            font-size: 18px;
            margin: 15px 0;
            line-height: 1.6;
        }
        .name {
            font-size: 22px;
            font-weight: bold;
            color: ' . $primaryColor . ';
            margin: 25px 0;
        }
        .event {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .footer {
            position: absolute;
            bottom: 30px;
            width: calc(100% - 40px);
            text-align: center;
            font-size: 12px;
            color: ' . $secondaryColor . ';
        }
        .signature {
            margin-top: 40px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid ' . $secondaryColor . ';
            margin: 0 auto;
            padding-top: 5px;
        }
        .verification {
            font-size: 10px;
            margin-top: 20px;
        }
    </style>
    
    <div class="certificate-container">
        <div class="certificate-border">
            <div class="header">
                <img src="images/logo.png" class="logo" alt="Logo da Instituição">
                <div class="title">CERTIFICADO</div>
                <div class="subtitle">Sistema de Gestão de Eventos Acadêmicos</div>
            </div>
            
            <div class="content">
                <div class="text">Certificamos que</div>
                <div class="name">' . htmlspecialchars($aluno_nome) . '</div>
                <div class="text">participou do evento</div>
                <div class="event">' . htmlspecialchars($evento_nome) . '</div>
                <div class="text">com carga horária de ' . $carga_horaria . ' horas.</div>
            </div>
            
            <div class="footer">
                <div class="signature">
                    <div class="signature-line"></div>
                    <div>Coordenador do Evento</div>
                </div>
                
                <div class="verification">
                    Código de verificação: ' . $codigo_verificacao . '<br>
                    Emitido em: ' . date('d/m/Y') . '
                </div>
            </div>
        </div>
    </div>
    ';
    
    // Escreve o conteúdo
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Caminho para salvar o arquivo
    $caminho = __DIR__ . '/../certificados/' . $nome_arquivo;
    
    // Verifica se o diretório existe
    if (!file_exists(dirname($caminho))) {
        mkdir(dirname($caminho), 0777, true);
    }
    
    // Salva o arquivo
    $pdf->Output($caminho, 'F');
}