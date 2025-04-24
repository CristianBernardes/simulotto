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
    protected $description = 'Refresh migrations for main (postgres_principal) and audit (postgres_auditoria) connections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Refreshing postgres_principal migrations (main)...');
        Artisan::call('migrate:fresh', [
            '--path' => 'database/migrations/main',
            '--seed' => true,
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('📝 Migrating postgres_auditoria migrations (audit)...');
        Artisan::call('migrate', [
            '--path' => 'database/migrations/audit',
            '--database' => 'pgsql_auditoria',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('🔄 Refreshing Function postgres_principal migrations (main)...');
        Artisan::call('migrate', [
            '--path' => 'database/migrations/functions',
            '--force' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('✅ Migrations completed successfully.');
        return Command::SUCCESS;
    }
}
