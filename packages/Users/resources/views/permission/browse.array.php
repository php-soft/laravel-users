<?php
$this->set('version', '1.0');
$this->set('links', $this->helper('phpsoft.users::helpers.links', $permissions['data']));
$this->set('meta', function ($section) use ($permissions) {

    $section->set('offset', $permissions['offset']);
    $section->set('limit', $permissions['limit']);
    $section->set('total', $permissions['total']);
});

$this->set('entities', $this->each($permissions['data'], function ($section, $permission) {

    $section->set($section->partial('phpsoft.users::partials/permission', [ 'permission' => $permission ]));
}));

$this->set('linked', '{}');
