<?php

namespace PhpSoft\Illuminate\Users\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MigrationCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'users:migrate';

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
        $this->call('entrust:migration');
        $this->info("Run 'php artisan migrate' to finish migration.");
    }
}
