<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Round extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tournament_id',
        'name',
        'round_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'round_number' => 'integer',
    ];

    // --- Relationships ---

    /**
     * Get the tournament that this round belongs to.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Get the matches scheduled within this specific round.
     */
    public function matches(): HasMany
    {
        // Points to your custom MatchInfo model and uses the round_id foreign key
        return $this->hasMany(MatchInfo::class, 'round_id')->orderBy('sort_order');
    }
}
