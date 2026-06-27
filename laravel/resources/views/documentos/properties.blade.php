@extends('layouts.app')
@section('title', 'Propriedades - '.$doc->titulo)
@section('content')
  @if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  <h1 class="h4 mb-3">Propriedades</h1>
  <div class="card card-dark card-outline">
    <div class="card-body">
      <p><strong>Nome:</strong> {{ $doc->titulo }}</p>
      <p><strong>Tipo:</strong> {{ $doc->tipo_nome }}</p>
      <p><strong>Pasta:</strong> {{ $doc->pasta_nome }}</p>
      <p><strong>Tamanho:</strong> {{ $tamanho ? number_format($tamanho/1024,2) . ' KB' : 'N/D' }}</p>
      <p><strong>Integridade:</strong> {!! $hashOk ? '<span class="badge badge-success">Válida</span>' : '<span class="badge badge-danger">Inválida</span>' !!}</p>
      @if($doc->caminho_arquivo)
        <p><a href="{{ $legacyBaseUrl }}/{{ ltrim($doc->caminho_arquivo,'/') }}" target="_blank" class="btn btn-primary btn-sm"><i class="fas fa-file"></i> Abrir Arquivo</a></p>
      @endif
      <hr>
      <h5>Metadados</h5>
      @if(count($metadados))
        <ul class="list-unstyled">
          @foreach($metadados as $m)
            <li><strong>{{ $m->chave }}:</strong> {{ $m->valor }}</li>
          @endforeach
        </ul>
      @else
        <p class="text-muted">Sem metadados registrados.</p>
      @endif
    </div>
  </div>
  <div class="mt-3">
    <a href="/documentos/{{ $doc->id }}/editar" class="btn btn-warning">Editar</a>
    <a href="/documentos" class="btn btn-secondary">Voltar</a>
  </div>
@endsection
