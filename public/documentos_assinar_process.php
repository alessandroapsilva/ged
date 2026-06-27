<?php
require_once '../core/init.php';
require_once '../core/assinatura_digital.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Acesso negado']));
}

try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception('Método inválido');
    }

    // Valida parâmetros
    if (!isset($_POST['documento_id']) || !isset($_FILES['certificado']) || !isset($_POST['senha'])) {
        throw new Exception('Parâmetros inválidos');
    }

    $documento_id = (int)$_POST['documento_id'];
    $senha = $_POST['senha'];

    // Move o certificado para um local temporário
    $certificado_temp = tempnam(sys_get_temp_dir(), 'cert_');
    if (!move_uploaded_file($_FILES['certificado']['tmp_name'], $certificado_temp)) {
        throw new Exception('Erro ao processar certificado');
    }

    try {
        // Assina o documento
        $assinatura = new AssinaturaDigital($pdo, $_SESSION['usuario']['id']);
        $assinatura->assinarDocumento($documento_id, $certificado_temp, $senha);

        // Remove o certificado temporário
        unlink($certificado_temp);

    // Registra no log (se a tabela existir)
    try {
        $sql = "INSERT INTO log_sistema (usuario_id, acao, tabela, registro_id, detalhes) 
            VALUES (?, 'assinar', 'documentos', ?, 'Documento assinado digitalmente')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['usuario']['id'], $documento_id]);
    } catch (Throwable $e) {
        error_log('documentos_assinar_process: log_sistema indisponível - ' . $e->getMessage());
    }

        // Retorna sucesso
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // Garante que o certificado temporário seja removido em caso de erro
        if (file_exists($certificado_temp)) {
            unlink($certificado_temp);
        }
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}