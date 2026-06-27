# Central de Digitalização (GED)

Este documento descreve como funciona a nova Central de Digitalização, requisitos de instalação, recursos avançados e procedimentos de troubleshooting.

## Visão Geral

A página `public/digitalizar.php` permite capturar documentos diretamente do scanner usando o **Dynamsoft WebTWAIN** ou, caso o serviço não esteja disponível, realizar **upload manual de PDF**. Após a captura, o documento é salvo na tabela `documentos` e seus metadados opcionais são registrados. Há suporte simplificado a OCR (texto extraído da primeira página) quando o add-on estiver presente.

## Requisitos

1. Windows 10+ (64-bit) ou Server com acesso ao dispositivo de digitalização.
2. Drivers TWAIN ou WIA do scanner instalados e testados via software nativo do fabricante.
3. Serviço Dynamsoft WebTWAIN instalado:
   - Download: https://download.dynamsoft.com/web-twain/setup/DynamsoftServiceSetup.msi
   - Porta padrão HTTP: 18622
   - Porta padrão HTTPS: 18623
4. Firewall liberando tráfego local nessas portas (loopback 127.0.0.1).
5. PHP com extensão `fileinfo` e permissões de escrita em `public/storage/uploads`.

## Recursos Implementados

| Recurso | Descrição |
|---------|-----------|
| Auto-detecção do serviço | Tenta carregar via protocolo da página e faz retry alternando http/https. |
| Controles avançados | DPI, modo de cor (RGB, Gray, BW), ADF, Duplex, Limite de páginas. |
| OCR simplificado | Extrai texto da primeira página (se addon disponível). |
| Fallback upload | Permite anexar PDF manualmente (validação de tipo e tamanho). |
| Cálculo de vencimento | Integra com lógica da tabela `tipos_documento` (prazo e unidade). |
| Hash de integridade | Calcula SHA-256 do PDF armazenado. |
| Auditoria opcional | Tenta gravar em `digitalizacoes_log` (se tabela existir). |
| Limite de páginas | Descarta páginas excedentes conforme limite definido pelo usuário. |
| Metadados dinâmicos | Carregados via `ajax_get_metadados_fields.php` para o tipo selecionado. |

## Estrutura de Dados

Tabela existente: `documentos` (campos principais já utilizados pelo processo).

Tabela opcional (criar para auditoria detalhada):

```sql
CREATE TABLE digitalizacoes_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  documento_id BIGINT NOT NULL,
  usuario_id BIGINT NOT NULL,
  modo VARCHAR(20) NOT NULL,          -- webtwain | upload_manual
  scanner_nome VARCHAR(255) NULL,
  paginas INT NOT NULL,
  tem_ocr TINYINT(1) NOT NULL DEFAULT 0,
  tamanho_bytes BIGINT NOT NULL,
  ip_origem VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL,
  INDEX (documento_id),
  INDEX (usuario_id),
  INDEX (modo),
  INDEX (scanner_nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Fluxo de Operação

1. Página é carregada e tenta inicializar WebTWAIN.
2. Se sucesso: lista scanners e habilita botão “Digitalizar Página”.
3. Usuário ajusta DPI, cor, ADF, duplex e (opcional) limite de páginas.
4. Digitaliza — páginas excedentes ao limite são descartadas automaticamente.
5. Usuário define título, tipo e metadados, opcionalmente OCR.
6. Ao salvar: gera PDF, calcula hash, extrai páginas, salva registro e metadados.
7. Registra log de auditoria se a tabela existir.
8. Redireciona para a listagem de documentos com flash message.
9. Fallback: Se serviço não instalado, mostra card de Upload Manual (PDF) usando o mesmo fluxo do item 6 em diante.

## Segurança e Boas Práticas

- Valide sempre o tamanho máximo do arquivo (implementado: 50 MB no upload manual).
- A pasta `public/storage/uploads` deve ter permissões restritas (não executável) e preferencialmente protegida contra listagem direta.
- Recomenda-se varredura antivirus/antimalware em PDFs recebidos, especialmente em cenários multiusuário.
- Utilize HTTPS na aplicação para evitar mistura de conteúdo ao acessar a porta segura do WebTWAIN.
- Ajuste cabeçalhos (ex.: `Content-Security-Policy`) para mitigar riscos de injeção via bibliotecas externas.

## Parametrização dos Controles

| Controle | Valor Padrão | Observação |
|----------|--------------|------------|
| DPI | 200 | Ajustar para 150–300 para equilíbrio entre qualidade e tamanho. |
| Modo de Cor | RGB | BW reduz tamanho; Gray é intermediário. |
| ADF | off | Ativar para scanners com alimentador automático. |
| Duplex | off | Só faz sentido se ADF suportar frente e verso. |
| Limite de Páginas | vazio | Sem limite; se preencher, excedentes são removidas pós aquisição. |

## Troubleshooting

| Problema | Causa Provável | Solução |
|----------|----------------|---------|
| “Serviço não encontrado” | Serviço WebTWAIN não instalado ou bloqueado | Instalar MSI, liberar firewall, clicar “tentar novamente”. |
| Nenhum scanner listado | Driver TWAIN/WIA ausente | Instalar driver oficial e reiniciar serviço. |
| OCR falha | Add-on OCR não presente ou licença faltando | Prosseguir sem OCR; verificar documentação Dynamsoft. |
| PDF salvo sem páginas | Falha na conversão | Checar console do navegador e atualizar versão do SDK. |
| Erro de hash ou mover arquivo | Permissões na pasta de upload | Ajustar ACL / NTFS para permitir escrita pelo usuário do servidor. |

## Scripts PowerShell Úteis

```powershell
# Testar portas do serviço
Test-NetConnection 127.0.0.1 -Port 18622
Test-NetConnection 127.0.0.1 -Port 18623

# Liberar firewall (administrador)
New-NetFirewallRule -DisplayName "Dynamsoft WebTWAIN 18622" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 18622
New-NetFirewallRule -DisplayName "Dynamsoft WebTWAIN 18623" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 18623
```

## Próximos Aperfeiçoamentos (Sugestões)

- Pré-visualização rápida de miniaturas por página.
- Crop e deskew automáticos antes de gerar PDF.
- OCR multi-página com fila assíncrona.
- Criptografia opcional do PDF armazenado (em repouso) + chave rotacionável.
- Integração com assinatura eletrônica pós-digitalização.
- Painel de estatísticas de volume (páginas digitalizadas por período, uso ADF, economia de espaço com BW vs RGB).

## Licenciamento

Confirme se a chave em `digitalizar.php` corresponde ao seu plano de uso (produção vs teste). Para produção, substitua a ProductKey antes de liberar a aplicação.

---
Última atualização: {{DATE}}