<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * Os atributos que podem ser atribuídos em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'cpf',
        'senha',
        'is_admin',
    ];

    /**
     * Os atributos que devem ser ocultados na serialização
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
        'remember_token',
    ];

    /**
     * Obter os atributos que devem ser convertidos
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'senha' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Obter o nome do identificador único do usuário
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'cpf';
    }

    /**
     * Obter a senha para autenticação
     */
    public function getAuthPassword()
    {
        return $this->senha;
    }

    /**
     * Formata o CPF para exibição
     */
    public function getCpfFormatadoAttribute(): string
    {
        $cpf = $this->cpf;
        return substr($cpf, 0, 3) . '.' .
               substr($cpf, 3, 3) . '.' .
               substr($cpf, 6, 3) . '-' .
               substr($cpf, 9, 2);
    }
}
