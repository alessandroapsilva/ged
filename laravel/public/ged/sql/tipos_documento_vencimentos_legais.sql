-- sql/tipos_documento_vencimentos_legais.sql
-- Atualiza prazos de vencimento padrão conforme legislação brasileira

-- FISCAL/TRIBUTÁRIO: 6 anos (CTN art. 173/174 + exercício corrente)
UPDATE tipos_documento 
SET vencimento_prazo = 6, vencimento_unidade = 'Anos'
WHERE LOWER(nome) LIKE '%nota%fiscal%' 
   OR LOWER(nome) LIKE '%nf-e%'
   OR LOWER(nome) LIKE '%nfe%'
   OR LOWER(nome) LIKE '%sped%'
   OR LOWER(nome) LIKE '%fiscal%'
   OR LOWER(nome) LIKE '%tribut%'
   OR LOWER(nome) LIKE '%imposto%'
   OR LOWER(nome) LIKE '%danfe%'
   OR LOWER(nome) LIKE '%livro%fiscal%';

-- TRABALHISTA: 5 anos (CF/88 art. 7º, XXIX)
UPDATE tipos_documento 
SET vencimento_prazo = 5, vencimento_unidade = 'Anos'
WHERE LOWER(nome) LIKE '%folha%pagamento%'
   OR LOWER(nome) LIKE '%ponto%eletrônico%'
   OR LOWER(nome) LIKE '%recibo%pagamento%'
   OR LOWER(nome) LIKE '%holerite%'
   OR LOWER(nome) LIKE '%trct%'
   OR LOWER(nome) LIKE '%fgts%'
   OR LOWER(nome) LIKE '%inss%'
   OR LOWER(nome) LIKE '%férias%'
   OR LOWER(nome) LIKE '%13%salário%'
   OR LOWER(nome) LIKE '%trabalhista%';

-- MÉDICO/SAÚDE OCUPACIONAL: 20 anos (Lei 13.787/2018 e NR-7)
UPDATE tipos_documento 
SET vencimento_prazo = 20, vencimento_unidade = 'Anos'
WHERE LOWER(nome) LIKE '%prontu%rio%'
   OR LOWER(nome) LIKE '%aso%'
   OR LOWER(nome) LIKE '%atestado%médico%'
   OR LOWER(nome) LIKE '%pcmso%'
   OR LOWER(nome) LIKE '%médico%'
   OR LOWER(nome) LIKE '%saúde%ocupacional%'
   OR LOWER(nome) LIKE '%exame%ocupacional%'
   OR LOWER(nome) LIKE '%ppp%'; -- Perfil Profissiográfico Previdenciário

-- CONTRATOS: 10 anos (Código Civil art. 205)
UPDATE tipos_documento 
SET vencimento_prazo = 10, vencimento_unidade = 'Anos'
WHERE LOWER(nome) LIKE '%contrato%'
   OR LOWER(nome) LIKE '%termo%aditivo%'
   OR LOWER(nome) LIKE '%acordo%';

-- SOCIETÁRIOS/PERMANENTES: sem vencimento (guarda indefinida)
UPDATE tipos_documento 
SET vencimento_prazo = NULL, vencimento_unidade = NULL
WHERE LOWER(nome) LIKE '%estatuto%'
   OR LOWER(nome) LIKE '%contrato%social%'
   OR LOWER(nome) LIKE '%ata%assembl%'
   OR LOWER(nome) LIKE '%ata%reuni%o%'
   OR LOWER(nome) LIKE '%livro%diário%'
   OR LOWER(nome) LIKE '%livro%razão%'
   OR LOWER(nome) LIKE '%certid%o%nascimento%'
   OR LOWER(nome) LIKE '%certid%o%casamento%'
   OR LOWER(nome) LIKE '%escrit%ra%pública%';

-- ADMINISTRAÇÃO PÚBLICA: variável (seguir TTD do órgão; exemplo genérico 5 anos)
UPDATE tipos_documento 
SET vencimento_prazo = 5, vencimento_unidade = 'Anos'
WHERE LOWER(nome) LIKE '%of%cio%'
   OR LOWER(nome) LIKE '%memorando%'
   OR LOWER(nome) LIKE '%despacho%'
   OR LOWER(nome) LIKE '%portaria%'
   OR LOWER(nome) LIKE '%decreto%'
   OR LOWER(nome) LIKE '%resolu%o%';

-- Exibe resumo após aplicação
SELECT 
    nome,
    vencimento_prazo,
    vencimento_unidade,
    CASE 
        WHEN vencimento_prazo IS NULL THEN 'Permanente (sem vencimento)'
        ELSE CONCAT('Vence em ', vencimento_prazo, ' ', vencimento_unidade)
    END AS prazo_legal
FROM tipos_documento
ORDER BY 
    CASE 
        WHEN vencimento_prazo IS NULL THEN 999
        ELSE vencimento_prazo 
    END ASC,
    nome ASC;
