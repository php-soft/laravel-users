<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each([ $role ], function ($section, $role) {

    $section->set($section->partial('phpsoft.users::partials/role', [ 'role' => $role ]));
}));

$this->set('linked', '{}');
