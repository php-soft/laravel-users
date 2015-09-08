<?php

$this->extract($user, [
    'id',
    'name',
    'username',
    'location',
    'country',
    'biography',
    'occupation',
    'website',
    'image',
    'birthday',
    'gender',
    'status'
]);
$this->set('isBlock', $user->isBlock());

