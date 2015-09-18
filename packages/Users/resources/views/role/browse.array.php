<?php
$this->set('version', '1.0');
$this->set('links', $this->helper('phpsoft.users::helpers.links', $roles['data']));
$this->set('meta', function ($section) use ($roles) {

    $section->set('offset', $roles['offset']);
    $section->set('limit', $roles['limit']);
    $section->set('total', $roles['total']);
});

$this->set('entities', $this->each($roles['data'], function ($section, $role) {

    $section->set($section->partial('phpsoft.users::partials/role', [ 'role' => $role ]));
}));

$this->set('linked', '{}');
