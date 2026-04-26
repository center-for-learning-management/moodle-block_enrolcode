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
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('block_enrolcode', get_string('pluginname', 'block_enrolcode'));

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_heading(
            'block_enrolcode/qrappearance',
            get_string('settings:qrappearance', 'block_enrolcode'),
            get_string('settings:qrappearance_desc', 'block_enrolcode')
        ));

        $settings->add(new admin_setting_configstoredfile(
            'block_enrolcode/logo',
            get_string('settings:logo', 'block_enrolcode'),
            get_string('settings:logo_desc', 'block_enrolcode'),
            'logo',
            0,
            array(
                'accepted_types' => array('web_image'),
                'maxfiles' => 1,
            )
        ));

        $settings->add(new admin_setting_configcolourpicker(
            'block_enrolcode/qrforegroundcolour',
            get_string('settings:qrforegroundcolour', 'block_enrolcode'),
            get_string('settings:qrforegroundcolour_desc', 'block_enrolcode'),
            '#000000'
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_enrolcode/roundqrcode',
            get_string('settings:roundqrcode', 'block_enrolcode'),
            get_string('settings:roundqrcode_desc', 'block_enrolcode'),
            0
        ));

        $settings->add(new admin_setting_configcheckbox(
            'block_enrolcode/circularqrcode',
            get_string('settings:circularqrcode', 'block_enrolcode'),
            get_string('settings:circularqrcode_desc', 'block_enrolcode'),
            0
        ));
    }
}
