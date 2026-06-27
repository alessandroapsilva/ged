@extends('layouts.app')
@section('title','Documentos')
@section('content')
  <style>
    .actions-fixed { position: sticky; bottom: 0; background: #111827; padding: 10px; border-top: 1px solid #374151; display:none; }
  </style>
  <script>
    function toggleBar(){
      const any = document.querySelectorAll('.check-item:checked').length>0;
      const docs = Array.from(document.querySelectorAll('.check-item:checked')).filter(x=>x.value.startsWith('d-')).length;
      document.getElementById('bar').style.display = any? 'block':'none';
      document.getElementById('btn-combinar').disabled = docs<2;
      document.getElementById('btn-mover').disabled = !any;
      document.getElementById('btn-apagar').disabled = !any;
    }
  </script>
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1>Documentos</h1>
      <form method="get" class="form-inline">
        <input type="hidden" name="pasta_id" value="{{ $pasta_id }}"/>
        <div class="input-group input-group-sm">
          <input class="form-control" type="search" name="q" placeholder="Buscar..." value="{{ $q }}">
          <div class="input-group-append"><button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button></div>
        </div>
      </form>
    </div>
  </section>
  <section class="content">
    <div class="container-fluid">
      <div class="mb-2">
        @foreach($breadcrumbs as $i=>$b)
          @if($i>0) <span class="text-muted">/</span> @endif
          @if($b['id']===null)
            <a href="/documentos">Raiz</a>
          @else
            <a href="/documentos?pasta_id={{ $b['id'] }}">{{ $b['nome'] }}</a>
          @endif
        @endforeach
      </div>

      @if(count($subpastas))
      <div class="card card-outline card-secondary">
        <div class="card-header"><strong>Pastas</strong></div>
        <div class="card-body p-2">
          @foreach($subpastas as $p)
            <a class="btn btn-sm btn-outline-secondary mr-1 mb-1" href="/documentos?pasta_id={{ $p->id }}"><i class="fas fa-folder mr-1"></i>{{ $p->nome }}</a>
          @endforeach
        </div>
      </div>
      @endif

      <div class="card card-dark card-outline">
        <div class="card-body p-0">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th style="width:32px"><input type="checkbox" onchange="document.querySelectorAll('.check-item').forEach(c=>c.checked=this.checked); toggleBar();"></th>
                <th>Documento</th>
                <th style="width:15%">Tipo</th>
                <th style="width:15%">Atualizado</th>
                <th style="width:20%" class="text-center">Ações</th>
              </tr>
            </thead>
            <tbody>
            @foreach($documentos as $doc)
              <tr>
                <td><input type="checkbox" class="check-item" value="d-{{ $doc->id }}" onchange="toggleBar()"></td>
                <td><a href="/documentos/{{ $doc->id }}"><i class="fas fa-file-alt mr-2 text-muted"></i><strong>{{ $doc->titulo }}</strong></a><br><small class="text-muted">{{ $doc->descricao }}</small></td>
                <td>{{ $doc->tipo_nome }}</td>
                <td>{{ $doc->data_atualizacao ?? $doc->atualizado_em ?? $doc->updated_at }}</td>
                <td class="text-center">
                  <a href="/documentos/{{ $doc->id }}" class="text-info mr-2" title="Visualizar"><i class="fas fa-eye"></i></a>
                  <a href="/documentos/{{ $doc->id }}/propriedades" class="text-secondary mr-2" title="Propriedades"><i class="fas fa-list-ul"></i></a>
                  <a href="/documentos/{{ $doc->id }}/editar" class="text-warning" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
        <div id="bar" class="actions-fixed">
          <div class="d-flex align-items-center">
            <button id="btn-combinar" class="btn btn-sm btn-primary mr-2" disabled><i class="fas fa-object-group mr-1"></i>Combinar</button>
            <button id="btn-mover" class="btn btn-sm btn-secondary mr-2" disabled><i class="fas fa-arrows-alt mr-1"></i>Mover</button>
            <button id="btn-apagar" class="btn btn-sm btn-danger" disabled><i class="fas fa-trash mr-1"></i>Apagar</button>
          </div>
        </div>
      </div>
    </div>
  </section>
  <form id="form-combinar" action="/documentos/combinar" method="post" target="_blank" style="display:none;">@csrf</form>
  <script>
    document.getElementById('btn-combinar').addEventListener('click', function(){
      const ids = Array.from(document.querySelectorAll('.check-item:checked'))
        .filter(el=>el.value.startsWith('d-'))
        .map(el=>parseInt(el.value.replace('d-','')));
      if (ids.length < 2) return;
      const f = document.getElementById('form-combinar');
      f.innerHTML = f.querySelector('input[name=_token]').outerHTML; // keep csrf
      ids.forEach(id=>{ const i=document.createElement('input'); i.type='hidden'; i.name='doc_ids[]'; i.value=id; f.appendChild(i); });
      f.submit();
    });
    document.getElementById('btn-apagar').addEventListener('click', function(){
      const ids = Array.from(document.querySelectorAll('.check-item:checked')).map(el=>el.value);
      if (!ids.length) return;
      fetch('/itens/apagar-lote', { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({ ids })})
        .then(r=>r.json()).then(()=> location.reload()).catch(()=> alert('Falha ao apagar'));
    });
    document.getElementById('btn-mover').addEventListener('click', function(){
      const ids = Array.from(document.querySelectorAll('.check-item:checked')).map(el=>el.value);
      if (!ids.length) return;
      // abre a tela legada de mover
      window.location.href = '/itens/mover?ids='+encodeURIComponent(ids.join(','));
    });
  </script>
@endsection
