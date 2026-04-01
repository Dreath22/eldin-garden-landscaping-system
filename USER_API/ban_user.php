<?php
// Legacy bridge — forwards to UsersController.php
// require_once prevents function redeclaration if this file is ever included twice.
$_GET['action'] = 'ban';
require_once __DIR__ . '/UsersController.php';