<?php
header('Content-Type: text/html; charset=utf-8');
/**
 * Exemplo de Pagina Simples - GED Profissional
 */

// Para usar quando nao quer template complexo
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GED - Exemplo Simples</title>
    <link rel="stylesheet" href="css/professional.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Bem-vindo ao GED Profissional</h1>
            </div>
            <div class="card-body">
                <p>Sistema de Gestao Eletronica de Documentos</p>
                <p>Versao 1.0 - Pronto para Producao</p>
                
                <div style="margin-top: 20px;">
                    <h3 style="margin-bottom: 15px;">Exemplos de Componentes:</h3>
                    
                    <div style="margin: 15px 0;">
                        <button class="btn btn-primary">Botao Primario</button>
                        <button class="btn btn-secondary">Botao Secundario</button>
                        <button class="btn btn-success">Botao Sucesso</button>
                        <button class="btn btn-danger">Botao Erro</button>
                    </div>
                    
                    <div style="margin: 15px 0;">
                        <span class="badge badge-primary">Novo</span>
                        <span class="badge badge-success">Aprovado</span>
                        <span class="badge badge-warning">Pendente</span>
                        <span class="badge badge-error">Erro</span>
                    </div>
                    
                    <div style="margin: 15px 0;">
                        <div class="alert alert-success">Mensagem de sucesso!</div>
                        <div class="alert alert-warning">Mensagem de aviso!</div>
                        <div class="alert alert-error">Mensagem de erro!</div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <p style="margin: 0; color: var(--color-text-secondary);">
                    Para mais informacoes, acesse a documentacao em /docs/
                </p>
            </div>
        </div>
    </div>
</body>
</html>
