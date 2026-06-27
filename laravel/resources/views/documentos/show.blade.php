@extends('layouts.app')
@section('title', $doc->titulo)
@section('content')
  <h1 class="h4 mb-3">{{ $doc->titulo }}</h1>
  @if($fileUrl)
    <p><a href="{{ $fileUrl }}" target="_blank" class="btn btn-primary"><i class="fas fa-external-link-alt mr-1"></i> Abrir Arquivo</a>
    <a href="{{ $fileUrl }}?download=1" class="btn btn-success ml-2"><i class="fas fa-download mr-1"></i> Baixar</a></p>
    <iframe src="{{ $fileUrl }}" style="width:100%;height:70vh;border:1px solid #e5e7eb"></iframe>
  @else
    <div class="alert alert-warning">Documento sem arquivo associado.</div>
  @endif
  <div class="mt-3">
    <a href="/documentos/{{ $doc->id }}/propriedades" class="btn btn-secondary">Propriedades</a>
    <a href="/documentos/{{ $doc->id }}/editar" class="btn btn-warning">Editar</a>
  </div>
@endsection
