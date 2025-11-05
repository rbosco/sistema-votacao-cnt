<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bancada extends Model
{
    use HasFactory;

    protected $table = 'bancadas';

    /**
     * Os atributos que podem ser atribuídos em massa
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'sigla',
    ];

    /**
     * Obter os votos desta bancada
     */
    public function votos(): HasMany
    {
        return $this->hasMany(Voto::class, 'bancada_id');
    }
}
