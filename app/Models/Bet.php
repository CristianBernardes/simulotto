<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Bet
 *
 * Representa o modelo de uma aposta.
 *
 * @property string $id                  O identificador único da aposta.
 * @property string $user_id             O identificador do usuário que realizou a aposta.
 * @property string $game_type_id        O identificador do tipo de jogo ao qual a aposta pertence.
 * @property array $numbers              Os números selecionados na aposta.
 *
 *
 * @property-read User $user Relacionamento com o modelo de usuário.
 * @property-read GameType $gameType Relacionamento com o modelo de tipo de jogo.
 */
class Bet extends Model
{
    use HasFactory, Uuid;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'user_id',
        'game_type_id',
        'numbers',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'numbers' => 'array',
    ];

    /**
     * Relacionamento com o modelo User.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
