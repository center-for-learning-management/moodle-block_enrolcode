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
$functions = [
    'block_enrolcode_delete' => [
        'classname' => \block_enrolcode\external::class,
        'methodname' => 'delete',
        'description' => 'Deletes a code.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_enrolcode_form' => [
        'classname' => \block_enrolcode\external::class,
        'methodname' => 'form',
        'description' => 'Retrieves the form to create an enrolment accesscode in HTML.',
        'type' => 'read',
        'ajax' => true,
    ],
    'block_enrolcode_get' => [
        'classname' => \block_enrolcode\external::class,
        'methodname' => 'get',
        'description' => 'Retrieves a code for fast enrolment',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_enrolcode_send' => [
        'classname' => \block_enrolcode\external::class,
        'methodname' => 'send',
        'description' => 'Sends a code for fast enrolment',
        'type' => 'write',
        'ajax' => true,
    ],
];
