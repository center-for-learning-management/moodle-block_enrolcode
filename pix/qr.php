<?php

require_once('../../../config.php');
//require_login();
require_once("../classes/phpqrcode/qrlib.php");
//require_once(__DIR__ . "../classes/phpqrcode/qrlib.php");

$format = optional_param('format', '', PARAM_TEXT);
$txt = required_param('txt', PARAM_TEXT);
switch($format) {
    case 'base64': $txt = base64_decode($txt); break;
}
//echo $txt;
QRcode::png($txt);
