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

namespace local_categoryfields\form;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');


class edit_category_fields_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        $categoryid = $this->_customdata['categoryid'];

        $currentcategory = \core_course_category::get($categoryid);
        if (!$currentcategory) {
            return;
        }

            $excludelist = [];
            $excludelist[] = $categoryid;
            $excludelist = array_merge($excludelist, $currentcategory->get_parents());

            $childobjects = $currentcategory->get_children();

            $childids = array_column($childobjects, 'id');

            $descendantids = \local_categoryfields_get_all_descendant_ids($currentcategory);
            $excludelist = array_merge($excludelist, $descendantids);

            $excludelist = array_unique($excludelist);

        $mform->addElement('header', 'local_categoryfields_header', get_string('extradata', 'local_categoryfields'));
        $mform->addElement(
            'filemanager',
            'image_manager',
            get_string('image', 'local_categoryfields'),
            null,
            ['maxfiles' => 1, 'accepted_types' => ['image/png', 'image/jpeg', 'image/gif']]
        );

        $categories = \core_course_category::get_all();
        $categoryoptions = [];
        foreach ($categories as $cat) {
            if (!in_array($cat->id, $excludelist)) {
                $categoryoptions[$cat->id] = \local_categoryfields_get_category_path_name($cat);
            }
        }

        $mform->addElement(
            'select',
            'related_categories',
            get_string('relatedcategories', 'local_categoryfields'),
            $categoryoptions,
            ['multiple' => true]
        );
        $mform->setType('related_categories', PARAM_RAW);

        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);

        $this->add_action_buttons();
    }
}
