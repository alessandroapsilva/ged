<?php
include 'auth_check.php';

// Verificar se é administrador
if ($_SESSION['user']['role'] !== 'Administrador') {
    die('Acesso negado. Apenas administradores podem acessar esta página.');
}

require_once 'classes/User.php';
require_once 'classes/Document.php';

$user = new User();
$document = new Document();

$users = $user->getAll();
$categories = $document->getCategories();

// Ações administrativas
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        // Adicionar usuário
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $name = $_POST['name'];
        $role = $_POST['role'];

        // Inserir no banco
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, password_hash, name, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $password, $name, $role])) {
            $message = 'Usuário adicionado com sucesso!';
            $users = $user->getAll(); // Recarregar lista
        } else {
            $message = 'Erro ao adicionar usuário.';
        }
    } elseif (isset($_POST['add_category'])) {
        // Adicionar categoria
        $name = $_POST['category_name'];
        $description = $_POST['category_description'];

        if ($document->createCategory($name, $description)) {
            $message = 'Categoria adicionada com sucesso!';
            $categories = $document->getCategories(); // Recarregar lista
        } else {
            $message = 'Erro ao adicionar categoria.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - E-Doc</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="container">
        <a href="index.php">&larr; Voltar ao Painel</a>

        <h1>Painel Administrativo</h1>

        <?php if ($message): ?>
            <div class="bulk-message success" style="margin-bottom: 2rem;">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">

            <!-- Gerenciamento de Usuários -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Gerenciar Usuários</h2>

                <form method="POST" style="margin-bottom: 2rem;">
                    <h3>Adicionar Novo Usuário</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div>
                            <label>Nome:</label>
                            <input type="text" name="name" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                        <div>
                            <label>Usuário:</label>
                            <input type="text" name="username" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                        <div>
                            <label>Senha:</label>
                            <input type="password" name="password" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                        </div>
                        <div>
                            <label>Função:</label>
                            <select name="role" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                                <option value="Analista">Analista</option>
                                <option value="Diretor">Diretor</option>
                                <option value="Administrador">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="button">Adicionar Usuário</button>
                </form>

                <h3>Usuários Cadastrados</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table style="width: 100%; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr>
                                <th style="padding: 0.5rem;">Nome</th>
                                <th style="padding: 0.5rem;">Usuário</th>
                                <th style="padding: 0.5rem;">Função</th>
                                <th style="padding: 0.5rem;">Criado em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($u['name']); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($u['username']); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($u['role']); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gerenciamento de Categorias -->
            <div style="background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
                <h2>Gerenciar Categorias</h2>

                <form method="POST" style="margin-bottom: 2rem;">
                    <h3>Adicionar Nova Categoria</h3>
                    <div style="margin-bottom: 1rem;">
                        <label>Nome da Categoria:</label>
                        <input type="text" name="category_name" required style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label>Descrição:</label>
                        <textarea name="category_description" style="width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; resize: vertical; min-height: 60px;"></textarea>
                    </div>
                    <button type="submit" name="add_category" class="button">Adicionar Categoria</button>
                </form>

                <h3>Categorias Cadastradas</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <table style="width: 100%; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr>
                                <th style="padding: 0.5rem;">Nome</th>
                                <th style="padding: 0.5rem;">Descrição</th>
                                <th style="padding: 0.5rem;">Criada em</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($cat['name']); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($cat['description'] ?? 'N/A'); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Logs de Auditoria -->
        <div style="margin-top: 2rem; background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.2);">
            <h2>Logs de Auditoria (Últimas 50 ações)</h2>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php
                $db = Database::getInstance()->getConnection();
                $logs = $db->query("
                    SELECT l.*, u.name as user_name
                    FROM audit_logs l
                    LEFT JOIN users u ON l.user_id = u.id
                    ORDER BY l.created_at DESC
                    LIMIT 50
                ")->fetchAll();

                if (!empty($logs)): ?>
                    <table style="width: 100%; background: rgba(255,255,255,0.05); border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr>
                                <th style="padding: 0.5rem;">Usuário</th>
                                <th style="padding: 0.5rem;">Ação</th>
                                <th style="padding: 0.5rem;">Entidade</th>
                                <th style="padding: 0.5rem;">Data/Hora</th>
                                <th style="padding: 0.5rem;">Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($log['user_name'] ?? 'Sistema'); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($log['entity_type'] . ($log['entity_id'] ? ' #' . $log['entity_id'] : '')); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($log['details'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #888;">Nenhum log encontrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>