# Módulo de Assinaturas - ENFAS GED

## Visão Geral

O módulo de assinaturas do ENFAS GED oferece suporte completo para assinatura digital de documentos, com duas modalidades:

### 1. **Assinatura Simples (Visual)**
- Desenhar assinatura com mouse/touch
- Digitar nome (convertido em imagem estilizada)
- Upload de imagem de assinatura
- Aplicação visual sobre o PDF
- Registro em banco de dados com timestamp e IP

### 2. **Assinatura ICP-Brasil (Certificado Digital)**
- Suporte para certificados A1 (PFX/P12)
- Assinatura criptográfica compatível com padrão ICP-Brasil
- Validação e verificação de certificados
- Validade jurídica

## Estrutura de Arquivos

```
ged/
├── public/
│   ├── assinaturas_assinar.php          # Página principal de assinatura
│   ├── assinaturas_assinar_process.php  # Processador de assinaturas
│   ├── assinaturas_verificar.php        # Verificação de assinaturas
│   ├── assinaturas_minhas.php           # Lista documentos assinados
│   └── storage/
│       └── assinaturas/                 # Imagens de assinaturas visuais
├── core/
│   └── assinatura_digital.php           # Classe para ICP-Brasil
├── helpers/
│   └── pdf_signer.php                   # Helper para assinatura visual
└── sql/
    └── assinaturas.sql                  # Schema de banco de dados
```

## Banco de Dados

### Tabela: `documentos_assinaturas`

```sql
CREATE TABLE IF NOT EXISTS documentos_assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_assinatura DATETIME NOT NULL,
    tipo_assinatura ENUM('ICP-Brasil', 'Simples', 'Eletronica') NOT NULL,
    detalhes JSON,
    FOREIGN KEY (documento_id) REFERENCES documentos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

### Campos adicionais em `documentos`:

```sql
ALTER TABLE documentos 
ADD COLUMN assinado BOOLEAN DEFAULT 0,
ADD COLUMN data_assinatura DATETIME NULL,
ADD COLUMN assinado_por INT NULL;
```

## Fluxo de Uso

### Para Assinatura Simples:

1. Usuário acessa documento e clica em "Assinar"
2. Escolhe entre: Desenhar / Digitar / Upload
3. Preenche motivo (opcional)
4. Sistema gera imagem da assinatura
5. Aplica overlay visual no PDF usando FPDI/TCPDF
6. Gera novo PDF assinado
7. Atualiza registro do documento
8. Insere log na tabela `documentos_assinaturas`

### Para Assinatura ICP-Brasil:

1. Usuário acessa documento e clica em "Assinar"
2. Seleciona aba "ICP-Brasil"
3. Faz upload do certificado PFX/P12
4. Informa senha do certificado
5. Sistema valida certificado
6. Assina PDF usando OpenSSL
7. Extrai informações do certificado
8. Atualiza documento e registra assinatura

## Funcionalidades

### `assinaturas_assinar.php`
- Interface com abas para Simples / ICP-Brasil
- Preview do documento
- Canvas para desenhar assinatura
- Geração de texto estilizado
- Upload de imagem
- Histórico de assinaturas do documento

### `assinaturas_assinar_process.php`
- Processa ambos os tipos de assinatura
- Valida certificados ICP-Brasil
- Gera PDFs assinados
- Registra logs de auditoria
- Retorna JSON com status

### `assinaturas_verificar.php`
- Exibe todas as assinaturas de um documento
- Valida assinaturas ICP-Brasil
- Mostra detalhes do certificado
- Exibe imagens de assinaturas simples
- Timeline de assinaturas

### `assinaturas_minhas.php`
- Lista todos os documentos assinados pelo usuário
- Filtros por tipo, data, busca
- Estatísticas (total, ICP, simples)
- Acesso rápido para visualizar/baixar/verificar

## Dependências

### Composer:
```json
{
    "require": {
        "setasign/fpdi": "^2.3",
        "tecnickcom/tcpdf": "^6.6"
    }
}
```

### PHP Extensions:
- OpenSSL (para ICP-Brasil)
- GD ou Imagick (para manipulação de imagens)

## Configuração

### 1. Instalar dependências:
```bash
cd c:\xampp\htdocs\ged
composer install
```

### 2. Criar estrutura de pastas:
```bash
mkdir -p public/storage/assinaturas
chmod 755 public/storage/assinaturas
```

### 3. Importar schema:
```bash
mysql -u root ged < sql/assinaturas.sql
```

## Integração

### Header (templates/header.php):
- Link rápido "Minhas Assinaturas" na barra superior

### Sidebar (templates/sidebar.php):
- Menu "Assinaturas" com submenu "Minhas Assinaturas"

### Lista de Documentos (documentos.php):
- Ícone de assinatura em cada documento
- Link direto para `assinaturas_assinar.php`

## Segurança

1. **Validação de Sessão**: Todas as páginas verificam autenticação
2. **Transações DB**: Rollback em caso de erro
3. **Sanitização**: Inputs sanitizados e validados
4. **Auditoria**: Logs completos em `log_sistema`
5. **IP Tracking**: Registro do IP em cada assinatura
6. **Certificados Temporários**: Removidos após uso

## Próximas Melhorias

- [ ] QR Code de verificação em PDFs assinados
- [ ] Assinatura em lote
- [ ] Notificações por email
- [ ] Integração com Gov.br para validação ICP
- [ ] Suporte a múltiplas páginas de assinatura
- [ ] Posicionamento customizável da assinatura visual
- [ ] API REST para assinaturas
- [ ] Suporte a assinatura eletrônica (OTP)

## Troubleshooting

### Erro: "Class 'setasign\Fpdi\Fpdi' not found"
- Executar: `composer install`

### Erro: "openssl_sign() has been disabled"
- Habilitar OpenSSL no php.ini

### Assinatura visual não aparece no PDF
- Verificar permissões da pasta `storage/assinaturas`
- Verificar se FPDI/TCPDF estão instalados

### Certificado ICP não aceito
- Verificar formato (deve ser PFX ou P12)
- Verificar validade do certificado
- Verificar senha informada

## Suporte

Para dúvidas ou problemas, consulte:
- Documentação oficial: [README.md](../README.md)
- Logs do sistema: `public/logs_sistema.php`
- Contato: suporte@enfasged.local
