USE ged;
SELECT slug,nome FROM email_templates WHERE slug IN ('compartilhar_documento','novo_documento','recuperar_senha','requisitar_assinatura');
