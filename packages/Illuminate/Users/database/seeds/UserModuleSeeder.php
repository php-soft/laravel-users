<?php

use Illuminate\Database\Seeder;
use PhpSoft\Illuminate\Users\Models\Role;

class UserModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create admin user
        $root = factory(App\User::class)->create([
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => bcrypt('123456'),
        ]);

        // create default roles
        $admin = new Role;
        $admin->name         = 'admin';
        $admin->display_name = 'Administrator';
        $admin->description  = 'User is allowed to manage all system.';
        $admin->save();

        // attach roles
        $root->attachRole($admin);
    }
}
