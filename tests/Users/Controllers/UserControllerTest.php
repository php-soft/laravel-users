<?php

use App\User as AppUser;
use PhpSoft\Users\Models\User;
use PhpSoft\Users\Models\Role;

class UserControllerTest extends TestCase
{
    public function testGetAuthenticatedUserNotSendToken()
    {
        $res = $this->call('GET', '/me');
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Token is not provided.', $results->message);
    }

    public function testCheckAuthGetMe()
    {
        $this->withoutMiddleware();
        $res = $this->call('GET', '/me');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testGetAuthenticatedUser()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $res = $this->call('GET', '/me', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Administrator', $results->entities[0]->name);
    }

    public function testRole()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        $user = Auth::user();
        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->can('edit-user'));
    }

    public function testRegisterValidateFailure()
    {
        $res = $this->call('POST', '/users');
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name field is required.', $results->errors->name[0]);
        $this->assertObjectHasAttribute('email', $results->errors);
        $this->assertEquals('The email field is required.', $results->errors->email[0]);
        $this->assertObjectHasAttribute('password', $results->errors);
        $this->assertEquals('The password field is required.', $results->errors->password[0]);

        $res = $this->call('POST', '/users', [
            'name'      => 'Fish Bone',
            'email'     => 'Invalid email',
            'password'  => '123',
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('email', $results->errors);
        $this->assertEquals('The email must be a valid email address.', $results->errors->email[0]);
        $this->assertObjectHasAttribute('password', $results->errors);
        $this->assertEquals('The password confirmation does not match.', $results->errors->password[0]);
        $this->assertEquals('The password must be at least 6 characters.', $results->errors->password[1]);

        $res = $this->call('POST', '/users', [
            'name'      => 'Fish Bone',
            'email'     => 'admin@example.com',
            'password'  => '123456',
            'password_confirmation'  => '123456',
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('email', $results->errors);
        $this->assertEquals('The email has already been taken.', $results->errors->email[0]);
    }

    public function testRegisterSuccess()
    {
        $res = $this->call('POST', '/users', [
            'name'                  => 'Fish Bone',
            'email'                 => 'fish@example.com',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);
        $this->assertEquals(201, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('Fish Bone', $results->entities[0]->name);

        $userId = $results->entities[0]->id;
        $user = \App\User::find($userId);
        $this->assertEquals('Fish Bone', $user->name);
        $this->assertEquals('fish@example.com', $user->email);
    }

    public function testCheckAuthUpdateProfile()
    {
        $this->withoutMiddleware();
        $res = $this->call('PATCH', '/me');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testUpdateProfileFailure()
    {
        // test auth
        $res = $this->call('PATCH', '/me', [
           'name' => 'Steven',
        ]);

        $results = json_decode($res->getContent());
        $this->assertEquals(400, $res->getStatusCode());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);

        // test invalid validate input
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $name = str_repeat("abc", 100);
        $res = $this->call('PATCH', '/me', [
           'name'     => $name,
           'gender'   => 'male',
           'birthday' => '1987',
           'email'    => 'email',
           'password' => 'password'
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);

        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('validation', $results->type);
        $this->assertEquals('error', $results->status);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name may not be greater than 255 characters.', $results->message);
        $this->assertEquals('The gender must be an integer.', $results->errors->gender[0]);
        $this->assertEquals('The birthday is not a valid date.', $results->errors->birthday[0]);
        $this->assertEquals('The email must be a valid email address.', $results->errors->email[0]);
        $this->assertEquals('The old password and password are required or the old password is incorrect.', $results->errors->password[0]);

        $user = factory(AppUser::class)->create(['email' => 'email@gmail.com']);

        $res = $this->call('PATCH', '/me', [
           'name'         => 'Admin',
           'gender'       => 1,
           'birthday'     => '2015-01-01',
           'email'        => 'email@gmail.com',
           'old_password' => '123456789',
           'password'     => 'password'
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('validation', $results->type);
        $this->assertEquals('error', $results->status);
        $this->assertEquals('The email has already been taken.', $results->errors->email[0]);
        $this->assertEquals('The old password and password are required or the old password is incorrect.', $results->errors->old_password[0]);

        // test update user by id
        $res = $this->call('PATCH', '/users/12');
        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testUpdateProfileSuccess()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('PATCH', '/me', [
            'name'                  => 'Steven Adam',
            'country'               => 'USA',
            'location'              => '',
            'gender'                => 1,
            'birthday'              => '1987-09-05',
            'email'                 => 'email@gmail.com',
            'old_password'          => '123456',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);

        $results = json_decode($res->getContent());
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('admin@example.com', Auth::user()->email);
        $this->assertEquals('Steven Adam', $results->entities[0]->name);
        $userId = $results->entities[0]->id;
        $user = \App\User::find($userId);
        $this->assertEquals('Steven Adam', $user->name);
        $this->assertEquals('USA', $user->country);
        $this->assertEquals('email@gmail.com', $user->email);
        $this->assertEquals('', $user->location);
        $checkPassword = Auth::attempt(['id' => 1, 'password' => 'password']);
        $this->assertTrue($checkPassword);
        $this->assertEquals('greenglobal.vn', $user->website);

        // test update user by id
        $res = $this->call('PATCH', '/users/1', [
            'name'    => 'timcook',
            'country' => 'UK',
        ]);

        $this->assertEquals(200, $res->getStatusCode());
        $user = \App\User::find(1);
        $this->assertEquals('timcook', $user->name);
        $this->assertEquals('UK', $user->country);
    }

    public function testDestroyUser()
    {
        // test delete user failure
        // test invalid user
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('DELETE', '/users/3', [],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(404, $res->getStatusCode());

        // test delete user success
        $res = $this->call('DELETE', '/users/1', [],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(204, $res->getStatusCode());
        $user = \App\User::find(1);
        $this->assertNull($user);
    }

    public function testViewUser()
    {
        // test view user failure
        $res = $this->call('GET', '/users/2');
        $this->assertEquals(404, $res->getStatusCode());

        // test view user succsess
        $res = $this->call('GET', '/users/1');
        $results = json_decode($res->getContent());
        $this->assertEquals(200, $res->getStatusCode());
        $user = \App\User::find(1);
        $this->assertEquals($user->name, $results->entities[0]->name);
        $this->assertEquals($user->username, $results->entities[0]->username);
        $this->assertEquals($user->id, $results->entities[0]->id);
        $this->assertEquals($user->website, $results->entities[0]->website);
        $this->assertEquals($user->birthday, $results->entities[0]->birthday);
        $this->assertEquals($user->gender, $results->entities[0]->gender);
    }

    public function testBrowseNotFound()
    {
        $removeAllUsers = App\User::truncate();
        $res = $this->call('GET', '/users');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));
    }

    public function testBrowseFilters()
    {
        // check list users with filters

        // check with right params request
        $res = $this->call('GET', '/users?name=Administrator&email=admin@example.com');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));

        // check with wrong params request
        $res = $this->call('GET', '/users?name=user&email=admin@example.com');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));

        // check fiter with number
        $user = factory(App\User::class)->create(['gender' => 1]);
        $user = factory(App\User::class)->create(['gender' => 11]);
        $res = $this->call('GET', '/users?gender=1');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));

        $res = $this->call('GET', '/users?gender=%1%');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(2, count($results->entities));

        // check with params not in filters
        $removeAllUsers = App\User::truncate();

        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create();
        }

        $res = $this->call('GET', '/users?password=password');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(0, count($results->entities));

        // check with value of param is null
        $res = $this->call('GET', '/users?gender=');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(count($users), count($results->entities));
    }

    public function testBrowseFound()
    {
        $users = [];
        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create();
        }

        $res = $this->call('GET', '/users');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(count($users)+1, count($results->entities));
        $this->assertObjectHasAttribute('isBlock', $results->entities[0]);
        for ($i = 0; $i < 10; ++$i) {
            $this->assertEquals($users[9 - $i]->id, $results->entities[$i]->id);
            $this->assertFalse($results->entities[$i]->isBlock);
        }
    }

    public function testBrowseWithOrderWrongParams()
    {
        $users = [];
        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create();
        }

        $arrayId = [];
        for ($i = count($users)-1; $i >= 0; --$i) {
            $arrayId[] = $users[$i]->id;
        }

        //check order users with emty params
        $res = $this->call('GET', '/users');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayId[$i], $results->entities[$i]->id);
        }

        // check order users with wrong params
        $res = $this->call('GET', '/users?sort=title&direction=aa');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayId[$i], $results->entities[$i]->id);
        }

        // check order users with the input doesn't has sort
        $res = $this->call('GET', '/users?direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayId[$i], $results->entities[$i]->id);
        }
    }

    public function testBrowseWithOrderRightParams()
    {
        $users = [];
        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create(['name'=>'Name ' . $i]);
        }

        $arrayNameDesc = [];
        for ($i = count($users)-1; $i >= 0; --$i) {
            $arrayNameDesc[] = $users[$i]->name;
        }

        $arrayNameAsc = [];
        for ($i = 0; $i < count($users); ++$i) {
            $arrayNameAsc[] = $users[$i]->name;
        }

        // check order users with full input
        $res = $this->call('GET', '/users?sort=name&direction=desc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayNameDesc[$i], $results->entities[$i]->name);
        }

        $res = $this->call('GET', '/users?sort=name&direction=asc');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayNameAsc[$i], $results->entities[$i+1]->name);
        }

        // check order users with only sort
        $res = $this->call('GET', '/users?sort=name');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        for ($i = 0; $i < count($users); ++$i) {
            $this->assertEquals($arrayNameDesc[$i], $results->entities[$i]->name);
        }
    }

    public function testBrowseWithScroll()
    {
        $users = [];
        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create();
        }

        // 5 items first
        $res = $this->call('GET', '/users?limit=5');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($users[9 - $i]->id, $results->entities[$i]->id);
        }

        // 5 items next
        $nextLink = $results->links->next->href;
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($users[4 - $i]->id, $results->entities[$i]->id);
        }

        // over list
        $nextLink = $results->links->next->href;
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));
    }

    public function testBrowseWithPagination()
    {
        $users = [];
        for ($i = 0; $i < 10; ++$i) {
            $users[] = factory(App\User::class)->create();
        }

        // 5 items first
        $res = $this->call('GET', '/users?limit=5');
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($users[9 - $i]->id, $results->entities[$i]->id);
        }

        // 5 items next
        $nextLink = '/users?limit=5&page=2';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(5, count($results->entities));
        for ($i = 0; $i < 5; ++$i) {
            $this->assertEquals($users[4 - $i]->id, $results->entities[$i]->id);
        }

        // over list
        $nextLink = '/users?limit=5&page=3';
        $res = $this->call('GET', $nextLink);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(1, count($results->entities));
    }

    public function testBlock()
    {
        // check user doesn't exist
        $res = $this->call('POST', '/users/2/block');
        $this->assertEquals(404, $res->getStatusCode());

        // check user has blocked
        $user = User::find(1);
        $user->status = User::STATUS_BLOCK;
        $user->save();

        $res = $this->call('POST', '/users/1/block');
        $this->assertEquals(204, $res->getStatusCode());

        // check user is blocked after set block
        $user = User::find(1);
        $user->status = User::STATUS_ACTIVE_EMAIL;
        $user->save();

        $res = $this->call('POST', '/users/1/block');
        $this->assertEquals(204, $res->getStatusCode());

        $user = User::find(1);
        $this->assertTrue($user->isBlock());
    }

    public function testUnBlock()
    {
        // check user doesn't exist
        $res = $this->call('POST', '/users/2/unblock');
        $this->assertEquals(404, $res->getStatusCode());

        // check user has unblocked
        $user = User::find(1);
        $user->status = User::STATUS_ACTIVE_EMAIL;
        $user->save();

        $res = $this->call('POST', '/users/1/unblock');
        $this->assertEquals(204, $res->getStatusCode());

        // check user is unblocked after set unblock
        $user = User::find(1);
        $user->status = User::STATUS_BLOCK;
        $user->save();

        $res = $this->call('POST', '/users/1/unblock');
        $this->assertEquals(204, $res->getStatusCode());

        $user = User::find(1);
        $this->assertFalse($user->isBlock());
    }

    public function testAssignRole()
    {
        // test invalid user
        $res = $this->call('POST', '/users/3/roles', [
            'roleIdOrName'      => 'post',
        ]);
        $this->assertEquals(404, $res->getStatusCode());

        // test invalid role
        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => 'post',
        ]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('validation', $results->type);
        $this->assertEquals('error', $results->status);
        $this->assertEquals('Role does not exist.', $results->message);

        // test user already has role
        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => 'admin',
        ]);
        $this->assertEquals(204, $res->getStatusCode());

        // test assign new role with name
        $editor = factory(Role::class)->create(['name' => 'editor']);
        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => 'editor',
        ]);
        $this->assertEquals(204, $res->getStatusCode());

        // test assign new role with id
        $superEditor = factory(Role::class)->create(['name' => 'super editor']);
        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => $superEditor->id,
        ]);
        $user = User::find(1);
        $this->assertEquals(204, $res->getStatusCode());
        $this->assertTrue($user->hasRole(['super editor', 'editor', 'admin']));
    }

    public function testGetRoles()
    {
        // test find not found user
        $res = $this->call('GET', '/users/5/roles', []);
        $this->assertEquals(404, $res->getStatusCode());

        // test doesn't have role
        $user = factory(App\User::class)->create();
        $res = $this->call('GET', '/users/' . $user->id . '/roles', []);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());

        // test has role
        $editor = factory(Role::class)->create(['name' => 'editor1']);
        $editor = factory(Role::class)->create(['name' => 'editor2']);
        $editor = factory(Role::class)->create(['name' => 'editor3']);
        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => 'editor1',
        ]);

        $res = $this->call('POST', '/users/1/roles', [
            'roleIdOrName'      => 'editor2',
        ]);

        $res = $this->call('GET', '/users/1/roles', []);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(3, count($results->entities));
        $this->assertEquals(1, $results->entities[0]->id);
        $this->assertEquals(2, $results->entities[1]->id);
        $this->assertEquals(3, $results->entities[2]->id);

        // test with oder id with desc
        $res = $this->call('GET', '/users/1/roles?sort=id&direction=desc', []);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(3, count($results->entities));
        $this->assertEquals(3, $results->entities[0]->id);
        $this->assertEquals(2, $results->entities[1]->id);
        $this->assertEquals(1, $results->entities[2]->id);

        // test oder name with desc
        $res = $this->call('GET', '/users/1/roles?sort=name&direction=desc', []);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(3, count($results->entities));
        $this->assertEquals('editor2', $results->entities[0]->name);
        $this->assertEquals('editor1', $results->entities[1]->name);
        $this->assertEquals('admin', $results->entities[2]->name);

        // test oder name with asc
        $res = $this->call('GET', '/users/1/roles?sort=name&direction=asc', []);
        $this->assertEquals(200, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals(3, count($results->entities));
        $this->assertEquals('admin', $results->entities[0]->name);
        $this->assertEquals('editor1', $results->entities[1]->name);
        $this->assertEquals('editor2', $results->entities[2]->name);
    }
}
