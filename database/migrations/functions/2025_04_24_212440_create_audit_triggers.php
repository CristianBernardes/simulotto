<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Função log_audit_event
     *
     * Esta função é chamada por triggers nas tabelas do banco e tem como objetivo
     * registrar eventos de auditoria para monitoramento de alterações em dados.
     *
     * Para cada evento (INSERT, UPDATE ou DELETE) nas tabelas associadas, a função:
     * - Formata os dados do registro afetado em JSON.
     * - Identifica o tipo de evento ('insert', 'update', 'delete').
     * - Gera um hash de integridade (SHA256) para validação futura.
     * - Insere os registros de auditoria em um banco externo dedicado à auditoria.
     *
     * @return TRIGGER Retorna `NULL` pois é uma função de trigger AFTER que não modifica as tuplas.
     *
     * Variáveis:
     * - `payload_json` (JSON): Dados da linha afetada serializados em JSON.
     * - `event_type` (string): O tipo de evento ('insert', 'update', 'delete').
     * - `table_name` (string): O nome da tabela onde a operação ocorreu.
     * - `record_id` (UUID): O identificador único da linha impactada.
     * - `integrity` (string): Hash SHA256 gerado a partir do payload JSON.
     *
     * Etapas executadas:
     * 1. Determinar o tipo do evento acionado pela trigger:
     *    - INSERT: Registro completo da nova linha.
     *    - UPDATE: Antes e depois das alterações.
     *    - DELETE: Registro deletado.
     * 2. Serializar os dados impactados em JSON.
     * 3. Gerar um hash criptográfico para garantir a integridade dos dados.
     * 4. Conectar ao banco de auditoria externo usando a extensão `dblink`.
     * 5. Inserir o evento de auditoria na tabela `audit_logs` do banco de auditoria.
     * 6. Encerrar a conexão após a operação ser concluída.
     *
     * Campos registrados na tabela `audit_logs`:
     * - `id` (UUID): Identificador único do evento de auditoria.
     * - `event` (string): Tipo de evento (insert, update, delete).
     * - `table_name` (string): Nome da tabela modificada.
     * - `record_id` (UUID): Identificador único do registro impactado.
     * - `payload` (JSON): Dados do registro impactado.
     * - `hash_integrity` (string): Hash de integridade gerado para o payload.
     * - `created_at` (timestamp): Data e hora do evento.
     *
     * Exemplo de utilização:
     * Esta função é utilizada por triggers criadas em tabelas como:
     * - `bets`: Trigger trg_bets_audit.
     * - `draws`: Trigger trg_draws_audit.
     * - `game_types`: Trigger trg_game_types_audit.
     */
    public function up(): void
    {
        DB::statement("CREATE EXTENSION IF NOT EXISTS dblink");
        DB::statement("CREATE EXTENSION IF NOT EXISTS pgcrypto");

        // Função que será chamada pelas triggers
        DB::statement("
            CREATE OR REPLACE FUNCTION log_audit_event()
            RETURNS TRIGGER AS \$\$
            DECLARE
                payload_json JSON;
                event_type TEXT;
                table_name TEXT := TG_TABLE_NAME;
                record_id UUID;
                integrity TEXT;
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    event_type := 'insert';
                    payload_json := row_to_json(NEW);
                    record_id := NEW.id;
                ELSIF (TG_OP = 'UPDATE') THEN
                    event_type := 'update';
                    payload_json := json_build_object('before', row_to_json(OLD), 'after', row_to_json(NEW));
                    record_id := NEW.id;
                ELSIF (TG_OP = 'DELETE') THEN
                    event_type := 'delete';
                    payload_json := row_to_json(OLD);
                    record_id := OLD.id;
                END IF;

                integrity := encode(digest(payload_json::text, 'sha256'), 'hex');

                PERFORM dblink_connect('audit', 'host=simulotto-postgres-auditoria user=simulotto password=simulotto dbname=simulotto');
                PERFORM dblink_exec(
                    'audit',
                    format(
                        'INSERT INTO audit_logs (id, event, table_name, record_id, payload, hash_integrity, created_at)
                         VALUES (%L, %L, %L, %L, %L, %L, NOW())',
                        gen_random_uuid(), event_type, table_name, record_id, payload_json::text, integrity
                    )
                );
                PERFORM dblink_disconnect('audit');

                RETURN NULL;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Trigger para a tabela bets (adicione para outras conforme necessário)
        DB::statement("
            CREATE TRIGGER trg_bets_audit
            AFTER INSERT OR UPDATE OR DELETE ON bets
            FOR EACH ROW EXECUTE FUNCTION log_audit_event()
        ");

        DB::statement("
            CREATE TRIGGER trg_draws_audit
            AFTER INSERT OR UPDATE OR DELETE ON draws
            FOR EACH ROW EXECUTE FUNCTION log_audit_event()
        ");

        DB::statement("
            CREATE TRIGGER trg_game_types_audit
            AFTER INSERT OR UPDATE OR DELETE ON game_types
            FOR EACH ROW EXECUTE FUNCTION log_audit_event()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS trg_bets_audit ON bets");
        DB::statement("DROP TRIGGER IF EXISTS trg_draws_audit ON draws");
        DB::statement("DROP TRIGGER IF EXISTS trg_game_types_audit ON game_types");
        DB::statement("DROP FUNCTION IF EXISTS log_audit_event");
    }
};
