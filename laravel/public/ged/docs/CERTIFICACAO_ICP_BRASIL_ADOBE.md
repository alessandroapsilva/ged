# Certificação de Assinaturas Digitais ICP-Brasil

## Reconhecimento ITI (Instituto Nacional de Tecnologia da Informação)

### O que é ICP-Brasil?
A **Infraestrutura de Chaves Públicas Brasileira (ICP-Brasil)** é uma cadeia hierárquica de confiança que viabiliza a emissão de **certificados digitais** para identificação virtual do cidadão.

### Base Legal
- **MP 2.200-2/2001**: Institui a ICP-Brasil e garante autenticidade, integridade e validade jurídica de documentos em forma eletrônica
- **Lei 14.063/2020**: Regulamenta assinaturas eletrônicas e digitais no Brasil
- **Decreto 10.278/2020**: Digitalização de documentos públicos e privados

### Validade Jurídica
Documentos assinados com **certificado ICP-Brasil** têm:
- ✅ **Presunção de autenticidade** (MP 2.200-2, art. 10, §1º)
- ✅ **Validade jurídica equivalente à assinatura manuscrita** (MP 2.200-2, art. 10, §2º)
- ✅ **Admissibilidade como prova** em processos judiciais e administrativos
- ✅ **Dispensa de reconhecimento de firma** em cartório

### Hierarquia de Certificação
```
AC-Raiz (ITI)
  └─ AC Intermediárias
      └─ Autoridades Certificadoras (AC)
          └─ Certificados Finais (e-CPF, e-CNPJ, e-Jurídico)
```

O **ITI** (autarquia federal vinculada à Casa Civil) é a **Autoridade Certificadora Raiz** da ICP-Brasil e credencia todas as ACs.

---

## Reconhecimento Adobe (Adobe Approved Trust List - AATL)

### O que é AATL?
A **Adobe Approved Trust List** é uma lista de **Autoridades Certificadoras confiáveis** mantida pela Adobe. Certificados de ACs incluídas na AATL são **automaticamente reconhecidos** pelo Adobe Reader e Acrobat **sem intervenção do usuário**.

### Como funciona?
1. **Adobe Reader/Acrobat** possui a lista AATL embutida no software
2. Ao abrir um PDF assinado com certificado ICP-Brasil de AC credenciada (ex: Serpro, Certisign, Soluti), o Adobe:
   - ✅ **Valida automaticamente** a assinatura
   - ✅ Exibe **ícone verde** de "Assinatura Válida"
   - ✅ Mostra detalhes do certificado
   - ✅ **Não exige** que o usuário importe manualmente a cadeia de certificados

### Autoridades Certificadoras ICP-Brasil na AATL
As principais ACs brasileiras estão incluídas na Adobe AATL:
- **Serpro** (Serviço Federal de Processamento de Dados)
- **Certisign**
- **Soluti** (antiga Serasa)
- **Valid Certificadora**
- **AC FENACON**
- Outras ACs credenciadas pelo ITI

### Vantagens para o usuário
- ✅ **Experiência sem fricção**: Assinatura aparece como válida imediatamente
- ✅ **Confiança visual**: Ícone verde de validação
- ✅ **Portabilidade internacional**: Documentos assinados são reconhecidos globalmente
- ✅ **Sem configuração adicional**: Funciona "out of the box"

---

## Validação Internacional

### eIDAS (União Europeia)
Embora ICP-Brasil não seja reconhecida diretamente pelo regulamento **eIDAS** (Electronic Identification, Authentication and Trust Services), certificados ICP-Brasil na AATL são:
- ✅ Aceitos em transações comerciais internacionais
- ✅ Reconhecidos por softwares globais (Adobe, Microsoft, etc.)
- ⚠️ Para uso oficial na UE, pode ser necessário uso de **assinatura qualificada eIDAS**

### UETA/ESIGN (Estados Unidos)
- **UETA** (Uniform Electronic Transactions Act)
- **ESIGN Act** (Electronic Signatures in Global and National Commerce Act)
- ✅ ICP-Brasil é aceita como **assinatura eletrônica válida** nos EUA
- ✅ Adobe AATL garante reconhecimento técnico

---

## Como verificar no Adobe Reader

### 1. Abrir o documento PDF assinado
- Abra no **Adobe Reader** ou **Adobe Acrobat**

### 2. Verificar assinatura
- Painel de assinaturas (lado esquerdo) ou
- Barra azul no topo "Assinado e todas as assinaturas são válidas"

### 3. Clicar na assinatura
- Exibe **detalhes do certificado**:
  - Nome do signatário
  - CPF/CNPJ
  - Autoridade Certificadora (ex: "AC SERPRO v5")
  - Validade do certificado
  - Timestamp (carimbo de tempo)

### 4. Validar cadeia de confiança
- Clique em **"Propriedades da Assinatura"**
- Aba **"Certificado"**
- Verifique:
  - ✅ **Emissor**: AC credenciada ICP-Brasil
  - ✅ **Raiz**: AC-Raiz ITI (na AATL)
  - ✅ **Status**: Válido

---

## Diferença: ICP-Brasil vs. Assinatura Simples

| Característica | ICP-Brasil | Assinatura Simples |
|----------------|------------|-------------------|
| **Certificado Digital** | Sim (A1/A3) | Não |
| **Validade Jurídica** | Presunção de autenticidade | Válida, mas pode exigir prova adicional |
| **Reconhecimento Adobe** | Automático (AATL) | Não (aparece como "desconhecido") |
| **Base Legal** | MP 2.200-2/2001 | Lei 14.063/2020 (Art. 4º, I) |
| **Uso recomendado** | Contratos, procurações, atos jurídicos | Documentos internos, aprovações |
| **Custo** | Pago (certificado A1/A3) | Gratuito |

---

## Carimbo de Tempo (Timestamp)

### O que é?
**Timestamp** é uma **prova criptográfica** de que um documento existia em determinado momento, emitida por uma **Autoridade de Carimbo de Tempo (ACT)** credenciada.

### Por que é importante?
- ✅ Garante que a assinatura foi feita **antes** da expiração do certificado
- ✅ **Validade de Longo Prazo (LTV)**: Documento permanece válido mesmo após expiração do certificado
- ✅ Conformidade com **Decreto 10.278/2020** (Art. 6º, §4º)

### No Adobe Reader
- Documento com timestamp exibe: **"Assinatura válida com carimbo de tempo confiável"**
- Detalhes mostram: **Data/hora da assinatura** emitida pela ACT

---

## Checklist de Conformidade

### Para assinaturas com validade jurídica máxima:
- [x] Certificado **ICP-Brasil** (e-CPF, e-CNPJ, e-Jurídico)
- [x] AC credenciada pelo **ITI**
- [x] AC presente na **Adobe AATL**
- [x] **Carimbo de tempo** de ACT credenciada
- [x] Formato **PAdES** (PDF Advanced Electronic Signatures)
- [x] Verificação de integridade (hash SHA-256)
- [x] Metadados preservados (autor, data, local)

---

## Referências

### Legislação
- [MP 2.200-2/2001](http://www.planalto.gov.br/ccivil_03/mpv/antigas_2001/2200-2.htm) - ICP-Brasil
- [Lei 14.063/2020](http://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/l14063.htm) - Assinaturas Eletrônicas
- [Decreto 10.278/2020](http://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/decreto/d10278.htm) - Digitalização

### Órgãos
- [ITI - Instituto Nacional de Tecnologia da Informação](https://www.gov.br/iti)
- [Adobe AATL](https://helpx.adobe.com/acrobat/kb/approved-trust-list2.html)

### Documentação Técnica
- [Padrão PAdES - ETSI EN 319 142](https://www.etsi.org/deliver/etsi_en/319100_319199/31914201/01.01.01_60/en_31914201v010101p.pdf)
- [DOC-ICP-15 - Assinaturas Digitais na ICP-Brasil](https://www.gov.br/iti/pt-br/centrais-de-conteudo/doc-icp-15-versao-7-1-pdf)

---

**Atualizado em**: 29/10/2025  
**Versão**: 1.0 - Documentação de Certificação ICP-Brasil e Adobe AATL
