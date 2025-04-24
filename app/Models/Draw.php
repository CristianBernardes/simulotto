<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Draw
 *
 * Representa o modelo de um sorteio.
 *
 * @property string $id                  O identificador único do sorteio.
 * @property string $game_type_id        O identificador do tipo de jogo associado ao sorteio.
 * @property array $numbers              Os números sorteados.
 * @property Carbon|null $drawn_at A data e hora do sorteio.
 *
 *
 * @property-read GameType $gameType Relacionamento com o modelo de tipo de jogo.
 */
class Draw extends Model
{
    use HasFactory, Uuid;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'game_type_id',
        'numbers',
        'drawn_at',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'numbers' => 'array',
        'drawn_at' => 'datetime',
    ];

    /**
     * Relacionamento com o modelo GameType.
     *
     * @return BelongsTo
     */
    public function gameType()
    {
        return $this->belongsTo(GameType::class);
    }
}
