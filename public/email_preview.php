<?php
/**
 * Pré-visualização do Template de E-mail ENFAS GED
 * Mostra como os e-mails serão exibidos com o novo design
 */
require_once '../core/init.php';

// Dados de exemplo para demonstração
$exemplos = [
    'recuperar_senha' => [
        'titulo' => 'Recuperação de Senha',
        'dados' => [
            'usuario' => ['nome' => 'Alessandro Silva'],
            'link' => 'https://ged.enfas.com.br/redefinir-senha?token=abc123xyz',
            'expiracao' => '1 hora'
        ]
    ],
    'usuario_criado' => [
        'titulo' => 'Boas-vindas ao Sistema',
        'dados' => [
            'nome_usuario' => 'Alessandro Silva',
            'email' => 'alessandrosilva@enfas.com.br',
            'senha_temporaria' => 'Temp@2025'
        ]
    ],
    'alerta_vencimento' => [
        'titulo' => 'Alerta de Vencimento',
        'dados' => [
            'nome' => 'Alessandro Silva',
            'documento' => [
                'titulo' => 'Contrato de Prestação de Serviços',
                'vencimento' => '31/12/2025',
                'link' => 'https://ged.enfas.com.br/documentos/12345'
            ],
            'dias' => '7'
        ]
    ],
    'compartilhar_link' => [
        'titulo' => 'Compartilhamento de Documento',
        'dados' => [
            'nome' => 'Alessandro Silva',
            'documento' => [
                'titulo' => 'Relatório Anual 2025',
                'link' => 'https://ged.enfas.com.br/shared/abc123'
            ],
            'mensagem' => 'Conforme solicitado, segue o relatório para sua análise.',
            'usuario' => ['nome' => 'Admin Sistema']
        ]
    ]
];

$template_slug = $_GET['template'] ?? 'alerta_vencimento';
if (!isset($exemplos[$template_slug])) {
    $template_slug = 'alerta_vencimento';
}

$exemplo = $exemplos[$template_slug];

// Renderiza o template
require_once PROJECT_ROOT . '/core/email.php';
try {
    $preview = email_preview_template($pdo, $template_slug, $exemplo['dados']);
    $html_preview = $preview['html'];
} catch (Exception $e) {
    $html_preview = "<div style='padding:40px;text-align:center;color:#ef4444;'><h2>Erro ao renderizar template</h2><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-visualização Template E-mail - ENFAS GED</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; }
        .preview-header { background: linear-gradient(135deg, #1d3441 0%, #2b3f4c 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .template-selector { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .template-selector .btn { margin: 0.25rem; }
        .preview-container { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .preview-iframe { width: 100%; min-height: 800px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .info-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; }
        .badge-custom { background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%); color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="preview-header">
        <div class="container">
            <h1 class="mb-0"><i class="fas fa-envelope-open-text"></i> Pré-visualização de Templates</h1>
            <p class="mb-0 mt-2 opacity-75">Novo Design Estilo eDok - ENFAS GED</p>
        </div>
    </div>

    <div class="container">
        <!-- Seletor de Templates -->
        <div class="template-selector">
            <h5 class="mb-3"><i class="fas fa-th-large"></i> Escolha o Template:</h5>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($exemplos as $slug => $info): ?>
                    <a href="?template=<?= urlencode($slug) ?>" 
                       class="btn <?= $slug === $template_slug ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <i class="fas fa-file-alt"></i> <?= htmlspecialchars($info['titulo']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <h6 class="mb-2"><i class="fas fa-info-circle"></i> Características do Novo Template:</h6>
            <ul class="mb-0 small">
                <li><strong>Design Moderno:</strong> Gradient azul, bordas arredondadas, sombras suaves</li>
                <li><strong>Responsivo:</strong> Otimizado para desktop, tablet e mobile</li>
                <li><strong>Identidade Visual:</strong> Logo ENFAS GED no header com fundo azul gradiente</li>
                <li><strong>Tipografia:</strong> Fonte Inter (Google Fonts) para melhor legibilidade</li>
                <li><strong>Rodapé Completo:</strong> Links para sistema, política de privacidade e suporte</li>
            </ul>
        </div>

        <!-- Preview -->
        <div class="preview-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">
                    <span class="badge-custom"><?= htmlspecialchars($exemplo['titulo']) ?></span>
                </h4>
                <div>
                    <a href="admin_email_template_test.php?id=<?= (int)array_search($template_slug, array_column($pdo->query("SELECT id, slug FROM email_templates")->fetchAll(), 'slug', 'id')) ?: 1 ?>" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-paper-plane"></i> Enviar Teste
                    </a>
                    <a href="admin_email_templates.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>

            <!-- Iframe com o HTML renderizado -->
            <iframe class="preview-iframe" srcdoc="<?= htmlspecialchars($html_preview, ENT_QUOTES) ?>"></iframe>
        </div>

        <!-- Informações Técnicas -->
        <div class="mt-4 p-3 bg-white rounded shadow-sm">
            <h6 class="mb-3"><i class="fas fa-code"></i> Informações Técnicas:</h6>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>Template Slug:</strong> <code><?= htmlspecialchars($template_slug) ?></code></p>
                    <p class="mb-2"><strong>Assunto:</strong> <?= htmlspecialchars($preview['assunto'] ?? 'N/A') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>Cores Principais:</strong></p>
                    <div class="d-flex gap-2">
                        <span style="background: #2563eb; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;" title="Azul Primary"></span>
                        <span style="background: #3b82f6; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;" title="Azul Accent"></span>
                        <span style="background: #1d3441; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;" title="Cinza Escuro"></span>
                        <span style="background: #f8fafc; width: 30px; height: 30px; border-radius: 4px; border: 1px solid #ddd;" title="Cinza Claro"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 mb-4 text-muted">
            <small>© 2025 ENFAS GED - Sistema de Gestão Eletrônica de Documentos</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
