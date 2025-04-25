<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The connection name to use for this migration.
     *
     * @var string
     */
    protected $connection = 'pgsql_auditoria';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::connection($this->connection)->statement("
           CREATE OR REPLACE FUNCTION prevent_audit_modifications()
            RETURNS trigger AS \$\$
            BEGIN
                RAISE EXCEPTION 'Modificação não permitida na tabela audit_logs!';
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::connection($this->connection)->statement("
            CREATE TRIGGER trg_prevent_audit_delete
            BEFORE DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION prevent_audit_modifications();
        ");

        DB::connection($this->connection)->statement("
            CREATE TRIGGER trg_prevent_audit_update
            BEFORE UPDATE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION prevent_audit_modifications();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)->statement("DROP TRIGGER IF EXISTS trg_prevent_audit_delete ON audit_logs");
        DB::connection($this->connection)->statement("DROP FUNCTION IF EXISTS prevent_audit_delete");
    }
};
