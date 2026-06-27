@extends('layouts.app')
@section('title','Tipos de Documentos')
@section('content')
    <section class="content-header">
        <div class="container-fluid"><h1>Tipos de Documentos</h1></div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <form method="get" class="form-inline">
                            <label for="limit" class="mr-2">Mostrar</label>
                            <select name="limit" id="limit" class="form-control form-control-sm mr-3" onchange="this.form.submit()">
                                @foreach([10,25,50,100] as $n)
                                    <option value="{{$n}}" {{ ($resultados_por_pagina==$n)?'selected':'' }}>{{$n}}</option>
                                @endforeach
                            </select>
                            <div class="input-group input-group-sm">
                                <input type="text" name="busca" class="form-control" placeholder="Pesquisar por nome ou código..." value="{{ $busca }}">
                                <div class="input-group-append"><button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button></div>
                            </div>
                        </form>
                        <div class="card-tools">
                            <a href="#" class="btn btn-success disabled" title="Migrando - use editar"> <i class="fas fa-plus"></i> Criar Tipo</a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width:5%">ID</th>
                                <th style="width:15%">Código</th>
                                <th>Nome</th>
                                <th style="width:15%" class="text-center">Restrito</th>
                                <th style="width:15%" class="text-center">Assinado</th>
                                <th style="width:10%" class="text-center">Documentos</th>
                                <th style="width:15%" class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($tipos as $t)
                            <tr>
                                <td>{{$t->id}}</td>
                                <td>{{ $t->codigo }}</td>
                                <td>{{ $t->nome }}</td>
                                <td class="text-center">{{ (int)($t->restrito ?? 0) ? 'Sim' : 'Não' }}</td>
                                <td class="text-center">{{ (int)($t->assinado ?? 0) ? 'Sim' : 'Não' }}</td>
                                <td class="text-center">{{ $t->total_documentos }}</td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-primary" href="/tipos/{{ $t->id }}/editar"><i class="fas fa-edit"></i> Editar</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
