<?php

define('ADMIN_IPS', ['82.64.103.47']);
if (in_array($_SERVER['REMOTE_ADDR'], ADMIN_IPS)) {

    require_once('./index2.php');

}else{

    require_once('./index1.php');

}

?>