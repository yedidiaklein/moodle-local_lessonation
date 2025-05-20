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
 * Service for checking state of lesson generation.
 *
 * @package     local_lessonation
 * @category    admin
 * @copyright   Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// This file contains the create class for the lessonation plugin.
// It handles the request of the user to create a lesson that will be sent to AI.

namespace local_lessonation\form;

defined('MOODLE_INTERNAL') || die();

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class for generation form.
 *
 * @package     local_lessonation
 */
class generate extends \moodleform {
    // Add elements to form.
    /**
     * Form definition.
     *
     * @return void
     */
    public function definition() {
        global $DB;
        $courseid = optional_param('courseid', 0, PARAM_INT);
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!

        // Add elements to your form.
        $mform->addElement('text', 'url', get_string('subject', 'local_lessonation'),
                            'maxlength="255" size="255"');

        // Set type of element to text.
        $mform->setType('url', PARAM_TEXT);

        // Default value.
        $mform->setDefault('url', 'https://en.wikipedia.org/wiki/Moodle_(software)');

        // Add wanted number of slides. (drop down 1-20).
        $mform->addElement('select', 'numberofslides', get_string('numberofslides', 'local_lessonation'), range(1, 20));
        // Set default value.
        $mform->setDefault('numberofslides', 5);

        // Add a course section selector.
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $options = [];
        foreach ($sections as $section) {
            $options[$section->id] = \get_section_name($courseid, $section);
        }
        $mform->addElement('select', 'sectionid', get_string('section'), $options);
        // Set default value for section.
        $mform->setDefault('sectionid', 0); // Default to the first section.
        // Set type of element to int.
        $mform->setType('sectionid', PARAM_INT);

        // Add hidden field for the course ID.
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->setDefault('courseid', $courseid); // Example course ID.

        // Add a container for the buttons.
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('submit'));
        $buttonarray[] = $mform->createElement('cancel', 'cancelbutton', get_string('cancel'));

        // Add the button array to the form.
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->setType('buttonar', PARAM_RAW);
    }

    // Custom validation should be added here.
    /**
     * Custom validation.
     *
     * @param array $data The data submitted by the form.
     * @param array $files The files submitted by the form.
     * @return array An array of errors, if any.
     */
    public function validation($data, $files) {
        return [];
    }
}
