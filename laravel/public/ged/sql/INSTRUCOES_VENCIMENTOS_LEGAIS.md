-- sql/INSTRUCOES_VENCIMENTOS_LEGAIS.md
# Vencimentos Legais - Guia de Aplicação

## Resumo das regras implementadas

Este script SQL (`tipos_documento_vencimentos_legais.sql`) aplica prazos de guarda conforme a legislação brasileira vigente:

### 1. **Fiscal/Tributário: 6 anos**
- **Base legal**: CTN arts. 173 (decadência) e 174 (prescrição)
- **Regra prática**: 5 anos + exercício corrente = ~6 anos civis
- **Documentos**: NF-e, DANFE, SPED, livros fiscais, DAS, impostos, etc.
- **Observação**: Safe harbor conservador; a Receita pode fiscalizar até 5 anos retroativos (contados do exercício seguinte).

### 2. **Trabalhista: 5 anos**
- **Base legal**: CF/88 art. 7º, XXIX; CLT; ARE 709.212 STF (FGTS)
- **Documentos**: Folha de pagamento, ponto eletrônico, recibos, holerite, TRCT, férias, 13º, FGTS, INSS
- **Observação**: Prescrição quinquenal; ação ajuizada até 2 anos do desligamento.

### 3. **Médico/Saúde Ocupacional: 20 anos**
- **Base legal**: Lei 13.787/2018 (prontuários médicos); NR-7 (PCMSO)
- **Documentos**: Prontuários médicos, ASO, PCMSO, PPP, exames ocupacionais
- **Observação**: Prontuário deve ser guardado 20 anos a partir do último registro.

### 4. **Contratos: 10 anos**
- **Base legal**: Código Civil art. 205 (prescrição geral)
- **Documentos**: Contratos, termos aditivos, acordos, parcerias
- **Observação**: Prescrição de 10 anos para obrigações em geral; algumas têm prazo menor (art. 206).

### 5. **Societários: Permanente (sem vencimento)**
- **Base legal**: Lei das S.A., Código Civil, Instrução CVM
- **Documentos**: Estatuto, contrato social, atas, livros diário/razão
- **Observação**: Guarda indefinida; documentos constitutivos e de governança.

### 6. **Administração Pública: 5 anos (ou conforme TTD)**
- **Base legal**: Lei 8.159/1991; Tabela de Temporalidade (CONARQ/órgão)
- **Documentos**: Ofícios, memorandos, despachos, portarias, decretos
- **Observação**: Regra genérica 5 anos; cada órgão deve seguir sua Tabela de Temporalidade oficial.

---

## Como aplicar

### Passo 1: Backup
```sql
-- Faça backup da tabela antes de executar
CREATE TABLE tipos_documento_backup AS SELECT * FROM tipos_documento;
```

### Passo 2: Executar o script
No terminal MySQL/phpMyAdmin ou via linha de comando:
```bash
mysql -u root -p ged < sql/tipos_documento_vencimentos_legais.sql
```

Ou pelo phpMyAdmin: copie e cole o conteúdo do arquivo `tipos_documento_vencimentos_legais.sql` na aba SQL.

### Passo 3: Revisar
O script exibe um `SELECT` final com resumo dos prazos aplicados. Revise e ajuste manualmente conforme necessário:
```sql
UPDATE tipos_documento SET vencimento_prazo = 7, vencimento_unidade = 'Anos' WHERE nome = 'Contrato XYZ';
```

---

## Personalização

- **Setor público**: substitua o bloco "Administração Pública" pelas regras da TTD do seu órgão.
- **Empresa privada**: adicione tipos específicos (ex: documentos de LGPD, SLA, etc.).
- **Documentos permanentes**: defina `vencimento_prazo = NULL, vencimento_unidade = NULL`.

---

## Referências

- **CTN (Código Tributário Nacional)**: arts. 173 e 174
- **CF/88**: art. 7º, XXIX (trabalhista)
- **Lei 13.787/2018**: prontuários médicos
- **NR-7 (MTE)**: PCMSO e guarda de documentos ocupacionais
- **Código Civil**: arts. 205 e 206 (prescrição)
- **Lei 8.159/1991**: Política Nacional de Arquivos
- **CONARQ**: Tabelas de Temporalidade e Destinação

---

**Data de criação**: 29/10/2025  
**Autor**: Sistema GED - Conforme eDok e legislação brasileira
