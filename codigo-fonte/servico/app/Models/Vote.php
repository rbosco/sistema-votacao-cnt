<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'proposal_id',
        'voter_cpf',
        'voter_name',
        'union_name',
        'vote',
        'voted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vote' => 'boolean',
            'voted_at' => 'datetime',
        ];
    }

    /**
     * Get the proposal that owns the vote.
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * Get the vote label (Sim/Não)
     */
    public function getVoteLabelAttribute(): string
    {
        return $this->vote ? 'Sim' : 'Não';
    }

    /**
     * Formata o CPF para exibição
     */
    public function getFormattedCpfAttribute(): string
    {
        $cpf = $this->voter_cpf;
        return substr($cpf, 0, 3) . '.' .
               substr($cpf, 3, 3) . '.' .
               substr($cpf, 6, 3) . '-' .
               substr($cpf, 9, 2);
    }
}
