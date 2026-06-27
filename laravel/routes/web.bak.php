<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TiposController;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ItemsController;

Route::get('/', function () { return redirect('/documentos'); });

// Autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Tipos de Documentos
Route::get('/tipos_listar', [TiposController::class, 'index']);
Route::get('/tipos/{id}/editar', [TiposController::class, 'edit'])->whereNumber('id');
Route::post('/tipos/{id}', [TiposController::class, 'update'])->whereNumber('id');

// Rotas protegidas
Route::middleware('gedauth')->group(function(){
    // Documentos
    Route::get('/documentos', [DocumentosController::class, 'index']);
    Route::get('/documentos/{id}', [DocumentosController::class, 'show'])->whereNumber('id');
    Route::get('/documentos/{id}/propriedades', [DocumentosController::class, 'properties'])->whereNumber('id');
    Route::get('/documentos/{id}/editar', [DocumentosController::class, 'edit'])->whereNumber('id');
    Route::post('/documentos/{id}', [DocumentosController::class, 'update'])->whereNumber('id');
    Route::post('/documentos/combinar', [DocumentosController::class, 'combinar']);

    // Tipos de Documentos
    Route::get('/tipos_listar', [TiposController::class, 'index']);
    Route::get('/tipos/{id}/editar', [TiposController::class, 'edit'])->whereNumber('id');
    Route::post('/tipos/{id}', [TiposController::class, 'update'])->whereNumber('id');

    // Itens (lote)
    Route::post('/itens/apagar-lote', [ItemsController::class, 'apagarLote']);
    Route::match(['GET','POST'], '/itens/mover', [ItemsController::class, 'mover']);
});

Route::get('/qrcode', [App\Http\Controllers\QRController::class, 'show']);

// Buscar: reusa lista de documentos
Route::get('/buscar', function(\Illuminate\Http\Request $r){
    $q = (string)$r->query('q','');
    return redirect('/documentos?q='.urlencode($q));
});

