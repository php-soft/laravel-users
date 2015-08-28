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
}