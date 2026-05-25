<?php
return array (
  'defaults' => 
  array (
    'enabled' => true,
    'auth' => true,
    'roles' => 
    array (
    ),
    'methods' => 
    array (
      0 => 'GET',
      1 => 'POST',
      2 => 'PUT',
      3 => 'DELETE',
    ),
    'max_limit' => 100,
  ),
  'tables' => 
  array (
    'task' => 
    array (
      'enabled' => true,
      'auth' => true,
      'roles' => 
      array (
      ),
      'methods' => 
      array (
        0 => 'POST',
        1 => 'PUT',
        2 => 'DELETE',
      ),
      'max_limit' => 100,
    ),
    'users' => 
    array (
      'enabled' => true,
      'auth' => true,
      'roles' => 
      array (
        0 => 'admin',
      ),
      'methods' => 
      array (
        0 => 'GET',
      ),
      'max_limit' => 100,
    ),
  ),
);
