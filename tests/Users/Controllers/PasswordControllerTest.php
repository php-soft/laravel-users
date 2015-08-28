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

        // check user is invalid
        $checkSendMail = $this->call('POST','/passwords/forgot', [
            'email'=> 'nouser@example.com'
        ]);
        $this->assertEquals(400, $checkSendMail->getStatusCode());

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

        // check email format
        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ]);
        $this->assertEquals(400, $checkResetPassword->getStatusCode());

        // check password confirmation
        $checkResetPassword = $this->call('POST','/passwords/reset', [
            'token'                 => 'token',
            'email'                 => 'admin@example.com',
            'password'              => '12345678',
            'password_confirmation' => '123456',
        ]);
        $this->assertEquals(400, $checkResetPassword->getStatusCode());

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