<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutenticacaoController;
use App\Http\Controllers\PropostaController;
use App\Http\Controllers\VotoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ConfiguracaoController;

// Rotas de Health Check (públicas)
Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/database', [HealthController::class, 'database']);
Route::get('/health/database/tables', [HealthController::class, 'databaseTables']);

Route::post('/entrar', [AutenticacaoController::class, 'entrar']);
Route::post('/votar', [VotoController::class, 'votar']);
Route::get('/propostas/ativa', [PropostaController::class, 'obterAtiva']);
Route::get('/propostas/{idCriptografado}/resultados', [PropostaController::class, 'obterResultados']);
Route::get('/configuracoes', [ConfiguracaoController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sair', [AutenticacaoController::class, 'sair']);
    Route::get('/eu', [AutenticacaoController::class, 'eu']);
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'armazenar']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'mostrar']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'atualizar']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destruir']);
    Route::get('/propostas', [PropostaController::class, 'index']);
    Route::post('/propostas', [PropostaController::class, 'armazenar']);
    Route::put('/propostas/{id}', [PropostaController::class, 'atualizar']);
    Route::delete('/propostas/{id}', [PropostaController::class, 'destruir']);
    Route::post('/propostas/{id}/ativar', [PropostaController::class, 'ativar']);
    Route::post('/propostas/{id}/desativar', [PropostaController::class, 'desativar']);
    Route::get('/propostas/modelo-excel/download', [PropostaController::class, 'downloadModelo']);
    Route::post('/propostas/importar-excel', [PropostaController::class, 'importarExcel']);
    Route::get('/votos', [VotoController::class, 'index']);
    Route::get('/votos/exportar-excel', [VotoController::class, 'exportarExcel']);
    Route::get('/propostas/{id}/votos', [VotoController::class, 'obterPorProposta']);
    Route::put('/configuracoes', [ConfiguracaoController::class, 'atualizar']);
    Route::post('/configuracoes/banner', [ConfiguracaoController::class, 'uploadBanner']);
    Route::post('/configuracoes/temporizador/reiniciar', [ConfiguracaoController::class, 'reiniciarTemporizador']);
});
