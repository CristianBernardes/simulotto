<?php

namespace App\Models;

use App\Models\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AuditLog
 *
 * Representa registros de auditoria no banco de dados.
 *
 *
 * @property string $id O identificador único do registro (UUID).
 * @property string $event O evento auditado (ex: 'created', 'updated', 'deleted').
 * @property string $table_name O nome da tabela na qual a auditoria foi registrada.
 * @property string $record_id O identificador do registro afetado na tabela auditada.
 * @property array|null $payload Dados ou informações adicionais relacionados ao evento.
 * @property string|null $hash_integrity Um hash para verificação da integridade dos dados.
 * @property string $created_at A data e hora em que a auditoria foi criada.
 */
class AuditLog extends Model
{
    use HasFactory, Uuid;

    /**
     * A conexão do banco de dados que será utilizada para o modelo.
     *
     * @var string
     */
    protected $connection = 'pgsql_auditoria';

    /**
     * Indica se o modelo deve utilizar timestamps automáticos.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'event',
        'table_name',
        'record_id',
        'payload',
        'hash_integrity',
        'created_at',
    ];
}
