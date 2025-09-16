<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Defines the events used by the plugin.
 *
 * @package     local_categoryfields
 * @copyright   2025 Stefano Lopes <stefanolopes84@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\form\course\edit_category_form_created',
        'callback'  => '\local_categoryfields\observer::on_category_edit_form',
    ],
    [
        'eventname' => '\core\event\course_category_updated',
        'callback'  => '\local_categoryfields\observer::on_category_updated',
    ],
    [
        'eventname' => '\core\event\course_category_created',
        'callback'  => '\local_categoryfields\observer::on_category_created',
    ],
];
