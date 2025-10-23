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

require_once($CFG->libdir . '/formslib.php');

require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/edit_category_fields_form.php');

$categoryid = required_param('categoryid', PARAM_INT);
$category = \core_course_category::get($categoryid, MUST_EXIST);
$context = context_coursecat::instance($categoryid);

require_login();
require_capability('moodle/category:manage', $context);

$PAGE->set_url(new moodle_url('/local/categoryfields/edit.php', ['categoryid' => $categoryid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_categoryfields'));
$PAGE->set_heading(get_string('pluginname', 'local_categoryfields'));

$draftitemid = file_get_submitted_draft_itemid('image_manager');
$record = $DB->get_record('local_categoryfields_data', ['categoryid' => $categoryid], '*', IGNORE_MISSING);
if ($record) {
    file_prepare_draft_area($draftitemid, $context->id, 'local_categoryfields', 'category_image', $record->id, ['subdirs' => 0, 'maxfiles' => 1]);
}

$formdata = new stdClass();
if ($record) {
    $formdata->id = $record->id;
    $formdata->related_categories = !empty($record->related_categories) ? explode(',', $record->related_categories) : [];
    $formdata->is_program = $record->is_program; // <-- LINHA 1 ADICIONADA
}
$formdata->image_manager = $draftitemid;

$mform = new \local_categoryfields\form\edit_category_fields_form(null, ['categoryid' => $categoryid]);
$mform->set_data($formdata);


if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/management.php', ['categoryid' => $categoryid]));
} else if ($data = $mform->get_data()) {
    $rec = new stdClass();
    $rec->categoryid = $categoryid;
    $rec->related_categories = !empty($data->related_categories) ? implode(',', $data->related_categories) : '';
    $rec->is_program = $data->is_program; // <-- LINHA 2 ADICIONADA

    if ($record) {
        $rec->id = $record->id;
        $DB->update_record('local_categoryfields_data', $rec);
        $itemid = $record->id;
    } else {
        $itemid = $DB->insert_record('local_categoryfields_data', $rec);
    }

    file_save_draft_area_files(
        $data->image_manager,
        $context->id,
        'local_categoryfields',
        'category_image',
        $itemid,
        ['subdirs' => 0, 'maxfiles' => 1]
    );

    

    redirect(new moodle_url('/course/management.php', ['categoryid' => $categoryid]), get_string('changessaved', 'local_categoryfields'), \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($category->name);
$mform->display();
echo $OUTPUT->footer();