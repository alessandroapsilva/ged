<?php
// public/tipos_adicionar.php (VERSÃO COMPLETA ESTILO EDOK)
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include '../templates/header.php';
include '../templates/sidebar.php';
?>

<style>
    /* Estilos para a tabela de metadados, igual à tela de edição */
    .table-meta-edit input, .table-meta-edit select {
        background-color: transparent !important; border: none !important; border-radius: 0 !important;
        color: #f8f9fa !important; width: 100%; padding: .375rem 0 !important; box-shadow: none !important;
    }
    .table-meta-edit input:focus, .table-meta-edit select:focus {
        border-bottom: 1px solid #007bff;
    }
    .table-meta-edit tr:hover { background-color: #454d55; }
    .drag-handle { cursor: move; color: #6c757d; }
    .ui-sortable-placeholder { background: #55626e; height: 50px; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><h1>Criar Novo Tipo de Documento</h1></div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <form action="tipos_adicionar_process.php" method="post" id="form-tipo-documento">
                
                <div class="card card-dark card-outline">
                    <div class="card-header"><h3 class="card-title">Informações Básicas</h3></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 form-group"><label>Nome</label><input type="text" class="form-control" name="nome" placeholder="Ex: Ficha de Internação" required></div>
                            <div class="col-md-4 form-group"><label>Código do Tipo</label><input type="text" class="form-control" id="codigo_tipo" name="codigo" placeholder="Ex: FI"></div>
                            <div class="col-md-4 form-group"><label>Pasta de Destino (ID)</label><input type="number" class="form-control" name="pasta_destino_id" placeholder="ID da pasta padrão (opcional)"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label>Prazo de Vencimento</label>
                                <input type="number" class="form-control" id="vencimento_prazo" name="vencimento_prazo" placeholder="Ex: 5">
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Unidade</label>
                                <select class="form-control" id="vencimento_unidade" name="vencimento_unidade">
                                    <option value="">Nenhum</option>
                                    <option value="Dias">Dias</option>
                                    <option value="Meses">Meses</option>
                                    <option value="Anos" selected>Anos</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group align-self-center">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="permanente" name="permanente" value="1">
                                    <label class="form-check-label" for="permanente">Permanente (sem vencimento)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2 form-group"><label>Arquivar Original</label><select name="arquivar_original" class="form-control"><option value="1">Sim</option><option value="0" selected>Não</option></select></div>
                            <div class="col-md-2 form-group"><label>Assinatura Certif.</label><select name="assinatura_certificadora" class="form-control"><option value="1">Sim</option><option value="0" selected>Não</option></select></div>
                            <div class="col-md-2 form-group"><label>Restrito</label><select name="restrito" class="form-control"><option value="1">Sim</option><option value="0" selected>Não</option></select></div>
                            <div class="col-md-2 form-group"><label>Assinado</label><select name="assinado" class="form-control"><option value="1">Sim</option><option value="0" selected>Não</option></select></div>
                            <div class="col-md-2 form-group"><label>Palavras-Chave</label><input type="text" class="form-control" name="palavras_chave" placeholder="Separadas por vírgula"></div>
                            <div class="col-md-2 form-group"><label>Separador</label><input type="text" class="form-control" id="separador_campos" name="separador_campos" value="-"></div>
                        </div>
                    </div>
                </div>

                <div class="card card-dark card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Identificadores / Metadados</h3>
                        <div class="card-tools"><button type="button" class="btn btn-success btn-sm" id="btn-adicionar-campo"><i class="fas fa-plus"></i> Adicionar Campo</button></div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-meta-edit">
                            <thead><tr><th style="width: 50px;"></th><th>Identificador</th><th>Rótulo</th><th>Conteúdo</th><th>Largura</th><th>Máscara</th><th class="text-center">CodT</th><th class="text-center">Dt.O</th><th class="text-center">Obrig</th><th style="width: 50px;"></th></tr></thead>
                            <tbody id="lista-campos-associados">
                                </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card card-dark card-outline">
                    <div class="card-header"><h3 class="card-title">Amostra do QR Code</h3></div>
                    <div class="card-body row align-items-center">
                        <div class="col-md-3 text-center">
                            <img id="qr-code-preview" src="qrcode_generator.php?text=Aguardando..." alt="QR Code Preview" style="max-width: 150px; border: 1px solid #ccc; padding: 10px; background: white;">
                            <code id="qr-code-text" class="d-block mt-2" style="word-break: break-all;"></code>
                        </div>
                        <div class="col-md-9">
                            <p>Prévia do código de barras gerado em tempo real com base nos campos, no "Código do Tipo" e no "Separador".</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3 mt-3">
                    <a href="tipos_listar.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Criar Tipo de Documento</button>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include '../templates/footer.php'; ?>

<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
$(document).ready(function(){
    function toggleVencimentoFields() {
        var isPermanente = $('#permanente').is(':checked');
        $('#vencimento_prazo').prop('disabled', isPermanente);
        $('#vencimento_unidade').prop('disabled', isPermanente);
        if (isPermanente) {
            $('#vencimento_prazo').val('');
            $('#vencimento_unidade').val('');
        }
    }
    $('#permanente').on('change', toggleVencimentoFields);

    // Todo o JavaScript da página de edição é copiado aqui
    // Ele funcionará perfeitamente para adicionar novas linhas.
    let campoCounter = <?= time() ?>;

    function gerarPlaceholder(tipo, largura) {
        largura = parseInt(largura) || 8;
        if (largura > 30) largura = 30;
        let resultado = '';
        if (tipo === 'Numerico') {
            for (let i = 0; i < largura; i++) { resultado += Math.floor(Math.random() * 10); }
            return resultado;
        }
        if (tipo === 'Data') { return 'DDMMAAAA'; }
        const caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        for (let i = 0; i < largura; i++) { resultado += caracteres.charAt(Math.floor(Math.random() * caracteres.length)); }
        return resultado;
    }

    function atualizarPreviewQrCode() {
        var codigoTipo = $('#codigo_tipo').val();
        var separador = $('#separador_campos').val() || '-';
        var textoFinal = codigoTipo;
        
        $('#lista-campos-associados tr').each(function() {
            var tipoConteudo = $(this).find('select[name^="conteudo"]').val();
            var largura = $(this).find('input[name^="largura"]').val();
            var placeholder = gerarPlaceholder(tipoConteudo, largura);
            textoFinal += `${separador}${placeholder}`;
        });

        $('#qr-code-text').text(textoFinal);
        var qrCodeUrl = 'qrcode_generator.php?text=' + encodeURIComponent(textoFinal);
        $('#qr-code-preview').attr('src', qrCodeUrl);
    }

    function updateOrderInputs() {
        $('#lista-campos-associados tr').each(function(index) {
            $(this).find('.ordem_input').val(index + 1);
        });
        atualizarPreviewQrCode();
    }

    function bindRowEvents(row) {
        row.find('.btn-remover').on('click', function() {
            if (confirm('Tem certeza?')) {
                $(this).closest('tr').remove();
                updateOrderInputs();
            }
        });
        row.find('input, select').on('change keyup', atualizarPreviewQrCode);
    }

    $("#lista-campos-associados").sortable({
        handle: ".drag-handle",
        placeholder: "ui-sortable-placeholder",
        update: function(event, ui) {
            updateOrderInputs();
        }
    }).disableSelection();

    $('#btn-adicionar-campo').on('click', function() {
        campoCounter++;
        var newRowHtml = `
            <tr>
                <td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value=""></td>
                <td><input type="text" name="identificador[]" placeholder="NOME_INTERNO"></td>
                <td><input type="text" name="rotulo[]" placeholder="Nome Para o Usuário"></td>
                <td><select name="conteudo[]"><option value="Alfanumerico">Alfanumérico</option><option value="Numerico" selected>Numérico</option><option value="Data">Data</option></select></td>
                <td><input type="number" name="largura[]" placeholder="8" value="8"></td>
                <td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data">Data</option><option value="CPF/CNPJ">CPF/CNPJ</option><option value="Moeda">Moeda</option></select></td>
                <td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="contem_cod_tipo_new_${campoCounter}" name="contem_cod_tipo[${campoCounter}]" value="1"><label class="custom-control-label" for="contem_cod_tipo_new_${campoCounter}"></label></div></td>
                <td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="data_de_origem_new_${campoCounter}" name="data_de_origem[${campoCounter}]" value="1"><label class="custom-control-label" for="data_de_origem_new_${campoCounter}"></label></div></td>
                <td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_new_${campoCounter}" name="obrigatorio[${campoCounter}]" value="1"><label class="custom-control-label" for="obrigatorio_new_${campoCounter}"></label></div></td>
                <td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
        var newRow = $(newRowHtml);
        $('#lista-campos-associados').append(newRow);
        bindRowEvents(newRow);
        updateOrderInputs();
    });

    bindRowEvents($('#lista-campos-associados tr'));
    $('#codigo_tipo, #separador_campos').on('keyup', atualizarPreviewQrCode);
    atualizarPreviewQrCode(); // Chama uma vez para iniciar
});
</script>