<?php

use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\Permission;

class PermissionTest extends TestCase
{
    public function testGuestAccess()
    {
        $res = $this->call('POST', '/posts');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testUserHaveNotPermission()
    {
        $user = factory(App\User::class)->make();
        Auth::login($user);

        $res = $this->call('POST', '/posts');
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testUserHavePermission()
    {
        // create role creator
        $creator = new Role();
        $creator->name = 'creator';
        $creator->save();

        // create permission
        $createPost = new Permission();
        $createPost->name = 'create-post';
        $createPost->save();

        $creator->attachPermission($createPost);

        $user = factory(App\User::class)->create();
        $user->attachRole($creator);
        Auth::login($user);

        $res = $this->call('POST', '/posts');
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testUserHaveNotPermissionButIsAdmin()
    {
        $user = App\User::where('email', 'admin@example.com')->first();
        Auth::login($user);

        $res = $this->call('POST', '/posts');
        $this->assertEquals(200, $res->getStatusCode());
    }
}
