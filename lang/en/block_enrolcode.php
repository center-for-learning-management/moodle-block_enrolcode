<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_enrolcode
 * @copyright  2019 Center for Learning Management (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'EnrolByCode';
$string['privacy:metadata'] = 'This plugin does not store any personal data';

$string['close'] = 'Close';
$string['code:accesscode'] = 'Accesscode';
$string['code:enrol'] = 'enrol';
$string['code:enrol:error'] = 'Accesscode did not work.';
$string['code:enrol:guesterror'] = 'You need to login before you can use the accesscode.';
$string['code:enter'] = 'Enter temporary accesscode (as provided by your trainer)';
$string['code:get'] = 'Temporary accesscode';
$string['code:get:error'] = 'Error creating temporary accesscode';
$string['confirmation'] = 'Confirmation';
$string['copied'] = 'copied';

$string['custommaturity'] = 'Enrolment allowed until';

$string['enrol:success:redirect'] = 'Successfully joined course. You will be redirected to the course immediately.';
$string['enrolcode:addinstance'] = 'Add EnrolByCode-Block';
$string['enrolcode:myaddinstance'] = 'Add EnrolByCode-Block to Dashboard';
$string['enrolmentend'] = 'Enrolment ends at date';
$string['enrolmentend:never'] = 'Never';
$string['enrolmentend:short'] = 'End of enrolment';

$string['finished'] = 'Finished';

$string['maturity'] = 'Maturity';
$string['maturity:immediately'] = 'immediately';

$string['really_delete'] = 'Really delete the code "{$a->code}"?';

$string['settings:logo'] = 'QR code logo';
$string['settings:logo_desc'] = 'Optional image to place in the middle of generated QR codes. Keep it simple for best scan reliability.';
$string['settings:circularqrcode'] = 'Wrap QR code in a circular badge';
$string['settings:circularqrcode_desc'] = 'Keep the full QR code intact, but present it inside a circular frame.';
$string['settings:qrforegroundcolour'] = 'QR code colour';
$string['settings:qrforegroundcolour_desc'] = 'Choose the colour used for the dark QR modules. Darker colours are the most reliable for scanning.';
$string['settings:qrappearance'] = 'QR code appearance';
$string['settings:qrappearance_desc'] = 'Adjust how generated QR codes are rendered.';
$string['settings:roundqrcode'] = 'Use rounded QR modules';
$string['settings:roundqrcode_desc'] = 'Render most QR modules as dots while keeping the corner finder patterns square for reliable scanning.';

$string['show_existing_codes'] = 'Show existing codes';
