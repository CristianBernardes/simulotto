<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        $this->down();

        Schema::connection($this->connection)->create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('event'); // ex: created, updated, deleted
            $table->string('table_name');
            $table->uuid('record_id')->nullable();
            $table->json('payload')->nullable(); // dados antes/depois
            $table->string('hash_integrity')->nullable(); // hash SHA256 por ex
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('audit_logs');
    }
};
