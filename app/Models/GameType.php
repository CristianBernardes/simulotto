<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class GameType
 *
 * Representa o modelo de um tipo de jogo.
 *
 * @property string $id                  O identificador único do tipo de jogo.
 * @property string $name                O nome do tipo de jogo.
 * @property int $max_numbers            O número máximo de números que podem ser escolhidos no jogo.
 * @property int $number_range           O intervalo de números que podem ser selecionados no jogo.
 * @property float $price                O preço para participar no jogo.
 * @property bool $is_active             Indica se o tipo de jogo está ativo.
 *
 * @property-read Collection|Bet[] $bets Relacionamento com as apostas do tipo de jogo.
 * @property-read Collection|Draw[] $draws Relacionamento com os sorteios do tipo de jogo.
 */
class GameType extends Model
{
    use HasFactory, Uuid;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'max_numbers',
        'number_range',
        'price',
        'is_active',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Relacionamento com o modelo Bet.
     *
     * @return HasMany
     */
    public function bets()
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Relacionamento com o modelo Draw.
     *
     * @return HasMany
     */
    public function draws()
    {
        return $this->hasMany(Draw::class);
    }
}
