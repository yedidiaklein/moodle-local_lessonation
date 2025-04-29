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

// show a form to create a lesson
$PAGE->set_url(new moodle_url('/local/lessonation/generate_form.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_lessonation'));
$PAGE->set_heading(get_string('generate', 'local_lessonation'));
$PAGE->set_pagelayout('standard');
$PAGE->set_button($OUTPUT->single_button(new moodle_url('/local/lessonation/index.php'), get_string('back')));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_lessonation'));
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
    echo $fromform->url;
    $aianswer = new \local_lessonation\sendtoai();
    // TODO : add  sections to form for creation in right section.
    $num = $fromform->numberofslides;
    $prompt = 'build me a presentation of ' . $num . ' slides based on the content of the following webpage: ' . $fromform->url .
              ' In the following format :
            { "name": "Lesson Name Test",
              "description": "Lesson Description Testing",
              "slides": 
                [ { "title": "Slide 1 Title",
                    "content": "<h1>Slide 1 Content</h1><ul><li>Item 1</li><li>Item 2</li>li>Item 3</li>li>Item 4</li></ul>" },
                  { "title": "Slide 2 Title",
                    "content": "<h1>Slide 2 Content</h1><ul><li>Item 3</li><li>Item 4</li>li>Item 3</li>li>Item 4</li></ul>" } ]
            }
            Use bullet points in the slides.';
    // TODO : put here real course context.
    $context = 1;
    $result = $aianswer->execute($context, $prompt);
    if ($result['success']) {
        $json = $result['generatedcontent'];
        $lessonation = new \local_lessonation\create();
        $courseid = $fromform->courseid;
        $moduleid = $lessonation->json_to_lesson($json, $courseid);
        echo get_string('lessoncreated', 'local_lessonation');
        echo $OUTPUT->single_button(new moodle_url('/mod/lesson/view.php', array('id' => $moduleid)),
                      get_string('clickhere', 'local_lessonation'));
    } else {
        echo get_string('error', 'local_lessonation') . $result['data'];
    }
} else {
    // Display the form.
    $mform->display();
}


echo $OUTPUT->box_end();


echo $OUTPUT->footer();

