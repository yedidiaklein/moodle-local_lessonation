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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Version details.
 *
 * @package    local_lessonation
 * @author     Yedidia Klein <yedidia@openapp.co.il>
 * @copyright  Yedidia Klein
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

defined('MOODLE_INTERNAL') || die();

// Check that the user is enrolled in the course.
$courseid = required_param('courseid', PARAM_INT);
require_login($courseid);

// show a form to create a lesson
$PAGE->set_url(new moodle_url('/local/lessonation/generate_form.php?courseid=' . $courseid));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_lessonation'));
$PAGE->set_heading(get_string('generate', 'local_lessonation'));
$PAGE->set_pagelayout('standard');
$PAGE->set_button($OUTPUT->single_button(new moodle_url('/course/view.php?id=' . $courseid), get_string('back')));

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide', 'lessonationform');

// Form to create a lesson.
// Using moodle form.
$mform = new \local_lessonation\form\generate();

// Form processing and displaying is done here.
if ($mform->is_cancelled()) {
    echo get_string('cancelled', 'local_lessonation');
    echo $OUTPUT->single_button(new moodle_url('/local/lessonation/index.php'), get_string('back'));
} else if ($fromform = $mform->get_data()) {
    // TODO: Check if user has rights to create a lesson.
    $prepare = new \local_lessonation\create();
    $adhoctaskid = $prepare->prepare_lesson_data($fromform);

    if ($adhoctaskid) {
        // Insert info to local_lessonation table.
        $lessonation = new \StdClass();
        $lessonation->userid = $USER->id;
        $lessonation->adhocid = $adhoctaskid;
        $lessonation->state = 0;
        $lessonation->lessonid = 0;
        $lessonationid = $DB->insert_record('local_lessonation', $lessonation);

        if (!$lessonationid) {
            // Error in inserting lessonation record.
            echo $OUTPUT->notification(get_string('error', 'local_lessonation'), 'notifyproblem');
            return;
        }

        // Show the "preparing" GIF and initialize the AMD module.
        echo '<div id="preparing-gif-container" style="display: none; text-align: center;">' .
                $OUTPUT->notification(get_string('lessonincreation', 'local_lessonation'), 'notifysuccess') .
                '<img src="' . $OUTPUT->image_url('preparing', 'local_lessonation') . '" alt="Preparing...">
              </div>';
        echo '<div id="status-container" style="text-align: center;"></div>';

        // Include the AMD module.
        $PAGE->requires->js_call_amd('local_lessonation/check_lesson_state', 'init', [$adhoctaskid]);
        
    } else {
        // Error in lesson creation.
        echo $OUTPUT->notification(get_string('error', 'local_lessonation'), 'notifyproblem');
    }
} else {
    // Display the form.
    $mform->display();
}

echo $OUTPUT->box_end();

echo $OUTPUT->footer();

