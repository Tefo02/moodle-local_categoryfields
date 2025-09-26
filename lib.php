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

defined('MOODLE_INTERNAL') || die();

function local_categoryfields_extend_navigation_category_settings(navigation_node $parentnode, context_coursecat $context) {
    global $PAGE;

    if (!has_capability('moodle/category:manage', $context)) {
        return;
    }
    $categoryid = $context->instanceid;
    $url = new moodle_url('/local/categoryfields/edit.php', ['categoryid' => $categoryid]);
    $parentnode->add(
        get_string('pluginname', 'local_categoryfields'),
        $url,
        navigation_node::NODETYPE_LEAF,
        null,
        'local_categoryfields',
        new pix_icon('i/settings', '')
    );
}

/**
 * Serves the files from the local_categoryfields plugin file area.
 *
 * @param stdClass $course The course object
 * @param stdClass $cm The course module object
 * @param context $context The context
 * @param string $filearea The name of the file area
 * @param array $args The arguments
 * @param bool $forcedownload Whether to force a download
 * @param array $options Additional options
 * @return bool
 * @package local_categoryfields
 */
function local_categoryfields_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($filearea !== 'category_image') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);

    if ($context->contextlevel != CONTEXT_COURSECAT) {
        return false;
    }

    $category = \core_course_category::get($context->instanceid, IGNORE_MISSING);
    if (!$category || !$category->is_uservisible()) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_categoryfields', $filearea, $itemid, '/', $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
