<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'number',
        'name',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the votes for the proposal.
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * Get the total votes count
     */
    public function getTotalVotesAttribute(): int
    {
        return $this->votes()->count();
    }

    /**
     * Get the yes votes count
     */
    public function getYesVotesAttribute(): int
    {
        return $this->votes()->where('vote', true)->count();
    }

    /**
     * Get the no votes count
     */
    public function getNoVotesAttribute(): int
    {
        return $this->votes()->where('vote', false)->count();
    }

    /**
     * Get encrypted ID for URL
     */
    public function getEncryptedIdAttribute(): string
    {
        return encrypt($this->id);
    }

    /**
     * Scope a query to only include active proposals.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
