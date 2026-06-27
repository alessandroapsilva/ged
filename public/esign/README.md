# eSign - Módulo de Assinatura Digital

## 📝 Visão Geral

O **eSign** é o módulo centralizado de assinaturas digitais do GED, oferecendo:

- ✅ **Assinatura com Certificado ICP-Brasil** (A1)
- ✅ **Assinatura Simples** (upload de imagem)
- ✅ **QR Code** embutido no PDF assinado
- ✅ **Verificação pública** via link único
- ✅ **Validação jurídica** com trilha de auditoria

---

## 🚀 Como Usar

### 1. Assinar um Documento

#### Via Lista de Documentos:
1. Acesse a lista de documentos em `documentos.php`
2. Clique no ícone de **assinatura** (✒️) do documento desejado
3. No modal que abre, escolha:
   - **"Assinar Digitalmente Agora"** → abre o eSign
   - **"Requisitar por E-mail"** → envia solicitação de assinatura

#### Via eSign Direto:
- Acesse: `http://localhost/ged/public/esign/index.php?id=ID_DO_DOCUMENTO`

---

### 2. Tipos de Assinatura

#### 🔐 Assinatura com Certificado (ICP-Brasil)
**Quando usar**: Para documentos que exigem validade jurídica robusta com certificado digital.

**Passos**:
1. No card "Assinatura com Certificado (ICP-Brasil)":
   - Faça upload do certificado digital (`.pfx` ou `.p12`)
   - Digite a senha do certificado
   - Opcionalmente, informe o motivo da assinatura
2. Clique em **"Assinar com Certificado"**
3. O sistema irá:
   - Processar a assinatura ICP
   - Gerar um **código verificador único**
   - Criar um **QR Code** com link de verificação
   - Carimbar o PDF com QR, link e informações da assinatura
   - Salvar em ambas as tabelas (`documentos_assinaturas` + `assinaturas` para compatibilidade)

**Observação sobre OpenSSL**:
- Se o OpenSSL não estiver configurado, o sistema entra em modo **fallback visual**: o PDF é carimbado com QR e metadados, mas sem assinatura criptográfica PKCS7.
- Para assinatura ICP completa, instale o OpenSSL no PATH do Windows.

---

#### ✍️ Assinatura Simples (Imagem)
**Quando usar**: Para documentos internos ou que não exigem certificado digital.

**Passos**:
1. No card "Assinatura Simples (Imagem)":
   - Faça upload de uma imagem da sua assinatura (PNG/JPG)
   - Opcionalmente, informe o motivo
2. Clique em **"Assinar (Simples)"**
3. O sistema irá:
   - Inserir a imagem da assinatura no PDF
   - Gerar **verificador** e **QR Code**
   - Carimbar o PDF com dados da assinatura
   - Registrar na base de dados

---

### 3. Verificar Assinatura

Após assinar, o PDF terá um **QR Code** embutido. Para verificar:

1. **Via QR Code**:
   - Escaneie o QR do PDF com seu celular
   - Será redirecionado para a página de verificação pública

2. **Via Link Manual**:
   - Acesse: `http://localhost/ged/public/esign/verificar.php?code=SEU_CODIGO_VERIFICADOR`

3. **Informações Exibidas**:
   - Nome do signatário
   - CPF/CNPJ (se disponível)
   - Data e hora da assinatura
   - IP de origem
   - Código verificador
   - ID do documento
   - Link de verificação compartilhável

---

## 🛠️ Estrutura de Arquivos

```
public/esign/
├── index.php                      # Interface principal de assinatura
├── assinar_process.php            # Processador de assinatura ICP-Brasil
├── assinar_simples_process.php   # Processador de assinatura simples
└── verificar.php                  # Página pública de verificação
```

---

## ⚙️ Configuração e Instalação

### Pré-requisitos

1. **Extensões PHP**:
   - `pdo`, `pdo_mysql`
   - `openssl` (para ICP-Brasil)
   - `gd` (para manipulação de imagens)

2. **Composer**:
   - FPDI (`setasign/fpdi`)
   - TCPDF (`tecnickcom/tcpdf`)

3. **Biblioteca QR Code**:
   - Baixe: https://sourceforge.net/projects/phpqrcode/
   - Extraia em: `public/libraries/phpqrcode/`

4. **Schema do Banco**:
   ```bash
   mysql -u root ged < sql/assinaturas_migration.sql
   ```

### Executar Instalador

```bash
php scripts/install_assinaturas.php
```

O instalador verifica:
- ✅ Extensões PHP
- ✅ Dependências Composer
- ✅ Estrutura de pastas
- ✅ Conexão com banco e tabelas
- ✅ Arquivos do módulo
- ✅ Biblioteca de QR Code

---

## 🔍 Troubleshooting

### Problema: "Acesso negado" ao abrir o eSign

**Causa**: Sessão não iniciada ou usuário não logado.

**Solução**:
- Verifique se você está logado no sistema
- Acesse via botão de assinatura na lista de documentos (não digite a URL manualmente sem estar logado)

---

### Problema: Assinatura ICP não gera PKCS7

**Causa**: OpenSSL não está instalado ou não está no PATH.

**Solução**:
1. Instale o OpenSSL para Windows
2. Adicione ao PATH do sistema
3. Reinicie o Apache/XAMPP
4. Teste: `openssl version` no PowerShell

**Alternativa**: O sistema funciona em **modo fallback visual** (carimbo + QR sem criptografia), adequado para validação interna.

---

### Problema: QR Code não aparece no PDF

**Causa**: Biblioteca `phpqrcode` não está instalada.

**Solução**:
1. Baixe: https://sourceforge.net/projects/phpqrcode/
2. Extraia em: `c:\xampp\htdocs\ged\public\libraries\phpqrcode\`
3. Verifique se existe o arquivo: `public/libraries/phpqrcode/qrlib.php`

---

### Problema: Erro ao assinar (FPDI/TCPDF)

**Causa**: Dependências Composer não instaladas.

**Solução**:
```bash
cd c:\xampp\htdocs\ged
composer install
```

---

### Problema: PDF não é carimbado visualmente

**Causa**: Permissões de escrita nas pastas ou FPDI não carregado.

**Solução**:
1. Verifique permissões:
   - `public/storage/uploads` (escrita)
   - `public/storage/assinaturas` (escrita)
2. Verifique se `vendor/autoload.php` existe
3. Rode `composer install` novamente

---

## 📊 Fluxo Técnico

### Assinatura ICP-Brasil

```
1. Upload certificado (.pfx/.p12) + senha
2. AssinaturaDigital::assinarDocumento()
   ├── Valida certificado via OpenSSL
   ├── Gera verificador único (SHA256)
   ├── Cria QR Code com link de verificação
   ├── Carimba PDF (PDFSigner::signWithImage)
   │   ├── Texto: nome, data, link, código
   │   └── QR Code embutido
   ├── Salva em documentos_assinaturas (JSON detalhado)
   └── Dual-write em assinaturas (compatibilidade)
3. Retorna verificador + URL
4. Redireciona com mensagem de sucesso
```

### Assinatura Simples

```
1. Upload imagem da assinatura
2. assinar_simples_process.php
   ├── Gera verificador único
   ├── Cria QR Code
   ├── Carimba PDF com imagem + QR + texto
   ├── Salva em documentos_assinaturas
   └── Dual-write em assinaturas (versao_id)
3. Redireciona para lista de documentos
```

### Verificação Pública

```
1. QR ou link com ?code=VERIFICADOR
2. verificar.php consulta tabela assinaturas
3. Exibe dados do signatário e documento
4. Valida integridade via verificador único
```

---

## 🔐 Segurança

- **Verificador único** (SHA256) por assinatura
- **Dual-write** para compatibilidade e auditoria
- **Hash SHA256** do arquivo salvo em `detalhes` (JSON)
- **IP e User-Agent** registrados
- **Trilha de auditoria** em `log_sistema`
- **Timestamps** com fuso horário configurado

---

## 📚 Tabelas do Banco

### `documentos_assinaturas` (nova)
```sql
- id
- documento_id
- usuario_id
- data_assinatura
- tipo_assinatura (Simples | ICP-Brasil)
- detalhes (JSON com verificador, URL, hash, IP, etc.)
```

### `assinaturas` (legado/compatibilidade)
```sql
- id
- documento_id
- versao_id
- usuario_id
- nome_signatario
- ip_assinatura
- verificador (UNIQUE)
- data_assinatura
- status
```

**Por que duas tabelas?**
- `documentos_assinaturas`: Schema moderno com JSON flexível
- `assinaturas`: Compatibilidade com sistema legado (esign/verificar.php)
- **Dual-write** garante que ambas fiquem sincronizadas

---

## 🎯 Roadmap

- [ ] Assinatura via Canvas (desenho digital)
- [ ] Assinatura em lote (múltiplos documentos)
- [ ] PAdES (assinatura ICP nativa no PDF com cadeia de confiança)
- [ ] Timestamp de servidor de tempo (ITI)
- [ ] Assinatura com biometria
- [ ] Migração completa para `documentos_assinaturas` (aposentar tabela legada)

---

## 📞 Suporte

Para dúvidas ou problemas, execute o diagnóstico:

```bash
php scripts/install_assinaturas.php
```

O instalador mostrará exatamente o que está faltando (extensões, bibliotecas, tabelas, etc.).

---

## 📄 Licença

© 2025 ENFAS GED - Todos os direitos reservados.
