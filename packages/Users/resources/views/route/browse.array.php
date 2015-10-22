<?php

$this->set('version', '1.0');
$this->set('links', '{}');
$this->set('meta', '{}');

$this->set('entities', $this->each($routes, function ($section, $route) {

    $section->set('method', $route->method);
    $section->set('uri', $route->uri);
}));

$this->set('linked', '{}');
