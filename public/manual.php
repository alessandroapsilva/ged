<?php
// public/sobre.php - PÃ¡gina Sobre o Sistema
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// InformaÃ§Ãµes do sistema
$versao_sistema = '2.0.0';
$data_versao = '29/10/2025';
$ambiente = getenv('APP_ENV') ?: 'production';

// EstatÃ­sticas do sistema
try {
    $stats = [];
    $stats['documentos'] = $pdo->query("SELECT COUNT(*) FROM documentos WHERE apagado_em IS NULL")->fetchColumn();
    $stats['usuarios'] = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $stats['tipos'] = $pdo->query("SELECT COUNT(*) FROM tipos_documento")->fetchColumn();
    $stats['assinaturas'] = $pdo->query("SELECT COUNT(*) FROM documentos_assinaturas")->fetchColumn();
    $stats['compartilhamentos'] = $pdo->query("SELECT COUNT(*) FROM documento_links WHERE expires_at IS NULL OR expires_at > NOW()")->fetchColumn();
} catch (Exception $e) {
    $stats = ['documentos' => 0, 'usuarios' => 0, 'tipos' => 0, 'assinaturas' => 0, 'compartilhamentos' => 0];
}

// Bibliotecas de terceiros
$packages = [];
$vendorDir = dirname(__DIR__) . '/vendor';
if (is_dir($vendorDir)) {
    foreach (glob($vendorDir . '/*/*/composer.json') as $composerFile) {
        try {
            $data = json_decode((string)file_get_contents($composerFile), true) ?: [];
            if (!empty($data['name'])) {
                $packages[] = [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'license' => is_array($data['license'] ?? null) ? implode(', ', $data['license']) : ($data['license'] ?? 'N/D'),
                    'homepage' => $data['homepage'] ?? ($data['support']['source'] ?? ''),
                ];
            }
        } catch (Throwable $e) {}
    }
}

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-info-circle"></i> Sobre o Sistema</h1>
                </div>
                <div class="col-sm-6">
                // public/manual.php - Manual do Usuário
                        <li class="breadcrumb-item"><a href="painel_produtividade.php">Painel</a></li>
                        <li class="breadcrumb-item active">Sobre</li>
                    </ol>
                                <tr><td class="font-weight-bold">UsuÃ¡rios:</td><td><?= number_format($stats['usuarios'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Tipos de Documento:</td><td><?= number_format($stats['tipos'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Assinaturas Digitais:</td><td><?= number_format($stats['assinaturas'], 0, ',', '.') ?></td></tr>
                                <tr><td class="font-weight-bold">Compartilhamentos Ativos:</td><td><?= number_format($stats['compartilhamentos'], 0, ',', '.') ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
                                    <h1><i class="fas fa-book"></i> Manual do Usuário</h1>
            <!-- Recursos do Sistema -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-star"></i> Principais Recursos</h3>
                                        <li class="breadcrumb-item active">Manual</li>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="fas fa-folder-open"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">GestÃ£o de Documentos</span>
                                    <span class="info-box-number">Upload, organizaÃ§Ã£o e pesquisa avanÃ§ada</span>
                        </div>
                            <div class="row">
                                <!-- Navegação Lateral -->
                                <div class="col-md-3">
                                    <div class="card card-outline card-primary sticky-top" style="top:70px;">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-list"></i> Índice</h3>
                                        </div>
                                        <div class="card-body p-0">
                                            <ul class="nav nav-pills flex-column">
                                                <li class="nav-item"><a class="nav-link" href="#inicio">Início Rápido</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#documentos">Gestão de Documentos</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#pastas">Organização em Pastas</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#assinaturas">Assinaturas Digitais</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#vencimentos">Controle de Vencimentos</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#compartilhamento">Compartilhamento</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#pesquisa">Pesquisa Avançada</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#relatorios">Relatórios</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#seguranca">Segurança</a></li>
                                                <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Conteúdo Principal -->
                                <div class="col-md-9">
                    
                                    <!-- Início Rápido -->
                                    <div class="card card-primary card-outline" id="inicio">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-rocket"></i> Início Rápido</h3>
                                        </div>
                                        <div class="card-body">
                                            <p>Bem-vindo ao <strong><?= BRAND_NAME ?></strong>! Este manual irá guiá-lo através das principais funcionalidades do sistema.</p>
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>Primeiros Passos:</strong>
                                                <ol class="mb-0 mt-2">
                                                    <li>Familiarize-se com a barra lateral de navegação</li>
                                                    <li>Explore a página de <strong>Documentos</strong> para ver seus arquivos</li>
                                                    <li>Crie sua primeira pasta para organizar documentos</li>
                                                    <li>Faça upload de um documento de teste</li>
                                                    <li>Experimente a pesquisa avançada</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Gestão de Documentos -->
                                    <div class="card card-success card-outline" id="documentos">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-file-alt"></i> Gestão de Documentos</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Upload de Documentos</h5>
                                            <p>Para fazer upload de um novo documento:</p>
                                            <ol>
                                                <li>Clique no botão <span class="badge badge-primary"><i class="fas fa-plus"></i> Novo Documento</span></li>
                                                <li>Preencha o título e selecione o tipo de documento</li>
                                                <li>Escolha a pasta de destino (opcional)</li>
                                                <li>Defina a data de vencimento se aplicável</li>
                                                <li>Selecione o arquivo em seu computador</li>
                                                <li>Clique em <strong>Salvar</strong></li>
                                            </ol>
                            
                                            <h5 class="mt-4">Visualização e Download</h5>
                                            <p>Você pode visualizar documentos PDF diretamente no navegador ou fazer download clicando no ícone correspondente.</p>
                            
                                            <h5 class="mt-4">Edição de Propriedades</h5>
                                            <p>Clique em <strong>Propriedades</strong> para editar título, tipo, vencimento e outras informações do documento.</p>
                            
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                                <strong>Importante:</strong> Documentos assinados digitalmente não podem ter seu arquivo alterado para preservar a integridade da assinatura.
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pastas -->
                                    <div class="card card-info card-outline" id="pastas">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-folder"></i> Organização em Pastas</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Criar Pastas</h5>
                                            <p>Use pastas para organizar documentos por projeto, departamento, ano ou qualquer critério relevante.</p>
                                            <ol>
                                                <li>Na página de Documentos, clique em <span class="badge badge-success"><i class="fas fa-folder-plus"></i> Nova Pasta</span></li>
                                                <li>Digite o nome da pasta</li>
                                                <li>A pasta será criada dentro da pasta atual (se houver)</li>
                                            </ol>
                            
                                            <h5 class="mt-4">Navegação</h5>
                                            <p>Use as "migalhas de pão" (breadcrumbs) no topo da página para navegar entre pastas pai e filho.</p>
                            
                                            <h5 class="mt-4">Mover Documentos</h5>
                                            <p>Você pode mover documentos editando suas propriedades e selecionando uma nova pasta de destino.</p>
                                        </div>
                                    </div>

                                    <!-- Assinaturas Digitais -->
                                    <div class="card card-warning card-outline" id="assinaturas">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-signature"></i> Assinaturas Digitais ICP-Brasil</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>O que é ICP-Brasil?</h5>
                                            <p>A <strong>Infraestrutura de Chaves Públicas Brasileira (ICP-Brasil)</strong> é o sistema que garante validade jurídica às assinaturas digitais no Brasil, equivalente à assinatura manuscrita.</p>
                            
                                            <h5 class="mt-4">Como Assinar um Documento</h5>
                                            <ol>
                                                <li>Conecte seu certificado digital A1 ou A3 ao computador</li>
                                                <li>Abra o documento PDF</li>
                                                <li>Em seu leitor de PDF (Adobe Acrobat), use a função de assinatura digital</li>
                                                <li>Selecione seu certificado ICP-Brasil</li>
                                                <li>Faça upload do documento assinado no sistema</li>
                                            </ol>
                            
                                            <h5 class="mt-4">Validação de Assinaturas</h5>
                                            <p>O sistema valida automaticamente assinaturas ICP-Brasil. Na página de <strong>Propriedades</strong> do documento você verá:</p>
                                            <ul>
                                                <li><strong>Certificação Técnica:</strong> ICP-Brasil (ITI) e Adobe AATL</li>
                                                <li><strong>Detalhes do Certificado:</strong> Nome do titular, CPF/CNPJ, autoridade certificadora</li>
                                                <li><strong>Validade:</strong> Status atual do certificado (válido, expirado, revogado)</li>
                                            </ul>
                            
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                <strong>Reconhecimento Automático:</strong> Certificados ICP-Brasil são automaticamente reconhecidos no Adobe Reader sem necessidade de importação manual, graças à inclusão no Adobe AATL (Approved Trust List).
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Vencimentos -->
                                    <div class="card card-danger card-outline" id="vencimentos">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Controle de Vencimentos</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Prazos Legais Automáticos</h5>
                                            <p>O sistema calcula automaticamente os prazos de guarda legal conforme a legislação brasileira:</p>
                                            <ul>
                                                <li><strong>Documentos Fiscais:</strong> 6 anos (art. 195 CTN)</li>
                                                <li><strong>Documentos Trabalhistas:</strong> 5 anos (CLT)</li>
                                                <li><strong>Prontuários Médicos:</strong> 20 anos (CFM)</li>
                                                <li><strong>Contratos:</strong> 10 anos (Código Civil)</li>
                                                <li><strong>Documentos Societários:</strong> Permanente</li>
                                            </ul>
                            
                                            <h5 class="mt-4">Indicador Global</h5>
                                            <p>No cabeçalho do sistema, você verá um ícone <i class="fas fa-calendar-alt text-warning"></i> que mostra documentos vencidos e a vencer.</p>
                            
                                            <h5 class="mt-4">Filtros Rápidos</h5>
                                            <p>Na página de Documentos, use os chips de filtro:</p>
                                            <ul>
                                                <li><span class="badge badge-danger">Vencidos</span> - Prazo expirado</li>
                                                <li><span class="badge badge-warning">A Vencer</span> - Próximo de vencer (30 dias)</li>
                                                <li><span class="badge badge-secondary">Sem Vencimento</span> - Sem prazo definido</li>
                                            </ul>
                            
                                            <h5 class="mt-4">Relatório de Vencimentos</h5>
                                            <p>Acesse <strong>Relatórios > Vencimentos</strong> para ver estatísticas completas e exportar para Excel (CSV).</p>
                                        </div>
                                    </div>

                                    <!-- Compartilhamento -->
                                    <div class="card card-secondary card-outline" id="compartilhamento">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-share-alt"></i> Compartilhamento Seguro</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Criar Link de Compartilhamento</h5>
                                            <p>Para compartilhar um documento com pessoas externas:</p>
                                            <ol>
                                                <li>Abra as <strong>Propriedades</strong> do documento</li>
                                                <li>Na seção "Compartilhamento", clique em <strong>Criar Link</strong></li>
                                                <li>Defina uma senha (recomendado)</li>
                                                <li>Defina uma data de expiração (opcional)</li>
                                                <li>Copie o link e envie por e-mail ou mensagem</li>
                                            </ol>
                            
                                            <h5 class="mt-4">Segurança</h5>
                                            <ul>
                                                <li>Links com senha exigem autenticação para visualização</li>
                                                <li>Links expirados não permitem acesso</li>
                                                <li>Você pode revogar um link a qualquer momento</li>
                                                <li>Todas as visualizações são registradas em auditoria</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Pesquisa -->
                                    <div class="card card-primary card-outline" id="pesquisa">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-search"></i> Pesquisa Avançada</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Busca Simples</h5>
                                            <p>Digite palavras-chave na barra de pesquisa no topo da página para buscar por título de documentos.</p>
                            
                                            <h5 class="mt-4">Filtros Combinados</h5>
                                            <p>Você pode combinar múltiplos filtros:</p>
                                            <ul>
                                                <li><strong>Tipo de Documento:</strong> Clique nos chips de tipo para filtrar</li>
                                                <li><strong>Pasta:</strong> Navegue até a pasta desejada</li>
                                                <li><strong>Status de Vencimento:</strong> Use os filtros de vencimento</li>
                                                <li><strong>Texto:</strong> Combine com busca por palavra-chave</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Relatórios -->
                                    <div class="card card-info card-outline" id="relatorios">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-chart-bar"></i> Relatórios</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Relatório de Vencimentos</h5>
                                            <p>Visualize estatísticas completas sobre vencimentos, com opção de exportar para Excel (formato CSV).</p>
                            
                                            <h5 class="mt-4">Painel de Produtividade</h5>
                                            <p>Acompanhe métricas de uso do sistema, uploads recentes e documentos mais acessados.</p>
                                        </div>
                                    </div>

                                    <!-- Segurança -->
                                    <div class="card card-danger card-outline" id="seguranca">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-shield-alt"></i> Segurança e Privacidade</h3>
                                        </div>
                                        <div class="card-body">
                                            <h5>Proteção de Dados</h5>
                                            <ul>
                                                <li><strong>Hash SHA-256:</strong> Cada arquivo recebe uma impressão digital única para detectar alterações</li>
                                                <li><strong>Auditoria Completa:</strong> Todas as ações são registradas com data, hora e usuário</li>
                                                <li><strong>LGPD:</strong> Sistema em conformidade com a Lei Geral de Proteção de Dados</li>
                                                <li><strong>Backup:</strong> Consulte o administrador sobre políticas de backup</li>
                                            </ul>
                            
                                            <h5 class="mt-4">Boas Práticas</h5>
                                            <ul>
                                                <li>Não compartilhe sua senha com outras pessoas</li>
                                                <li>Use senhas fortes ao criar links de compartilhamento</li>
                                                <li>Revise periodicamente os compartilhamentos ativos</li>
                                                <li>Faça logout ao sair de computadores compartilhados</li>
                                            </ul>
                            
                                            <p class="mt-3">Para mais detalhes, consulte nossa <a href="privacidade.php">Política de Privacidade</a>.</p>
                                        </div>
                                    </div>

                                    <!-- FAQ -->
                                    <div class="card card-warning card-outline" id="faq">
                                        <div class="card-header">
                                            <h3 class="card-title"><i class="fas fa-question-circle"></i> Perguntas Frequentes (FAQ)</h3>
                                        </div>
                                        <div class="card-body">
                                            <h6><strong>Q: Quais formatos de arquivo são suportados?</strong></h6>
                                            <p><strong>R:</strong> O sistema suporta PDF, imagens (JPG, PNG), documentos Office (DOC, XLS, PPT) e outros formatos comuns. PDFs são recomendados para assinaturas digitais.</p>
                            
                                            <h6 class="mt-3"><strong>Q: Posso assinar documentos diretamente no sistema?</strong></h6>
                                            <p><strong>R:</strong> Atualmente, a assinatura deve ser feita em software externo (Adobe Acrobat, LibreOffice) antes do upload. O sistema valida e exibe informações das assinaturas.</p>
                            
                                            <h6 class="mt-3"><strong>Q: Como funciona o vencimento automático?</strong></h6>
                                            <p><strong>R:</strong> Ao selecionar um tipo de documento (ex: Nota Fiscal), o sistema calcula automaticamente a data de vencimento conforme a legislação. Você pode ajustar manualmente se necessário.</p>
                            
                                            <h6 class="mt-3"><strong>Q: Posso recuperar um documento excluído?</strong></h6>
                                            <p><strong>R:</strong> Documentos excluídos entram em "lixeira" por 30 dias antes da exclusão permanente. Contate o administrador para recuperação.</p>
                            
                                            <h6 class="mt-3"><strong>Q: Como sei se uma assinatura digital é válida?</strong></h6>
                                            <p><strong>R:</strong> Na página de Propriedades, verifique a seção "Assinaturas Digitais". O sistema mostra o status (válido/expirado/revogado), dados do certificado e reconhecimento ITI/Adobe AATL.</p>
                            
                                            <h6 class="mt-3"><strong>Q: Preciso ter internet para acessar documentos compartilhados?</strong></h6>
                                            <p><strong>R:</strong> Sim, tanto para criar quanto para acessar links de compartilhamento é necessário conexão com a internet.</p>
                                        </div>
                                    </div>

                                    <!-- Suporte -->
                                    <div class="card card-light">
                                        <div class="card-body text-center">
                                            <h5><i class="fas fa-life-ring text-primary"></i> Precisa de Ajuda?</h5>
                                            <p>Se você tiver dúvidas adicionais, entre em contato com o suporte:</p>
                                            <p><i class="fas fa-envelope mr-2"></i><a href="mailto:<?= SYSTEM_EMAIL ?>"><?= SYSTEM_EMAIL ?></a></p>
                                            <div class="mt-3">
                                                <a href="sobre.php" class="btn btn-outline-secondary btn-sm mr-2">
                                                    <i class="fas fa-info-circle"></i> Sobre o Sistema
                                                </a>
                                                <a href="privacidade.php" class="btn btn-outline-secondary btn-sm">
                                                    <i class="fas fa-user-shield"></i> Política de Privacidade
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <i class="fab fa-bootstrap fa-3x text-info mb-2"></i>

                <script>
                // Scroll suave para as seções
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    });
                });

                // Highlight do link ativo conforme scroll
                window.addEventListener('scroll', function() {
                    const sections = document.querySelectorAll('.card[id]');
                    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    
                    let current = '';
                    sections.forEach(section => {
                        const sectionTop = section.offsetTop - 100;
                        if (window.pageYOffset >= sectionTop) {
                            current = section.getAttribute('id');
                        }
                    });
    
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + current) {
                            link.classList.add('active');
                        }
                    });
                });
                </script>

                            <h6>Bootstrap 4 / AdminLTE</h6>
                            <small class="text-muted">Interface UI</small>
                        </div>
                    </div>
                    <hr>
                    <h6 class="font-weight-bold"><i class="fas fa-cube text-secondary"></i> Bibliotecas de Terceiros</h6>
                    <?php if (empty($packages)): ?>
                        <p class="text-muted">Nenhuma biblioteca de terceiros detectada.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Pacote</th>
                                        <th>DescriÃ§Ã£o</th>
                                        <th>LicenÃ§a</th>
                                        <th>Site</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($packages, 0, 10) as $pkg): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($pkg['name']) ?></code></td>
                                            <td><small><?= htmlspecialchars(mb_strimwidth($pkg['description'], 0, 60, 'â€¦')) ?></small></td>
                                            <td><span class="badge badge-light"><?= htmlspecialchars($pkg['license']) ?></span></td>
                                            <td>
                                                <?php if (!empty($pkg['homepage'])): ?>
                                                    <a href="<?= htmlspecialchars($pkg['homepage']) ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php if (count($packages) > 10): ?>
                                <p class="text-center text-muted"><small>... e mais <?= count($packages) - 10 ?> pacote(s)</small></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CrÃ©ditos e LicenÃ§a -->
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-award"></i> CrÃ©ditos e LicenÃ§a</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">Desenvolvimento</h6>
                            <p>
                                <strong><?= BRAND_NAME ?></strong> foi desenvolvido para atender Ã s necessidades de gestÃ£o eletrÃ´nica de documentos 
                                com foco em <strong>conformidade legal</strong>, <strong>seguranÃ§a</strong> e <strong>usabilidade</strong>.
                            </p>
                            <p class="mb-1"><strong>Desenvolvido por:</strong> Equipe <?= BRAND_NAME ?></p>
                            <p class="mb-1"><strong>Suporte:</strong> <a href="mailto:<?= SYSTEM_EMAIL ?>"><?= SYSTEM_EMAIL ?></a></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="font-weight-bold">LicenÃ§a de Uso</h6>
                            <p>
                                Este software Ã© fornecido "como estÃ¡", sem garantias de qualquer tipo. 
                                O uso Ã© restrito aos termos acordados com a organizaÃ§Ã£o.
                            </p>
                            <p class="mb-0">
                                <span class="badge badge-secondary">Copyright Â© <?= date('Y') ?> <?= BRAND_NAME ?></span>
                                <span class="badge badge-secondary ml-1">Todos os direitos reservados</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Links Ãšteis -->
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-link"></i> Links Ãšteis</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="manual.php" class="btn btn-block btn-outline-primary">
                                <i class="fas fa-book mr-2"></i> Manual do UsuÃ¡rio
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="privacidade.php" class="btn btn-block btn-outline-info">
                                <i class="fas fa-user-shield mr-2"></i> PolÃ­tica de Privacidade
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="https://www.gov.br/iti" target="_blank" rel="noopener" class="btn btn-block btn-outline-success">
                                <i class="fas fa-external-link-alt mr-2"></i> ITI - ICP-Brasil
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>