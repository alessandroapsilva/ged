# GED Completo — Estilo eDok

Este sistema de Gestão Eletrônica de Documentos (GED) foi aprimorado com funcionalidades tipo **eDok.com.br** e **Taugor GED**:

## Funções Implementadas

### 1. Dashboard Executivo
- **Métricas principais**: Total de documentos, pastas, usuários, uploads do dia
- **Métricas extras**: Documentos assinados, workflows em andamento
- **Gráficos**: Documentos por tipo (barras), uploads nos últimos 7 dias (linhas)

### 2. Documentos com Filtros Avançados
- **Alternância Lista/Grade** (botões) com preferência salva no navegador
- **Filtro rápido por título** (campo no cabeçalho)
- **Chips de Tipos** com contadores ativos (1-clique para filtrar)
- **Paginação** preservada (10, 25, 50, 100 itens)
- **Ações em lote**: combinar, mover, apagar (lista)

### 3. Busca Full-Text
- **Indexação automática** ao fazer upload (PDFParser)
- **`buscar.php`** busca em:
  - Título, descrição, tipo (metadados)
  - Conteúdo do PDF via índice `documentos_indice` (fulltext)
- Fallback seguro se índice não existir
- Realce do termo (highlight) em título/descrição

### 4. Notificações Reais
- **Sino no topo** com badge e contador dinâmico
- **Dropdown** com até 5 últimas notificações (workflow/internas)
- `core/init.php` atualiza `$_SESSION['notification_count']` a cada request (workflow_notificacoes + notifications)

### 5. Workflows e Assinaturas (estrutura pronta)
- Tabelas criadas (workflow.sql, assinaturas.sql)
- Fluxo de aprovação: etapas, aprovadores, histórico
- Assinatura digital: ICP-Brasil, simples, eletrônica
- Cards no dashboard para monitoramento

### 6. Edição com Substituição (padrão eDok)
- Ao editar, o sistema substitui o arquivo e metadados atuais (sem manter histórico por padrão).
- O versionamento é opcional e vem desativado: defina `GED_ENABLE_VERSIONING=1` para ativar.
- Se ativado, a tabela `documento_versoes` (em `sql/versioning_2025-10-26.sql`) registra snapshots a cada upload/edição.

### 7. OCR e Indexação
- Classe `DocumentoOCR` (Tesseract) em `core/documento_ocr.php`
- `PDFIndexer` (PDFParser, sem OCR) em `helpers/pdf_indexer.php`
- Endpoint admin `ajax_indexar_documentos.php` para reindexar tudo
 - OCR opcional: defina `GED_ENABLE_OCR=1` e instale dependências para tentar OCR automático quando o PDF não tiver texto extraível.

### 8. Branding Configurável
- `core/branding.php`: BRAND_NAME, BRAND_LOGO, cores primárias
- CSS com variáveis (`--brand-primary`, `--brand-accent`)
- Tema claro/escuro com persistência (localStorage)

### 9. Navegação Otimizada
- Menu lateral: Início, Documentos, Gestão, Registros, Administração
- Breadcrumbs e navegação por pastas
- Logo clicável no topo, busca centralizada, ícones de ajuda e fullscreen


## Como Usar

### Pré-requisitos
1. XAMPP rodando (Apache + MySQL/MariaDB)
2. PHP >=7.4
3. Composer (já rodado: `composer install` na raiz `/ged`)
4. Banco `ged` criado

### Configuração Inicial
```bash
# 1) Importar base principal (já deve ter feito)
mysql -u root ged < sql/base.sql

# 2) Importar módulos extras (opcional, para workflows/assinaturas/OCR/versão/compartilhamento)
mysql -u root ged < sql/workflow.sql
mysql -u root ged < sql/assinaturas.sql
# (Opcional) Versionamento de documentos
mysql -u root ged < sql/versioning_2025-10-26.sql
mysql -u root ged < sql/ocr.sql
mysql -u root ged < sql/sharing_2025-10-26.sql
mysql -u root ged < sql/logs_2025-10-26.sql

# 3) Ajustar db_config.php e recursos (se necessário)
# Verifique c:\xampp\htdocs\ged\db_config.php (senha do root, nome do banco)
# (Opcional) Ative versionamento via variável de ambiente no Apache/XAMPP:
# GED_ENABLE_VERSIONING=1
# (Opcional) Ative OCR quando tiver Tesseract + Imagick disponíveis:
# GED_ENABLE_OCR=1

# (Recomendado) Aplicar melhorias de segurança e RBAC adicionais
mysql -u root ged < sql/upgrade_2025-10-26.sql
mysql -u root ged < sql/rbac_2025-10-26.sql
mysql -u root ged < sql/rbac_patch_share_2025-10-26.sql
```

### Acessar o Sistema
- Login: http://localhost/ged/public/login.php
- Dashboard: http://localhost/ged/public/ (redireciona para `painel_produtividade.php`)
- Documentos: http://localhost/ged/public/documentos.php
- Reindexação (Admin): http://localhost/ged/public/admin_indexacao.php

### Indexar Documentos Existentes
Para habilitar busca por conteúdo em PDFs já enviados:
1. Acesse com usuário admin (ID 1 ou com permissão `admin.access`)
2. Pela interface: acesse a tela “Indexação de Conteúdo” e clique em Reindexar.
3. Via AJAX/Postman/curl (opção técnica):
   ```bash
   curl -X POST http://localhost/ged/public/ajax_indexar_documentos.php \
     -H "Cookie: PHPSESSID=<sua-sessao>"
   ```
4. Agendamento (Windows): Crie uma Tarefa no Agendador para executar periodicamente:
  ```powershell
  php c:\xampp\htdocs\ged\scripts\cron_reindex.php
  ```

### Personalizar Marca
Edite `c:\xampp\htdocs\ged\core\branding.php`:
```php
define('BRAND_NAME', 'Minha Empresa GED');
define('BRAND_LOGO', BASE_URL . '/assets/dist/img/minha_logo.svg');
define('BRAND_PRIMARY_COLOR', '#0056b3');
define('BRAND_ACCENT_COLOR', '#28a745');
```

---

## Estrutura de Pastas
```
ged/
├── classes/          # Database, Document, User, Notification
├── core/             # init.php, branding.php, documento_ocr.php, workflow.php, email.php
├── helpers/          # auth_helper.php, log_helper.php, pdf_indexer.php
├── libraries/        # FPDF, FPDI, PHPMailer, phpqrcode, Smalot PdfParser, TCPDF
├── public/           # Páginas principais, assets, uploads
├── scripts/          # (futuros crons/jobs)
├── sql/              # assinaturas.sql, ocr.sql, versioning.sql, workflow.sql
├── templates/        # header.php, sidebar.php, footer.php, partials/notifications.php
├── tests/            # (futuros testes)
├── vendor/           # Composer autoload
├── composer.json
└── db_config.php
```

---

## Funcionalidades-Chave por Arquivo

### `documentos.php`
- Lista/grade toggle
- Filtros: tipo, título (q)
- Paginação
- Ações em lote (combinar, mover, apagar)
- Preferência de visão (localStorage)

### `buscar.php`
- Busca em pastas, documentos (metadados) e índice de conteúdo
- MATCH…AGAINST (fulltext) com fallback LIKE

### `painel_produtividade.php`
- Cards: docs, pastas, usuários, hoje, assinados, workflows
- Gráficos Chart.js

### `ajax_indexar_documentos.php`
- Reindex de todos PDFs (admin only)
- Retorna contador de sucessos/erros

### `documentos_adicionar_process.php`
- Upload + contagem de páginas + hash SHA256
- Indexação automática após commit
- Cálculo de vencimento baseado em `tipos_documento.vencimento_*`

### `helpers/pdf_indexer.php`
- Classe `PDFIndexer`: extrai texto via `Smalot\PdfParser\Parser`
- Atualiza tabela `documentos_indice` (fulltext)

### `core/init.php`
- Carrega branding, db, helpers
- Atualiza `$_SESSION['notification_count']` de workflow/notificações

### `templates/header.php`
- Logo clicável, busca centralizada
- Dropdown de notificações (últimas 5)
- Tema claro/escuro, fullscreen, menu usuário

---

## Próximos Passos Recomendados

1. **Ativar Workflows**: criar interface de cadastro de workflows, etapas e aprovadores
2. **Assinatura Digital**: integrar lib ICP-Brasil ou usar e-Sign simplificado
3. **OCR Automático**: rodar `DocumentoOCR` em fila ao subir scan (se tiver Tesseract/Imagick)
4. **Notificações Email**: integrar PHPMailer em `core/email.php`
5. **Relatórios**: adicionar tela de relatórios com filtros de período, tipo, status
6. **Compartilhamento Seguro**: links públicos com expiração, senhas e logs de acesso (implementado)
7. **Mobile**: adaptar CSS para responsivo total (AdminLTE já ajuda)
8. **API REST**: expor endpoints JSON para integração externa

---

## Referências
- eDok: https://edok.com.br / https://demo.edok.com.br
- AdminLTE: https://adminlte.io/
- Smalot PDF Parser: https://github.com/smalot/pdfparser
- Chart.js: https://www.chartjs.org/

---

**Desenvolvido para ser um GED completo, seguro, integrado e moderno, inspirado nas melhores práticas do mercado.**

---

## Novidades (2025-10-26)

### Templates de E-mail (Módulo TI)
- Nova administração de templates: `public/admin_email_templates.php` (somente quem tem a permissão `email.templates.manage`).
- Tabelas: `email_templates`, `email_template_versions`, `emails_log` (aplicar `sql/email_templates_2025-10-26.sql`).
- Placeholders no corpo/assunto: `{{chave}}`, `{{obj.campo}}` e `{{chave|Padrão}}`.
- Serviço de envio: `core/email.php` com `email_send_template($pdo, $destino, $slug, $dados)` e log automático em `emails_log`.
- Página de teste por template: `public/admin_email_template_test.php` com pré-visualização.

SMTP por variáveis de ambiente (defina no Apache/XAMPP):
- `GED_SMTP_HOST` (ex: smtp.office365.com)
- `GED_SMTP_PORT` (ex: 587)
- `GED_SMTP_USER` / `GED_SMTP_PASS`
- `GED_SMTP_SECURE` (tls|ssl)
- `GED_MAIL_FROM` / `GED_MAIL_FROM_NAME`

Aplicação do upgrade:

```bash
# 1) No MySQL aplique:
#    SOURCE c:/xampp/htdocs/ged/sql/email_templates_2025-10-26.sql
# 2) Ajuste variáveis de ambiente de SMTP e reinicie o Apache.
```

### API Segura e Limitação de Taxa
- Suporte a API Key (header X-API-Key) e Bearer Token via tabelas `api_keys` e `api_tokens`.
- Rate limiting: 120 requisições por minuto por chave/IP utilizando a tabela `api_access_log`.
- Fallback para lista estática de chaves em ambiente de desenvolvimento.

Aplicação do upgrade:

```bash
mysql -u root ged < sql/upgrade_2025-10-26.sql
```

Depois, gere uma chave em `api_keys` para o seu usuário e use-a no header:

```text
X-API-Key: <sua-chave>

### OCR (opcional)
- Requisitos: PHP Imagick habilitado e Tesseract instalado no sistema (binários no PATH).
- Dependência Composer: `thiagoalessio/tesseract_ocr`.
- Ativação: defina `GED_ENABLE_OCR=1`. Quando um PDF não tiver texto extraível, o indexador tentará OCR e atualizará `documentos_indice` automaticamente.

### Compartilhamento Seguro de Documentos
- Criar link: Acesse as propriedades do documento e clique em “Compartilhar” (perm. `document.share`).
- Opções: senha (opcional), expiração, limite de downloads.
- Logs/Auditoria: criação de link e downloads são registrados em `audit_logs` (e em `logs`, se presente).
 - Visualização com marca d'água: ao abrir o link com `?view=1`, PDFs são exibidos com marca d'água diagonal (texto com data e prefixo do código) quando `GED_SHARE_WATERMARK` não é `0`.
 - Flags por link:
   - Somente visualização (view_only): força visualização inline, aplica marca d'água em PDFs e bloqueia download. Para formatos não suportados pelo navegador (ex.: DOCX/XLSX/PPTX/ZIP), o acesso é barrado com mensagem amigável.
   - Forçar marca d'água (force_watermark): aplica a marca d'água para PDFs mesmo sem `?view=1` e independentemente da variável de ambiente.
 - Gestão de links: nas propriedades do documento há um painel “Links de Compartilhamento” para listar, copiar e revogar links existentes; exibe badges “Somente visualização” e “Marca d'água forçada” quando aplicável.
 - Variáveis de ambiente:
   - `GED_SHARE_WATERMARK` (padrão: ligado). Defina `0` para desativar a marca d'água ao visualizar.

Aplicação dos upgrades de compartilhamento:

```powershell
# Base do compartilhamento
mysql -u root ged < sql/sharing_2025-10-26.sql

# Patch que adiciona flags view_only e force_watermark
mysql -u root ged < sql/sharing_patch_2025-10-27.sql
```

### Compartilhamento Interno por Usuário
- Conceda acesso a usuários do sistema para um documento específico, com opções de:
  - Somente visualização (bloqueia download)
  - Permitir download
  - Expiração do acesso
- Interface: botão “Compartilhar com usuário” nas ações da lista e painel “Compartilhamentos Internos” nas propriedades do documento.
- E-mails de notificação: usa o serviço de templates (`core/email.php`). Template sugerido: `compartilhar_interno`.

Aplicação do upgrade:

```powershell
mysql -u root ged < sql/sharing_users_2025-10-27.sql
```
```
