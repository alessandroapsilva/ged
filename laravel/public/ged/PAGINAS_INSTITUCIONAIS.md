# PÁGINAS INSTITUCIONAIS INDEPENDENTES
## Sistema de Gestão Eletrônica de Documentos

**Data de Criação:** 29/10/2025  
**Versão:** 2.0.0

---

## 📋 RESUMO DA IMPLEMENTAÇÃO

Foram criadas **3 páginas independentes e profissionais** para fornecer informações completas sobre o sistema:

### ✅ Páginas Criadas

1. **sobre.php** - Sobre o Sistema
2. **manual.php** - Manual do Usuário
3. **privacidade.php** - Política de Privacidade

---

## 📄 1. SOBRE.PHP - Página Sobre o Sistema

### Localização
```
c:\xampp\htdocs\ged\public\sobre.php
```

### Funcionalidades

#### 1.1 Informações do Sistema
- **Versão:** 2.0.0
- **Data de lançamento:** 29/10/2025
- **Ambiente:** Production/Development
- **Stack Tecnológico:** PHP, MySQL, JavaScript, Bootstrap

#### 1.2 Estatísticas Dinâmicas
Consulta em tempo real:
- Total de documentos
- Total de usuários
- Tipos de documento
- Assinaturas digitais
- Compartilhamentos ativos

#### 1.3 Recursos do Sistema (6 info-boxes)
1. **Gestão de Documentos** - Upload, organização, pesquisa
2. **Assinaturas Digitais** - ICP-Brasil com validade jurídica
3. **Controle de Vencimentos** - Prazos legais automáticos
4. **Compartilhamento Seguro** - Links com senha
5. **Segurança** - Hash SHA-256 e criptografia
6. **Auditoria Completa** - Logs de todas as ações

#### 1.4 Conformidade Legal
**Legislação Brasileira:**
- MP 2.200-2/2001 (ICP-Brasil)
- Lei 14.063/2020 (Assinaturas Eletrônicas)
- Decreto 10.278/2020 (Digitalização)
- Lei 13.709/2018 (LGPD)
- Lei 12.682/2012 (Armazenamento Eletrônico)
- Lei 8.159/1991 (Política Nacional de Arquivos)

**Padrões Internacionais:**
- ISO 15489 (Gestão de Documentos)
- ISO 27001 (Segurança da Informação)
- PAdES (PDF Advanced Electronic Signatures)
- Adobe AATL (Assinaturas Reconhecidas)
- eIDAS (Europa) / UETA/ESIGN (EUA)

#### 1.5 Tecnologias
Exibição visual com ícones FontAwesome:
- PHP (versão dinâmica)
- MySQL/MariaDB
- JavaScript
- Bootstrap 4 / AdminLTE

#### 1.6 Bibliotecas de Terceiros
Leitura automática de `composer.json` dos pacotes instalados:
- Nome do pacote
- Descrição
- Licença
- Homepage

#### 1.7 Créditos e Licença
- Informações de desenvolvimento
- Contato de suporte
- Copyright notice

#### 1.8 Links Úteis
- Manual do Usuário
- Política de Privacidade
- ITI - ICP-Brasil (externo)

---

## 📖 2. MANUAL.PHP - Manual do Usuário

### Localização
```
c:\xampp\htdocs\ged\public\manual.php
```

### Estrutura

#### 2.1 Navegação Lateral (Sticky)
Índice com scroll automático para seções:
- Início Rápido
- Gestão de Documentos
- Organização em Pastas
- Assinaturas Digitais
- Controle de Vencimentos
- Compartilhamento
- Pesquisa Avançada
- Relatórios
- Segurança
- FAQ

#### 2.2 Conteúdo Detalhado

##### Início Rápido
- Boas-vindas
- 5 primeiros passos para usar o sistema

##### Gestão de Documentos
- Como fazer upload
- Visualização e download
- Edição de propriedades
- **Alerta:** Documentos assinados não podem ter arquivo alterado

##### Organização em Pastas
- Criar pastas
- Navegação hierárquica
- Mover documentos entre pastas

##### Assinaturas Digitais ICP-Brasil
- **O que é ICP-Brasil** (validade jurídica)
- **Como assinar:** Passo a passo com certificado A1/A3
- **Validação de Assinaturas:**
  - Certificação Técnica (ITI + Adobe AATL)
  - Detalhes do certificado
  - Status de validade
- **Reconhecimento Automático** no Adobe Reader

##### Controle de Vencimentos
- **Prazos Legais Automáticos:**
  - Fiscais: 6 anos
  - Trabalhistas: 5 anos
  - Médicos: 20 anos
  - Contratos: 10 anos
  - Societários: Permanente
- **Indicador Global** no header
- **Filtros Rápidos** (Vencidos, A Vencer, Sem Vencimento)
- **Relatório de Vencimentos** com exportação CSV

##### Compartilhamento Seguro
- Criar link com senha
- Definir expiração
- Revogar links
- Auditoria de visualizações

##### Pesquisa Avançada
- Busca simples por título
- Combinação de filtros (tipo, pasta, status, texto)

##### Relatórios
- Relatório de Vencimentos
- Painel de Produtividade

##### Segurança e Privacidade
- Hash SHA-256
- Auditoria completa
- LGPD
- Backup
- Boas práticas

##### FAQ (Perguntas Frequentes)
6 questões comuns:
1. Formatos de arquivo suportados
2. Assinatura direta no sistema
3. Funcionamento do vencimento automático
4. Recuperação de documentos excluídos
5. Validação de assinatura digital
6. Acesso offline a compartilhamentos

#### 2.3 Suporte
- E-mail de contato
- Links para Sobre e Privacidade

#### 2.4 JavaScript Interativo
- **Scroll suave** para seções clicadas
- **Highlight automático** do link ativo conforme scroll

---

## 🔒 3. PRIVACIDADE.PHP - Política de Privacidade

### Localização
```
c:\xampp\htdocs\ged\public\privacidade.php
```

### Estrutura (Conformidade LGPD)

#### 3.1 Introdução
- Descrição da política
- Referência à LGPD (Lei 13.709/2018)
- Data de última atualização (dinâmica)

#### 3.2 Dados Coletados

**Dados Pessoais dos Usuários:**
- Identificação (nome, CPF, e-mail)
- Acesso (login, senha criptografada, IP)
- Uso (logins, páginas, documentos visualizados)
- Profissionais (cargo, departamento, telefone)

**Dados dos Documentos:**
- Metadados (título, tipo, autor, pasta)
- Conteúdo (arquivos)
- Hash SHA-256
- Assinaturas digitais (certificados ICP-Brasil)

**Logs de Auditoria:**
- Todas as ações
- Usuário, data/hora, IP
- Acessos via links

#### 3.3 Base Legal (LGPD)

Tabela detalhada das 5 bases legais:

| Base Legal | Descrição | Aplicação no Sistema |
|------------|-----------|---------------------|
| Art. 7º, I - Consentimento | Autorização do titular | Aceite dos termos de uso |
| Art. 7º, II - Obrigação legal | Cumprimento de lei | Retenção por prazos legais |
| Art. 7º, V - Execução de contrato | Fornecimento de serviço | Gestão de documentos |
| Art. 7º, VI - Exercício de direitos | Processos judiciais | Logs de auditoria |
| Art. 7º, IX - Legítimo interesse | Interesse do controlador | Segurança e prevenção |

#### 3.4 Finalidade do Tratamento

7 finalidades explícitas:
1. Autenticação e controle de acesso
2. Gestão eletrônica de documentos
3. Auditoria e conformidade
4. Assinaturas digitais
5. Compartilhamento seguro
6. Notificações
7. Melhorias do sistema

**Alerta:** Não compartilhamento comercial/publicitário

#### 3.5 Medidas de Segurança

**Proteção Técnica:**
- Criptografia (bcrypt para senhas)
- HTTPS/TLS
- Hash SHA-256
- Controle de acesso
- Firewall e antivírus

**Proteção Organizacional:**
- Acesso restrito
- Logs de auditoria
- Backup regular
- Treinamento de equipe

#### 3.6 Retenção e Eliminação

**Prazos de Retenção:**
- Usuários ativos: Enquanto conta em uso
- Documentos: Prazos legais (6-20 anos)
- Logs: Mínimo 5 anos
- Compartilhamentos expirados: 30 dias

**Eliminação Segura:**
- Exclusão permanente de DBs
- Remoção de arquivos e backups
- Anonimização de logs antigos

#### 3.7 Direitos do Titular (Art. 18 LGPD)

8 direitos explicados visualmente (2 colunas):
1. ✅ Confirmação de tratamento
2. ✅ Acesso aos dados
3. ✅ Correção
4. ✅ Anonimização/Bloqueio
5. ✅ Portabilidade
6. ✅ Eliminação
7. ✅ Informação sobre compartilhamento
8. ✅ Revogação de consentimento

**Alerta:** Limitações legais (retenção obrigatória)

**Como Exercer:**
- E-mail de contato
- Telefone
- Prazo de resposta: 15 dias (Art. 19 LGPD)

#### 3.8 Compartilhamento de Dados

Tabela de compartilhamento:

| Categoria | Finalidade | Base Legal |
|-----------|-----------|------------|
| Outros usuários da organização | Colaboração | Legítimo interesse |
| Terceiros via links | Visualização | Consentimento |
| Fornecedores de TI | Hospedagem/backup | Execução de contrato |
| Autoridades governamentais | Ordem judicial | Obrigação legal |

#### 3.9 Transferência Internacional
- **Localização:** Servidores no Brasil
- Notificação prévia se houver transferência internacional
- Salvaguardas adequadas (cláusulas contratuais)

#### 3.10 Alterações na Política
- Atualizações periódicas
- Notificação por e-mail em alterações substanciais
- Recomendação de revisão periódica

#### 3.11 Encarregado de Proteção de Dados (DPO)
- Nome e cargo do DPO
- Contato (e-mail, telefone)
- **Link para ANPD** (Autoridade Nacional de Proteção de Dados)
  - www.gov.br/anpd
  - falabr.cgu.gov.br (Ouvidoria)

#### 3.12 Links Úteis
- Sobre o Sistema
- Manual do Usuário
- ANPD (externo)

---

## 🎨 CARACTERÍSTICAS COMUNS

### Design
- **Interface:** AdminLTE 3 (Bootstrap 4)
- **Cards com outline:** Separação visual clara
- **Ícones:** FontAwesome 5
- **Responsivo:** Funciona em desktop e mobile

### Navegação
- **Breadcrumbs:** Painel > Página Atual
- **Links cruzados:** Interligação entre as 3 páginas
- **Sidebar integration:** Acessível via menu lateral

### Segurança
- **Autenticação obrigatória:** Require login
- **Session check:** Redirect se não logado
- **SQL dinâmico:** Estatísticas em tempo real (sobre.php)

### Acessibilidade
- **Cores semânticas:** Success, Warning, Danger, Info
- **Texto alternativo:** Em badges e ícones
- **Hierarquia de títulos:** H1 > H3 > H5 > H6

---

## 🔗 INTEGRAÇÃO COM O SISTEMA

### Header/Footer
Todas as páginas usam:
```php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
...
require_once '../templates/footer.php';
```

### Links Internos
Cada página referencia as outras:
- **Sobre:** Manual, Privacidade, ITI (externo)
- **Manual:** Sobre, Privacidade, suporte
- **Privacidade:** Sobre, Manual, ANPD (externo)

### Constantes do Sistema
Uso dinâmico de:
- `BRAND_NAME` - Nome do sistema
- `BRAND_LOGO` - Logo
- `SYSTEM_EMAIL` - E-mail de contato

---

## ✅ VALIDAÇÃO

### PHP Lint
```bash
get_errors() - 0 erros encontrados em todos os arquivos
```

### Arquivos Criados
1. ✅ `c:\xampp\htdocs\ged\public\sobre.php` (331 linhas)
2. ✅ `c:\xampp\htdocs\ged\public\manual.php` (522 linhas)
3. ✅ `c:\xampp\htdocs\ged\public\privacidade.php` (448 linhas)

### Encoding
- UTF-8 (BOM opcional)
- Compatível com Windows/Linux

---

## 📊 ESTATÍSTICAS

| Página | Linhas | Seções | Cards | Tabelas |
|--------|--------|--------|-------|---------|
| sobre.php | 331 | 8 | 8 | 2 |
| manual.php | 522 | 11 | 11 | 0 |
| privacidade.php | 448 | 12 | 12 | 3 |
| **TOTAL** | **1.301** | **31** | **31** | **5** |

---

## 🚀 PRÓXIMOS PASSOS (OPCIONAL)

### Melhorias Futuras
1. **Screenshots:** Adicionar imagens ilustrativas no manual
2. **Vídeo-tutoriais:** Embed de vídeos no manual
3. **Search:** Busca dentro do manual
4. **Versões:** Sistema de versionamento da política de privacidade
5. **Aceite formal:** Checkbox de aceite da política na criação de conta
6. **Exportação:** Gerar PDF da política para download

### SEO e Acessibilidade
- Meta tags apropriadas
- Schema.org markup
- WCAG 2.1 AA compliance

---

## 📝 CONCLUSÃO

As três páginas institucionais independentes foram criadas com sucesso, seguindo as melhores práticas de:

✅ **Design Profissional** - Interface limpa e moderna  
✅ **Informação Completa** - Cobertura de todos os aspectos do sistema  
✅ **Conformidade Legal** - LGPD, ICP-Brasil, retenção legal  
✅ **Navegação Intuitiva** - Índice, scroll suave, links cruzados  
✅ **Responsividade** - Funciona em todos os dispositivos  
✅ **Segurança** - Autenticação obrigatória  
✅ **Manutenibilidade** - Código limpo e documentado  

---

**Desenvolvido por:** Equipe de Desenvolvimento  
**Data:** 29 de outubro de 2025  
**Versão do Sistema:** 2.0.0  
**Status:** ✅ Concluído e Validado
