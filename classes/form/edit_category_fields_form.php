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
        $categoryid = $this->_customdata['categoryid'] ?? 0;

        $mform->addElement('header', 'local_categoryfields_header', get_string('extradata', 'local_categoryfields'));
        $mform->addElement(
            'textarea',
            'summary',
            get_string('summary', 'local_categoryfields'),
            'wrap="virtual" rows="8" cols="60"'
        );
        $mform->setType('summary', PARAM_RAW);
        $mform->addElement('text', 'imageurl', get_string('imageurl', 'local_categoryfields'), ['size' => '50']);
        $mform->setType('imageurl', PARAM_URL);

        $mform->addElement('hidden', 'id'); // id do registro local_categoryfields_data (se houver)
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'categoryid', $categoryid);
        $mform->setType('categoryid', PARAM_INT);

        $this->add_action_buttons();
    }
}
