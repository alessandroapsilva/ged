<?php
// public/privacidade_publica.php - Política de Privacidade (pública, sem login)
require_once '../core/init.php';

$brandName    = defined('BRAND_NAME') ? BRAND_NAME : 'ENFAS GED';
$contactEmail = defined('SYSTEM_EMAIL') ? SYSTEM_EMAIL : (defined('MAIL_FROM') ? MAIL_FROM : 'privacidade@example.com');
$contactPhone = getenv('GED_CONTACT_PHONE') ?: '(00) 0000-0000';
$dpoName      = getenv('GED_DPO_NAME') ?: 'Encarregado de Protecao de Dados';
$lastUpdated  = date('d/m/Y');
$baseUrl      = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($brandName) ?> | Política de Privacidade</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg: #0f172a;
            --card: #ffffff;
            --text: #111827;
            --muted: #6b7280;
            --primary: #1e3a8a;
            --accent: #3b82f6;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1f2937 40%, #0b1220 100%);
            color: var(--text);
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 16px 64px;
        }
        .hero {
            color: #f8fafc;
            margin-bottom: 20px;
        }
        .hero h1 { margin: 0 0 6px; font-size: 28px; }
        .hero p { margin: 0; color: #cbd5e1; }
        .tags { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .tag { background: rgba(59,130,246,0.15); color: #bfdbfe; padding: 6px 12px; border-radius: 999px; font-size: 12px; border: 1px solid rgba(59,130,246,0.3); }
        .cards { display: grid; gap: 16px; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 20px 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }
        .card h3 { margin: 0 0 12px; font-size: 17px; display:flex; align-items:center; gap:8px; color: var(--primary); }
        .card h5 { margin: 16px 0 8px; font-size: 14px; color: #0f172a; }
        .card p { margin: 0 0 8px; color: var(--muted); line-height: 1.55; }
        ul { margin: 8px 0 0 18px; color: var(--muted); line-height: 1.55; }
        ol { margin: 8px 0 0 18px; color: var(--muted); line-height: 1.55; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid var(--border); padding: 10px; text-align: left; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; }
        .alert { border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; padding: 12px 14px; border-radius: 10px; }
        .footer { text-align: center; color: #94a3b8; margin-top: 24px; font-size: 13px; }
        @media (max-width: 768px) {
            .page { padding: 24px 14px 48px; }
            .card { padding: 16px 14px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="hero">
            <h1><i class="fas fa-user-shield"></i> Política de Privacidade</h1>
            <p>Transparência no tratamento de dados pessoais, em conformidade com a LGPD.</p>
            <div class="tags">
                <span class="tag">LGPD</span>
                <span class="tag">Segurança</span>
                <span class="tag">Atualizado em <?= $lastUpdated ?></span>
            </div>
        </div>

        <div class="alert">Esta política é pública e válida para o uso do sistema <?= htmlspecialchars($brandName) ?>.</div>

        <div class="cards">
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> Resumo rápido</h3>
                <ul>
                    <li>Usamos dados para autenticação, gestão de documentos e auditoria.</li>
                    <li>Não vendemos dados pessoais a terceiros.</li>
                    <li>Cookies apenas para sessão, preferências e segurança.</li>
                </ul>
            </div>

            <div class="card">
                <h3><i class="fas fa-database"></i> Quais dados coletamos</h3>
                <div style="display:grid; gap:10px; grid-template-columns: repeat(auto-fit, minmax(260px,1fr));">
                    <div>
                        <h5>Cadastro e acesso</h5>
                        <ul>
                            <li>Nome, e-mail corporativo</li>
                            <li>Login e senha (hash)</li>
                            <li>IP, data/hora, agente de usuário</li>
                        </ul>
                    </div>
                    <div>
                        <h5>Uso do sistema</h5>
                        <ul>
                            <li>Metadados: título, tipo, pasta, autor, datas</li>
                            <li>Arquivos enviados (PDF, imagens etc.)</li>
                            <li>Hashes e assinaturas digitais (quando aplicável)</li>
                            <li>Logs de auditoria (ações, IP, carimbo de tempo)</li>
                        </ul>
                    </div>
                    <div>
                        <h5>Navegação</h5>
                        <ul>
                            <li>Preferências (tema, filtros, paginação)</li>
                            <li>Cookies de sessão e segurança</li>
                            <li>Dados técnicos mínimos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-balance-scale"></i> Bases legais (LGPD)</h3>
                <table>
                    <thead><tr><th>Base</th><th>Quando usamos</th></tr></thead>
                    <tbody>
                        <tr><td>Execução de contrato</td><td>Prestação do serviço de GED e autenticação de usuários.</td></tr>
                        <tr><td>Obrigação legal</td><td>Retenção de registros exigidos por leis fiscais, trabalhistas ou setoriais.</td></tr>
                        <tr><td>Legítimo interesse</td><td>Segurança, prevenção a fraudes, melhoria do produto (com avaliação de impacto).</td></tr>
                        <tr><td>Consentimento</td><td>Comunicações opcionais ou compartilhamentos explícitos.</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h3><i class="fas fa-bullseye"></i> Finalidade do tratamento</h3>
                <ol>
                    <li>Autenticar e controlar acesso por permissões.</li>
                    <li>Armazenar, organizar, versionar e assinar documentos.</li>
                    <li>Rastrear ações para fins legais, auditoria e segurança.</li>
                    <li>Notificar sobre vencimentos e eventos relevantes.</li>
                    <li>Melhorar estabilidade, desempenho e usabilidade.</li>
                </ol>
            </div>

            <div class="card">
                <h3><i class="fas fa-lock"></i> Segurança da informação</h3>
                <div style="display:grid; gap:10px; grid-template-columns: repeat(auto-fit, minmax(260px,1fr));">
                    <div>
                        <h5>Proteções técnicas</h5>
                        <ul>
                            <li>Senhas com hash forte.</li>
                            <li>TLS/HTTPS para tráfego seguro.</li>
                            <li>Controle de acesso e privilégios mínimos.</li>
                            <li>Logs de auditoria e monitoramento.</li>
                            <li>Backups regulares.</li>
                        </ul>
                    </div>
                    <div>
                        <h5>Práticas operacionais</h5>
                        <ul>
                            <li>Acesso restrito a pessoal autorizado.</li>
                            <li>Treinamento recorrente em segurança e LGPD.</li>
                            <li>Resposta a incidentes e comunicação aos afetados quando exigido.</li>
                            <li>Revisões periódicas de vulnerabilidades.</li>
                        </ul>
                    </div>
                </div>
                <p class="alert" style="margin-top:12px;"><i class="fas fa-exclamation-triangle"></i> Nenhum ambiente é 100% livre de riscos. Em caso de incidente que gere risco ou dano relevante, comunicaremos os afetados e a ANPD, conforme a lei.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-clock"></i> Retenção e eliminação</h3>
                <ul>
                    <li>Dados de conta: enquanto houver relação ativa.</li>
                    <li>Documentos: conforme prazos legais ou contratuais definidos pela controladora.</li>
                    <li>Logs de auditoria: pelo tempo necessário para obrigações legais e defesa de direitos.</li>
                </ul>
                <p>Após o prazo, aplicamos exclusão segura ou anonimização, exceto quando a lei exigir retenção.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-user-check"></i> Direitos do titular (Art. 18, LGPD)</h3>
                <div style="display:grid; gap:10px; grid-template-columns: repeat(auto-fit, minmax(260px,1fr));">
                    <ul>
                        <li>Confirmação e acesso.</li>
                        <li>Correção de dados.</li>
                        <li>Anonimização, bloqueio ou eliminação.</li>
                        <li>Portabilidade, quando aplicável.</li>
                    </ul>
                    <ul>
                        <li>Informação sobre compartilhamentos.</li>
                        <li>Revogação de consentimento.</li>
                        <li>Oposição a tratamentos indevidos.</li>
                        <li>Revisão de decisões automatizadas, quando houver.</li>
                    </ul>
                </div>
                <p>Alguns pedidos podem ser limitados por obrigações legais (ex.: retenção fiscal ou trabalhista).</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-cookie-bite"></i> Cookies e preferências</h3>
                <p>Usamos cookies estritamente necessários para sessão, segurança e preferências de exibição (tema, filtros, paginação, estado de tabelas e menus). Não usamos cookies para publicidade comportamental.</p>
                <p class="mb-0">Você pode limpar ou bloquear cookies no navegador; algumas funções podem ficar limitadas.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-share-alt"></i> Compartilhamento de dados</h3>
                <ul>
                    <li>Usuários autorizados da mesma organização (conforme permissões).</li>
                    <li>Fornecedores de infraestrutura/backup/monitoramento sob contrato e confidencialidade.</li>
                    <li>Autoridades, mediante obrigação legal ou ordem judicial.</li>
                </ul>
                <p class="mb-0">Não comercializamos dados pessoais.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-envelope"></i> Como exercer seus direitos</h3>
                <p>Envie sua solicitação ao Encarregado de Dados (DPO):</p>
                <ul>
                    <li><strong>Nome:</strong> <?= htmlspecialchars($dpoName) ?></li>
                    <li><strong>E-mail:</strong> <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a></li>
                    <li><strong>Telefone:</strong> <?= htmlspecialchars($contactPhone) ?></li>
                </ul>
                <p class="mb-0">Para garantir sua identidade, poderemos solicitar comprovação adequada antes de atender ao pedido.</p>
            </div>

            <div class="card">
                <h3><i class="fas fa-sync-alt"></i> Alterações nesta política</h3>
                <p>Podemos atualizar esta política para refletir melhorias do sistema ou mudanças legais. Manteremos a data de atualização visível. Ao continuar usando o serviço após publicarmos alterações, você concorda com a nova versão.</p>
                <p class="mb-0"><strong>Última atualização:</strong> <?= $lastUpdated ?></p>
            </div>
        </div>

        <div class="footer">
            <?= htmlspecialchars($brandName) ?> · Política de Privacidade · <?= $lastUpdated ?>
        </div>
    </div>
</body>
</html>
