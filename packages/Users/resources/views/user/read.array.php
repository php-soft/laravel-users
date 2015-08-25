<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each([ $user ], function ($section, $user) {
    $section->set($section->partial('partials/user', [ 'user' => $user ]));
}));

$this->set('linked', '{}');
