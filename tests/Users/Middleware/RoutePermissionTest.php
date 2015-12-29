<?php

use PhpSoft\Users\Models\Role;
use PhpSoft\Users\Models\Permission;
use PhpSoft\Users\Models\RoutePermission;
use Tymon\JWTAuth\Facades\JWTAuth;

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

        $request = Mockery::mock();
        $request->shouldReceive('getToken')->once()->andReturn('mocktoken');
        JWTAuth::shouldReceive('setRequest')->once()->andReturn($request);
        JWTAuth::shouldReceive('authenticate')->once()->andThrow(new Tymon\JWTAuth\Exceptions\JWTException('Not authenticate.', 401));
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer fake_token"]);
        $this->assertEquals(401, $res->getStatusCode());

        $request = Mockery::mock();
        $request->shouldReceive('getToken')->once()->andReturn('mocktoken');
        JWTAuth::shouldReceive('setRequest')->once()->andReturn($request);
        JWTAuth::shouldReceive('authenticate')->once()->andReturn(null);
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer fake_token"]);
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testRouteRequirePermissionUserHaveNotPermission()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);

        $user = factory(App\User::class)->create(['password'=>bcrypt('123456')]);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
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

        $user = factory(App\User::class)->create(['password'=>bcrypt('123456')]);
        $user->attachRole($creator);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testUserHaveNotPermissionButIsAdmin()
    {
        RoutePermission::setRoutePermissions('POST /blog/{id}', ['create-blog']);
        RoutePermission::setRouteRoles('POST /blog/{id}', ['creator', 'admin']);

        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testUserPermission()
    {
        RoutePermission::setRouteRoles('POST /blog/{id}', ['@']);

        // not login
        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(401, $res->getStatusCode());

        // has login
        $user = factory(App\User::class)->create(['password'=>bcrypt('123456')]);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testSetRoutePermissionAllRouter()
    {
        RoutePermission::setRouteRoles('*', ['@']);

        // not login
        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(401, $res->getStatusCode());

        // has login
        $user = factory(App\User::class)->create(['password'=>bcrypt('123456')]);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
    }

    public function testSetRoutePermissionAllRouterAndCurrentRoute()
    {
        RoutePermission::setRouteRoles('*', ['@']);
        RoutePermission::setRouteRoles('POST /blog/{id}', ['admin']);

        // not login
        $res = $this->call('POST', '/blog/1');
        $this->assertEquals(401, $res->getStatusCode());

        // has login, not admin
        $user = factory(App\User::class)->create(['password'=>bcrypt('123456')]);
        $credentials = [ 'email' => $user->email, 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(403, $res->getStatusCode());
    }

    public function testSetRoutePermissionAllRouterAndCurrentRouteAdminAccess()
    {
        RoutePermission::setRouteRoles('*', ['@']);
        RoutePermission::setRouteRoles('POST /blog/{id}', ['admin']);

        // has login, is admin
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('POST', '/blog/1', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
    }
}
