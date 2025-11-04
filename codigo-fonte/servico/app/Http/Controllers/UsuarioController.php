<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Listar todos os usuários
     */
    public function index(Request $request)
    {
        $query = Usuario::query();

        // Filtro de pesquisa
        if ($request->has('busca') && !empty($request->busca)) {
            $busca = $request->busca;
            $query->where(function($q) use ($busca) {
                $q->where('nome', 'like', "%{$busca}%")
                  ->orWhere('cpf', 'like', "%{$busca}%");
            });
        }

        $query->orderBy('nome');

        $perPage = $request->get('per_page', 10);
        $usuarios = $query->paginate($perPage);
        return response()->json($usuarios);
    }

    /**
     * Criar um novo usuário
     */
    public function armazenar(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:usuarios',
            'senha' => 'required|string|min:6',
            'is_admin' => 'boolean',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.size' => 'O CPF deve ter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'senha.required' => 'A senha é obrigatória.',
            'senha.min' => 'A senha deve ter no mínimo :min caracteres.',
        ]);

        $usuario = Usuario::create([
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'senha' => Hash::make($request->senha),
            'is_admin' => $request->is_admin ?? false,
        ]);

        return response()->json($usuario, 201);
    }

    /**
     * Exibir um usuário específico
     */
    public function mostrar($id)
    {
        $usuario = Usuario::findOrFail($id);
        return response()->json($usuario);
    }

    /**
     * Atualizar um usuário
     */
    public function atualizar(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:usuarios,cpf,' . $id,
            'senha' => 'nullable|string|min:6',
            'is_admin' => 'boolean',
        ], [
            'nome.required' => 'O nome é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de :max caracteres.',
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.size' => 'O CPF deve ter 11 dígitos.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'senha.min' => 'A senha deve ter no mínimo :min caracteres.',
        ]);

        $dados = [
            'nome' => $request->nome,
            'cpf' => $request->cpf,
            'is_admin' => $request->is_admin ?? false,
        ];

        if ($request->filled('senha')) {
            $dados['senha'] = Hash::make($request->senha);
        }

        $usuario->update($dados);

        return response()->json($usuario);
    }

    /**
     * Remover um usuário
     */
    public function destruir($id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return response()->json([
            'mensagem' => 'Usuário removido com sucesso.',
        ]);
    }
}
