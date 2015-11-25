<?php

class PasswordControllerTest extends TestCase
{
    public function testForgotPassword()
    {
        // check email validate
        $checkSendMail = $this->call('POST','/passwords/forgot', [
            'email'=> 'admin@example'
        ]);
        $this->assertEquals(400, $checkSendMail->getStatusCode());
        $result = json_decode($checkSendMail->getContent());
        $this->assertEquals('The email must be a valid email address.', $result->email[0]);

        // check user is invalid
        $checkSendMail = $this->call('POST','/passwords/forgot', [
            'email'=> 'nouser@example.com'
        ]);
        $this->assertEquals(400, $checkSendMail->getStatusCode());
        $result = json_decode($checkSendMail->getContent());
        $this->assertEquals('User is invalid.', $result);

        // check send mail success
        $checkSendMail = $this->call('POST','/passwords/forgot', [
            'email'=> 'admin@example.com'
        ]);
        $this->assertEquals(200, $checkSendMail->getStatusCode());
    }

    public function testResetPasswordFailure()
    {
        // check validate input

        // check input is empty
        $checkResetPassword = $this->call('POST','/passwords/reset');
        $this->assertEquals(400, $checkResetPassword->getStatusCode());
        $result = json_decode($checkResetPassword->getContent());
        $this->assertEquals('The email field is required.', $result->email[0]);
        $this->assertEquals('The token field is required.', $result->token[0]);
        $this->assertEquals('The password field is required.', $result->password[0]);

        // check email format
        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $this->assertEquals(400, $checkResetPassword->getStatusCode());
        $result = json_decode($checkResetPassword->getContent());
        $this->assertEquals('The email must be a valid email address.', $result->email[0]);

        // check password confirmation
        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example.com',
            'password'              => '12345678',
            'password_confirmation' => '123456',
        ]);
        $this->assertEquals(400, $checkResetPassword->getStatusCode());
        $this->assertEquals(400, $checkResetPassword->getStatusCode());
        $result = json_decode($checkResetPassword->getContent());
        $this->assertEquals('The password confirmation does not match.', $result->password[0]);

        // check input incorrect
        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $this->assertEquals(400, $checkResetPassword->getStatusCode());
    }

    public function testResetPasswordSuccess()
    {
        // check reset password success
        Password::shouldReceive('reset')->once()->andReturn('passwords.reset');

        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $this->assertEquals(200, $checkResetPassword->getStatusCode());
    }

    public function testCheckAuthChangePassword()
    {
        $this->withoutMiddleware();
        $res = $this->call('PUT', '/me/password');
        $this->assertEquals(401, $res->getStatusCode());
    }

    public function testChangePassword()
    {
        // Check authenticate
        $res = $this->call('PUT', '/me/password');
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('authenticate', $results->type);
        $this->assertEquals('Token is not provided.', $results->message);

        $credentials = [ 'email' => 'admin@example.com', 'password' => '123456' ];
        $token = JWTAuth::attempt($credentials);

        // Input is empty
        $res = $this->call('PUT', '/me/password', [], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('old_password', $results->errors);
        $this->assertEquals('The old password field is required.', $results->errors->old_password[0]);
        $this->assertObjectHasAttribute('password', $results->errors);
        $this->assertEquals('The password field is required.', $results->errors->password[0]);

        // Check validate input
        $res = $this->call('PUT', '/me/password', [
            'old_password'          => '1234',
            'password'              => '1234',
            'password_confirmation' => '123'
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('old_password', $results->errors);
        $this->assertEquals('The old password must be at least 6 characters.', $results->errors->old_password[0]);
        $this->assertObjectHasAttribute('password', $results->errors);
        $this->assertEquals('The password confirmation does not match.', $results->errors->password[0]);
        $this->assertEquals('The password must be at least 6 characters.', $results->errors->password[1]);

        // Old password is wrong
        $res = $this->call('PUT', '/me/password', [
            'old_password'          => '123456789',
            'password'              => '12345678',
            'password_confirmation' => '12345678'
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals("The old password is incorrect.", $results->message);
        $this->assertEquals('validation', $results->type);

        // Change password success
        $res = $this->call('PUT', '/me/password', [
            'old_password'          => '123456',
            'password'              => '12345678',
            'password_confirmation' => '12345678'
        ], [], [], ['HTTP_Authorization' => "Bearer {$token}"]);
        $this->assertEquals(204, $res->getStatusCode());
        $checkPassword = Auth::attempt(['id' => 1, 'password' => '12345678']);
        $this->assertTrue($checkPassword);
    }
}
