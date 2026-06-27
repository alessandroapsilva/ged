# ✅ CHECKLIST - Próximas Ações

## 🎯 OBJETIVO
Finalizar a modernização visual do GED em 2-3 dias e ir para produção.

---

## 📋 DIA 1 - PÁGINAS PRINCIPAIS (6-8h)

### Manhã (4h)

#### ☐ 1. Dashboard Principal (`/public/index.php` ou `/public/dashboard.php`)
- [ ] Incluir `<link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/ged-modern.css">`
- [ ] Trocar `.card` por `.card-modern`
- [ ] Atualizar botões para `.btn-modern .btn-primary-modern`
- [ ] Cards de métricas com `.animate-slide-up`
- [ ] Testar responsividade

**Tempo estimado:** 2h

#### ☐ 2. Lista de Documentos (`/public/documentos.php` ou similar)
- [ ] Incluir CSS moderno
- [ ] Atualizar tabela para `.table-modern`
- [ ] Botão "Novo Documento" com `.btn-primary-modern`
- [ ] Filtros com `.input-modern`
- [ ] Badges de status com `.badge-modern`
- [ ] Testar funcionalidades

**Tempo estimado:** 2h

### Tarde (4h)

#### ☐ 3. Visualização de Documento (`/public/documento_view.php` ou similar)
- [ ] Incluir CSS moderno
- [ ] Card de preview com `.card-modern`
- [ ] Botões de ação com `.btn-modern`
- [ ] Comentários/histórico modernizados
- [ ] Testar download, compartilhamento

**Tempo estimado:** 2h

#### ☐ 4. Cadastro/Upload de Documento (`/public/documento_new.php` ou similar)
- [ ] Incluir CSS moderno
- [ ] Formulário com `.input-modern`
- [ ] Upload área modernizada
- [ ] Botões com `.btn-success-modern`
- [ ] Validações visuais
- [ ] Testar upload

**Tempo estimado:** 2h

---

## 📋 DIA 2 - PÁGINAS SECUNDÁRIAS (6-8h)

### Manhã (4h)

#### ☐ 5. Usuários (`/public/usuarios.php`)
- [ ] Incluir CSS moderno
- [ ] Tabela com `.table-modern`
- [ ] Formulário com `.input-modern`
- [ ] Badges de perfil/status
- [ ] Testar CRUD

**Tempo estimado:** 1.5h

#### ☐ 6. Departamentos/Setores (`/public/departamentos.php`)
- [ ] Incluir CSS moderno
- [ ] Lista modernizada
- [ ] Formulário modernizado
- [ ] Testar CRUD

**Tempo estimado:** 1h

#### ☐ 7. Relatórios (`/public/relatorios.php`)
- [ ] Incluir CSS moderno
- [ ] Filtros com `.input-modern`
- [ ] Cards de métricas
- [ ] Tabelas/gráficos modernizados

**Tempo estimado:** 1.5h

### Tarde (4h)

#### ☐ 8. Configurações (`/public/configuracoes.php`)
- [ ] Incluir CSS moderno
- [ ] Abas modernizadas
- [ ] Formulários com `.input-modern`
- [ ] Switches/toggles estilizados
- [ ] Testar salvamento

**Tempo estimado:** 2h

#### ☐ 9. Perfil do Usuário (`/public/perfil.php`)
- [ ] Incluir CSS moderno
- [ ] Avatar/foto modernizada
- [ ] Formulário de dados
- [ ] Mudança de senha
- [ ] Testar edição

**Tempo estimado:** 1.5h

#### ☐ 10. Notificações (`/public/notificacoes.php`)
- [ ] Incluir CSS moderno
- [ ] Lista de notificações
- [ ] Badges de status
- [ ] Marcar como lida
- [ ] Testar interações

**Tempo estimado:** 0.5h

---

## 📋 DIA 3 - FINALIZAÇÕES E TESTES (6-8h)

### Manhã (4h)

#### ☐ 11. Páginas Restantes
- [ ] Logs/Auditoria
- [ ] Ajuda/FAQ
- [ ] Sobre/Versão
- [ ] Recuperação de senha
- [ ] 2FA (se houver página específica)

**Tempo estimado:** 2h

#### ☐ 12. Componentes Globais
- [ ] Sidebar - aplicar `.sidebar-modern`
- [ ] Header/Navbar - aplicar `.navbar-modern`
- [ ] Modais - atualizar para design moderno
- [ ] Toasts/Notifications - estilizar
- [ ] Loading overlays - padronizar

**Tempo estimado:** 2h

### Tarde (4h)

#### ☐ 13. Testes de Responsividade
- [ ] Mobile (375px - iPhone)
  - [ ] Login
  - [ ] Dashboard
  - [ ] Documentos
  - [ ] Todas as páginas principais
- [ ] Tablet (768px - iPad)
  - [ ] Login
  - [ ] Dashboard
  - [ ] Documentos
- [ ] Desktop (1920px)
  - [ ] Todas as páginas
- [ ] Navegação mobile (hamburguer menu)

**Tempo estimado:** 1.5h

#### ☐ 14. Testes de Funcionalidade
- [ ] Login/Logout
- [ ] Upload de documento
- [ ] Download de documento
- [ ] Compartilhamento
- [ ] Busca/Filtros
- [ ] CRUD de usuários
- [ ] CRUD de departamentos
- [ ] Relatórios
- [ ] Configurações
- [ ] Notificações

**Tempo estimado:** 1.5h

#### ☐ 15. Testes de Performance
- [ ] Lighthouse (Performance, Accessibility, Best Practices, SEO)
- [ ] Tempo de carregamento < 3s
- [ ] Tamanho total de assets < 1MB
- [ ] Otimizar imagens (se necessário)
- [ ] Minificar CSS/JS (se necessário)

**Tempo estimado:** 1h

---

## 📋 VALIDAÇÃO FINAL (2h)

#### ☐ 16. Checklist de Qualidade Visual
- [ ] Cores consistentes em todo o sistema
- [ ] Espaçamento uniforme (grid de 8px)
- [ ] Bordas arredondadas padronizadas
- [ ] Sombras consistentes
- [ ] Animações suaves (não travando)
- [ ] Hover states em todos os elementos interativos
- [ ] Focus states visíveis (teclado)
- [ ] Tipografia Inter aplicada globalmente
- [ ] Ícones FontAwesome consistentes
- [ ] Sem elementos "quebrados" visualmente

#### ☐ 17. Checklist de Acessibilidade
- [ ] Contraste adequado (mínimo 4.5:1)
- [ ] Labels associados aos inputs
- [ ] Alt text em imagens
- [ ] Navegação por teclado (Tab/Shift+Tab)
- [ ] Enter para submeter formulários
- [ ] Esc para fechar modais
- [ ] Screen reader friendly (testar com NVDA)

#### ☐ 18. Checklist de Compatibilidade
- [ ] Chrome (última versão)
- [ ] Firefox (última versão)
- [ ] Edge (última versão)
- [ ] Safari (se disponível)
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

---

## 📋 DEPLOY PRODUÇÃO (2h)

#### ☐ 19. Preparação
- [ ] Backup completo do sistema atual
- [ ] Backup do banco de dados
- [ ] Testar em ambiente de staging (se disponível)
- [ ] Documentar mudanças para usuários

#### ☐ 20. Deploy
- [ ] Fazer merge/push dos arquivos
- [ ] Verificar permissões de arquivos
- [ ] Limpar cache do navegador
- [ ] Limpar cache do servidor (se houver)
- [ ] Atualizar versão no `config/version.json`

#### ☐ 21. Validação Pós-Deploy
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Upload funciona
- [ ] Download funciona
- [ ] Busca funciona
- [ ] Sem erros no console (F12)
- [ ] Sem erros de PHP (log)

#### ☐ 22. Comunicação
- [ ] Avisar usuários sobre nova interface
- [ ] Criar tutorial rápido (se necessário)
- [ ] Disponibilizar guia de mudanças
- [ ] Coletar feedback inicial

---

## 🎯 RESUMO EXECUTIVO

### Tempo Total Estimado:
- **Dia 1:** 6-8h (Páginas Principais)
- **Dia 2:** 6-8h (Páginas Secundárias)
- **Dia 3:** 6-8h (Finalizações e Testes)
- **Deploy:** 2h
- **TOTAL:** 20-26h (~3 dias de trabalho)

### Prioridades:
1. 🔴 **CRÍTICO:** Login (✅ Feito!), Dashboard, Documentos
2. 🟡 **IMPORTANTE:** Usuários, Relatórios, Configurações
3. 🟢 **DESEJÁVEL:** Logs, Ajuda, Sobre

### Critérios de Sucesso:
- ✅ 100% das páginas com design moderno
- ✅ Responsividade total (mobile, tablet, desktop)
- ✅ Performance > 90 no Lighthouse
- ✅ Acessibilidade WCAG AA (mínimo)
- ✅ Zero bugs críticos
- ✅ Feedback positivo dos usuários

---

## 🚀 COMEÇAR AGORA!

**Próxima ação imediata:**
1. Abrir `/public/index.php` (ou dashboard.php)
2. Adicionar `<link rel="stylesheet" href="<?= BASE_URL ?>/assets/dist/css/ged-modern.css">`
3. Começar a trocar classes antigas por modernas
4. Testar e iterar

**Lembre-se:**
- Use o `GUIA_ESTILO_APLICACAO.md` como referência
- Teste cada página após modificar
- Não tenha medo de ajustes finos
- Peça feedback de colegas

---

## 📞 AJUDA

**Em caso de dúvida:**
1. Consultar `GUIA_ESTILO_APLICACAO.md`
2. Ver exemplos em `login.php` (referência completa)
3. Inspecionar com DevTools (F12) para debug
4. Revisar variáveis CSS em `ged-modern.css`

---

**🎉 SUCESSO NO PROJETO!**

**Meta:** Sistema 100% modernizado e em produção em 3 dias.

**Você consegue!** 💪🚀
