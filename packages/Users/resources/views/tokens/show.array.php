<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each([ $token ], function ($section, $token) {

    $section->set('token', $token);
}));

$this->set('linked', '{}');
