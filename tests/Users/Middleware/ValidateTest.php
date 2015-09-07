<?php

class ValidateTest extends TestCase
{
    public function testValidateFailure()
    {
        $res = $this->call('POST', '/user');
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
    }

    public function testValidateSuccess()
    {
        $res = $this->call('POST', '/user', [
            'name'                  => 'User',
            'email'                 => 'user@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password'
        ]);
        $this->assertEquals(200, $res->getStatusCode());
    }
}
