<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each([ $permission ], function ($section, $permission) {

    $section->set($section->partial('phpsoft.users::partials/permission', [ 'permission' => $permission ]));
}));

$this->set('linked', '{}');
