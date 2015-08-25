<?php
namespace PhpSoft\Illuminate\Users\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * @codeCoverageIgnore
 */
class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ps-users:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Users module migration data.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->createMigration();
        $this->info("Run 'php artisan migrate' to finish migration.");
    }

    /**
     * Create the migration.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function createMigration()
    {
        $files = scandir(__DIR__ . '/migrations');
        foreach ($files as $file) {
            if ($file == '.' || $file == '..' || file_exists(base_path('/database/migrations') . '/' . $file)) {
                continue;
            }
            if (copy(__DIR__ . '/migrations/' . $file, base_path('/database/migrations') . '/' . $file)) {
                $this->line("<info>Created Migration:</info> $file");
            }
        }
    }
}
