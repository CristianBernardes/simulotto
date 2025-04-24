<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * Representa o modelo de um usuário do sistema.
 *
 * @property string $id                  O identificador único do usuário.
 * @property string $name                O nome do usuário.
 * @property string $email               O endereço de e-mail do usuário.
 * @property string $password            A senha do usuário (armazenada de forma segura).
 * @property bool $is_admin              Indica se o usuário tem privilégios de administrador.
 * @property Carbon|null $email_verified_at A data e hora da verificação do e-mail do usuário.
 *
 * @property string|null $remember_token Token utilizado para a funcionalidade "lembrar de mim" na autenticação.
 *
 * @property-read Collection|Bet[] $bets Relacionamento com as apostas do usuário.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, Uuid;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Define os tipos de dados de atributos na classe.
     *
     * @return array
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Relacionamento com o modelo Bet.
     *
     * @return HasMany
     */
    public function bets()
    {
        return $this->hasMany(Bet::class);
    }
}
