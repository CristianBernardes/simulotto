<?php

namespace App\Models\Traits;

use Illuminate\Support\Str;

trait Uuid
{
    /**
     * Substitua a função de inicialização do Laravel para que
     * damos ao modelo um novo UUID quando o criamos.
     */
    protected static function boot()
    {
        /**
         * Método de Inicialização da Classe que a usar
         */
        parent::boot();

        $creationCallback = function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            }
        };

        static::creating($creationCallback);
    }


    /**
     * Substitua a função getIncrementing() para retornar false para dizer
     * Laravel que o identificador não incrementa automaticamente (é uma string).
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }


    /**
     * Diga ao laravel que o tipo de chave é uma string, não um inteiro.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
