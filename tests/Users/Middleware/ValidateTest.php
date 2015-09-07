<?php

class ValidateTest extends TestCase
{
    public function testValidateFailure()
    {
        $res = $this->call('POST', '/blog');
        $this->assertEquals(400, $res->getStatusCode());
        $results = json_decode($res->getContent());
        $this->assertEquals('error', $results->status);
        $this->assertEquals('validation', $results->type);
        $this->assertObjectHasAttribute('title', $results->errors);
        $this->assertEquals('The title field is required.', $results->errors->title[0]);
        $this->assertObjectHasAttribute('contents', $results->errors);
        $this->assertEquals('The contents field is required.', $results->errors->contents[0]);
        $this->assertObjectHasAttribute('image', $results->errors);
        $this->assertEquals('The image field is required.', $results->errors->image[0]);
    }

    public function testValidateSuccess()
    {
        $res = $this->call('POST', '/blog', [
            'title'    => 'Title',
            'contents' => 'Contents',
            'image'    => 'image'
        ]);
        $this->assertEquals(200, $res->getStatusCode());
    }
}
