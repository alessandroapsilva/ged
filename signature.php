<?php
include 'auth_check.php';
require_once 'db_config.php'; // Conexão com o banco de dados
require_once 'classes/Document.php';
require_once 'classes/User.php';
require_once 'helpers/pdf_helper.php'; // Helper para manipular PDF

if (!isset($_GET['id'])) {
    die('ID do documento não fornecido.');
}

$documentId = (int)$_GET['id'];
$document = new Document();
$doc = $document->getById($documentId);

if (!$doc) {
    die('Documento não encontrado.');
}

// Verificar se o usuário pode assinar
$userRole = $_SESSION['user']['role'];
$canSign = in_array($userRole, ['Diretor', 'Administrador']);

if (!$canSign) {
    die('Você não tem permissão para assinar documentos.');
}

$message = '';
$user = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature'])) {
    $signatureDataUrl = trim($_POST['signature']);

    if (empty($signatureDataUrl) || strpos($signatureDataUrl, 'data:image/png;base64,') === false) {
        $message = 'A assinatura não pode estar vazia.';
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documentos_assinaturas WHERE documento_id = ? AND usuario_id = ?");
        $stmt->execute([$documentId, $_SESSION['user']['id']]);
        $alreadySigned = $stmt->fetchColumn() > 0;

        if ($alreadySigned) {
            $message = 'Você já assinou este documento.';
        } else {
            $pdo->beginTransaction();
            try {
                $currentTime = date('Y-m-d H:i:s');
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

                // 1. Inserir na tabela de assinaturas
                $details = json_encode(['ip_address' => $ipAddress, 'signature_image_base64' => $signatureDataUrl]);
                $stmt = $pdo->prepare(
                    "INSERT INTO documentos_assinaturas (documento_id, usuario_id, data_assinatura, tipo_assinatura, detalhes) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$documentId, $_SESSION['user']['id'], $currentTime, 'Eletronica', $details]);

                // 2. Atualizar a tabela de documentos (se for a primeira assinatura)
                if (!$doc['assinado']) {
                    $stmt = $pdo->prepare(
                        "UPDATE documentos SET assinado = 1, data_assinatura = ?, assinado_por = ? WHERE id = ?"
                    );
                    $stmt->execute([$currentTime, $_SESSION['user']['id'], $documentId]);
                }

                // 3. Log da ação
                $user->logAction($_SESSION['user']['id'], 'SIGN_DOCUMENT', 'document', $documentId, 'Documento assinado eletronicamente');
                
                $pdo->commit();
                $message = 'Documento assinado com sucesso!';

                // 4. Estampar a assinatura no PDF
                $stmt = $pdo->prepare("SELECT filepath FROM attachments WHERE document_id = ? AND (LOWER(filename) LIKE '%.pdf' OR mimetype = 'application/pdf') ORDER BY id ASC LIMIT 1");
                $stmt->execute([$documentId]);
                $attachment = $stmt->fetch();

                if ($attachment) {
                    // Construir o caminho absoluto do arquivo
                    $pdfPath = 'c:/xampp/htdocs/ged/' . $attachment['filepath'];
                    
                    if (file_exists($pdfPath)) {
                        $stampSuccess = embedSignatureInPdf($pdfPath, $signatureDataUrl, $_SESSION['user']['name'], $currentTime, $ipAddress);
                        if (!$stampSuccess) {
                            $message .= ' (Aviso: Falha ao estampar a assinatura no PDF.)';
                        }
                    } else {
                        $message .= ' (Aviso: arquivo PDF não encontrado para estampagem da assinatura. Caminho: ' . $pdfPath . ')';
                    }
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Erro ao assinar o documento: ' . $e->getMessage();
            }
        }
    }
}

// Carregar assinaturas existentes do banco de dados
$stmt = $pdo->prepare(
    "SELECT da.*, u.name as user_name FROM documentos_assinaturas da JOIN usuarios u ON da.usuario_id = u.id WHERE da.documento_id = ? ORDER BY da.data_assinatura ASC"
);
$stmt->execute([$documentId]);
$signatures = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Documento - E-Doc</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .signature-pad { border: 2px dashed #ccc; border-radius: 8px; cursor: crosshair; }
        .signature-pad-actions { margin-top: 1rem; }
    </style>
</head>
<body>

    <div class="container">
        <a href="ver_processo.php?id=<?php echo $documentId; ?>">&larr; Voltar ao Documento</a>

        <h1>Assinatura Digital</h1>
        <h2><?php echo htmlspecialchars($doc['title']); ?></h2>

        <?php if ($message): ?>
            <div class="bulk-message <?php echo strpos($message, 'sucesso') !== false ? 'success' : 'error'; ?>" style="margin-bottom: 2rem;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

            <!-- Assinar Documento -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Assinar Documento</h2>
                <p style="margin-bottom: 1rem; color: #ddd;">
                    Como <strong><?php echo htmlspecialchars($_SESSION['user']['name']); ?></strong>, desenhe sua assinatura abaixo.
                </p>

                <form method="POST" id="signature-form">
                    <div id="signature-pad-container">
                        <canvas id="signature-pad" class="signature-pad" width=450 height=200></canvas>
                    </div>
                    <input type="hidden" name="signature" id="signature-data">

                    <div class="signature-pad-actions">
                        <button type="button" id="clear-signature" class="button button-secondary">Limpar</button>
                    </div>

                    <div style="background: rgba(255,0,0,0.1); padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                        <strong>Atenção:</strong> Esta assinatura representa sua aprovação formal do documento.
                    </div>

                    <button type="submit" id="save-signature" class="button" style="background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); width: 100%; padding: 15px; font-size: 1.1rem;">
                        🖊️ Assinar e Salvar
                    </button>
                </form>
            </div>

            <!-- Assinaturas Existentes -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Assinaturas do Documento</h2>

                <?php if (!empty($signatures)): ?>
                    <div>
                        <?php foreach ($signatures as $signature): 
                            $details = json_decode($signature['detalhes'], true);
                        ?>
                            <div style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <strong><?php echo htmlspecialchars($signature['user_name']); ?></strong>
                                    <span style="color: #888; font-size: 0.9rem;"><?php echo date('d/m/Y H:i', strtotime($signature['data_assinatura'])); ?></span>
                                </div>
                                <div style="background: #fff; padding: 0.5rem; border-radius: 4px; margin-bottom: 0.5rem; text-align: center;">
                                    <?php if (isset($details['signature_image_base64'])): ?>
                                        <img src="<?php echo $details['signature_image_base64']; ?>" alt="Assinatura" style="max-width: 200px; max-height: 80px;">
                                    <?php else: ?>
                                        <span style="font-family: monospace; color: #333;"><?php echo htmlspecialchars($details['signature_text'] ?? 'Assinatura de texto'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="color: #666; font-size: 0.8rem;">
                                    IP: <?php echo htmlspecialchars($details['ip_address'] ?? 'N/A'); ?> | Tipo: <?php echo htmlspecialchars($signature['tipo_assinatura']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; color: #888; padding: 2rem;">
                        <p>📝 Nenhuma assinatura ainda.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = document.getElementById('signature-pad');
            var signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)' // necessary for saving image as JPEG; can be removed for PNG.
            });

            document.getElementById('clear-signature').addEventListener('click', function () {.
                signaturePad.clear();
            });

            document.getElementById('signature-form').addEventListener('submit', function (event) {
                if (signaturePad.isEmpty()) {
                    alert("Por favor, forneça sua assinatura.");
                    event.preventDefault();
                } else {
                    var dataURL = signaturePad.toDataURL('image/png');
                    document.getElementById('signature-data').value = dataURL;
                }
            });
        });
    </script>

</body>
</html>