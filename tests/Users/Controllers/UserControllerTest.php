<?php

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
        $res = $this->call('PATCH', '/me/profile');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testUpdateProfileFailure()
    {
        // test auth
        $res = $this->call('PATCH', '/me/profile', [
           'name' => 'Steven',
        ]);

        $results = json_decode($res->getContent());
        $this->assertEquals(400, $res->getStatusCode());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);

        // test invalid name
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $name = str_repeat("abc", 100);
        $res = $this->call('PATCH', '/me/profile', [
           'name'     => $name,
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);
        
        $results = json_decode($res->getContent());
        $this->assertEquals('validation', $results->type);
        $this->assertEquals('error', $results->status);
        $this->assertObjectHasAttribute('name', $results->errors);
        $this->assertEquals('The name may not be greater than 255 characters.', $results->message);
        //test input invalid
        $res = $this->call('PATCH', '/me/profile', [
           'password' => '123456'
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(400, $res->getStatusCode());
    }

    public function testUpdateProfileSuccess()
    {
        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);
        $res = $this->call('PATCH', '/me/profile', [
            'name'    => 'Steven Adam',
            'country' => 'USA',
        ],[],[], ['HTTP_Authorization' => "Bearer {$token}"]);

        $results = json_decode($res->getContent());
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals('admin@example.com', Auth::user()->email);
        $this->assertEquals('Steven Adam', $results->entities[0]->name);
        $userId = $results->entities[0]->id;
        $user = \App\User::find($userId);
        $this->assertEquals('Steven Adam', $user->name);
        $this->assertEquals('USA', $user->country);
        $this->assertEquals('admin@example.com', $user->email);
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
        $this->assertEquals('Administrator', $results->entities[0]->name);
        $user = \App\User::find(1);
        $this->assertEquals('admin', $user->username);
    }
}
