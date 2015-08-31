<?php
$this->set('version', '1.0');
$this->set('links', $this->helper('phpsoft.users::helpers.links', $users['data']));
$this->set('meta', function ($section) use ($users) {

    $section->set('offset', $users['offset']);
    $section->set('limit', $users['limit']);
    $section->set('total', $users['total']);
});

$this->set('entities', $this->each($users['data'], function ($section, $user) {

    $section->set($section->partial('phpsoft.users::partials/user', [ 'user' => $user ]));
}));

$this->set('linked', '{}');
