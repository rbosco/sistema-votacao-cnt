<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voto extends Model
{
    use HasFactory;

    protected $table = 'votos';

    /**
     * Os atributos que podem ser atribuídos em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proposta_id',
        'cpf_votante',
        'bancada_id',
        'voto',
        'votado_em',
    ];

    /**
     * Obter os atributos que devem ser convertidos
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'voto' => 'boolean',
            'votado_em' => 'datetime',
        ];
    }

    /**
     * Obter a proposta do voto
     */
    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class, 'proposta_id');
    }

    /**
     * Obter a bancada do voto
     */
    public function bancada(): BelongsTo
    {
        return $this->belongsTo(Bancada::class, 'bancada_id');
    }

    /**
     * Obter o rótulo do voto (Sim/Não)
     */
    public function getRotuloVotoAttribute(): string
    {
        return $this->voto ? 'Sim' : 'Não';
    }

    /**
     * Formata o CPF para exibição
     */
    public function getCpfFormatadoAttribute(): string
    {
        $cpf = $this->cpf_votante;
        return substr($cpf, 0, 3) . '.' .
               substr($cpf, 3, 3) . '.' .
               substr($cpf, 6, 3) . '-' .
               substr($cpf, 9, 2);
    }
}
