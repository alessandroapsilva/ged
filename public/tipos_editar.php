<?php
require_once '../core/init.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$tipo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tipo_id <= 0) { header('Location: tipos_listar.php'); exit(); }

try {
    $stmt_tipo = $pdo->prepare("SELECT * FROM tipos_documento WHERE id = ?");
    $stmt_tipo->execute([$tipo_id]);
    $tipo_doc = $stmt_tipo->fetch(PDO::FETCH_ASSOC);
    if (!$tipo_doc) { header('Location: tipos_listar.php'); exit(); }

    $stmt_campos = $pdo->prepare("SELECT * FROM metadado_campos WHERE tipo_documento_id = ? ORDER BY ordem ASC");
    $stmt_campos->execute([$tipo_id]);
    $campos_associados = $stmt_campos->fetchAll(PDO::FETCH_ASSOC);

    $funcoes = $pdo->query("SELECT id, nome_funcao FROM funcoes ORDER BY nome_funcao ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stmt_funcoes_permitidas = $pdo->prepare("SELECT funcao_id FROM tipo_documento_funcoes WHERE tipo_documento_id = ?");
    $stmt_funcoes_permitidas->execute([$tipo_id]);
    $funcoes_permitidas_ids = $stmt_funcoes_permitidas->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) { die("Erro ao carregar dados: " . $e->getMessage()); }

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<style>
    .table-meta-edit input, .table-meta-edit select { background-color: transparent !important; border: none !important; color: inherit !important; width: 100%; box-shadow: none !important; }
    .table-meta-edit tr:hover { background-color: #454d55; }
    .drag-handle { cursor: move; color: #6c757d; }
    .ui-sortable-placeholder { background: #55626e; height: 50px; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between">
            <h1>Editar: <?= htmlspecialchars($tipo_doc['nome']) ?></h1>
            <div><a href="tipos_listar" class="btn btn-secondary">Voltar para a Lista de Tipos</a></div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <form action="tipos_salvar" method="post" id="form-tipo-documento">
                <?php if (function_exists('csrf_input')) { echo csrf_input(); } ?>
                <input type="hidden" name="tipo_id" value="<?= $tipo_id; ?>">
                <div class="card card-dark card-outline">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Nome</label><input type="text" class="form-control" name="nome" value="<?= htmlspecialchars($tipo_doc['nome']); ?>" required></div>
                            <div class="col-md-6 form-group"><label>Pasta de Destino (Opcional)</label><input type="text" class="form-control" name="pasta_destino" value="<?= htmlspecialchars($tipo_doc['pasta_destino'] ?? ''); ?>"></div>
                            <div class="col-md-3 form-group"><label>Código do Tipo</label><input type="text" class="form-control" id="codigo_tipo" name="codigo" value="<?= htmlspecialchars($tipo_doc['codigo'] ?? ''); ?>"></div>
                            
                            <div class="col-md-3 form-group">
                                <label>Prazo de Vencimento</label>
                                <input type="number" class="form-control" id="vencimento_prazo" name="vencimento_prazo" value="<?= htmlspecialchars($tipo_doc['vencimento_prazo'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 form-group">
                                <label>Unidade</label>
                                <select class="form-control" id="vencimento_unidade" name="vencimento_unidade">
                                    <option value="" <?= empty($tipo_doc['vencimento_unidade']) ? 'selected' : '' ?>>Nenhum</option>
                                    <option value="Dias" <?= ($tipo_doc['vencimento_unidade'] ?? '') == 'Dias' ? 'selected' : '' ?>>Dias</option>
                                    <option value="Meses" <?= ($tipo_doc['vencimento_unidade'] ?? '') == 'Meses' ? 'selected' : '' ?>>Meses</option>
                                    <option value="Anos" <?= ($tipo_doc['vencimento_unidade'] ?? '') == 'Anos' ? 'selected' : '' ?>>Anos</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group align-self-center">
                                <div class="form-check mt-3">
                                    <input class="form-check-input" type="checkbox" id="permanente" name="permanente" value="1" <?= empty($tipo_doc['vencimento_prazo']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="permanente">Permanente (sem vencimento)</label>
                                </div>
                            </div>

                            <div class="col-md-3 form-group"><label>Separador de Campos</label><input type="text" class="form-control" id="separador_campos" name="separador" value="<?= htmlspecialchars($tipo_doc['separador'] ?? '-'); ?>"></div>
                            <div class="col-md-3 form-group"><label>Restrito</label><select name="restrito" id="campo-restrito" class="form-control"><option value="0" <?= ($tipo_doc['restrito'] ?? 0) == 0 ? 'selected' : '' ?>>Não</option><option value="1" <?= ($tipo_doc['restrito'] ?? 0) == 1 ? 'selected' : '' ?>>Sim</option></select></div>
                        <div class="col-12 form-group">
    <label for="palavras_chave">Palavras-Chave de Identificação (separadas por vírgula)</label>
    <input type="text" class="form-control" name="palavras_chave" id="palavras_chave" value="<?= htmlspecialchars($tipo_doc['palavras_chave'] ?? ''); ?>" placeholder="Ex: nota fiscal, contrato, NFe">
    <small class="form-text text-muted">Quando um documento for digitalizado, o sistema procurará por estas palavras no texto para classificar o tipo automaticamente.</small>
</div>
                        </div>
                    </div>
                </div>

                <div class="card card-dark card-outline" id="card-permissoes-acesso" style="display: <?= ($tipo_doc['restrito'] ?? 0) == 1 ? 'block' : 'none' ?>;">
                    <div class="card-header"><h3 class="card-title">Funções Permitidas</h3></div>
                    <div class="card-body">
                        <?php foreach($funcoes as $funcao): ?>
                            <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="funcoes_permitidas[]" value="<?= $funcao['id'] ?>" id="funcao_<?= $funcao['id'] ?>" <?= in_array($funcao['id'], $funcoes_permitidas_ids) ? 'checked' : '' ?>><label class="form-check-label" for="funcao_<?= $funcao['id'] ?>"><?= htmlspecialchars($funcao['nome_funcao']) ?></label></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card card-dark card-outline">
                    <div class="card-header"><h3 class="card-title">Identificadores / Metadados</h3><div class="card-tools"><button type="button" class="btn btn-success btn-sm" id="btn-adicionar-campo"><i class="fas fa-plus"></i> Adicionar Campo</button></div></div>
                    <div class="card-body p-0"><table class="table table-striped table-meta-edit"><thead><tr><th style="width: 50px;"></th><th>Identificador</th><th>Rótulo</th><th>Conteúdo</th><th>Largura</th><th>Máscara</th><th class="text-center">Obrigatório</th><th style="width: 50px;"></th></tr></thead><tbody id="lista-campos-associados"><?php foreach ($campos_associados as $campo): ?><tr><input type="hidden" class="campo_id_input" name="campo_id[]" value="<?= $campo['id'] ?>"><td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value="<?= $campo['ordem'] ?>"></td><td><input type="text" name="identificador[]" value="<?= htmlspecialchars($campo['identificador']) ?>"></td><td><input type="text" name="rotulo[]" value="<?= htmlspecialchars($campo['rotulo']) ?>"></td><td><select name="conteudo[]"><option value="Alfanumerico" <?= $campo['conteudo'] == 'Alfanumerico' ? 'selected' : '' ?>>Alfanumérico</option><option value="Numerico" <?= $campo['conteudo'] == 'Numerico' ? 'selected' : '' ?>>Numérico</option><option value="Data" <?= $campo['conteudo'] == 'Data' ? 'selected' : '' ?>>Data</option></select></td><td><input type="number" name="largura[]" value="<?= $campo['largura'] ?>"></td><td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data" <?= $campo['mascara'] == 'Data' ? 'selected' : '' ?>>Data</option><option value="CPF/CNPJ" <?= $campo['mascara'] == 'CPF/CNPJ' ? 'selected' : '' ?>>CPF/CNPJ</option></select></td><td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_<?= $campo['id'] ?>" name="obrigatorio[<?= $campo['id'] ?>]" value="1" <?= $campo['obrigatorio'] ? 'checked' : '' ?>><label class="custom-control-label" for="obrigatorio_<?= $campo['id'] ?>"></label></div></td><td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td></tr><?php endforeach; ?></tbody></table></div>
                </div>
                <div class="card card-dark card-outline">
                    <div class="card-header"><h3 class="card-title">Amostra do QR Code</h3></div>
                    <div class="card-body"><div class="row align-items-center"><div class="col-md-3 text-center"><img id="qr-code-preview" src="" alt="QR Code Preview" style="max-width: 150px; border: 1px solid #ccc; padding: 10px; background: white;"><code id="qr-code-text" class="d-block mt-2" style="word-break: break-all;"></code></div><div class="col-md-9"><ul><li>Aqui você confere uma prévia do QR code deste tipo de documento conforme suas configurações.</li><li>Ajustes feitos em "Código", "Separador" e nos campos "Conteúdo" e "Largura" são visualizados em tempo real.</li></ul></div></div></div>
                </div>
                <div class="mb-3 mt-3"><a href="tipos_listar.php" class="btn btn-secondary">Cancelar</a><button type="submit" class="btn btn-success float-right"><i class="fas fa-save"></i> Salvar Alterações</button></div>
            </form>
        </div>
    </section>
</div>
<?php require_once '../templates/footer.php'; ?>
<script>
// Robustez: só usa jQuery UI sortable se disponível
(function(){
  function updateOrderInputs(){ $('#lista-campos-associados tr').each(function(index){ $(this).find('.ordem_input').val(index + 1); }); }
  function bindRowEvents(row){
    row.find('.btn-remover').on('click', function(){ if(confirm('Tem certeza?')){ $(this).closest('tr').remove(); updateOrderInputs(); }});
    row.find('input, select').on('change keyup', function(){ /* preview atualizado em outro bloco */ });
  }
  $(function(){
    if ($.fn.sortable) {
      $("#lista-campos-associados").sortable({ handle: ".drag-handle", placeholder: "ui-sortable-placeholder", update: updateOrderInputs }).disableSelection();
    } else {
      // Degrada graciosamente: sem arrastar, mantém ordem pela sequência
      try { console.warn('jQuery UI Sortable ausente; usando fallback simples.'); } catch(e){}
    }
    bindRowEvents($('#lista-campos-associados tr'));
    $('#btn-adicionar-campo').off('click').on('click', function(){
      window.campoCounter = (window.campoCounter||0)+1;
      var id = 'new_' + window.campoCounter;
      var newRow = $(
        '<tr>'+
          '<input type="hidden" class="campo_id_input" name="campo_id[]" value="'+id+'">'+
          '<td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value=""></td>'+
          '<td><input type="text" name="identificador[]"></td>'+
          '<td><input type="text" name="rotulo[]"></td>'+
          '<td><select name="conteudo[]"><option value="Alfanumerico">Alfanumérico</option><option value="Numerico" selected>Numérico</option><option value="Data">Data</option></select></td>'+
          '<td><input type="number" name="largura[]" value="8"></td>'+
          '<td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data">Data</option><option value="CPF/CNPJ">CPF/CNPJ</option></select></td>'+
          '<td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_'+id+'" name="obrigatorio['+id+']" value="1"><label class="custom-control-label" for="obrigatorio_'+id+'"></label></div></td>'+
          '<td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td>'+
        '</tr>'
      );
      $('#lista-campos-associados').append(newRow);
      bindRowEvents(newRow);
      updateOrderInputs();
    });
  });
})();
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

    // Executa no carregamento da página
    toggleVencimentoFields();

    // Executa quando o checkbox muda
    $('#permanente').on('change', toggleVencimentoFields);

    // O restante do seu script JS existente continua aqui...
    $('#campo-restrito').on('change', function() { $('#card-permissoes-acesso').slideToggle(this.value == 1); });
    let campoCounter = <?= time() ?>;
    function gerarPlaceholder(tipo, largura) { let r = ''; largura = parseInt(largura) || 8; if(tipo==='Numerico'){for(let i=0;i<largura;i++){r+=Math.floor(Math.random()*10);}}else{const c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';for(let i=0;i<largura;i++){r+=c.charAt(Math.floor(Math.random()*c.length));}} return r; }
    function atualizarPreviewQrCode() {
        var textoFinal = $('#codigo_tipo').val();
        var separador = $('#separador_campos').val() || '-';
        $('#lista-campos-associados tr').each(function() {
            var tipoConteudo = $(this).find('select[name^="conteudo"]').val();
            var largura = $(this).find('input[name^="largura"]').val();
            textoFinal += separador + gerarPlaceholder(tipoConteudo, largura);
        });
        $('#qr-code-text').text(textoFinal);
        $('#qr-code-preview').attr('src', 'qrcode_generator.php?text=' + encodeURIComponent(textoFinal));
    }
    function updateOrderInputs() { $('#lista-campos-associados tr').each(function(index) { $(this).find('.ordem_input').val(index + 1); }); atualizarPreviewQrCode(); }
    function bindRowEvents(row) { row.find('.btn-remover').on('click', function() { if(confirm('Tem certeza?')){ $(this).closest('tr').remove(); updateOrderInputs(); }}); row.find('input, select').on('change keyup', atualizarPreviewQrCode); }
    $("#lista-campos-associados").sortable({ handle: ".drag-handle", placeholder: "ui-sortable-placeholder", update: function() { updateOrderInputs(); }}).disableSelection();
    $('#btn-adicionar-campo').on('click', function() { campoCounter++; var newRow = $(`<tr><input type="hidden" class="campo_id_input" name="campo_id[]" value="new_${campoCounter}"><td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value=""></td><td><input type="text" name="identificador[]"></td><td><input type="text" name="rotulo[]"></td><td><select name="conteudo[]"><option value="Alfanumerico">Alfanumérico</option><option value="Numerico" selected>Numérico</option><option value="Data">Data</option></select></td><td><input type="number" name="largura[]" value="8"></td><td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data">Data</option><option value="CPF/CNPJ">CPF/CNPJ</option></select></td><td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_new_${campoCounter}" name="obrigatorio[new_${campoCounter}]" value="1"><label class="custom-control-label" for="obrigatorio_new_${campoCounter}"></label></div></td><td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td></tr>`); $('#lista-campos-associados').append(newRow); bindRowEvents(newRow); updateOrderInputs(); });
    bindRowEvents($('#lista-campos-associados tr'));
    $('#codigo_tipo, #separador_campos').on('keyup', atualizarPreviewQrCode);
    updateOrderInputs();
});
// Toggle vencimento fields when "permanente" is checked
$(function(){
  function toggleVencimento(){
    var perm = $('#permanente').is(':checked');
    $('#vencimento_prazo, #vencimento_unidade').prop('disabled', perm);
    if(perm){ $('#vencimento_prazo').val(''); $('#vencimento_unidade').val(''); }
  }
  toggleVencimento();
  $(document).on('change','#permanente', toggleVencimento);
  $('#form-tipo-documento').on('submit', function(){ toggleVencimento(); });
});
</script>
