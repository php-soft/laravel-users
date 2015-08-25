<?php

use PhpSoft\Illuminate\Users\Controllers\Controller;
use PhpSoft\Illuminate\Users\Models\Permission;
use PhpSoft\Illuminate\Users\Models\Role;

class ControllerTest extends TestCase 
{
    public function testPermissionUserNotAdmin()
    {
        // Create user is not admin
        $user = factory(App\User::class)->create();
        $login = Auth::login($user);

        // Create role
        $creator = new Role();
        $creator->name = 'creator';
        $creator->save();

        // Create permission
        $createPost = new Permission();
        $createPost->name         = 'create-post';
        $createPost->display_name = 'Create Posts';
        $createPost->description  = 'create new blog posts';
        $createPost->save();

        // Attach creator role for user
        $user->attachRole($creator);

        // Attach createPost for creator role
        $creator->attachPermission($createPost);

        $controller = new Controller();

        // Check user hasn't permission
        $hasPermission = $controller->checkPermission('edit-profile');
        $this->assertEquals(false, $hasPermission);

        // Check user has permission
        $hasPermission = $controller->checkPermission('create-post');
        $this->assertEquals(true, $hasPermission);
    }

    public function testPermissionUserIsAdmin()
    {
        // Check user is admin
        $user = factory(App\User::class)->create();
        $login = Auth::login($user);

        $admin = Role::find(1);

        // Attach admin role for user
        $user->attachRole($admin);

        $controller = new Controller();

        $isAdmin = $controller->checkPermission('manage-user');
        $this->assertEquals(true, $isAdmin);
    }
}