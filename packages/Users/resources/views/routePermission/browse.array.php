<?php
$this->set('version', '1.0');
$this->set('links', $this->helper('phpsoft.users::helpers.links', $routePermissions['data']));
$this->set('meta', function ($section) use ($routePermissions) {

    $section->set('offset', $routePermissions['offset']);
    $section->set('limit', $routePermissions['limit']);
    $section->set('total', $routePermissions['total']);
});

$this->set('entities', $this->each($routePermissions['data'], function ($section, $routePermission) {

    $section->set($section->partial('phpsoft.users::partials/routePermission', [
        'routePermission' => $routePermission
    ]));
}));

$this->set('linked', '{}');
