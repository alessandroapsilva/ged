<?php
// public/privacidade.php - Política de Privacidade e LGPD
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<?php
// public/privacidade.php - Política de Privacidade (LGPD)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$brandName    = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
$contactEmail = defined('SYSTEM_EMAIL') ? SYSTEM_EMAIL : (defined('MAIL_FROM') ? MAIL_FROM : 'privacidade@example.com');
$contactPhone = getenv('GED_CONTACT_PHONE') ?: '(00) 0000-0000';
$dpoName      = getenv('GED_DPO_NAME') ?: 'Encarregado de Protecao de Dados';
$lastUpdated  = date('d/m/Y');

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="mb-0"><i class="fas fa-user-shield mr-2"></i>Política de Privacidade</h1>
                <p class="text-muted mb-0">Transparência no tratamento de dados pessoais, em conformidade com a LGPD.</p>
            </div>
            <div class="d-flex align-items-center flex-wrap" style="gap:0.5rem;">
                <span class="badge badge-primary">LGPD</span>
                <span class="badge badge-success">Segurança</span>
                <span class="badge badge-info">Atualizado em <?= $lastUpdated ?></span>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="alert alert-secondary">
                Consulte também os <a href="termos.php" class="alert-link">Termos de Serviço</a> para entender a relação contratual do uso do sistema.
            </div>

            <!-- Resumo rápido -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Resumo Rápido</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li>Usamos dados para autenticação, gestão de documentos e auditoria.</li>
                                <li>Não vendemos dados a terceiros.</li>
                                <li>Cookies apenas para sessão, preferências e segurança.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="mb-0">
                                <li>Direitos do titular: acesso, correção, portabilidade, revogação.</li>
                                <li>Contato do DPO: <?= htmlspecialchars($contactEmail) ?> (<?= htmlspecialchars($dpoName) ?>).</li>
                                <li>Base legal: LGPD (consentimento, contrato, obrigação legal, legítimo interesse).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados coletados -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-database mr-2"></i>Quais dados coletamos</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>1. Cadastro e acesso</h5>
                            <ul>
                                <li>Nome, e-mail corporativo</li>
                                <li>Login e senha (hash)</li>
                                <li>IP, data/hora de acesso, agente de usuário</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h5>2. Uso do sistema</h5>
                            <ul>
                                <li>Metadados de documentos (título, tipo, pasta, autor, datas)</li>
                                <li>Arquivos enviados (PDF, imagens, etc.)</li>
                                <li>Hashes e assinaturas digitais (quando aplicável)</li>
                                <li>Logs de auditoria (ações, usuário, IP, carimbo de tempo)</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h5>3. Navegação</h5>
                            <ul>
                                <li>Preferências de exibição (tema claro/escuro, filtros)</li>
                                <li>Cookies de sessão e segurança</li>
                                <li>Dados técnicos mínimos para manter a conexão estável</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bases legais -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-balance-scale mr-2"></i>Bases legais (LGPD)</h3></div>
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Base</th>
                                <th>Quando usamos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Execução de contrato</td><td>Prestação do serviço de GED e autenticação de usuários.</td></tr>
                            <tr><td>Obrigação legal</td><td>Retenção de registros exigidos por leis fiscais, trabalhistas ou setoriais.</td></tr>
                            <tr><td>Legítimo interesse</td><td>Segurança, prevenção a fraudes, melhoria do produto (com avaliação de impacto).</td></tr>
                            <tr><td>Consentimento</td><td>Comunicações opcionais, funcionalidades adicionais ou compartilhamentos explícitos.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Finalidades -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-bullseye mr-2"></i>Finalidade do tratamento</h3></div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li>Autenticar e controlar acesso por perfis e permissões.</li>
                        <li>Armazenar, organizar, versionar e assinar documentos.</li>
                        <li>Rastrear ações para fins legais, auditoria e segurança.</li>
                        <li>Notificar sobre vencimentos, tarefas e eventos relevantes.</li>
                        <li>Melhorar estabilidade, desempenho e usabilidade do sistema.</li>
                    </ol>
                </div>
            </div>

            <!-- Segurança -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-lock mr-2"></i>Segurança da informação</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Proteções técnicas</h6>
                            <ul>
                                <li>Senhas com hash forte (nunca em texto puro).</li>
                                <li>TLS/HTTPS para tráfego seguro.</li>
                                <li>Controle de acesso e privilégios mínimos.</li>
                                <li>Logs de auditoria e monitoramento.</li>
                                <li>Backups regulares.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Práticas operacionais</h6>
                            <ul>
                                <li>Acesso restrito a pessoal autorizado.</li>
                                <li>Treinamento recorrente em segurança e LGPD.</li>
                                <li>Resposta a incidentes e comunicação aos afetados quando exigido.</li>
                                <li>Revisões periódicas de vulnerabilidades.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Nenhum ambiente é 100% livre de riscos. Em caso de incidente que gere risco ou dano relevante, comunicaremos os afetados e a ANPD, conforme a lei.
                    </div>
                </div>
            </div>

            <!-- Retenção -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-clock mr-2"></i>Retenção e eliminação</h3></div>
                <div class="card-body">
                    <ul>
                        <li>Dados de conta: enquanto o usuário mantiver relação ativa com o sistema.</li>
                        <li>Documentos: conforme prazos legais ou contratuais definidos pelo cliente controladora.</li>
                        <li>Logs de auditoria: mantidos pelo período necessário para obrigações legais e defesa de direitos.</li>
                    </ul>
                    <p class="mb-0">Quando o prazo expirar, adotamos exclusão segura ou anonimização, exceto se houver obrigação legal de retenção.</p>
                </div>
            </div>

            <!-- Direitos do titular -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check mr-2"></i>Seus direitos (Art. 18, LGPD)</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li>Confirmação e acesso aos dados.</li>
                                <li>Correção de dados incompletos ou desatualizados.</li>
                                <li>Anonimização, bloqueio ou eliminação de dados desnecessários.</li>
                                <li>Portabilidade, quando aplicável.</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li>Informação sobre compartilhamentos.</li>
                                <li>Revogação de consentimento.</li>
                                <li>Oposição a tratamentos que não respeitem a lei.</li>
                                <li>Revisão de decisões automatizadas, quando houver.</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mb-0">Alguns pedidos podem ser limitados por obrigações legais (ex.: retenção fiscal ou trabalhista).</p>
                </div>
            </div>

            <!-- Cookies -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-cookie-bite mr-2"></i>Cookies e preferências</h3></div>
                <div class="card-body">
                    <p>Usamos cookies estritamente necessários para:</p>
                    <ul>
                        <li>Manter a sessão autenticada e protegida.</li>
                        <li>Lembrar preferências de exibição (tema, filtros, paginação).</li>
                        <li>Registrar estado de UI (menu lateral, colunas de tabelas, ordenação).</li>
                    </ul>
                    <p class="mb-2">Você pode limpar ou bloquear cookies no navegador; algumas funções podem ficar limitadas.</p>
                    <p class="mb-0"><strong>Não usamos cookies para publicidade comportamental.</strong></p>
                </div>
            </div>

            <!-- Compartilhamento -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-share-alt mr-2"></i>Compartilhamento de dados</h3></div>
                <div class="card-body">
                    <ul>
                        <li>Usuários autorizados da mesma organização (conforme permissões).</li>
                        <li>Fornecedores de infraestrutura/backup/monitoramento sob contrato e cláusulas de confidencialidade.</li>
                        <li>Autoridades, mediante obrigação legal ou ordem judicial.</li>
                    </ul>
                    <p class="mb-0">Não comercializamos dados pessoais.</p>
                </div>
            </div>

            <!-- Exercício de direitos -->
            <div class="card card-outline">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-envelope mr-2"></i>Como exercer seus direitos</h3></div>
                <div class="card-body">
                    <p>Envie sua solicitação para o Encarregado de Dados (DPO):</p>
                    <ul>
                        <li><strong>Nome:</strong> <?= htmlspecialchars($dpoName) ?></li>
                        <li><strong>E-mail:</strong> <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></li>
                        <li><strong>Telefone:</strong> <?= htmlspecialchars($contactPhone) ?></li>
                    </ul>
                    <p class="mb-0">Para garantir sua identidade, podemos solicitar comprovação adequada antes de atender ao pedido.</p>
                </div>
            </div>

            <!-- Alterações -->
            <div class="card card-outline mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-sync-alt mr-2"></i>Alterações nesta política</h3></div>
                <div class="card-body">
                    <p>Podemos atualizar esta política para refletir melhorias do sistema ou mudanças legais. Manteremos a data de atualização visível. Ao continuar usando o serviço após publicarmos alterações, você concorda com a nova versão.</p>
                    <p class="mb-0"><strong>Última atualização:</strong> <?= $lastUpdated ?></p>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once '../templates/footer.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-user-shield"></i> PolÃ­tica de Privacidade</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="painel_produtividade.php">Painel</a></li>
                        <li class="breadcrumb-item active">Privacidade</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="alert alert-secondary" role="alert">
                Consulte os <a href="termos.php" class="alert-link">Termos do Serviço</a> para saber como é definida a relação entre o serviço e seus contratantes.
            </div>
            
            <!-- IntroduÃ§Ã£o -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> IntroduÃ§Ã£o</h3>
                </div>
                <div class="card-body">
                    <p>
                        Esta PolÃ­tica de Privacidade descreve como o <strong><?= BRAND_NAME ?></strong> coleta, usa, armazena e protege 
                        as informaÃ§Ãµes pessoais dos usuÃ¡rios, em conformidade com a <strong>Lei Geral de ProteÃ§Ã£o de Dados (LGPD)</strong> 
                        - Lei nÂº 13.709/2018.
                    </p>
                    <p class="mb-0">
                        <strong>Ãšltima atualizaÃ§Ã£o:</strong> <?= date('d/m/Y') ?>
                    </p>
                </div>
            </div>

            <!-- Dados Coletados -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-database"></i> Dados Coletados</h3>
                </div>
                <div class="card-body">
                    <h5>1. Dados Pessoais dos UsuÃ¡rios</h5>
                    <p>Para o funcionamento do sistema, coletamos e processamos:</p>
                    <ul>
                        <li><strong>Dados de IdentificaÃ§Ã£o:</strong> Nome completo, CPF, e-mail</li>
                        <li><strong>Dados de Acesso:</strong> Login, senha (criptografada), endereÃ§o IP</li>
                        <li><strong>Dados de Uso:</strong> Data/hora de login, pÃ¡ginas acessadas, documentos visualizados</li>
                        <li><strong>Dados Profissionais:</strong> Cargo, departamento, telefone corporativo</li>
                    </ul>
                    
                    <h5 class="mt-4">2. Dados dos Documentos</h5>
                    <ul>
                        <li><strong>Metadados:</strong> TÃ­tulo, tipo, data de upload, autor, pasta</li>
                        <li><strong>ConteÃºdo:</strong> Arquivos enviados pelos usuÃ¡rios (PDFs, imagens, etc.)</li>
                        <li><strong>Hash SHA-256:</strong> ImpressÃ£o digital do arquivo para verificaÃ§Ã£o de integridade</li>
                        <li><strong>Assinaturas Digitais:</strong> InformaÃ§Ãµes dos certificados ICP-Brasil (quando aplicÃ¡vel)</li>
                    </ul>
                    
                    <h5 class="mt-4">3. Logs de Auditoria</h5>
                    <ul>
                        <li>Todas as aÃ§Ãµes realizadas no sistema (upload, download, ediÃ§Ã£o, exclusÃ£o)</li>
                        <li>UsuÃ¡rio responsÃ¡vel, data/hora e endereÃ§o IP</li>
                        <li>Acessos via links de compartilhamento</li>
                    </ul>
                </div>
            </div>

            <!-- Base Legal -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <?php
                    // public/privacidade.php - Política de Privacidade e LGPD (UTF-8)
                    require_once '../core/init.php';
                    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

                    require_once '../templates/header.php';
                    require_once '../templates/sidebar.php';
                    ?>

                    <div class="content-wrapper">
                        <section class="content-header">
                            <div class="container-fluid">
                                <div class="row mb-2">
                                    <div class="col-sm-6">
                                        <h1><i class="fas fa-user-shield"></i> Política de Privacidade</h1>
                                    </div>
                                    <div class="col-sm-6">
                                        <ol class="breadcrumb float-sm-right">
                                            <li class="breadcrumb-item"><a href="painel_produtividade.php">Painel</a></li>
                                            <li class="breadcrumb-item active">Privacidade</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="content">
                            <div class="container-fluid">
                                <div class="alert alert-secondary" role="alert">
                                    Consulte os <a href="#" class="alert-link">Termos do Serviço</a> para saber como é definida a relação entre o serviço e seus contratantes.
                                </div>

                                <!-- Introdução -->
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-info-circle"></i> Introdução</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            Esta Política de Privacidade descreve como o <strong><?= BRAND_NAME ?></strong> coleta, usa, armazena e protege 
                                            as informações pessoais dos usuários, em conformidade com a <strong>Lei Geral de Proteção de Dados (LGPD)</strong> 
                                            - Lei nº 13.709/2018.
                                        </p>
                                        <p class="mb-0">
                                            <strong>Última atualização:</strong> <?= date('d/m/Y') ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Dados Coletados -->
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-database"></i> Dados Coletados</h3>
                                    </div>
                                    <div class="card-body">
                                        <h5>1. Dados Pessoais dos Usuários</h5>
                                        <p>Para o funcionamento do sistema, coletamos e processamos:</p>
                                        <ul>
                                            <li><strong>Dados de Identificação:</strong> Nome completo, CPF, e-mail</li>
                                            <li><strong>Dados de Acesso:</strong> Login, senha (criptografada), endereço IP</li>
                                            <li><strong>Dados de Uso:</strong> Data/hora de login, páginas acessadas, documentos visualizados</li>
                                            <li><strong>Dados Profissionais:</strong> Cargo, departamento, telefone corporativo</li>
                                        </ul>

                                        <h5 class="mt-4">2. Dados dos Documentos</h5>
                                        <ul>
                                            <li><strong>Metadados:</strong> Título, tipo, data de upload, autor, pasta</li>
                                            <li><strong>Conteúdo:</strong> Arquivos enviados pelos usuários (PDFs, imagens, etc.)</li>
                                            <li><strong>Hash SHA-256:</strong> Impressão digital do arquivo para verificação de integridade</li>
                                            <li><strong>Assinaturas Digitais:</strong> Informações dos certificados ICP-Brasil (quando aplicável)</li>
                                        </ul>

                                        <h5 class="mt-4">3. Logs de Auditoria</h5>
                                        <ul>
                                            <li>Todas as ações realizadas no sistema (upload, download, edição, exclusão)</li>
                                            <li>Usuário responsável, data/hora e endereço IP</li>
                                            <li>Acessos via links de compartilhamento</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Base Legal -->
                                <div class="card card-success card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-balance-scale"></i> Base Legal (LGPD)</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>O tratamento de dados pessoais é realizado com base nas seguintes hipóteses legais da LGPD:</p>

                                        <table class="table table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Base Legal</th>
                                                    <th>Descrição</th>
                                                    <th>Aplicação no Sistema</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Art. 7º, I - Consentimento</strong></td>
                                                    <td>Mediante autorização do titular</td>
                                                    <td>Aceite dos termos de uso ao criar conta</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Art. 7º, II - Cumprimento de obrigação legal</strong></td>
                                                    <td>Necessário para cumprimento de lei</td>
                                                    <td>Retenção de documentos por prazos legais (fiscais, trabalhistas)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Art. 7º, V - Execução de contrato</strong></td>
                                                    <td>Necessário para fornecimento de serviço</td>
                                                    <td>Gestão de documentos da organização</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Art. 7º, VI - Exercício de direitos</strong></td>
                                                    <td>Processos judiciais e administrativos</td>
                                                    <td>Logs de auditoria para defesa legal</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Art. 7º, IX - Legítimo interesse</strong></td>
                                                    <td>Interesse legítimo do controlador</td>
                                                    <td>Segurança do sistema, prevenção de fraudes</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Finalidade -->
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-bullseye"></i> Finalidade do Tratamento</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Os dados pessoais são utilizados exclusivamente para:</p>
                                        <ol>
                                            <li><strong>Autenticação e Controle de Acesso:</strong> Verificar identidade e permissões dos usuários</li>
                                            <li><strong>Gestão Eletrônica de Documentos:</strong> Armazenar, organizar e recuperar documentos</li>
                                            <li><strong>Auditoria e Conformidade:</strong> Rastrear ações para fins legais e de segurança</li>
                                            <li><strong>Assinaturas Digitais:</strong> Validar certificados ICP-Brasil e preservar integridade</li>
                                            <li><strong>Compartilhamento Seguro:</strong> Permitir acesso controlado a terceiros via links</li>
                                            <li><strong>Notificações:</strong> Alertar sobre vencimentos e eventos importantes</li>
                                            <li><strong>Melhorias do Sistema:</strong> Análise de uso para aprimoramento de funcionalidades</li>
                                        </ol>

                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Importante:</strong> Não compartilhamos dados pessoais com terceiros para fins comerciais ou publicitários. 
                                            Os dados são utilizados estritamente para as finalidades descritas acima.
                                        </div>
                                    </div>
                                </div>

                                <!-- Segurança -->
                                <div class="card card-danger card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-lock"></i> Medidas de Segurança</h3>
                                    </div>
                                    <div class="card-body">
                                        <h5>Proteção Técnica</h5>
                                        <ul>
                                            <li><strong>Criptografia:</strong> Senhas armazenadas com hash bcrypt (nunca em texto puro)</li>
                                            <li><strong>HTTPS/TLS:</strong> Comunicação criptografada entre navegador e servidor</li>
                                            <li><strong>Hash SHA-256:</strong> Verificação de integridade de arquivos</li>
                                            <li><strong>Controle de Acesso:</strong> Autenticação obrigatória para todas as funcionalidades</li>
                                            <li><strong>Firewall e Antivírus:</strong> Proteção contra ameaças externas</li>
                                        </ul>

                                        <h5 class="mt-4">Proteção Organizacional</h5>
                                        <ul>
                                            <li><strong>Acesso Restrito:</strong> Somente pessoal autorizado pode acessar servidores</li>
                                            <li><strong>Logs de Auditoria:</strong> Rastreamento de todas as ações sensíveis</li>
                                            <li><strong>Backup Regular:</strong> Cópias de segurança periódicas</li>
                                            <li><strong>Treinamento:</strong> Equipe capacitada em segurança da informação e LGPD</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Retenção -->
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-clock"></i> Retenção e Eliminação</h3>
                                    </div>
                                    <div class="card-body">
                                        <h5>Prazo de Retenção</h5>
                                        <p>Os dados pessoais são mantidos pelo tempo necessário para:</p>
                                        <ul>
                                            <li><strong>Usuários Ativos:</strong> Enquanto a conta estiver em uso</li>
                                            <li><strong>Documentos:</strong> Conforme prazos legais de guarda (6 anos fiscais, 20 anos médicos, etc.)</li>
                                            <li><strong>Logs de Auditoria:</strong> Mínimo de 5 anos para fins legais e de segurança</li>
                                            <li><strong>Compartilhamentos Expirados:</strong> 30 dias após expiração</li>
                                        </ul>

                                        <h5 class="mt-4">Eliminação Segura</h5>
                                        <p>Após o término do prazo de retenção, os dados são eliminados de forma segura e irreversível, incluindo:</p>
                                        <ul>
                                            <li>Exclusão permanente de bancos de dados</li>
                                            <li>Remoção de arquivos e backups</li>
                                            <li>Anonimização de logs antigos (quando necessário para fins estatísticos)</li>
                                        </ul>
                                    </div>
                                </div>

                                <!-- Direitos do Titular -->
                                <div class="card card-primary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-user-check"></i> Direitos do Titular (Art. 18 LGPD)</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>Você tem os seguintes direitos em relação aos seus dados pessoais:</p>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Confirmação de Tratamento</h6>
                                                <p><small>Saber se seus dados estão sendo tratados</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Acesso aos Dados</h6>
                                                <p><small>Obter cópia dos dados pessoais armazenados</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Correção</h6>
                                                <p><small>Atualizar dados incompletos ou incorretos</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Anonimização/Bloqueio</h6>
                                                <p><small>Solicitar anonimização ou bloqueio de dados desnecessários</small></p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Portabilidade</h6>
                                                <p><small>Transferir dados para outro fornecedor</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Eliminação</h6>
                                                <p><small>Excluir dados quando não mais necessários</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Informação sobre Compartilhamento</h6>
                                                <p><small>Saber com quem seus dados foram compartilhados</small></p>

                                                <h6><i class="fas fa-check-circle text-success mr-2"></i> Revogação de Consentimento</h6>
                                                <p><small>Retirar consentimento a qualquer momento</small></p>
                                            </div>
                                        </div>

                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-exclamation-triangle mr-2"></i>
                                            <strong>Limitações Legais:</strong> Alguns direitos podem ser limitados por obrigações legais de retenção 
                                            (ex: documentos fiscais devem ser mantidos por 6 anos mesmo após solicitação de exclusão).
                                        </div>

                                        <h5 class="mt-4">Como Exercer seus Direitos</h5>
                                        <p>Para exercer qualquer dos direitos acima, entre em contato através de:</p>
                                        <ul>
                                            <li><i class="fas fa-envelope mr-2"></i><strong>E-mail:</strong> <a href="mailto:<?= SYSTEM_EMAIL ?>"><?= SYSTEM_EMAIL ?></a></li>
                                            <li><i class="fas fa-phone mr-2"></i><strong>Telefone:</strong> (XX) XXXX-XXXX</li>
                                        </ul>
                                        <p><small>Responderemos sua solicitação em até 15 dias, conforme Art. 19 da LGPD.</small></p>
                                    </div>
                                </div>

                                <!-- Compartilhamento -->
                                <div class="card card-info card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-share-alt"></i> Compartilhamento de Dados</h3>
                                    </div>
                                    <div class="card-body">
                                        <h5>Com Quem Compartilhamos</h5>
                                        <p>Seus dados pessoais podem ser compartilhados com:</p>

                                        <table class="table table-sm">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Categoria</th>
                                                    <th>Finalidade</th>
                                                    <th>Base Legal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><strong>Outros Usuários da Organização</strong></td>
                                                    <td>Colaboração em documentos compartilhados</td>
                                                    <td>Legítimo interesse / Execução de contrato</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Terceiros via Links</strong></td>
                                                    <td>Visualização de documentos compartilhados</td>
                                                    <td>Consentimento (criação do link)</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Fornecedores de TI</strong></td>
                                                    <td>Hospedagem, backup, manutenção</td>
                                                    <td>Legítimo interesse / Execução de contrato</td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Autoridades Governamentais</strong></td>
                                                    <td>Cumprimento de ordem judicial</td>
                                                    <td>Obrigação legal</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <p class="mb-0"><strong>Importante:</strong> Todos os terceiros com acesso a dados pessoais são contratualmente obrigados a seguir a LGPD e esta Política de Privacidade.</p>
                                    </div>
                                </div>

                                <!-- Transferência Internacional -->
                                <div class="card card-warning card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-globe"></i> Transferência Internacional</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            <strong>Localização dos Dados:</strong> Todos os dados são armazenados em servidores localizados no Brasil, 
                                            em conformidade com a legislação nacional.
                                        </p>
                                        <p class="mb-0">
                                            Caso haja necessidade de transferência internacional de dados no futuro, você será previamente notificado 
                                            e serão adotadas salvaguardas adequadas (cláusulas contratuais padrão, certificações internacionais, etc.).
                                        </p>
                                    </div>
                                </div>

                                <!-- Alterações -->
                                <div class="card card-secondary card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-edit"></i> Alterações nesta Política</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            Esta Política de Privacidade pode ser atualizada periodicamente para refletir mudanças em nossas práticas, 
                                            legislação ou funcionalidades do sistema.
                                        </p>
                                        <p>
                                            Em caso de alterações substanciais, você será notificado por e-mail ou através de aviso destacado no sistema. 
                                            O uso continuado do sistema após as alterações constitui aceitação da nova política.
                                        </p>
                                        <p class="mb-0">
                                            Recomendamos revisar esta página periodicamente.
                                        </p>
                                    </div>
                                </div>

                                <!-- Contato DPO -->
                                <div class="card card-dark card-outline">
                                    <div class="card-header">
                                        <h3 class="card-title"><i class="fas fa-address-card"></i> Encarregado de Proteção de Dados (DPO)</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>
                                            Para dúvidas, reclamações ou solicitações relacionadas ao tratamento de dados pessoais, 
                                            você pode entrar em contato com nosso Encarregado de Proteção de Dados (DPO):
                                        </p>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-user mr-2"></i> Encarregado (DPO)</h6>
                                                <p>[Nome do DPO]<br>[Cargo]</p>
                                            </div>
                                            <div class="col-md-6">
                                                <h6><i class="fas fa-envelope mr-2"></i> Contato</h6>
                                                <p>
                                                    E-mail: <a href="mailto:<?= SYSTEM_EMAIL ?>"><?= SYSTEM_EMAIL ?></a><br>
                                                    Telefone: (XX) XXXX-XXXX
                                                </p>
                                            </div>
                                        </div>

                                        <div class="alert alert-light mt-3 mb-0">
                                            <p class="mb-2"><strong>Você também pode recorrer à Autoridade Nacional de Proteção de Dados (ANPD):</strong></p>
                                            <ul class="mb-0">
                                                <li>Site: <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">www.gov.br/anpd</a></li>
                                                <li>Ouvidoria: <a href="https://falabr.cgu.gov.br" target="_blank" rel="noopener">falabr.cgu.gov.br</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <!-- Links Úteis -->
                                <div class="card card-light">
                                    <div class="card-body text-center">
                                        <h5><i class="fas fa-link text-primary"></i> Links Úteis</h5>
                                        <div class="mt-3">
                                            <a href="sobre.php" class="btn btn-outline-secondary btn-sm mr-2">
                                                <i class="fas fa-info-circle"></i> Sobre o Sistema
                                            </a>
                                            <a href="manual.php" class="btn btn-outline-secondary btn-sm mr-2">
                                                <i class="fas fa-book"></i> Manual do Usuário
                                            </a>
                                            <a href="https://www.gov.br/anpd" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-external-link-alt"></i> ANPD - Lei Geral de Proteção de Dados
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </section>
                    </div>

                    <?php require_once '../templates/footer.php'; ?>
?>

<div class="content-wrapper">

    <?php
    // ##### ATUALIZAÃ‡ÃƒO: Inclui o novo sistema de notificaÃ§Ãµes #####
    include_once '../templates/partials/notifications.php';
    ?>

    <style>
        .actions-bar-footer{background-color:#3e444a;padding:12px;color:#fff;text-align:center;display:none}.actions-bar-footer .btn{border-radius:4px;border:none;padding:6px 16px;font-weight:700;font-size:.85rem;transition:background-color .2s}#btn-combinar{background-color:#28a745;color:white}#btn-mover-lote{background-color:#ffc107;color:#212529}#btn-apagar-lote{background-color:#e74c3c;color:#fff}.actions-bar-footer .btn:disabled{background-color:#6c757d;color:#adb5bd;cursor:not-allowed;opacity:.65}.action-icon{margin:0 6px;font-size:1rem;text-decoration:none!important;transition:all .2s ease-in-out}.action-icon:hover{transform:scale(1.15)}.text-primary{color:#007bff!important}.actions-cell{white-space:nowrap}
    </style>
    <section class="content-header">
        <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1><?= $titulo_pagina ?></h1></div><div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <?php foreach ($breadcrumbs as $index => $crumb): ?>
                    <li class="breadcrumb-item <?= ($index === count($breadcrumbs) - 1) ? 'active' : '' ?>">
                        <?php if ($index < count($breadcrumbs) - 1): ?><a href="documentos.php?pasta_id=<?= $crumb['id'] ?>"><?= htmlspecialchars($crumb['nome']) ?></a><?php else: ?><?= htmlspecialchars($crumb['nome']) ?><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div></div></div>
    </section>
    <section class="content"><div class="container-fluid"><div class="card card-dark card-outline">
        <div class="card-header"><div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex align-items-center my-1">
                <form method="get" class="form-inline">
                    <?php if($pasta_atual_id): ?><input type="hidden" name="pasta_id" value="<?= htmlspecialchars($pasta_atual_id) ?>"><?php endif; ?>
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    <label for="limit" class="mr-2">Mostrar</label>
                    <select name="limit" id="limit" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="10" <?= $resultados_por_pagina == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $resultados_por_pagina == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $resultados_por_pagina == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $resultados_por_pagina == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <input type="text" class="form-control form-control-sm ml-2" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Filtrar por tÃ­tulo...">
                    <?php if($tipo_id): ?><input type="hidden" name="tipo_id" value="<?= (int)$tipo_id ?>"><?php endif; ?>
                </form>
                <div class="btn-group btn-group-toggle view-toggler ml-2" role="group" aria-label="Alternar visualizaÃ§Ã£o">
                    <a href="<?= htmlspecialchars($linkList) ?>" id="btn-list-view" class="btn btn-sm btn-outline-secondary <?= $view === 'list' ? 'active' : '' ?>" title="Lista"><i class="fas fa-list"></i></a>
                    <a href="<?= htmlspecialchars($linkGrid) ?>" id="btn-grid-view" class="btn btn-sm btn-outline-secondary <?= $view === 'grid' ? 'active' : '' ?>" title="Grade"><i class="fas fa-th-large"></i></a>
                </div>
            </div>
            <div class="my-1">
                <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#modal-criar-pasta"><i class="fas fa-folder-plus"></i> Criar Pasta</button>
                <a href="documentos_adicionar.php?pasta_id=<?=htmlspecialchars($pasta_atual_id);?>" class="btn btn-sm btn-success ml-2"><i class="fas fa-plus"></i> Adicionar Documento</a>
            </div>
        </div>
        <div class="px-3 py-2 border-top">
            <div class="d-flex align-items-center flex-wrap">
                <strong class="mr-2">Tipos:</strong>
                <a href="?<?= htmlspecialchars($baseQS . ($baseQS? '&' : '') . 'view=' . $view) ?>" class="badge badge-pill <?= $tipo_id ? 'badge-light' : 'badge-primary' ?> mr-2 mb-1">Todos</a>
                <?php foreach ($tipos as $t): ?>
                    <a href="?<?= htmlspecialchars($baseQS . ($baseQS? '&' : '') . 'view=' . $view . '&tipo_id=' . (int)$t['id']) ?>" class="badge badge-pill <?= ($tipo_id == (int)$t['id']) ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1">
                        <?= htmlspecialchars($t['nome']) ?> <span class="ml-1 badge badge-secondary"><?= (int)$t['total'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="d-flex align-items-center flex-wrap mt-2">
                <strong class="mr-2">Vencimento:</strong>
                <?php $qsNoStatus = $_GET; unset($qsNoStatus['status'], $qsNoStatus['page']); $qsBaseNoStatus = http_build_query($qsNoStatus); ?>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'view=' . $view) ?>" class="badge badge-pill <?= $status === '' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1">Todos</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=a_vencer&view=' . $view) ?>" class="badge badge-pill <?= $status === 'a_vencer' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-hourglass-half mr-1"></i> A vencer (30d)</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=vencidos&view=' . $view) ?>" class="badge badge-pill <?= $status === 'vencidos' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Vencidos</a>
                <a href="?<?= htmlspecialchars($qsBaseNoStatus . ($qsBaseNoStatus? '&' : '') . 'status=sem_vencimento&view=' . $view) ?>" class="badge badge-pill <?= $status === 'sem_vencimento' ? 'badge-primary' : 'badge-light' ?> mr-2 mb-1"><i class="fas fa-minus-circle mr-1"></i> Sem vencimento</a>
            </div>
        </div></div>
        <div class="card-body p-0">
            <?php if ($view === 'grid'): ?>
                <div class="items-container">
                    <?php if(empty($subpastas) && empty($documentos)): ?>
                        <div class="empty-state w-100">
                            <div class="empty-state-icon"><i class="far fa-folder-open"></i></div>
                            <p>Esta pasta estÃ¡ vazia.</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach($subpastas as $pasta): ?>
                        <a class="item" href="documentos.php?pasta_id=<?= htmlspecialchars($pasta['id']); ?>&view=grid" title="Abrir pasta: <?= htmlspecialchars($pasta['nome']); ?>">
                            <div class="item-icon fas fa-folder"></div>
                            <div class="item-name"><?= htmlspecialchars($pasta['nome']); ?></div>
                            <div class="item-details">Criada em <?= date('d/m/Y H:i', strtotime($pasta['data_criacao'])); ?></div>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach($documentos as $doc): ?>
                        <div class="item" role="button" onclick="window.location.href='documentos_ver.php?id=<?= htmlspecialchars($doc['id']); ?>'" title="Abrir: <?= htmlspecialchars($doc['titulo']); ?>">
                            <div class="item-icon fas fa-file-alt"></div>
                            <div class="item-name"><?= htmlspecialchars($doc['titulo']); ?></div>
                            <div class="item-details">
                                <?= htmlspecialchars($doc['tipo_nome']); ?> Â· <?= date('d/m/Y H:i', strtotime($doc['data_upload'])); ?>
                                <?php if (!empty($doc['data_vencimento'])): ?>
                                    <?php 
                                        $dataVenc = new DateTime($doc['data_vencimento']);
                                        $agora = new DateTime();
                                        $diff = $agora->diff($dataVenc);
                                        $textoVencimento = $diff->y > 0 ? "em " . $diff->y . " anos" : ($diff->m > 0 ? "em " . $diff->m . " meses" : "em " . $diff->d . " dias");
                                        if ($diff->invert) $textoVencimento = "Vencido";
                                    ?>
                                    Â· Vence: <?= date('d/m/Y', strtotime($doc['data_vencimento'])); ?> (<?= $textoVencimento ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <table class="table table-hover">
            <thead><tr><th style="width:1%"><input type="checkbox" id="check-all"></th><th>ID</th><th>Nome</th><th>Tipo</th><th>Criado(a)</th><th>Vencimento</th><th class="text-right">AÃ§Ãµes</th></tr></thead>
            <tbody>
                <?php if(empty($subpastas) && empty($documentos)):?><tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-folder-open mr-2"></i>Esta pasta estÃ¡ vazia.</td></tr><?php endif;?>
                <?php foreach($subpastas as $pasta):?><tr><td><input type="checkbox" class="check-item" value="p-<?= htmlspecialchars($pasta['id']);?>"></td><td><?= htmlspecialchars($pasta['id']) ?></td><td><a href="documentos.php?pasta_id=<?= htmlspecialchars($pasta['id']);?>" class="text-primary"><i class="fas fa-folder mr-2"></i><?=htmlspecialchars($pasta['nome']);?></a></td><td>Pasta</td><td><?=date('d/m/Y H:i',strtotime($pasta['data_criacao']));?></td><td class="text-right actions-cell"><a href="pastas_propriedades.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a><a href="#" class="action-icon text-warning" data-toggle="modal" data-target="#modal-renomear-pasta" data-pasta-id="<?= htmlspecialchars($pasta['id']);?>" data-pasta-nome="<?=htmlspecialchars($pasta['nome']);?>" title="Renomear"><i class="fas fa-pencil-alt"></i></a><a href="pastas_apagar.php?id=<?= htmlspecialchars($pasta['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Lixeira"><i class="fas fa-trash"></i></a></td></tr><?php endforeach;?>
                <?php foreach($documentos as $doc):?><tr>
                    <td><input type="checkbox" class="check-item" value="d-<?= htmlspecialchars($doc['id']);?>"></td>
                    <td><?= htmlspecialchars($doc['id']) ?></td>
                    <td><a href="documentos_ver.php?id=<?= htmlspecialchars($doc['id']);?>" data-toggle="modal" data-target="#modal-visualizar" data-doc-title="<?=htmlspecialchars($doc['titulo']);?>"><i class="fas fa-file-alt mr-2 text-muted"></i><strong><?=htmlspecialchars($doc['titulo']);?></strong></a><?php if (!empty($doc['descricao'])): ?><br><small class="text-muted ml-4"><?= htmlspecialchars($doc['descricao']); ?></small><?php endif; ?></td>
                    <td><?=htmlspecialchars($doc['tipo_nome']);?> <?= isset($doc['restrito']) && $doc['restrito'] ? '<i class="fas fa-lock text-warning ml-1" title="Restrito"></i>' : '' ?></td>
                    <td><?=date('d/m/Y H:i',strtotime($doc['data_upload']));?></td>
                    <td>
                        <?php if (!empty($doc['data_vencimento'])): ?>
                            <?php
                                $dataVenc = new DateTime($doc['data_vencimento']);
                                $agora = new DateTime();
                                $diff = $agora->diff($dataVenc);
                                $textoVencimento = $diff->y > 0 ? " (em " . $diff->y . " anos)" : ($diff->m > 0 ? " (em " . $diff->m . " meses)" : " (em " . $diff->d . " dias)");
                                if ($diff->invert) $textoVencimento = " (Vencido)";
                            ?>
                            <?= date('d/m/Y', strtotime($doc['data_vencimento'])); ?> <span class="text-muted"><?= $textoVencimento; ?></span>
                        <?php else: ?>
                            <span class="text-muted">â€”</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right actions-cell">
                        <a href="documentos_ver.php?id=<?= htmlspecialchars($doc['id']);?>" data-toggle="modal" data-target="#modal-visualizar" data-doc-title="<?=htmlspecialchars($doc['titulo']);?>" class="action-icon text-info" title="Visualizar"><i class="fas fa-eye"></i></a>
                        <a href="documentos_compartilhar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Compartilhar por link"><i class="fas fa-share-alt"></i></a>
                        <a href="compartilhar_usuario.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Compartilhar com usuÃ¡rio"><i class="fas fa-user-plus"></i></a>
                        <a href="documentos_propriedades.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Propriedades"><i class="fas fa-list-ul"></i></a>
                        <a href="documentos_separar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-secondary" title="Separar"><i class="fas fa-cut"></i></a>
                        <a href="documentos_editar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-warning" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                        <a href="#" class="action-icon text-success" title="Assinar Documento" data-toggle="modal" data-target="#modal-escolher-assinatura" data-doc-id="<?= htmlspecialchars($doc['id']);?>" data-doc-title="<?=htmlspecialchars($doc['titulo']);?>"><i class="fas fa-signature"></i></a>
                        <a href="documentos_apagar.php?id=<?= htmlspecialchars($doc['id']);?>" class="action-icon text-danger btn-apagar-swal" title="Mover para Lixeira"><i class="fas fa-trash"></i></a>
                    </td>
                </tr><?php endforeach;?>
            </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php if ($view !== 'grid'): ?>
        <div class="card-footer actions-bar-footer" id="card-footer-actions"><div class="d-flex justify-content-center align-items-center"><strong class="mr-3"><i class="fas fa-check-double"></i> Com selecionados:</strong><button id="btn-combinar" class="btn btn-sm btn-success" disabled>Combinar</button><button id="btn-mover-lote" class="btn btn-sm btn-warning ml-2">Mover</button><button id="btn-apagar-lote" class="btn btn-sm btn-danger ml-2">Apagar</button></div></div>
        <?php endif; ?>
        <?php if ($total_paginas > 1): ?>
        <div class="card-footer clearfix"><div class="d-flex justify-content-between"><div class="text-muted">Mostrando de <?= $offset + 1 ?> a <?= $offset + count($documentos) ?> de <?= $total_encontrados ?> documentos</div><ul class="pagination pagination-sm m-0 float-right"><?php $queryParams = $_GET; unset($queryParams['page']); $queryString = http_build_query($queryParams); ?><li class="page-item <?= (int)$pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=1&<?= $queryString ?>">&laquo;</a></li><li class="page-item <?= (int)$pagina_atual <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= (int)$pagina_atual - 1 ?>&<?= $queryString ?>">&lsaquo;</a></li><?php for ($i = max(1, (int)$pagina_atual - 2); $i <= min($total_paginas, (int)$pagina_atual + 2); $i++): ?><li class="page-item <?= $i == (int)$pagina_atual ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&<?= $queryString ?>"><?= $i ?></a></li><?php endfor; ?><li class="page-item <?= (int)$pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= (int)$pagina_atual + 1 ?>&<?= $queryString ?>">&rsaquo;</a></li><li class="page-item <?= (int)$pagina_atual >= $total_paginas ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $total_paginas ?>&<?= $queryString ?>">&raquo;</a></li></ul></div></div>
        <?php endif; ?>
    </div></div></section>
</div>
<div class="modal fade" id="modal-criar-pasta" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form action="pastas_criar.php" method="post"><div class="modal-header"><h5 class="modal-title">Criar Pasta</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" name="pasta_pai_id" value="<?php echo $pasta_atual_id; ?>"><div class="form-group"><label>Nome da Pasta</label><input type="text" name="nome" class="form-control" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Criar</button></div></form></div></div></div><div class="modal fade" id="modal-renomear-pasta" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form action="pastas_renomear.php" method="post"><div class="modal-header"><h5 class="modal-title">Renomear Pasta</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><input type="hidden" name="id" id="renomear-pasta-id"><div class="form-group"><label>Novo nome</label><input type="text" name="nome" id="novo_nome_pasta" class="form-control" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div></form></div></div></div><div class="modal fade" id="modal-visualizar" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="modal-doc-title">Visualizando</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body p-0"><iframe id="pdf-viewer" src="about:blank" style="width:100%; height:80vh; border:none;"></iframe></div></div></div></div><div class="modal fade" id="modal-mover" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Mover Itens</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><p>Selecione a pasta de destino:</p><div id="arvore-pastas-container" style="height: 250px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px;"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" id="btn-confirmar-movimentacao" disabled>Mover</button></div></div></div></div><div class="modal fade" id="modal-escolher-assinatura" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Requisitar Assinatura</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><p>Requisitar para <strong id="assinatura-doc-title"></strong></p><div class="text-center mt-3"><a href="#" id="btn-assinar-agora" class="btn btn-primary">Assinar Digitalmente Agora</a><a href="#" id="btn-assinar-email" class="btn btn-secondary mt-2">Requisitar por E-mail</a></div></div></div></div></div><form id="form-combinar" action="documentos_combinar.php" method="post" target="_blank" style="display:none;"></form>

<?php require_once '../templates/footer.php'; ?>