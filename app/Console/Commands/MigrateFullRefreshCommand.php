<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateFullRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:full-refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh migrations for main (MySQL) and audit (PostgreSQL) connections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Refreshing MySQL migrations (main)...');
        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/main',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('📝 Migrating PostgreSQL migrations (audit)...');
        Artisan::call('migrate', [
            '--path' => 'database/migrations/audit',
            '--database' => 'pgsql_auditoria',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('✅ Migrations completed successfully.');
        return Command::SUCCESS;
    }
}
