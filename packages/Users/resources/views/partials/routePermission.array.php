<?php

$this->extract($routePermission, [
    'id',
    'route',
]);

$this->set('roles', function($section) use ($routePermission) {
    $section->set(json_decode($routePermission->roles));
});

$this->set('permissions', function($section) use ($routePermission) {
    $section->set(json_decode($routePermission->permissions));
});
