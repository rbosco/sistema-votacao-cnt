<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuário administrador padrão
        User::create([
            'name' => 'Administrador',
            'cpf' => '12345678901',
            'password' => Hash::make('admin123'),
            'is_admin' => true,
        ]);

        // Usuários de teste
        User::create([
            'name' => 'João Silva',
            'cpf' => '98765432100',
            'password' => Hash::make('senha123'),
            'is_admin' => false,
        ]);

        User::create([
            'name' => 'Maria Santos',
            'cpf' => '11122233344',
            'password' => Hash::make('senha123'),
            'is_admin' => false,
        ]);
    }
}
