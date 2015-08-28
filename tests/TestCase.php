<?php

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        @unlink(base_path('storage/database.sqlite'));
        @copy(base_path('storage/database.sqlite.blank'), base_path('storage/database.sqlite'));
        Artisan::call('migrate');
        Artisan::call('db:seed', [ '--class' => 'UserModuleSeeder' ]);
    }
}
