<?php
require_once '../core/init.php';
require_once PROJECT_ROOT . '/helpers/auth_helper.php';
require_auth();
require_permission('admin.access');

require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid"><div class="row mb-2"><div class="col-sm-6"><h1>Indexação de Conteúdo</h1></div></div></div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="card card-dark card-outline">
        <div class="card-body">
          <p>Esta ferramenta cria/atualiza o índice de busca full-text dos PDFs. Use após grandes importações ou quando o conteúdo não aparecer nos resultados.</p>
          <ul>
            <li><strong>OCR</strong>: <?= (defined('ENABLE_OCR_INDEXING') && ENABLE_OCR_INDEXING) ? '<span class="text-success">Ativado</span>' : '<span class="text-muted">Desativado</span>'; ?> (Tesseract + Imagick)</li>
            <li><strong>Tesseract</strong>: <?= class_exists('thiagoalessio\\TesseractOCR\\TesseractOCR') ? '<span class="text-success">Disponível</span>' : '<span class="text-warning">Indisponível</span>'; ?></li>
            <li><strong>Imagick</strong>: <?= extension_loaded('imagick') ? '<span class="text-success">Disponível</span>' : '<span class="text-warning">Indisponível</span>'; ?></li>
          </ul>
          <button id="btn-indexar" class="btn btn-primary"><i class="fas fa-cogs mr-1"></i> Reindexar todos os documentos</button>
          <div id="saida" class="mt-3" style="white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"></div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php require_once '../templates/footer.php'; ?>
<script>
$(function(){
  $('#btn-indexar').on('click', function(){
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Indexando...');
    $('#saida').text('Iniciando indexação...');
    $.post('ajax_indexar_documentos.php')
      .done(function(resp){
        try{
          const data = typeof resp === 'string' ? JSON.parse(resp) : resp;
          if (data.sucesso) {
            $('#saida').text(data.mensagem + (Object.keys(data.detalhes||{}).length ? "\nFalhas:\n" + JSON.stringify(data.detalhes, null, 2) : ''));
            Swal.fire('Concluído', data.mensagem, 'success');
          } else {
            $('#saida').text(data.mensagem || 'Falha');
            Swal.fire('Erro', data.mensagem || 'Falha na indexação', 'error');
          }
        } catch(e){
          $('#saida').text('Resposta inesperada do servidor');
        }
      })
      .fail(function(xhr){
        $('#saida').text('Erro ' + xhr.status + ': ' + (xhr.responseText || 'Falha na requisição'));
        Swal.fire('Erro', 'Falha na requisição', 'error');
      })
      .always(function(){
        btn.prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Reindexar todos os documentos');
      });
  });
});
</script>
