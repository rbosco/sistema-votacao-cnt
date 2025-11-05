<?php

namespace App\Http\Controllers;

use App\Models\Bancada;
use Illuminate\Http\JsonResponse;

class BancadaController extends Controller
{
    /**
     * Lista todas as bancadas disponíveis
     */
    public function index(): JsonResponse
    {
        $bancadas = Bancada::orderBy('nome')->get();

        return response()->json([
            'data' => $bancadas
        ]);
    }
}
