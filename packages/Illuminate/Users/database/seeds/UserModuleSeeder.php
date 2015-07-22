<?php

use Illuminate\Database\Seeder;

class UserModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\User::class)->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('123456'),
        ]);
    }
}
