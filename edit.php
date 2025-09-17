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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/categoryfields/classes/form/edit_category_fields_form.php');

$categoryid = required_param('categoryid', PARAM_INT);
$context = context_coursecat::instance($categoryid);
require_login();
$PAGE->set_context(context_system::instance());

require_capability('moodle/category:manage', $context);

$PAGE->set_url(new moodle_url('/local/categoryfields/edit.php', ['categoryid' => $categoryid]));
$PAGE->set_title(get_string('pluginname', 'local_categoryfields'));
$PAGE->set_heading(get_string('pluginname', 'local_categoryfields'));

$mform = new \local_categoryfields\form\edit_category_fields_form(null, ['categoryid' => $categoryid]);

global $DB;
if ($record = $DB->get_record('local_categoryfields_data', ['categoryid' => $categoryid])) {
    $formdata = new stdClass();
    $formdata->id = $record->id;
    $formdata->summary = $record->summary;
    $formdata->imageurl = $record->imageurl;
    $mform->set_data($formdata);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/management.php', ['category' => $categoryid]));
} else if ($data = $mform->get_data()) {
    $rec = (object)[
        'categoryid' => $categoryid,
        'summary'    => $data->summary,
        'imageurl'   => $data->imageurl,
    ];
    if (!empty($data->id)) {
        $rec->id = $data->id;
        $DB->update_record('local_categoryfields_data', $rec);
    } else {
        $DB->insert_record('local_categoryfields_data', $rec);
    }
    redirect(new moodle_url('/course/management.php', ['category' => $categoryid]), get_string('changessaved', 'local_categoryfields'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
