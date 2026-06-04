<?php
// Forward old insert request to new API
$_POST['action'] = 'create';
require __DIR__ . '/users_api.php';
exit();
?>
