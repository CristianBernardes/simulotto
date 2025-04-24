<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
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
