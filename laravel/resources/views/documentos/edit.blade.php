@extends('layouts.app')
@section('title','Editar Documento')
@section('content')
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  <h1 class="h4 mb-3">Editar: {{ $doc->titulo }}</h1>
  <form method="post" action="/documentos/{{ $doc->id }}" enctype="multipart/form-data">
    @csrf
    <div class="card card-dark card-outline">
      <div class="card-body">
        <div class="form-group"><label>Nome</label><input type="text" class="form-control" name="titulo" value="{{ $doc->titulo }}" required></div>
        <div class="form-group"><label>Descrição</label><textarea class="form-control" name="descricao" rows="2">{{ $doc->descricao }}</textarea></div>

        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Tipo</label>
            <select class="form-control" name="tipo_documento_id" required>
              @foreach($tipos as $t)
                <option value="{{ $t->id }}" {{ (int)$doc->tipo_documento_id === (int)$t->id ? 'selected' : '' }}>{{ $t->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Proprietário</label>
            <select class="form-control" name="proprietario_id">
              <option value="">—</option>
              @php $currOwner = $doc->proprietario_id ?? ($doc->owner_id ?? ($doc->usuario_id_proprietario ?? $doc->usuario_id)); @endphp
              @foreach($usuarios as $u)
                <option value="{{ $u->id }}" {{ (int)$currOwner === (int)$u->id ? 'selected' : '' }}>{{ $u->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="form-group col-md-4">
            <label>Substituir Arquivo (opcional)</label>
            <input type="file" name="arquivo" class="form-control-file">
          </div>
        </div>

        <hr>
        <h5>Identificadores / Metadados</h5>
        @foreach($campos as $c)
          @php $val = $metaVals[$c->id] ?? ''; @endphp
          <div class="form-group">
            <label>{{ $c->rotulo }}</label>
            <input type="text" class="form-control" name="meta[{{ $c->id }}]" value="{{ $val }}" {{ $c->obrigatorio ? 'required' : '' }}>
          </div>
        @endforeach

        <hr>
        <div class="form-group">
          <label>Funções Permitidas</label>
          <select class="form-control" name="funcoes_permitidas[]" multiple>
            @foreach($funcoes as $f)
              <option value="{{ $f->id }}" {{ in_array($f->id, $docFuncoes) ? 'selected' : '' }}>{{ $f->nome_funcao }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>
    <div class="mt-3">
      <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Salvar</button>
      <a href="/documentos/{{ $doc->id }}/propriedades" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
@endsection
