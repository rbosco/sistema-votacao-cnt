<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    /**
     * Executar os seeds do banco de dados
     */
    public function run(): void
    {
        // Usuário administrador padrão
        Usuario::create([
            'nome' => 'Administrador',
            'cpf' => '12345678901',
            'senha' => Hash::make('admin123'),
            'is_admin' => true,
        ]);

        // Usuários de teste
        Usuario::create([
            'nome' => 'João Silva',
            'cpf' => '98765432100',
            'senha' => Hash::make('senha123'),
            'is_admin' => false,
        ]);

        Usuario::create([
            'nome' => 'Maria Santos',
            'cpf' => '11122233344',
            'senha' => Hash::make('senha123'),
            'is_admin' => false,
        ]);
    }
}
