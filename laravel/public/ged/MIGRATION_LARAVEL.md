# Migração para Laravel (preservando tudo)

Este documento descreve uma estratégia segura e incremental ("strangler pattern") para migrar o GED em PHP procedural para Laravel, preservando:
- Banco de dados e dados atuais
- URLs limpas (compatíveis com as rotas existentes sem ".php")
- Templates/visuais (AdminLTE + branding)
- Envio de e-mails e templates (PHPMailer -> Mailables)
- Uploads/assinaturas/fluxos do eSign

## 1) Preparação

1. Instale o Laravel em uma pasta irmã, por exemplo `c:/xampp/htdocs/ged-laravel`:
   - Requisitos: PHP 8.2+, Composer, ext-intl, OpenSSL, mbstring, pdo_mysql
   - `composer create-project laravel/laravel ged-laravel`
2. Aponte o DocumentRoot do Apache de um host de desenvolvimento para `ged-laravel/public` (ou use `php artisan serve` durante dev).
3. Configure `.env`:
   - DB_* apontando para o mesmo banco atual do GED
   - MAIL_* com as credenciais (podemos reaproveitar do GED via variáveis de ambiente)
   - APP_URL=http://localhost/ged (ou outro domínio)

## 2) Preservação de URLs

Objetivo: manter URLs como `/ged/documentos`, `/ged/esign`, etc.
- Em `routes/web.php`, defina rotas equivalentes às URLs limpas já ativas no .htaccess:
  ```php
  Route::get('/', [HomeController::class, 'index']);
  Route::get('/documentos', [DocumentosController::class, 'index']);
  Route::get('/documentos_ver', [DocumentosController::class, 'ver']); // aceita id via query
  Route::get('/esign', [EsignController::class, 'index']);
  Route::post('/login_process', [AuthController::class, 'login']);
  Route::get('/login', [AuthController::class, 'showLogin']);
  Route::get('/logout', [AuthController::class, 'logout']);
  // etc.
  ```
- Se necessário, crie middlewares para sessão e CSRF compatíveis com o legado enquanto migra.

## 3) Modelagem de dados (Eloquent)

Mapeie tabelas existentes sem renomear neste momento:
- `Usuarios` model -> tabela `usuarios` (id, nome, username, senha, email, funcao_id, ativo, twofa_*)
- `Documentos` model -> tabela `documentos`
- `WorkflowNotificacao` -> tabela `workflow_notificacoes`
- Outras tabelas usadas pelos fluxos (permissões, pastas, logs)

Ajustes:
- Use casts para datas (timestamps), e `guarded`/`fillable` apropriados
- Se senha estiver em `senha` (hash), configure verificação manual no AuthController (ou customize o provider)

## 4) Autenticação

- Opção A (mais rápida): Auth manual no controller (verificando `usuarios`), salvando dados mínimos na sessão do Laravel.
- Opção B (mais aderente): Implementar um `UserProvider` customizado para usar tabela `usuarios` e campo `senha`.
- Proteja rotas com middleware `auth` e crie `Gate/Policy` para permissões (migrando as chaves de permissões gradualmente).

## 5) Views (Blade) e layout

- Traga o HTML do AdminLTE e brand.css para `resources/views/layouts` e `public/assets` (Laravel)
- Converta páginas do GED para Blade passo a passo (ex.: `resources/views/documentos/index.blade.php`)
- Use componentes para header/footer/menu e toasts (SweetAlert)

## 6) E-mails

- Migrar templates para `resources/views/emails/...`
- Criar `Mailable`s equivalentes (ex.: `CompartilharDocumentoMail`)
- Configurar `.env` para SMTP (MAIL_HOST, MAIL_PORT, MAIL_ENCRYPTION, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS, MAIL_FROM_NAME)
- Opcional: usar queue para envio assíncrono (Redis/Database queue)

## 7) Uploads/Storage

- Mover uploads para `storage/app/uploads` com link simbólico via `php artisan storage:link` (ou manter `public/uploads` no curto prazo)
- Criar `FilesController` para downloads com checagem de permissão

## 8) eSign

- Pontos de entrada: viewer, assinaturas simples (desenho/arquivo), ICP-Brasil
- Criar controllers: `EsignController`, `AssinaturasController`
- Adaptar geração de carimbo/QR e lógica de posicionamento como services injetáveis

## 9) Segurança e prod

- Headers de segurança via middleware
- CSRF com `@csrf` nos forms
- Rate-limit para login (ThrottleRequests)
- .env somente no servidor (sem versionar credenciais)

## 10) Migração incremental (Strangler)

- Fase 1: Autenticação + Dashboard no Laravel; restante continua no legado
  - No `.htaccess` do GED atual, para rotas migradas, reescrever para `ged-laravel/public/` (reverse proxy simples via RewriteRule)
- Fase 2: Documentos (listar/visualizar/upload)
- Fase 3: Compartilhamento e E-mails
- Fase 4: eSign completo
- Fase 5: Admin/Relatórios/Integrações

Cada fase, após migrada e validada, remove ou congela a página PHP legada correspondente.

## 11) Compatibilidade

- Preserve os nomes de parâmetros (`id`, etc.) e o formato de URLs
- Quando necessário, mantenha adaptadores (helpers) para reusar templates de e-mail e logs
- Adicione testes básicos (Pest/PhpUnit) para rotas críticas (login, listar documentos, compartilhar)

## 12) Checklist de Go-live

- APP_ENV=production, APP_DEBUG=false
- Cache de config/route/view (`artisan config:cache`, `route:cache`, `view:cache`)
- Storage permissões
- Job queue (se usada) rodando como serviço
- Backups do banco e storage

---
Dúvidas? Podemos iniciar criando o projeto Laravel aqui na workspace e subir os primeiros controllers/rotas mantendo o banco e URLs.
