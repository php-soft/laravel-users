<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each([ $routePermission ], function ($section, $routePermission) {

    $section->set($section->partial('phpsoft.users::partials/routePermission', [
        'routePermission' => $routePermission
    ]));
}));

$this->set('linked', '{}');
