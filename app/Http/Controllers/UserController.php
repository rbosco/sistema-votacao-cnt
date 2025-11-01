<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Lista todos os usuários
     */
    public function index()
    {
        $users = User::orderBy('name')->get();
        return response()->json($users);
    }

    /**
     * Cria um novo usuário
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:users',
            'password' => 'required|string|min:6',
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'cpf' => $request->cpf,
            'password' => Hash::make($request->password),
            'is_admin' => $request->is_admin ?? false,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Exibe um usuário específico
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Atualiza um usuário
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'required|string|size:11|unique:users,cpf,' . $id,
            'password' => 'nullable|string|min:6',
            'is_admin' => 'boolean',
        ]);

        $data = [
            'name' => $request->name,
            'cpf' => $request->cpf,
            'is_admin' => $request->is_admin ?? false,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json($user);
    }

    /**
     * Remove um usuário
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'message' => 'Usuário removido com sucesso.',
        ]);
    }
}
