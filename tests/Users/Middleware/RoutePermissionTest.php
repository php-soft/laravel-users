<?php

use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\Permission;
use PhpSoft\Users\Models\RoutePermission;

class RoutePermissionTest extends TestCase
{
    public function testRouteNotRequirePermission()
    {
        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testRouteRequirePermissionGuestAccess()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);

        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testRouteRequirePermissionUserHaveNotPermission()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);

        $user = factory(App\User::class)->make();
        Auth::login($user);

        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testRouteRequirePermissionUserHavePermission()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);

        // create role creator
        $creator = new Role();
        $creator->name = 'creator';
        $creator->save();

        // create permission
        $createPost = new Permission();
        $createPost->name = 'create-blog';
        $createPost->save();

        $creator->attachPermission($createPost);

        $user = factory(App\User::class)->create();
        $user->attachRole($creator);
        Auth::login($user);

        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testUserHaveNotPermissionButIsAdmin()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);
        RoutePermission::setRouteRoles('POST /blog/{id}', ['creator', 'admin']);

        $user = App\User::where('email', 'admin@example.com')->first();
        Auth::login($user);

        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(200, $res->getStatusCode());
    }
}
