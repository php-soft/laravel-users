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
]);
$this->set('isBlock', $user->isBlock());
