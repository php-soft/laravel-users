<?php

$this->set('version', '1.0');
$this->set('status', 'error');
$this->set('type', 'validation');
$this->set('errors', $errors);
$this->set('message', is_array($errors)? $errors[0] : $errors->first());
