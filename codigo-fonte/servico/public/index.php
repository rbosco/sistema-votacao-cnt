<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determinar se a aplicação está em modo de manutenção
if (file_exists($manutencao = __DIR__.'/../storage/framework/maintenance.php')) {
    require $manutencao;
}

// Registrar o autoloader do Composer
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel e processar a requisição
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
