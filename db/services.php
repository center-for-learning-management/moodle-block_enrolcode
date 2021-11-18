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

defined('MOODLE_INTERNAL') || die;

// We define the web service functions to install.
$functions = array(
    'block_enrolcode_delete' => array(
        'classname'   => 'block_enrolcode_external',
        'methodname'  => 'delete',
        'classpath'   => 'blocks/enrolcode/externallib.php',
        'description' => 'Deletes a code.',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'block_enrolcode_form' => array(
        'classname'   => 'block_enrolcode_external',
        'methodname'  => 'form',
        'classpath'   => 'blocks/enrolcode/externallib.php',
        'description' => 'Retrieves the form to create an enrolment accesscode in HTML.',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_enrolcode_get' => array(
        'classname'   => 'block_enrolcode_external',
        'methodname'  => 'get',
        'classpath'   => 'blocks/enrolcode/externallib.php',
        'description' => 'Retrieves a code for fast enrolment',
        'type'        => 'read',
        'ajax'        => 1,
    ),
    'block_enrolcode_revoke' => array(
        'classname'   => 'block_enrolcode_external',
        'methodname'  => 'revoke',
        'classpath'   => 'blocks/enrolcode/externallib.php',
        'description' => 'Revokes a code for fast enrolment',
        'type'        => 'write',
        'ajax'        => 1,
    ),
    'block_enrolcode_send' => array(
        'classname'   => 'block_enrolcode_external',
        'methodname'  => 'send',
        'classpath'   => 'blocks/enrolcode/externallib.php',
        'description' => 'Sends a code for fast enrolment',
        'type'        => 'write',
        'ajax'        => 1,
    ),
);
