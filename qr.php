<?php

require_once(__DIR__ . '/../../config.php');
require_login();

$code = required_param('code', PARAM_ALPHANUM);
$enrolurl = \block_enrolcode\api::get_enrol_url($code)->out(false);

$barcode = (new core_qrcode($enrolurl))->getBarcodeArray();

// TCPDF's own PNG output has a transparent background and no quiet zone, which scanners
// can't read reliably on dark backgrounds, so draw the code ourselves with a white border.
$modulesize = 10;
$margin = 4;
$image = imagecreate(($barcode['num_cols'] + 2 * $margin) * $modulesize, ($barcode['num_rows'] + 2 * $margin) * $modulesize);
imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
foreach ($barcode['bcode'] as $row => $cols) {
    foreach ($cols as $col => $bit) {
        if ($bit) {
            $x = ($col + $margin) * $modulesize;
            $y = ($row + $margin) * $modulesize;
            imagefilledrectangle($image, $x, $y, $x + $modulesize - 1, $y + $modulesize - 1, $black);
        }
    }
}

header('Content-Type: image/png');
imagepng($image);
