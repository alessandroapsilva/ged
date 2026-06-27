<?php
include 'auth_check.php';
require_once 'classes/Document.php';

$document = new Document();
$templates = $document->getTemplates($_SESSION['user']['id']);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_template'])) {
        $templateData = [
            'name' => $_POST['template_name'],
            'description' => $_POST['template_description'],
            'content_template' => $_POST['template_content'],
            'category_id' => $_POST['category_id'] ?: null,
            'is_public' => isset($_POST['is_public'])
        ];

        if ($document->createTemplate($templateData, $_SESSION['user']['id'])) {
            $message = 'Template criado com sucesso!';
            $templates = $document->getTemplates($_SESSION['user']['id']);
        } else {
            $message = 'Erro ao criar template.';
        }
    } elseif (isset($_POST['use_template'])) {
        $templateId = $_POST['template_id'];
        $documentData = [
            'title' => $_POST['document_title'],
            'priority' => $_POST['priority'],
            'deadline' => $_POST['deadline'] ?: null
        ];

        $docId = $document->createFromTemplate($templateId, $_SESSION['user']['id'], $documentData);
        if ($docId) {
            header('Location: ver_processo.php?id=' . $docId);
            exit;
        } else {
            $message = 'Erro ao criar documento do template.';
        }
    }
}

$categories = $document->getCategories();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates - E-Doc</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <a href="index.php">&larr; Voltar ao Painel</a>

        <h1>Sistema de Templates</h1>

        <?php if ($message): ?>
            <div class="bulk-message success" style="margin-bottom: 2rem;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">

            <!-- Criar Template -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Criar Novo Template</h2>
                <form method="POST">
                    <div style="margin-bottom: 1rem;">
                        <label>Nome do Template:</label>
                        <input type="text" name="template_name" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Descrição:</label>
                        <textarea name="template_description" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; resize: vertical; min-height: 60px;"></textarea>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Categoria:</label>
                        <select name="category_id" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                            <option value="">Nenhuma</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Template de Conteúdo:</label>
                        <textarea name="template_content" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; resize: vertical; min-height: 200px; font-family: monospace;" placeholder="Use {{variable}} para campos dinâmicos. Ex: Prezado {{nome}}, seu documento {{titulo}} foi {{status}}."></textarea>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>
                            <input type="checkbox" name="is_public" checked>
                            Template público (visível para todos)
                        </label>
                    </div>
                    <button type="submit" name="create_template" class="button">Criar Template</button>
                </form>
            </div>

            <!-- Templates Disponíveis -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Templates Disponíveis</h2>
                <?php if (!empty($templates)): ?>
                    <div style="display: grid; gap: 1rem;">
                        <?php foreach ($templates as $template): ?>
                            <div class="template-card" style="background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                                <h3><?php echo htmlspecialchars($template['name']); ?></h3>
                                <p><?php echo htmlspecialchars($template['description'] ?? 'Sem descrição'); ?></p>
                                <p><small>Categoria: <?php echo htmlspecialchars($template['category_name'] ?? 'Nenhuma'); ?> | Criado por: <?php echo htmlspecialchars($template['creator_name']); ?></small></p>

                                <!-- Formulário para usar template -->
                                <form method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 0.5rem; align-items: end;">
                                        <div>
                                            <label>Título do Documento:</label>
                                            <input type="text" name="document_title" required style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                                        </div>
                                        <div>
                                            <label>Prioridade:</label>
                                            <select name="priority" style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                                                <option value="Baixa">Baixa</option>
                                                <option value="Média" selected>Média</option>
                                                <option value="Alta">Alta</option>
                                                <option value="Urgente">Urgente</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label>Prazo:</label>
                                            <input type="date" name="deadline" style="width: 100%; padding: 6px; border-radius: 4px; border: 1px solid #ccc;">
                                        </div>
                                        <button type="submit" name="use_template" class="button" style="padding: 6px 12px; font-size: 0.9rem;">Usar Template</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #888;">Nenhum template disponível.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>