<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AutenticacaoController extends Controller
{
    /**
     * Realizar login do usuário
     */
    public function entrar(Request $request)
    {
        $request->validate([
            'cpf' => 'required|string|size:11',
            'senha' => 'required|string',
        ]);

        $usuario = Usuario::where('cpf', $request->cpf)->first();

        if (!$usuario || !Hash::check($request->senha, $usuario->senha)) {
            throw ValidationException::withMessages([
                'cpf' => ['CPF ou senha inválidos.'],
            ]);
        }

        // Revogar tokens anteriores
        $usuario->tokens()->delete();

        // Criar novo token
        $token = $usuario->createToken('token-autenticacao')->plainTextToken;

        return response()->json([
            'usuario' => $usuario,
            'token' => $token,
        ]);
    }

    /**
     * Realizar logout do usuário
     */
    public function sair(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensagem' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Retornar o usuário autenticado
     */
    public function eu(Request $request)
    {
        return response()->json($request->user());
    }
}
