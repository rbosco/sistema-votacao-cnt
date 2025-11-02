<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'propostas';

    /**
     * Os atributos que podem ser atribuídos em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'numero',
        'nome',
        'esta_ativa',
    ];

    /**
     * Obter os atributos que devem ser convertidos
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'esta_ativa' => 'boolean',
        ];
    }

    /**
     * Obter os votos da proposta
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'proposta_id');
    }

    /**
     * Obter o total de votos
     */
    public function getTotalVotosAttribute(): int
    {
        return $this->votos()->count();
    }

    /**
     * Obter os votos "Sim"
     */
    public function getVotosSimAttribute(): int
    {
        return $this->votos()->where('voto', true)->count();
    }

    /**
     * Obter os votos "Não"
     */
    public function getVotosNaoAttribute(): int
    {
        return $this->votos()->where('voto', false)->count();
    }

    /**
     * Obter ID criptografado para URL
     */
    public function getIdCriptografadoAttribute(): string
    {
        return encrypt($this->id);
    }

    /**
     * Escopo para buscar apenas propostas ativas
     */
    public function scopeAtiva($query)
    {
        return $query->where('esta_ativa', true);
    }
}
