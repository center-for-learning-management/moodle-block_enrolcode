<?php

require_once(__DIR__ . '/../../../config.php');
require_login();
require_once(__DIR__ . '/../locallib.php');

$format = optional_param('format', '', PARAM_TEXT);
$preset = optional_param('preset', 'default', PARAM_ALPHA);
$txt = required_param('txt', PARAM_TEXT);
switch ($format) {
    case 'base64':
        $txt = base64_decode($txt);
        break;
}

block_enrolcode_lib::output_qr_png($txt, $preset);
