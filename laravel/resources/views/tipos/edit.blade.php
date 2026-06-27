@extends('layouts.app')
@section('title','Editar Tipo')
@section('content')
    <style>
        .table-meta-edit input, .table-meta-edit select { background-color: transparent !important; border: none !important; color: inherit !important; width: 100%; box-shadow: none !important; }
        .table-meta-edit tr:hover { background-color: #f4f6f9; }
        .drag-handle { cursor: move; color: #6c757d; }
    </style>
    <section class="content-header">
        <div class="container-fluid d-flex justify-content-between">
            <h1>Editar: {{ $tipo->nome }}</h1>
            <div><a href="/tipos_listar" class="btn btn-secondary">Voltar para a Lista</a></div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            <form action="/tipos/{{ $tipo->id }}" method="post" id="form-tipo-documento">
                @csrf
                <div class="card card-dark card-outline">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 form-group"><label>Nome</label><input type="text" class="form-control" name="nome" value="{{ $tipo->nome }}" required></div>
                            <div class="col-md-6 form-group"><label>Pasta de Destino (Opcional)</label><input type="text" class="form-control" name="pasta_destino" value="{{ $tipo->pasta_destino }}"></div>
                            <div class="col-md-3 form-group"><label>Código do Tipo</label><input type="text" class="form-control" id="codigo_tipo" name="codigo" value="{{ $tipo->codigo }}"></div>
                            <div class="col-md-3 form-group"><label>Prazo de Vencimento</label><input type="number" class="form-control" id="vencimento_prazo" name="vencimento_prazo" value="{{ $tipo->vencimento_prazo }}"></div>
                            <div class="col-md-3 form-group"><label>Unidade</label><select class="form-control" id="vencimento_unidade" name="vencimento_unidade"><option value="" {{ empty($tipo->vencimento_unidade) ? 'selected' : '' }}>Nenhum</option><option value="Dias" {{ ($tipo->vencimento_unidade)=='Dias' ? 'selected' : '' }}>Dias</option><option value="Meses" {{ ($tipo->vencimento_unidade)=='Meses' ? 'selected' : '' }}>Meses</option><option value="Anos" {{ ($tipo->vencimento_unidade)=='Anos' ? 'selected' : '' }}>Anos</option></select></div>
                            <div class="col-md-3 form-group align-self-center"><div class="form-check mt-3"><input class="form-check-input" type="checkbox" id="permanente" name="permanente" value="1" {{ empty($tipo->vencimento_prazo) ? 'checked' : '' }}><label class="form-check-label" for="permanente">Permanente (sem vencimento)</label></div></div>
                            <div class="col-md-3 form-group"><label>Separador de Campos</label><input type="text" class="form-control" id="separador_campos" name="separador" value="{{ $tipo->separador ?? '-' }}"></div>
                            <div class="col-md-3 form-group"><label>Restrito</label><select name="restrito" id="campo-restrito" class="form-control"><option value="0" {{ ($tipo->restrito ?? 0) == 0 ? 'selected' : '' }}>Não</option><option value="1" {{ ($tipo->restrito ?? 0) == 1 ? 'selected' : '' }}>Sim</option></select></div>
                            <div class="col-12 form-group"><label for="palavras_chave">Palavras-Chave</label><input type="text" class="form-control" name="palavras_chave" id="palavras_chave" value="{{ $tipo->palavras_chave }}" placeholder="Ex: nota fiscal, contrato, NFe"></div>
                        </div>
                    </div>
                </div>

                <div class="card card-dark card-outline">
                    <div class="card-header"><h3 class="card-title">Identificadores / Metadados</h3><div class="card-tools"><button type="button" class="btn btn-success btn-sm" id="btn-adicionar-campo"><i class="fas fa-plus"></i> Adicionar Campo</button></div></div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-meta-edit">
                            <thead><tr><th style="width:50px;"></th><th>Identificador</th><th>Rótulo</th><th>Conteúdo</th><th>Largura</th><th>Máscara</th><th class="text-center">Obrigatório</th><th style="width:50px;"></th></tr></thead>
                            <tbody id="lista-campos-associados">
                            @foreach($campos as $campo)
                                <tr>
                                    <input type="hidden" class="campo_id_input" name="campo_id[]" value="{{ $campo->id }}">
                                    <td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value="{{ $campo->ordem }}"></td>
                                    <td><input type="text" name="identificador[]" value="{{ $campo->identificador }}"></td>
                                    <td><input type="text" name="rotulo[]" value="{{ $campo->rotulo }}"></td>
                                    <td><select name="conteudo[]"><option value="Alfanumerico" {{ $campo->conteudo=='Alfanumerico' ? 'selected' : '' }}>Alfanumérico</option><option value="Numerico" {{ $campo->conteudo=='Numerico' ? 'selected' : '' }}>Numérico</option><option value="Data" {{ $campo->conteudo=='Data' ? 'selected' : '' }}>Data</option></select></td>
                                    <td><input type="number" name="largura[]" value="{{ $campo->largura }}"></td>
                                    <td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data" {{ $campo->mascara=='Data' ? 'selected' : '' }}>Data</option><option value="CPF/CNPJ" {{ $campo->mascara=='CPF/CNPJ' ? 'selected' : '' }}>CPF/CNPJ</option></select></td>
                                    <td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_{{ $campo->id }}" name="obrigatorio[{{ $campo->id }}]" value="1" {{ $campo->obrigatorio ? 'checked' : '' }}><label class="custom-control-label" for="obrigatorio_{{ $campo->id }}"></label></div></td>
                                    <td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card card-dark card-outline" id="card-permissoes-acesso" style="display: {{ ($tipo->restrito??0)?'block':'none' }};">
                    <div class="card-header"><h3 class="card-title">Acesso por Funções</h3></div>
                    <div class="card-body">
                        <label for="funcoes_permitidas">Funções Permitidas</label>
                        <select class="form-control" id="funcoes_permitidas" name="funcoes_permitidas[]" multiple>
                            @foreach($funcoes as $f)
                                <option value="{{ $f->id }}" {{ in_array($f->id, $funcoesPermitidas) ? 'selected' : '' }}>{{ $f->nome_funcao }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
                </div>
            </form>
        </div>
    </section>
<script>
function updateOrderInputs(){
  document.querySelectorAll('#lista-campos-associados tr').forEach((tr, idx)=>{
    let ordem = tr.querySelector('.ordem_input'); if (ordem) ordem.value = (idx+1);
  });
}
document.addEventListener('click', function(e){ if(e.target.closest('.btn-remover')){ e.preventDefault(); const tr = e.target.closest('tr'); tr.parentNode.removeChild(tr); updateOrderInputs(); }});
document.getElementById('btn-adicionar-campo').addEventListener('click', function(){ const tbody=document.getElementById('lista-campos-associados'); const id='new_'+Date.now(); const tr=document.createElement('tr'); tr.innerHTML=`<input type="hidden" class="campo_id_input" name="campo_id[]" value="${id}"><td class="text-center align-middle drag-handle"><i class="fas fa-arrows-alt"></i><input type="hidden" class="ordem_input" name="ordem[]" value=""></td><td><input type="text" name="identificador[]"></td><td><input type="text" name="rotulo[]"></td><td><select name="conteudo[]"><option value="Alfanumerico">Alfanumérico</option><option value="Numerico" selected>Numérico</option><option value="Data">Data</option></select></td><td><input type="number" name="largura[]" value="8"></td><td><select name="mascara[]"><option value="">Nenhuma</option><option value="Data">Data</option><option value="CPF/CNPJ">CPF/CNPJ</option></select></td><td class="text-center align-middle"><div class="custom-control custom-switch"><input type="checkbox" class="custom-control-input" id="obrigatorio_${id}" name="obrigatorio[${id}]" value="1"><label class="custom-control-label" for="obrigatorio_${id}"></label></div></td><td class="align-middle"><button type="button" class="btn btn-danger btn-xs btn-remover"><i class="fas fa-trash"></i></button></td>`; tbody.appendChild(tr); updateOrderInputs(); });
document.getElementById('campo-restrito').addEventListener('change', function(){ document.getElementById('card-permissoes-acesso').style.display = (this.value=='1')?'block':'none'; });
function toggleV(){ var perm=document.getElementById('permanente').checked; document.getElementById('vencimento_prazo').disabled=perm; document.getElementById('vencimento_unidade').disabled=perm; if(perm){document.getElementById('vencimento_prazo').value=''; document.getElementById('vencimento_unidade').value='';}}; toggleV(); document.getElementById('permanente').addEventListener('change', toggleV);
</script>
@endsection
